<?php
namespace App\Controllers;

use App\Models\BienBanKiemKe;
use App\Models\ChenhLechKiemKe;
use App\Models\DanhMuc;
use App\Models\KetQuaKiemKe;
use App\Models\MaChungTu;
use App\Models\PhieuDieuChinhTonKho;
use App\Models\PhuongAnXuLy;
use App\Models\ThongBao;
use Core\Auth;
use Core\Request;

/**
 * KiemKeController — UC24 (lập biên bản), UC25 (ghi nhận kết quả), UC26 (đối chiếu),
 * UC27 (xử lý chênh lệch + thi hành điều chỉnh tồn kho).
 *
 * Nguồn dữ liệu: CSDL gốc feed_wms.
 * Áp dụng phân quyền:
 * - ROLE_MANAGER: Quản lý toàn bộ quy trình kiểm kê (lập biên bản, đối chiếu, xử lý).
 * - ROLE_INVENTORY: Xem biên bản được phân công và nhập kết quả kiểm kê thực tế.
 */
class KiemKeController extends Controller
{
    private const PER_PAGE = 5;

    /** UC24 — Danh sách biên bản kiểm kê (Giao diện Master-Detail). */
    public function index(): void
    {
        $f = [
            'q'          => Request::get('q'),
            'trangThai'  => Request::get('trangThai'),
            'loaiKiemKe' => Request::get('loaiKiemKe'),
            'maKho'      => Request::get('maKho'),
            'maKhuVuc'   => Request::get('maKhuVuc'),
            'tuNgay'     => Request::get('tuNgay'),
            'denNgay'    => Request::get('denNgay'),
        ];

        // Phạm vi kho cố định đối với Quản lý kho / Nhân viên kho được gán kho phụ trách
        $userKho = $this->warehouseScope();
        $scopedKho = (in_array(Auth::role(), [ROLE_MANAGER, ROLE_WAREHOUSE], true) && !empty($userKho)) ? $userKho : null;
        if ($scopedKho !== null) {
            $f['maKho'] = $scopedKho;
        }

        $allKho = DanhMuc::khoKiemKe();
        $khoList = $scopedKho !== null
            ? (isset($allKho[$scopedKho]) ? [$scopedKho => $allKho[$scopedKho]] : [])
            : $allKho;
        $areas = $scopedKho !== null ? (DanhMuc::khuVucTheoKho()[$scopedKho] ?? []) : [];

        $page = max(1, Request::int('page', 1));
        [$rows, $total] = BienBanKiemKe::search($f, $page, self::PER_PAGE, Auth::staffId(), Auth::role());

        $this->render('kiem-ke/index', [
            'f'                 => $f,
            'rows'              => $rows,
            'total'             => $total,
            'page'              => $page,
            'perPage'           => self::PER_PAGE,
            'counts'            => BienBanKiemKe::statusCounts($f, Auth::staffId(), Auth::role()),
            'kho'               => $khoList,
            'areas'             => $areas,
            'isWarehouseScoped' => ($scopedKho !== null),
            'loaiKho'           => DanhMuc::loaiKiemKe(),
        ], [
            'title'      => 'Kiểm kê kho',
            'subtitle'   => '',
            'module'     => 'kiem-ke',
            'breadcrumb' => [['Kiểm kê', null]],
            'actions'    => can('kiem-ke', 'create')
                ? '<button type="button" class="btn btn-primary" onclick="openInitModal()">+ Lập biên bản</button>'
                : '',
        ]);
    }

