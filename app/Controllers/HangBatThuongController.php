<?php
namespace App\Controllers;

use App\Models\DanhMuc;
use App\Models\HangBatThuong;
use App\Models\KhuVuc;
use App\Models\MaChungTu;
use App\Models\PhieuDieuChinhTonKho;
use App\Models\PhuongAnXuLy;
use App\Models\ThongBao;
use Core\Auth;
use Core\Request;

/**
 * HangBatThuongController — UC28: danh sách hàng bất thường (rách vỡ/hư hỏng/hết hạn),
 * ghi nhận thủ công, quét lô sắp hết hạn, đề xuất - phê duyệt/hủy - thi hành phương án xử lý.
 */
class HangBatThuongController extends Controller
{
    private const PER_PAGE = 5;

    /** Phạm vi kho: Quản lý kho & Nhân viên kho theo kho được gán; Admin, QC và Ban giám đốc xem toàn hệ thống. */
    private function khoScope(): ?string
    {
        if (in_array(Auth::role(), [ROLE_MANAGER, ROLE_WAREHOUSE], true)) {
            return $this->warehouseScope();
        }
        return null;
    }

    /** Nhân viên kho chỉ xem các bản ghi do chính mình ghi nhận / đề xuất. */
    private function staffScope(): ?string
    {
        if (Auth::role() === ROLE_WAREHOUSE) {
            return Auth::staffId();
        }
        return null;
    }

    public function index(): void
    {
        $scope = $this->khoScope();
        $staffOnly = $this->staffScope();
        $f = [
            'q'             => Request::get('q'),
            'loaiBatThuong' => Request::get('loaiBatThuong'),
            'trangThai'     => Request::get('trangThai'),
            'maKho'         => Request::get('maKho'),
            'tuNgay'        => Request::get('tuNgay'),
            'denNgay'       => Request::get('denNgay'),
        ];
        if ($scope !== null && $scope !== '') {
            $f['maKho'] = $scope;
        }
        $page = max(1, Request::int('page', 1));
        [$rows, $total] = HangBatThuong::search($f, $page, self::PER_PAGE, $scope, $staffOnly);

        $canRecord = can('hang-bat-thuong', 'record');
        $canScan = can('hang-bat-thuong', 'scan');
        $canPropose = can('hang-bat-thuong', 'propose');
        $canHandle = can('hang-bat-thuong', 'handle');
        $canExecute = can('hang-bat-thuong', 'execute');
        $khoList = DanhMuc::kho();
        if ($scope !== null && $scope !== '') {
            $khoList = isset($khoList[$scope]) ? [$scope => $khoList[$scope]] : [];
        }

        $actionsHtml = [];
        if ($canScan) {
            $actionsHtml[] = '<button type="button" class="btn btn-ghost" onclick="openScanModal()">Quét lô hết hạn</button>';
        }
        if ($canRecord) {
            $actionsHtml[] = '<a class="btn btn-primary" href="' . e(route('hang-bat-thuong/create')) . '">+ Ghi nhận bất thường</a>';
        }
        $actions = $actionsHtml ? '<div style="display:inline-flex;gap:8px;flex-wrap:wrap;">' . implode('', $actionsHtml) . '</div>' : '';

        $this->render('hang-bat-thuong/index', [
            'f'               => $f,
            'rows'            => $rows,
            'total'           => $total,
            'page'            => $page,
            'perPage'         => self::PER_PAGE,
            'counts'          => HangBatThuong::totals($f, $scope, $staffOnly),
            'ungVien365'      => $canScan ? HangBatThuong::expiredCandidates(365, $scope) : [],
            'kho'             => $khoList,
            'loai'            => array_combine(HangBatThuong::LOAI, HangBatThuong::LOAI),
            'trangThais'      => [
                HangBatThuong::TT_CACH_LY    => HangBatThuong::TT_CACH_LY,
                HangBatThuong::TT_DANG_XU_LY => HangBatThuong::TT_DANG_XU_LY,
                HangBatThuong::TT_DA_XU_LY   => HangBatThuong::TT_DA_XU_LY,
            ],
            'canRecord'       => $canRecord,
            'canScan'         => $canScan,
            'canPropose'      => $canPropose,
            'canXuLy'         => $canHandle,
            'canExecute'      => $canExecute,
            'isWarehouseRole' => (Auth::role() === ROLE_WAREHOUSE),
        ], [
            'title'      => 'Hàng bất thường',
            'subtitle'   => '',
            'module'     => 'hang-bat-thuong',
            'breadcrumb' => [['Hàng bất thường', null]],
            'actions'    => $actions,
        ]);
    }