    /** UC24 — Form lập biên bản kiểm kê (Giao diện Master-Detail MISA CukCuk với danh sách tồn kho xem trước). */
    public function create(): void
    {
        if (!can('kiem-ke', 'create')) {
            $this->forbidden('Chỉ Quản lý kho mới có quyền lập biên bản kiểm kê.');
        }

        $allKho = DanhMuc::khoKiemKe();
        $userKho = $this->warehouseScope();
        $reqKho = Request::get('maKho');
        if (empty($reqKho) || !isset($allKho[$reqKho])) {
            $reqKho = (!empty($userKho) && isset($allKho[$userKho])) ? $userKho : (array_key_first($allKho) ?? '');
        }

        $rawLoai = Request::get('loaiKiemKe', 'DinhKy');
        $loaiCode = inventory_type_code($rawLoai);
        $reqNgayKetThuc = Request::get('ngayKetThuc', date('Y-m-d'));

        // Khu vực và vị trí trong kho đã chọn
        $areas = [];
        $locations = [];
        $scopeLots = [];

        if ($reqKho !== '') {
            $areasList = \Core\Database::all(
                "SELECT maKhuVuc, tenKhuVuc FROM KhuVuc WHERE maKho = ? AND trangThai IN ('Hoạt động', 'Đang hoạt động') ORDER BY tenKhuVuc",
                [$reqKho]
            );
            foreach ($areasList as $ar) {
                $areas[$ar['maKhuVuc']] = $ar['tenKhuVuc'];
            }

            $locations = \Core\Database::all(
                "SELECT vt.maViTri, vt.tenViTri, vt.maKhuVuc, kv.tenKhuVuc
                 FROM ViTriLuuTru vt
                 JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
                 WHERE kv.maKho = ?
                 ORDER BY kv.tenKhuVuc, vt.tenViTri",
                [$reqKho]
            );

            // Truy vấn các lô hàng đang tồn kho thực tế trong kho đã chọn
            $scopeLots = \Core\Database::all(
                "SELECT tk.maTonKho, tk.soLuongTon, tk.maViTri,
                        lh.maLoHang, lh.ngayHetHan, lh.ngaySanXuat,
                        hh.maHangHoa, hh.tenHangHoa, hh.donViTinhChuan, hh.loaiHangHoa,
                        vt.tenViTri, kv.maKhuVuc, kv.tenKhuVuc
                 FROM TonKho tk
                 JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
                 JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
                 JOIN LoHang lh ON lh.maLoHang = tk.maLoHang
                 JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa
                 WHERE kv.maKho = ? AND tk.soLuongTon > 0
                 ORDER BY hh.tenHangHoa, lh.maLoHang",
                [$reqKho]
            );
        }

        // Tên hiển thị kho và loại
        $selectedKhoName = $allKho[$reqKho] ?? 'Chưa chọn kho';
        $selectedLoaiName = inventory_type_label($loaiCode);

        // Tiêu đề tab danh mục (Nguyên liệu / Thành phẩm / Phụ gia / Bao bì / Hàng hóa)
        $tabTitle = 'Hàng hóa';
        if (mb_stripos($selectedKhoName, 'nguyên liệu') !== false) {
            $tabTitle = 'Nguyên liệu';
        } elseif (mb_stripos($selectedKhoName, 'thành phẩm') !== false) {
            $tabTitle = 'Thành phẩm';
        } elseif (mb_stripos($selectedKhoName, 'phụ gia') !== false) {
            $tabTitle = 'Phụ gia';
        } elseif (mb_stripos($selectedKhoName, 'bao bì') !== false) {
            $tabTitle = 'Bao bì';
        }

        $nextCodePreview = MaChungTu::preview('BienBanKiemKe');
        $creatorName = Auth::user()['hoTen'] ?? 'Lê Thị Thanh Mai';
        $creatorId = Auth::staffId();

        $this->render('kiem-ke/create', [
            'kho'                => $allKho,
            'selectedKho'        => $reqKho,
            'selectedKhoName'    => $selectedKhoName,
            'selectedLoai'       => $loaiCode,
            'selectedLoaiName'   => $selectedLoaiName,
            'selectedNgay'       => $reqNgayKetThuc,
            'displayNgay'        => date('d/m/Y', strtotime($reqNgayKetThuc)),
            'areas'              => $areas,
            'locations'          => $locations,
            'staffList'          => DanhMuc::nhanVienKho($reqKho),
            'scopeLots'          => $scopeLots,
            'tabTitle'           => $tabTitle,
            'nextCodePreview'    => $nextCodePreview,
            'creatorName'        => $creatorName,
            'creatorId'          => $creatorId,
        ], [
            'title'      => 'Lập biên bản kiểm kê',
            'subtitle'   => '',
            'module'     => 'kiem-ke',
            'breadcrumb' => [['Kiểm kê', 'kiem-ke'], ['Lập biên bản', null]],
            'actions'    => '<a class="btn btn-ghost" href="' . e(route('kiem-ke')) . '" title="Quay lại danh sách" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px; text-decoration: none; font-size: 16px;"><span aria-hidden="true" style="line-height: 1;">&larr;</span></a>',
        ]);
    }

    /** UC24 — Lưu biên bản mới với tính năng chống lặp form (idempotency guard). */
    public function store(): void
    {
        if (!can('kiem-ke', 'create')) {
            $this->forbidden('Chỉ Quản lý kho mới có quyền lập biên bản kiểm kê.');
        }

        // Chống gửi lặp form (Idempotency Guard)
        $formToken = trim((string)Request::post('form_token'));
        if ($formToken !== '') {
            $submitted = $_SESSION['submitted_form_tokens'] ?? [];
            if (in_array($formToken, $submitted, true)) {
                $this->flash('warning', 'Yêu cầu đang được xử lý hoặc form đã được gửi.');
                redirect('kiem-ke');
                return;
            }
            $_SESSION['submitted_form_tokens'][] = $formToken;
            if (count($_SESSION['submitted_form_tokens']) > 50) {
                array_shift($_SESSION['submitted_form_tokens']);
            }
        }

        try {
            $maNhanVienGiaoViec = trim((string)Request::post('maNhanVienGiaoViec'));
            if ($maNhanVienGiaoViec !== '') {
                $_POST['maNhanVienTruongDoan'] = $maNhanVienGiaoViec;
            }
            $ma = BienBanKiemKe::create($_POST, Auth::staffId());

            // Gửi thông báo đến nhân viên kho được phân công
            if ($maNhanVienGiaoViec !== '') {
                $allKho = DanhMuc::khoKiemKe();
                $tenKho = $allKho[$_POST['maKho'] ?? ''] ?? 'Kho';
                ThongBao::notifyUser(
                    $maNhanVienGiaoViec,
                    'Phân công kiểm kê ' . $tenKho,
                    'Bạn vừa được phân công thực hiện kiểm kê theo biên bản ' . $ma . '. Vui lòng kiểm tra và nhập số lượng kiểm đếm thực tế.',
                    'kiem-ke/record-result?ma=' . rawurlencode($ma),
                    'info'
                );
            }

            $this->flash('success', 'Đã lập biên bản kiểm kê ' . $ma . ' thành công.');
            redirect('kiem-ke/detail?ma=' . rawurlencode($ma));
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
            redirect('kiem-ke/create');
        }
    }