    /** UC28 — Trang form ghi nhận thủ công hàng bất thường (chuẩn 2 khung + bảng chọn lô). */
    public function create(): void
    {
        $scope = $this->khoScope();
        $allKho = DanhMuc::kho();
        if ($scope !== null && $scope !== '') {
            $khoList = isset($allKho[$scope]) ? [$scope => $allKho[$scope]] : [];
            $selectedKho = $scope;
        } else {
            $khoList = $allKho;
            $reqKho = (string)Request::get('maKho');
            $selectedKho = ($reqKho !== '' && isset($khoList[$reqKho])) ? $reqKho : (string)(array_key_first($khoList) ?? '');
        }

        $areas = $selectedKho !== '' ? KhuVuc::options($selectedKho) : [];
        $locations = $selectedKho !== '' ? DanhMuc::viTriChiTiet($selectedKho) : [];
        $stockLots = $selectedKho !== '' ? HangBatThuong::stockCandidates($selectedKho) : [];

        $this->render('hang-bat-thuong/create', [
            'kho'             => $khoList,
            'selectedKho'     => $selectedKho,
            'selectedKhoName' => $khoList[$selectedKho] ?? 'Chưa chọn kho',
            'canSwitchKho'    => ($scope === null && count($khoList) > 1),
            'areas'           => $areas,
            'locations'       => $locations,
            'stockLots'       => $stockLots,
            'loai'            => array_combine(HangBatThuong::LOAI, HangBatThuong::LOAI),
            'nextCodePreview' => MaChungTu::preview('HangBatThuong'),
            'creatorName'     => Auth::user()['hoTen'] ?? '',
            'preKhuVuc'       => (string)Request::get('maKhuVuc'),
            'preViTri'        => (string)Request::get('maViTri'),
            'preLoHang'       => (string)Request::get('maLoHang'),
        ], [
            'title'      => 'Ghi nhận hàng bất thường',
            'subtitle'   => '',
            'module'     => 'hang-bat-thuong',
            'breadcrumb' => [['Hàng bất thường', 'hang-bat-thuong'], ['Ghi nhận mới', null]],
            'actions'    => '<a class="btn btn-ghost" href="' . e(route('hang-bat-thuong')) . '" title="Quay lại danh sách" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #d0d5dd; color: #344054; border-radius: 6px; text-decoration: none; font-size: 16px;"><span aria-hidden="true" style="line-height: 1;">&larr;</span></a>',
        ]);
    }

    /** Chi tiết một bản ghi: thông tin lô/vị trí + lịch sử phương án xử lý + phiếu điều chỉnh. */
    public function detail(): void
    {
        $ma = (string)Request::get('ma');
        $hbt = HangBatThuong::find($ma);
        if (!$hbt) {
            $this->flash('error', 'Không tìm thấy bản ghi hàng bất thường ' . $ma . '.');
            redirect('hang-bat-thuong');
        }
        $scope = $this->khoScope();
        $staffOnly = $this->staffScope();
        if ($scope !== null && ($scope === '' || ($hbt['maKho'] ?? '') !== $scope)) {
            $this->forbidden();
            return;
        }
        if ($staffOnly !== null && !HangBatThuong::isOwnedByStaff($ma, $staffOnly)) {
            $this->forbidden();
            return;
        }
        $this->render('hang-bat-thuong/detail', [
            'hbt'       => $hbt,
            'phuongAn'  => PhuongAnXuLy::byHangBatThuong($ma),
            'phieu'     => PhieuDieuChinhTonKho::byHangBatThuong($ma),
            'bienPhap'  => PhuongAnXuLy::bienPhapChoHangBatThuong(),
        ], [
            'title'      => '',
            'subtitle'   => '',
            'module'     => 'hang-bat-thuong',
            'breadcrumb' => [['Hàng bất thường', 'hang-bat-thuong'], [$ma, null]],
        ]);
    }