    /** UC24 — Form sửa biên bản kiểm kê. */
    public function edit(): void
    {
        if (!can('kiem-ke', 'create')) {
            $this->forbidden('Chỉ Quản lý kho mới có quyền chỉnh sửa biên bản kiểm kê.');
        }

        $ma = (string)Request::get('ma');
        $bb = BienBanKiemKe::find($ma);
        if (!$bb) {
            $this->flash('error', 'Không tìm thấy biên bản kiểm kê ' . $ma . '.');
            redirect('kiem-ke');
            return;
        }

        if ($bb['trangThai'] !== 'Chờ xử lý') {
            $this->flash('warning', 'Biên bản đã ở trạng thái "' . inventory_status_label($bb['trangThai']) . '" nên không thể chỉnh sửa.');
            redirect('kiem-ke/detail?ma=' . rawurlencode($ma));
            return;
        }

        $daGhi = (int)($bb['soDaGhi'] ?? 0);
        if ($daGhi > 0) {
            $this->flash('warning', 'Biên bản đã có ' . $daGhi . ' dòng kiểm đếm nên không thể chỉnh sửa.');
            redirect('kiem-ke/detail?ma=' . rawurlencode($ma));
            return;
        }

        $areas = DanhMuc::khuVucTheoKho()[$bb['maKho']] ?? [];

        // Lấy danh sách nhân viên kho theo kho chỉ hiển thị họ tên (không kèm chức vụ/mã)
        $staffList = DanhMuc::nhanVienKho($bb['maKho']);
        if (!empty($bb['maNhanVienTruongDoan']) && !isset($staffList[$bb['maNhanVienTruongDoan']])) {
            $staffList[$bb['maNhanVienTruongDoan']] = $bb['tenTruongDoan'] ?? $bb['maNhanVienTruongDoan'];
        }

        // Lấy danh sách lô hàng thực tế trong kho để xem trước phạm vi
        $scopeLots = \Core\Database::all(
            "SELECT tk.maTonKho, tk.soLuongTon, tk.maViTri,
                    lh.maLoHang, lh.ngayHetHan, lh.ngaySanXuat,
                    hh.maHangHoa, hh.tenHangHoa, hh.donViTinhChuan, hh.loaiHangHoa,
                    vt.tenViTri, kv.maKhuVuc, kv.tenKhuVuc
             FROM TonKho tk
             JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
             JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc
             JOIN LoHang lh ON lh.maLoHang = tk.maLoHang
             JOIN HangHoa hh ON hh.maHangHoa = lh.maHangHoa
             WHERE kv.maKho = ? AND tk.soLuongTon > 0
             ORDER BY hh.tenHangHoa, lh.maLoHang",
            [$bb['maKho']]
        );

        // Tiêu đề tab danh mục
        $tabTitle = 'Hàng hóa';
        if (mb_stripos($bb['tenKho'], 'nguyên liệu') !== false) {
            $tabTitle = 'Nguyên liệu';
        } elseif (mb_stripos($bb['tenKho'], 'thành phẩm') !== false) {
            $tabTitle = 'Thành phẩm';
        } elseif (mb_stripos($bb['tenKho'], 'phụ gia') !== false) {
            $tabTitle = 'Phụ gia';
        } elseif (mb_stripos($bb['tenKho'], 'bao bì') !== false) {
            $tabTitle = 'Bao bì';
        }

        $this->render('kiem-ke/edit', [
            'bb'        => $bb,
            'areas'     => $areas,
            'staffList' => $staffList,
            'scopeLots' => $scopeLots,
            'tabTitle'  => $tabTitle,
        ], [
            'title'      => 'Sửa biên bản kiểm kê ' . $ma,
            'subtitle'   => '',
            'module'     => 'kiem-ke',
            'breadcrumb' => [['Kiểm kê', 'kiem-ke'], [$ma, 'kiem-ke/detail?ma=' . rawurlencode($ma)], ['Chỉnh sửa', null]],
            'actions'    => '<a class="btn btn-ghost" href="' . e(route('kiem-ke/detail', ['ma' => $ma])) . '" title="Quay lại chi tiết biên bản" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px; text-decoration: none; font-size: 16px;"><span aria-hidden="true" style="line-height: 1;">&larr;</span></a>',
        ]);
    }

    /** UC24 — Lưu cập nhật biên bản kiểm kê. */
    public function update(): void
    {
        if (!can('kiem-ke', 'create')) {
            $this->forbidden('Chỉ Quản lý kho mới có quyền chỉnh sửa biên bản kiểm kê.');
        }

        $ma = (string)Request::post('maBienBanKiemKe');
        try {
            $oldBB = BienBanKiemKe::find($ma);
            $maTruongDoanCu = $oldBB['maNhanVienTruongDoan'] ?? '';

            BienBanKiemKe::update($ma, $_POST);

            // Nếu phân công nhân viên mới, gửi thông báo
            $maNhanVienMoi = trim((string)Request::post('maNhanVienTruongDoan'));
            if ($maNhanVienMoi !== '' && $maNhanVienMoi !== $maTruongDoanCu) {
                $bb = BienBanKiemKe::find($ma);
                ThongBao::notifyUser(
                    $maNhanVienMoi,
                    'Phân công kiểm kê ' . ($bb['tenKho'] ?? 'Kho'),
                    'Bạn vừa được phân công phụ trách kiểm kê theo biên bản ' . $ma . '. Vui lòng kiểm tra và ghi nhận số lượng kiểm đếm thực tế.',
                    'kiem-ke/record-result?ma=' . rawurlencode($ma),
                    'info'
                );
            }

            $this->flash('success', 'Đã cập nhật biên bản kiểm kê ' . $ma . ' thành công.');
            redirect('kiem-ke/detail?ma=' . rawurlencode($ma));
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
            redirect('kiem-ke/edit?ma=' . rawurlencode($ma));
        }
    }

    /** UC24 — Chi tiết biên bản: tiến độ, danh sách lô hàng trong phạm vi, chênh lệch, điều chỉnh. */
    public function detail(): void
    {
        $ma = (string)Request::get('ma');
        $userKho = $this->warehouseScope();

        if (!BienBanKiemKe::canAccess($ma, Auth::staffId(), Auth::role(), $userKho)) {
            $this->flash('error', 'Bạn không được phân công tham gia hoặc không thuộc phạm vi biên bản kiểm kê ' . $ma . '.');
            redirect('kiem-ke');
            return;
        }

        $bb = BienBanKiemKe::find($ma);
        if (!$bb) {
            $this->flash('error', 'Không tìm thấy biên bản kiểm kê ' . $ma . '.');
            redirect('kiem-ke');
            return;
        }

        $this->render('kiem-ke/detail', [
            'bb'          => $bb,
            'tienDo'      => BienBanKiemKe::progress($ma),
            'scopeItems'  => BienBanKiemKe::scopeItems($ma),
            'chenhLech'   => class_exists(ChenhLechKiemKe::class) ? ChenhLechKiemKe::rows($ma) : [],
            'phuongAn'    => class_exists(PhuongAnXuLy::class) ? PhuongAnXuLy::byBienBan($ma) : [],
            'phieu'       => class_exists(PhieuDieuChinhTonKho::class) ? PhieuDieuChinhTonKho::byBienBan($ma) : [],
            'nguyenNhan'  => DISCREPANCY_REASON_MAP,
        ], [
            'title'      => '',
            'subtitle'   => '',
            'module'     => 'kiem-ke',
            'breadcrumb' => [['Kiểm kê', 'kiem-ke'], [$ma, null]],
        ]);
    }

    /** In biên bản kiểm kê Mẫu 05-VT A4 độc lập. */
    public function printVoucher(): void
    {
        $ma = (string)Request::get('ma');
        $userKho = $this->warehouseScope();

        if (!BienBanKiemKe::canAccess($ma, Auth::staffId(), Auth::role(), $userKho)) {
            $this->flash('error', 'Bạn không được phân công tham gia hoặc không thuộc phạm vi biên bản kiểm kê ' . $ma . '.');
            redirect('kiem-ke');
            return;
        }

        $bb = BienBanKiemKe::find($ma);
        if (!$bb) {
            $this->flash('error', 'Không tìm thấy biên bản kiểm kê ' . $ma . '.');
            redirect('kiem-ke');
            return;
        }

        $this->render('kiem-ke/print', [
            'bb'         => $bb,
            'scopeItems' => BienBanKiemKe::scopeItems($ma),
        ], [
            'title' => 'In biên bản kiểm kê ' . $ma,
        ], null);
    }

    /** UC25 — Ghi nhận số lượng thực tế theo từng dòng sổ sách. */
    public function recordResult(): void
    {
        $ma = (string)Request::get('ma');
        $userKho = $this->warehouseScope();
        if (!BienBanKiemKe::canAccess($ma, Auth::staffId(), Auth::role(), $userKho)) {
            $this->flash('error', 'Bạn không được phân công tham gia hoặc không thuộc phạm vi biên bản kiểm kê ' . $ma . '.');
            redirect('kiem-ke');
            return;
        }

        $bb = BienBanKiemKe::find($ma);
        if (!$bb) {
            $this->flash('error', 'Không tìm thấy biên bản kiểm kê ' . $ma . '.');
            redirect('kiem-ke');
            return;
        }
        if (!in_array($bb['trangThai'], ['Chờ xử lý', 'Từ chối'], true)) {
            if ($bb['trangThai'] === 'Đang xử lý') {
                $this->flash('info', 'Biên bản ' . $ma . ' đang ở trạng thái "Chờ xác nhận" nên tạm thời không thể nhập thêm.');
            } else {
                $this->flash('warning', 'Biên bản ' . $ma . ' đã ở trạng thái "' . inventory_status_label($bb['trangThai']) . '" nên không ghi nhận thêm.');
            }
            redirect('kiem-ke/detail?ma=' . rawurlencode($ma));
            return;
        }

        $scopeItems = BienBanKiemKe::scopeItems($ma);

        $this->render('kiem-ke/record-result', [
            'bb'         => $bb,
            'scopeItems' => $scopeItems,
            'tienDo'     => BienBanKiemKe::progress($ma),
        ], [
            'title'      => '',
            'subtitle'   => '',
            'module'     => 'kiem-ke',
            'breadcrumb' => [['Kiểm kê', 'kiem-ke'], ['Ghi nhận kiểm đếm ' . $ma, null]],
        ]);
    }