    /** In chứng từ A4 độc lập cho bản ghi hàng bất thường. */
    public function printVoucher(): void
    {
        $ma = (string)Request::get('ma');
        $hbt = HangBatThuong::find($ma);
        if (!$hbt) {
            $this->flash('error', 'Không tìm thấy bản ghi hàng bất thường ' . $ma . '.');
            redirect('hang-bat-thuong');
        }
        $scope = $this->khoScope();
        $staffOnly = $this->staffScope();
        if ($scope !== null && ($scope === '' || ($hbt['maKho'] ?? '') !== $scope)) {
            $this->forbidden();
            return;
        }
        if ($staffOnly !== null && !HangBatThuong::isOwnedByStaff($ma, $staffOnly)) {
            $this->forbidden();
            return;
        }
        $this->render('hang-bat-thuong/print', [
            'hbt'      => $hbt,
            'phuongAn' => PhuongAnXuLy::byHangBatThuong($ma),
            'phieu'    => PhieuDieuChinhTonKho::byHangBatThuong($ma),
        ], [
            'title' => 'In chứng từ ' . $ma,
        ], null);
    }

    /** UC28 — Ghi nhận thủ công 1 hoặc nhiều bản ghi hàng bất thường (Rách vỡ / Hư hỏng / Hết hạn). */
    public function store(): void
    {
        $scope = $this->khoScope();
        try {
            $ids = HangBatThuong::createManualBatch($_POST, Auth::staffId(), $scope);
            foreach ($ids as $ma) {
                $hbt = HangBatThuong::find($ma);
                if ($hbt) {
                    $this->notifyWarehouseManager(
                        (string)$hbt['maKho'],
                        'Ghi nhận hàng bất thường mới',
                        'Bản ghi ' . $ma . ' (' . $hbt['loaiBatThuong'] . ' · ' . $hbt['tenHangHoa'] . ') cần lập phương án xử lý.',
                        'hang-bat-thuong/detail?ma=' . $ma,
                        'warning'
                    );
                }
            }
            if (count($ids) === 1) {
                $this->flash('success', 'Đã ghi nhận bản ghi hàng bất thường ' . $ids[0] . '.');
                redirect('hang-bat-thuong/detail?ma=' . rawurlencode($ids[0]));
            }
            $this->flash('success', 'Đã ghi nhận ' . count($ids) . ' bản ghi hàng bất thường: ' . implode(', ', $ids) . '.');
            redirect('hang-bat-thuong');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        } catch (\Throwable $ex) {
            $this->flash('error', config('debug') ? $ex->getMessage() : 'Không thể ghi nhận hàng bất thường lúc này.');
        }
        $maKhoBack = trim((string)Request::post('maKho'));
        redirect($maKhoBack !== '' ? ('hang-bat-thuong/create?maKho=' . rawurlencode($maKhoBack)) : 'hang-bat-thuong/create');
    }

    /** UC28 — Quét lô sắp/đã hết hạn còn tồn và tạo bản ghi 'Hết hạn' (tránh trùng bản ghi đang mở). */
    public function detect(): void
    {
        $scope = $this->khoScope();
        try {
            $rawNgay = trim((string)Request::post('soNgay', (string)config('expiry_warning_days', 30)));
            if (!preg_match('/^\d+$/', $rawNgay) || (int)$rawNgay > 365) {
                throw new \RuntimeException('Số ngày cảnh báo phải là số nguyên từ 0 đến 365.');
            }
            $soNgay = (int)$rawNgay;
            $created = HangBatThuong::detectExpired(Auth::staffId(), $soNgay, $scope);
            if ($created > 0) {
                $this->notifyWarehouseManager(
                    $scope ?? '',
                    'Phát hiện lô hàng sắp/đã hết hạn',
                    'Đã ghi nhận ' . $created . ' lô hết hạn/sắp hết hạn trong ' . $soNgay . ' ngày tới.',
                    'hang-bat-thuong',
                    'warning'
                );
                $this->flash('success', 'Đã ghi nhận ' . $created . ' lô hết hạn/sắp hết hạn trong ' . $soNgay . ' ngày tới.');
            } else {
                $this->flash('info', 'Không có lô nào cần ghi nhận thêm trong ' . $soNgay . ' ngày tới.');
            }
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        } catch (\Throwable $ex) {
            $this->flash('error', config('debug') ? $ex->getMessage() : 'Không thể quét lô hết hạn lúc này.');
        }
        redirect('hang-bat-thuong');
    }