    /** UC25 — Lưu tạm kết quả kiểm đếm (DRAFT). KHÔNG đổi trạng thái biên bản. */
    public function saveResult(): void
    {
        $ma = (string)Request::post('ma');
        $userKho = $this->warehouseScope();
        if (!BienBanKiemKe::canAccess($ma, Auth::staffId(), Auth::role(), $userKho)) {
            $this->flash('error', 'Bạn không có quyền cập nhật biên bản kiểm kê này.');
            redirect('kiem-ke');
            return;
        }

        try {
            $res = KetQuaKiemKe::save($_POST, $ma, Auth::staffId());
            $daLuu = $res['saved'] + $res['updated'];

            if ($daLuu > 0) {
                $this->flash('success', 'Đã ghi lại kết quả kiểm đếm cho ' . $daLuu . ' lô hàng. Biên bản vẫn ở trạng thái "Chờ kiểm kê". Nhấn "Gửi duyệt" khi đã hoàn thành 100%.');
            } elseif (empty($res['errors'])) {
                $this->flash('info', 'Không có thay đổi nào để lưu.');
            }
            if (!empty($res['errors'])) {
                $this->flash($daLuu > 0 ? 'warning' : 'error', implode(' ', array_slice($res['errors'], 0, 3)));
            }
            redirect('kiem-ke/record-result?ma=' . rawurlencode($ma));
            return;
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
            redirect('kiem-ke/record-result?ma=' . rawurlencode($ma));
        }
    }

    /** UC24/UC25 — Nhân viên kho gửi kết quả kiểm đếm cho Quản lý kho xem xét duyệt. */
    public function submitForApproval(): void
    {
        $ma = (string)Request::post('ma');
        $userKho = $this->warehouseScope();
        if (!BienBanKiemKe::canAccess($ma, Auth::staffId(), Auth::role(), $userKho)) {
            $this->flash('error', 'Bạn không có quyền thực hiện thao tác trên biên bản kiểm kê này.');
            redirect('kiem-ke');
            return;
        }

        try {
            if (!empty($_POST['items'])) {
                $saveRes = KetQuaKiemKe::save($_POST, $ma, Auth::staffId());
                if (!empty($saveRes['errors'])) {
                    throw new \RuntimeException(implode(' ', array_slice($saveRes['errors'], 0, 3)));
                }
            }

            BienBanKiemKe::submitForApproval($ma, Auth::staffId());
            $this->flash('success', 'Đã gửi biên bản kiểm kê ' . $ma . ' cho Quản lý kho xem xét xác nhận thành công.');
            redirect('kiem-ke');
            return;
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
            redirect('kiem-ke/record-result?ma=' . rawurlencode($ma));
        }
    }

    /** UC24 — Quản lý kho trả lại kết quả kiểm đếm yêu cầu nhân viên kiểm đếm lại. */
    public function returnResult(): void
    {
        if (!can('kiem-ke', 'create') && !can('kiem-ke', 'handle') && !can('kiem-ke', 'reconcile')) {
            $this->forbidden('Chỉ Quản lý kho mới có quyền trả lại kết quả kiểm kê.');
        }

        $ma = (string)Request::post('ma');
        $userKho = $this->warehouseScope();
        if (!BienBanKiemKe::canAccess($ma, Auth::staffId(), Auth::role(), $userKho)) {
            $this->flash('error', 'Bạn không phụ trách kho của biên bản kiểm kê này.');
            redirect('kiem-ke');
            return;
        }

        try {
            $lyDo = trim((string)Request::post('lyDo'));
            BienBanKiemKe::returnResult($ma, Auth::staffId(), $lyDo);
            $this->flash('success', 'Đã từ chối biên bản kiểm kê ' . $ma . '. Nhân viên phụ trách sẽ kiểm tra và gửi lại.');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        }
        redirect('kiem-ke/detail?ma=' . rawurlencode($ma));
    }