    /** UC28 — Đề xuất phương án xử lý hàng bất thường (gửi thông báo Ban giám đốc duyệt). */
    public function propose(): void
    {
        $ma = (string)Request::post('ma');
        $scope = $this->khoScope();
        try {
            $maPhuongAn = PhuongAnXuLy::propose($_POST, Auth::staffId(), $scope);
            $hbt = HangBatThuong::find($ma);
            if ($hbt) {
                $this->notifyDirector(
                    'Có phương án xử lý hàng bất thường chờ duyệt',
                    'Phương án ' . $maPhuongAn . ' cho ' . $ma . ' (' . $hbt['tenKho'] . ' · ' . $hbt['tenHangHoa'] . ') đang chờ Giám đốc phê duyệt.',
                    'hang-bat-thuong/detail?ma=' . $ma,
                    'info'
                );
            }
            $this->flash('success', 'Đã tạo phương án ' . $maPhuongAn . ' (chờ Giám đốc duyệt).');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        } catch (\Throwable $ex) {
            $this->flash('error', config('debug') ? $ex->getMessage() : 'Không thể tạo phương án xử lý lúc này.');
        }
        $this->back($ma);
    }

    /** UC28 — Phê duyệt phương án (Ban giám đốc / Admin). */
    public function approveProposal(): void
    {
        $ma = (string)Request::post('ma');
        $maPA = (string)Request::post('maPhuongAn');
        $scope = $this->khoScope();
        try {
            $pa = PhuongAnXuLy::approve($maPA, Auth::staffId(), (string)Request::post('yKien'), $scope);
            $this->notifyProposer(
                (string)($pa['maNhanVienDeXuat'] ?? ''),
                'Phương án xử lý đã được Giám đốc phê duyệt',
                'Phương án ' . $maPA . ' (' . $ma . ') đã được duyệt và đang chờ Quản lý kho thi hành.',
                'hang-bat-thuong/detail?ma=' . $ma,
                'success'
            );
            if (!empty($pa['maKho'])) {
                $this->notifyWarehouseManager(
                    (string)$pa['maKho'],
                    'Phương án hàng bất thường đã duyệt — cần thi hành',
                    'Giám đốc đã duyệt phương án ' . $maPA . ' (' . $ma . '). Vui lòng thực hiện thi hành tại kho.',
                    'hang-bat-thuong/detail?ma=' . $ma,
                    'info'
                );
            }
            $this->flash('success', 'Đã phê duyệt phương án ' . $maPA . ' (chờ thi hành).');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        } catch (\Throwable $ex) {
            $this->flash('error', config('debug') ? $ex->getMessage() : 'Không thể phê duyệt phương án lúc này.');
        }
        $this->back($ma);
    }

    /** UC28 — Từ chối phương án (bắt buộc nêu lý do). */
    public function rejectProposal(): void
    {
        $ma = (string)Request::post('ma');
        $maPA = (string)Request::post('maPhuongAn');
        $scope = $this->khoScope();
        try {
            $pa = PhuongAnXuLy::reject($maPA, Auth::staffId(), (string)Request::post('lyDo'), $scope);
            $this->notifyProposer(
                (string)($pa['maNhanVienDeXuat'] ?? ''),
                'Phương án xử lý bị từ chối',
                'Phương án ' . $maPA . ' (' . $ma . ') đã bị từ chối: ' . Request::post('lyDo'),
                'hang-bat-thuong/detail?ma=' . $ma,
                'danger'
            );
            $this->flash('success', 'Đã từ chối phương án ' . $maPA . '.');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        } catch (\Throwable $ex) {
            $this->flash('error', config('debug') ? $ex->getMessage() : 'Không thể từ chối phương án lúc này.');
        }
        $this->back($ma);
    }

    /** UC28 — Hủy phương án chưa thi hành. */
    public function cancelProposal(): void
    {
        $ma = (string)Request::post('ma');
        $maPA = (string)Request::post('maPhuongAn');
        $scope = $this->khoScope();
        try {
            PhuongAnXuLy::cancel($maPA, Auth::staffId(), (string)Request::post('lyDo'), can('hang-bat-thuong', 'handle'), $scope);
            $this->flash('success', 'Đã hủy phương án ' . $maPA . '.');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        } catch (\Throwable $ex) {
            $this->flash('error', config('debug') ? $ex->getMessage() : 'Không thể hủy phương án lúc này.');
        }
        $this->back($ma);
    }

    /** UC28 — Thi hành phương án: giảm tồn kho theo biện pháp (trừ 'Hạ cấp chất lượng'). */
    public function executeProposal(): void
    {
        $ma = (string)Request::post('ma');
        $maPA = (string)Request::post('maPhuongAn');
        $scope = $this->khoScope();
        try {
            $paBefore = PhuongAnXuLy::find($maPA);
            $maPhieu = PhieuDieuChinhTonKho::post($maPA, Auth::staffId(), $scope);
            if ($paBefore) {
                $msg = $maPhieu === null
                    ? 'Phương án ' . $maPA . ' (' . $ma . ') đã được thi hành (hạ cấp chất lượng).'
                    : 'Phương án ' . $maPA . ' (' . $ma . ') đã được thi hành, sinh phiếu ' . $maPhieu . '.';
                $this->notifyProposer(
                    (string)($paBefore['maNhanVienDeXuat'] ?? ''),
                    'Phương án xử lý đã thi hành xong',
                    $msg,
                    'hang-bat-thuong/detail?ma=' . $ma,
                    'success'
                );
            }
            if ($maPhieu === null) {
                $this->flash('success', 'Đã ghi nhận xử lý phương án ' . $maPA . ' (hạ cấp chất lượng không làm thay đổi số lượng tồn kho).');
            } else {
                $this->flash('success', 'Đã thi hành phương án ' . $maPA . ', sinh phiếu điều chỉnh ' . $maPhieu . '.');
            }
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        } catch (\Throwable $ex) {
            $this->flash('error', config('debug') ? $ex->getMessage() : 'Không thể thi hành phương án lúc này.');
        }
        $this->back($ma);
    }

    /** Quay lại trang chi tiết nếu có mã, ngược lại về danh sách. */
    private function back(string $ma): void
    {
        redirect($ma !== '' ? 'hang-bat-thuong/detail?ma=' . rawurlencode($ma) : 'hang-bat-thuong');
    }

    /** Gửi thông báo cho Ban giám đốc khi có phương án xử lý chờ phê duyệt. */
    private function notifyDirector(string $title, string $message, string $link, string $type): void
    {
        try {
            ThongBao::notifyRole(ROLE_DIRECTOR, $title, $message, $link, $type, Auth::staffId());
        } catch (\Throwable $ex) {
        }
    }

    /** Gửi thông báo cho quản lý kho phụ trách (hoặc toàn bộ ROLE_MANAGER nếu chưa rõ kho). */
    private function notifyWarehouseManager(string $maKho, string $title, string $message, string $link, string $type): void
    {
        try {
            if ($maKho !== '') {
                $ql = DanhMuc::quanLyKho($maKho);
                if ($ql && $ql['maNhanVien'] !== Auth::staffId()) {
                    ThongBao::notifyUser($ql['maNhanVien'], $title, $message, $link, $type);
                    return;
                }
            }
            ThongBao::notifyRole(ROLE_MANAGER, $title, $message, $link, $type, Auth::staffId());
        } catch (\Throwable $ex) {
        }
    }

    /** Gửi thông báo cho người đề xuất (nếu khác người thao tác). */
    private function notifyProposer(string $maNhanVienDeXuat, string $title, string $message, string $link, string $type): void
    {
        try {
            if ($maNhanVienDeXuat !== '' && $maNhanVienDeXuat !== Auth::staffId()) {
                ThongBao::notifyUser($maNhanVienDeXuat, $title, $message, $link, $type);
            }
        } catch (\Throwable $ex) {
        }
    }
}