    /** UC26 — Đối chiếu số liệu: so sánh số sách với thực tế, phân loại thừa/thiếu và ghi nguyên nhân. */
    public function reconcile(): void
    {
        $ma = (string)Request::get('ma');
        $bb = BienBanKiemKe::find($ma);
        if (!$bb) {
            $this->flash('error', 'Không tìm thấy biên bản kiểm kê ' . $ma . '.');
            redirect('kiem-ke');
        }
        if (!in_array($bb['trangThai'], ['Đang xử lý', 'Chờ xử lý'], true)) {
            $this->flash('warning', 'Biên bản ' . $ma . ' đã ở trạng thái "' . inventory_status_label($bb['trangThai']) . '" nên không đối chiếu được.');
            redirect('kiem-ke/detail?ma=' . rawurlencode($ma));
        }
        $this->render('kiem-ke/reconcile', [
            'bb'         => $bb,
            'tienDo'     => BienBanKiemKe::progress($ma),
            'dong'       => ChenhLechKiemKe::rows($ma),
            'nguyenNhan' => DISCREPANCY_REASON_MAP,
        ], [
            'title'      => '',
            'subtitle'   => '',
            'module'     => 'kiem-ke',
            'breadcrumb' => [['Kiểm kê', 'kiem-ke'], [$ma, 'kiem-ke/detail?ma=' . rawurlencode($ma)], ['Đối chiếu số liệu', null]],
        ]);
    }

    /** UC26 — Lưu kết quả đối chiếu cho các dòng chênh lệch. */
    public function saveReconcile(): void
    {
        $ma = (string)Request::post('ma');
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || Request::post('is_ajax') === '1';

        try {
            $res = ChenhLechKiemKe::save($_POST, $ma);
            $msg = '';
            if ($res['saved'] > 0) {
                $_SESSION['da_doi_chieu_' . $ma] = true;
                $msg = 'Đã ghi nhận đối chiếu cho ' . $res['saved'] . ' dòng chênh lệch.';
                $this->flash('success', $msg);
            } else {
                $msg = 'Không có dòng nào được cập nhật.';
                $this->flash('info', $msg);
            }
            if ($res['errors']) {
                $errMsg = implode(' ', array_slice($res['errors'], 0, 3));
                $this->flash('error', $errMsg);
                if ($isAjax) {
                    $this->json(['success' => false, 'message' => $errMsg], 400);
                    return;
                }
            }
            if ($isAjax) {
                $this->json(['success' => true, 'message' => $msg ?: 'Đã lưu đối chiếu thành công!']);
                return;
            }
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $ex->getMessage()], 400);
                return;
            }
        }
        redirect('kiem-ke/detail?ma=' . rawurlencode($ma));
    }

    /** UC27 — Xử lý chênh lệch: đề xuất, phê duyệt và thi hành phương án điều chỉnh. */
    public function handleDiscrepancy(): void
    {
        $ma = (string)Request::get('ma');
        $bb = BienBanKiemKe::find($ma);
        if (!$bb) {
            $this->flash('error', 'Không tìm thấy biên bản kiểm kê ' . $ma . '.');
            redirect('kiem-ke');
        }
        $this->render('kiem-ke/handle-discrepancy', [
            'bb'            => $bb,
            'tienDo'        => BienBanKiemKe::progress($ma),
            'dong'          => ChenhLechKiemKe::rows($ma),
            'phuongAn'      => PhuongAnXuLy::byBienBan($ma),
            'phieu'         => PhieuDieuChinhTonKho::byBienBan($ma),
            'bienPhapThua'  => PhuongAnXuLy::bienPhapChoChenhLech('Thừa'),
            'bienPhapThieu' => PhuongAnXuLy::bienPhapChoChenhLech('Thiếu'),
        ], [
            'title'      => 'Xử lý chênh lệch kiểm kê ' . $ma,
            'subtitle'   => 'Chênh lệch thừa chỉ điều chỉnh tăng sổ sách; chênh lệch thiếu điều chỉnh giảm hoặc ghi nhận hao hụt.',
            'module'     => 'kiem-ke',
            'breadcrumb' => [['Kiểm kê kho', 'kiem-ke'], [$ma, 'kiem-ke/detail?ma=' . rawurlencode($ma)], ['Xử lý chênh lệch', null]],
        ]);
    }

    /** UC27 — Đề xuất phương án cho một dòng chênh lệch. */
    public function propose(): void
    {
        $ma = (string)Request::post('ma');
        try {
            $maPhuongAn = PhuongAnXuLy::propose($_POST, Auth::staffId());
            $this->flash('success', 'Đã tạo phương án ' . $maPhuongAn . ' (chờ phê duyệt).');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        }
        redirect('kiem-ke/handle-discrepancy?ma=' . rawurlencode($ma));
    }

    /** UC27 — Phê duyệt phương án đang chờ. */
    public function approveProposal(): void
    {
        $ma = (string)Request::post('ma');
        try {
            PhuongAnXuLy::approve((string)Request::post('maPhuongAn'), Auth::staffId(), (string)Request::post('yKien'));
            $this->flash('success', 'Đã phê duyệt phương án. Có thể thi hành để cập nhật tồn kho.');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        }
        redirect('kiem-ke/handle-discrepancy?ma=' . rawurlencode($ma));
    }

    /** UC27 — Từ chối phương án (bắt buộc nêu lý do). */
    public function rejectProposal(): void
    {
        $ma = (string)Request::post('ma');
        try {
            PhuongAnXuLy::reject((string)Request::post('maPhuongAn'), Auth::staffId(), (string)Request::post('lyDo'));
            $this->flash('success', 'Đã từ chối phương án.');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        }
        redirect('kiem-ke/handle-discrepancy?ma=' . rawurlencode($ma));
    }

    /** UC27 — Thi hành phương án đã phê duyệt: sinh phiếu điều chỉnh + cập nhật tồn kho. */
    public function executeProposal(): void
    {
        $ma = (string)Request::post('ma');
        try {
            $maPhieu = PhieuDieuChinhTonKho::post((string)Request::post('maPhuongAn'), Auth::staffId());
            if ($maPhieu === null) {
                $this->flash('success', 'Đã ghi nhận xử lý (biện pháp không làm thay đổi số lượng tồn kho).');
            } else {
                $this->flash('success', 'Đã thi hành, sinh phiếu điều chỉnh ' . $maPhieu . '.');
            }
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        }
        redirect('kiem-ke/handle-discrepancy?ma=' . rawurlencode($ma));
    }

    /** UC24 — Quản lý kho xác nhận hoàn thành biên bản kiểm kê sau khi đã kiểm tra. */
    public function close(): void
    {
        if (!can('kiem-ke', 'create') && !can('kiem-ke', 'handle') && !can('kiem-ke', 'reconcile')) {
            $this->forbidden('Chỉ Quản lý kho mới có quyền xác nhận hoàn thành biên bản kiểm kê.');
        }

        $ma = (string)Request::post('ma');
        $userKho = $this->warehouseScope();
        if (!BienBanKiemKe::canAccess($ma, Auth::staffId(), Auth::role(), $userKho)) {
            $this->flash('error', 'Bạn không phụ trách kho của biên bản kiểm kê này.');
            redirect('kiem-ke');
            return;
        }

        try {
            BienBanKiemKe::close($ma);
            $this->flash('success', 'Đã xác nhận hoàn thành biên bản kiểm kê ' . $ma . '.');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        }
        redirect('kiem-ke/detail?ma=' . rawurlencode($ma));
    }

    /** UC24 — Hủy biên bản lập nhầm (chỉ khi chưa ghi kết quả). */
    public function cancel(): void
    {
        if (!can('kiem-ke', 'create') && !can('kiem-ke', 'handle')) {
            $this->forbidden('Chỉ Quản lý kho mới có quyền hủy biên bản kiểm kê.');
        }

        $ma = (string)Request::post('ma');
        $userKho = $this->warehouseScope();
        if (!BienBanKiemKe::canAccess($ma, Auth::staffId(), Auth::role(), $userKho)) {
            $this->flash('error', 'Bạn không phụ trách kho của biên bản kiểm kê này.');
            redirect('kiem-ke');
            return;
        }

        try {
            BienBanKiemKe::cancel($ma, (string)Request::post('lyDo'), Auth::staffId());
            $this->flash('success', 'Đã hủy biên bản ' . $ma . ' thành công.');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        }
        redirect('kiem-ke' . ($ma ? '?selected=' . rawurlencode($ma) : ''));
    }
}
