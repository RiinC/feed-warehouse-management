<?php
namespace App\Controllers;

use App\Models\Kho;
use App\Models\KhuVuc;
use App\Models\LichSuBienDongTonKho;
use App\Models\LoHang;
use App\Models\NhanVien;
use App\Models\PhieuDieuChuyen;
use App\Models\ThongBao;
use App\Models\TonKho;
use App\Models\ViTriLuuTru;
use Core\Auth;
use Core\Request;

/** UC17 - Điều chuyển nội bộ có phê duyệt. */
class DieuChuyenController extends Controller
{
    private const STATUSES = ['Chờ duyệt', 'Đã duyệt', 'Đã từ chối', 'Chờ điều chuyển', 'Đang thực hiện', 'Đã hoàn tất', 'Đã hủy'];

    /** Danh sách phiếu theo phạm vi: Admin tất cả, Quản lý theo kho, Nhân viên phiếu mình lập. */
    public function index(): void
    {
        $scope = $this->warehouseScope();
        if ($scope === '') {
            $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.');
            $this->redirect('dashboard');
            return;
        }
        $f = [
            'q' => (string)Request::get('q'), 'trangThai' => (string)Request::get('trangThai'),
            'maLoHang' => (string)Request::get('maLoHang'), 'maViTriNguon' => (string)Request::get('maViTriNguon'),
            'maViTriDich' => (string)Request::get('maViTriDich'), 'tuNgay' => (string)Request::get('tuNgay'),
            'denNgay' => (string)Request::get('denNgay'), 'nguoiLap' => (string)Request::get('nguoiLap'),
        ];
        $page = max(1, Request::int('page', 1));
        $perPage = (int)config('per_page', 10);
        [$rows, $total] = PhieuDieuChuyen::search($f, $page, $perPage, $this->listScope($scope));
        $actions = action_buttons([
            ['dieu-chuyen', 'create', 'Lập phiếu điều chuyển', 'dieu-chuyen/create', 'btn btn-primary'],
        ]);
        if (can('dieu-chuyen', 'approve')) {
            $actions .= ' <a class="btn" href="' . e(route('dieu-chuyen/approve')) . '">Phiếu chờ duyệt</a>';
        }
        $this->render('dieu-chuyen/index', [
            'rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage,
            'statuses' => self::STATUSES, 'scope' => $scope,
        ], [
            'title'      => 'Điều chuyển hàng nội bộ',
            'module'     => 'dieu-chuyen',
            'breadcrumb' => [['Điều chuyển nội bộ', null]],
            'actions'    => $actions,
        ]);
    }

    /** Bước 1 - Chọn lô hàng cần điều chuyển. */
    public function create(): void
    {
        if (!$this->requireScope()) { return; }
        $scope = $this->warehouseScope();
        $q = (string)Request::get('q');
        $allowedSorts = ['maLoHang', 'tenHangHoa', 'ngayHetHan', 'khaDung', 'soViTri'];
        $sort = (string)Request::get('sort', 'ngayHetHan');
        if (!in_array($sort, $allowedSorts, true)) { $sort = 'ngayHetHan'; }
        $dir = strtolower((string)Request::get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $lots = TonKho::sourceLots($scope, $q);
        $sortText = static function (string $value): string {
            $value = mb_strtolower($value);
            $ascii = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : false;
            return $ascii !== false ? $ascii : $value;
        };
        usort($lots, function (array $a, array $b) use ($sort, $dir, $sortText): int {
            switch ($sort) {
                case 'khaDung':
                    $cmp = (float)$a['khaDung'] <=> (float)$b['khaDung'];
                    break;
                case 'soViTri':
                    $cmp = (int)$a['soViTri'] <=> (int)$b['soViTri'];
                    break;
                case 'tenHangHoa':
                    $cmp = strcmp($sortText((string)$a['tenHangHoa']), $sortText((string)$b['tenHangHoa']));
                    break;
                case 'maLoHang':
                    $cmp = strnatcasecmp((string)$a['maLoHang'], (string)$b['maLoHang']);
                    break;
                default:
                    $cmp = strcmp((string)$a['ngayHetHan'], (string)$b['ngayHetHan']);
            }
            if ($cmp === 0) {
                $cmp = strnatcasecmp((string)$a['maLoHang'], (string)$b['maLoHang']);
            }
            return $dir === 'desc' ? -$cmp : $cmp;
        });
        $total = count($lots);
        $perPage = 10;
        $page = max(1, min((int)$total === 0 ? 1 : (int)ceil($total / $perPage), Request::int('page', 1)));
        $lots = array_slice($lots, ($page - 1) * $perPage, $perPage);
        $this->render('dieu-chuyen/create-lot', [
            'lots' => $lots, 'draft' => $this->draft(), 'q' => $q,
            'page' => $page, 'total' => $total, 'perPage' => $perPage,
            'sort' => $sort, 'dir' => $dir,
        ], ['title' => 'Lập phiếu điều chuyển - Chọn lô hàng', 'module' => 'dieu-chuyen', 'breadcrumb' => [['Điều chuyển nội bộ', 'dieu-chuyen'], ['Lập phiếu - Bước 1', null]]]);
    }

    /** Bước 1 - lưu lô đã chọn. */
    public function createLot(): void
    {
        if (!$this->requireScope()) { return; }
        $scope = $this->warehouseScope();
        $val = (string)Request::post('lot');
        $parts = $val !== '' ? explode('|', $val, 2) : ['', ''];
        $lot = $parts[0] ?? ''; $kho = $parts[1] ?? '';
        $ok = false;
        foreach (TonKho::sourceLots($scope) as $r) {
            if ((string)$r['maLoHang'] === $lot && (string)$r['maKho'] === $kho) { $ok = true; break; }
        }
        if (!$ok) { $this->flash('error', 'Lô hàng không còn tồn khả dụng trong kho đã chọn.'); $this->redirect('dieu-chuyen/create'); return; }
        $this->saveDraft(['maLoHang' => $lot, 'maKho' => $kho]);
        $this->redirect('dieu-chuyen/create/source');
    }

    /** Bước 2 - chọn vị trí nguồn. */
    public function createSource(): void
    {
        if (!$this->requireScope()) { return; }
        $scope = $this->warehouseScope(); $d = $this->draft();
        if (empty($d['maLoHang']) || empty($d['maKho'])) { $this->flash('warning', 'Vui lòng chọn lô hàng trước.'); $this->redirect('dieu-chuyen/create'); return; }
        $positions = TonKho::positionsForLot((string)$d['maLoHang'], (string)$d['maKho'], $scope);
        $areas = KhuVuc::options((string)$d['maKho']);
        $khuVuc = (string)Request::get('khuVuc');
        if ($khuVuc !== '' && !isset($areas[$khuVuc])) { $khuVuc = ''; }
        if ($khuVuc !== '') {
            $positions = array_values(array_filter($positions, fn(array $row): bool => (string)$row['maKhuVuc'] === $khuVuc));
        }
        $total = count($positions);
        $perPage = 10;
        $page = max(1, min($total === 0 ? 1 : (int)ceil($total / $perPage), Request::int('page', 1)));
        $positions = array_slice($positions, ($page - 1) * $perPage, $perPage);
        $this->render('dieu-chuyen/create-source', [
            'draft' => $d, 'positions' => $positions, 'areas' => $areas,
            'lot' => LoHang::find((string)$d['maLoHang']),
            'khuVuc' => $khuVuc, 'page' => $page, 'total' => $total, 'perPage' => $perPage,
        ], ['title' => 'Lập phiếu điều chuyển - Chọn vị trí nguồn', 'module' => 'dieu-chuyen', 'breadcrumb' => [['Điều chuyển nội bộ', 'dieu-chuyen'], ['Lập phiếu - Bước 2', null]]]);
    }

    /** Bước 2 - lưu vị trí nguồn. */
    public function createSourceSave(): void
    {
        if (!$this->requireScope()) { return; }
        $scope = $this->warehouseScope(); $d = $this->draft();
        if (empty($d['maLoHang']) || empty($d['maKho'])) { $this->redirect('dieu-chuyen/create'); return; }
        $selected = (string)Request::post('maViTriNguon'); $found = null;
        foreach (TonKho::positionsForLot((string)$d['maLoHang'], (string)$d['maKho'], $scope) as $r) { if ((string)$r['maViTri'] === $selected) { $found = $r; break; } }
        if (!$found) { $this->flash('error', 'Vị trí nguồn không hợp lệ hoặc không còn tồn khả dụng.'); $this->redirect('dieu-chuyen/create/source'); return; }
        $d['maViTriNguon'] = (string)$found['maViTri']; $d['maKhuVucNguon'] = (string)$found['maKhuVuc']; $this->saveDraft($d);
        $this->redirect('dieu-chuyen/create/destination');
    }

    /** Bước 3 - chọn vị trí đích. */
    public function createDestination(): void
    {
        if (!$this->requireScope()) { return; }
        $scope = $this->warehouseScope(); $d = $this->draft();
        if (empty($d['maViTriNguon'])) { $this->flash('warning', 'Vui lòng chọn vị trí nguồn trước.'); $this->redirect('dieu-chuyen/create/source'); return; }
        $areas = KhuVuc::options((string)$d['maKho']);
        $positions = array_values(array_filter(
            ViTriLuuTru::positionOptions((string)$d['maKho'], true),
            fn(array $row): bool => (string)$row['maViTri'] !== (string)$d['maViTriNguon']
        ));
        $khuVuc = (string)Request::get('khuVuc');
        if ($khuVuc !== '' && !isset($areas[$khuVuc])) { $khuVuc = ''; }
        if ($khuVuc !== '') {
            $positions = array_values(array_filter($positions, fn(array $row): bool => (string)$row['maKhuVuc'] === $khuVuc));
        }
        $total = count($positions);
        $perPage = 10;
        $page = max(1, min($total === 0 ? 1 : (int)ceil($total / $perPage), Request::int('page', 1)));
        $positions = array_slice($positions, ($page - 1) * $perPage, $perPage);
        $this->render('dieu-chuyen/create-destination', [
            'draft' => $d, 'positions' => $positions, 'areas' => $areas,
            'lot' => LoHang::find((string)$d['maLoHang']), 'src' => ViTriLuuTru::find((string)$d['maViTriNguon']),
            'khuVuc' => $khuVuc, 'page' => $page, 'total' => $total, 'perPage' => $perPage,
        ], ['title' => 'Lập phiếu điều chuyển - Chọn vị trí đích', 'module' => 'dieu-chuyen', 'breadcrumb' => [['Điều chuyển nội bộ', 'dieu-chuyen'], ['Lập phiếu - Bước 3', null]]]);
    }

    /** Bước 3 - lưu vị trí đích. */
    public function createDestinationSave(): void
    {
        if (!$this->requireScope()) { return; }
        $d = $this->draft(); if (empty($d['maViTriNguon'])) { $this->redirect('dieu-chuyen/create/source'); return; }
        $selected = (string)Request::post('maViTriDich'); $found = null;
        foreach (ViTriLuuTru::positionOptions((string)$d['maKho'], true) as $r) { if ((string)$r['maViTri'] === $selected && (string)$r['maViTri'] !== (string)$d['maViTriNguon']) { $found = $r; break; } }
        if (!$found) { $this->flash('error', 'Vị trí đích không hợp lệ, không cùng kho hoặc đã hết sức chứa.'); $this->redirect('dieu-chuyen/create/destination'); return; }
        $d['maViTriDich'] = (string)$found['maViTri']; $d['maKhuVucDich'] = (string)$found['maKhuVuc']; $this->saveDraft($d);
        $this->redirect('dieu-chuyen/create/info');
    }

    private function listScope(?string $scope): array
    {
        if (Auth::role() === ROLE_ADMIN) { return ['type' => 'all', 'value' => null]; }
        if (Auth::role() === ROLE_MANAGER) { return ['type' => 'kho', 'value' => $scope]; }
        return ['type' => 'own', 'value' => Auth::staffId()];
    }

    /** Bước 4 - nhập số lượng và lý do. */
    public function createInfo(): void
    {
        if (!$this->requireScope()) { return; }
        $d = $this->draft();
        if (empty($d['maViTriNguon']) || empty($d['maViTriDich'])) { $this->flash('warning', 'Vui lòng chọn đủ vị trí nguồn và vị trí đích.'); $this->redirect('dieu-chuyen/create/source'); return; }
        $src = TonKho::findRow((string)$d['maLoHang'], (string)$d['maViTriNguon']);
        $avail = $src ? ((float)$src['soLuongTon'] - (float)$src['soLuongDaGiu']) : 0;
        $destQ = ViTriLuuTru::quantities((string)$d['maViTriDich']);
        $dv = ViTriLuuTru::find((string)$d['maViTriDich']);
        $destCon = $dv ? ((float)$dv['sucChuaToiDa'] - $destQ['daDung']) : 0;
        $defaultQty = isset($d['soLuong']) ? (float)$d['soLuong'] : max(0, $avail);
        $this->render('dieu-chuyen/create-info', ['draft' => $d, 'lot' => LoHang::find((string)$d['maLoHang']), 'src' => ViTriLuuTru::find((string)$d['maViTriNguon']), 'dst' => $dv, 'avail' => max(0, $avail), 'destCon' => max(0, $destCon), 'defaultQty' => $defaultQty], ['title' => 'Lập phiếu điều chuyển - Nhập thông tin', 'module' => 'dieu-chuyen', 'breadcrumb' => [['Điều chuyển nội bộ', 'dieu-chuyen'], ['Lập phiếu - Bước 4', null]]]);
    }

    /** Bước 4 - lưu số lượng/lý do. */
    public function createInfoSave(): void
    {
        if (!$this->requireScope()) { return; }
        $d = $this->draft();
        if (empty($d['maViTriNguon']) || empty($d['maViTriDich'])) { $this->redirect('dieu-chuyen/create/source'); return; }
        $qty = (float)str_replace(',', '.', (string)Request::post('soLuong'));
        $reason = trim((string)Request::post('lyDoDieuChuyen'));
        $src = TonKho::findRow((string)$d['maLoHang'], (string)$d['maViTriNguon']);
        $avail = $src ? ((float)$src['soLuongTon'] - (float)$src['soLuongDaGiu']) : 0;
        $destQ = ViTriLuuTru::quantities((string)$d['maViTriDich']);
        $dv = ViTriLuuTru::find((string)$d['maViTriDich']);
        $destCon = $dv ? ((float)$dv['sucChuaToiDa'] - $destQ['daDung']) : 0;
        if ($qty <= 0 || $qty > $avail + 0.000001 || $qty > $destCon + 0.000001) { $this->flash('error', 'Số lượng không hợp lệ hoặc vượt tồn nguồn/sức chứa đích.'); $this->redirect('dieu-chuyen/create/info'); return; }
        if ($reason === '') { $this->flash('error', 'Vui lòng nhập lý do điều chuyển.'); $this->redirect('dieu-chuyen/create/info'); return; }
        $d['soLuong'] = $qty; $d['lyDoDieuChuyen'] = $reason; $this->saveDraft($d);
        $this->redirect('dieu-chuyen/create/confirm');
    }

    /** Bước 5 - xem lại. */
    public function createConfirm(): void
    {
        if (!$this->requireScope()) { return; }
        $d = $this->draft();
        if (empty($d['maLoHang']) || empty($d['maViTriNguon']) || empty($d['maViTriDich']) || empty($d['soLuong'])) { $this->flash('warning', 'Thông tin phiếu chưa đầy đủ.'); $this->redirect('dieu-chuyen/create'); return; }
        $this->render('dieu-chuyen/create-confirm', ['draft' => $d, 'lot' => LoHang::find((string)$d['maLoHang']), 'src' => ViTriLuuTru::find((string)$d['maViTriNguon']), 'dst' => ViTriLuuTru::find((string)$d['maViTriDich']), 'scope' => $this->warehouseScope()], ['title' => 'Lập phiếu điều chuyển - Xác nhận', 'module' => 'dieu-chuyen', 'breadcrumb' => [['Điều chuyển nội bộ', 'dieu-chuyen'], ['Lập phiếu - Bước 5', null]]]);
    }

    /** Bước 5 - tạo phiếu thật. */
    public function store(): void
    {
        if (!$this->requireScope()) { return; }
        $scope = $this->warehouseScope(); $d = $this->draft();
        if (empty($d['maLoHang']) || empty($d['maViTriNguon']) || empty($d['maViTriDich']) || empty($d['soLuong'])) { $this->flash('error', 'Thông tin phiếu chưa đầy đủ.'); $this->redirect('dieu-chuyen/create'); return; }
        try {
            $code = PhieuDieuChuyen::createRequest($d, Auth::staffId(), $scope);
            $this->notifyPending($code); $this->clearDraft();
            $this->flash('success', 'Đã lập phiếu ' . $code . ', chờ quản lý kho phê duyệt.');
            $this->redirect('dieu-chuyen/detail?id=' . urlencode($code));
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage()); $this->redirect('dieu-chuyen/create/confirm');
        }
    }

    /** Hủy quy trình và xóa draft. */
    public function createReset(): void
    {
        $this->clearDraft();
        $this->flash('info', 'Đã hủy quá trình lập phiếu điều chuyển.');
        $this->redirect('dieu-chuyen');
    }

    private function requireScope(): bool
    {
        if ($this->warehouseScope() === '') { $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.'); $this->redirect('dashboard'); return false; }
        return true;
    }

    private function draft(): array
    {
        $d = $_SESSION['dc_draft'] ?? [];
        return is_array($d) ? $d : [];
    }

    private function saveDraft(array $d): void
    {
        $_SESSION['dc_draft'] = $d;
    }

    private function clearDraft(): void
    {
        unset($_SESSION['dc_draft']);
    }

    /** Chi tiết phiếu (người lập, quản lý kho phụ trách, quản trị viên). */
    public function detail(): void
    {
        $id = (string)Request::get('id');
        $p = $id !== '' ? PhieuDieuChuyen::find($id) : null;
        if (!$p) {
            $this->flash('warning', 'Phiếu điều chuyển không tồn tại.');
            $this->redirect('dieu-chuyen');
            return;
        }
        $scope = $this->warehouseScope();
        if (!PhieuDieuChuyen::canView($p, Auth::staffId(), Auth::role(), $scope)) {
            $this->forbidden();
            return;
        }
        $stock = TonKho::findRow((string)$p['maLoHang'], (string)$p['maViTriNguon']);
        $avail = $stock ? ((float)$stock['soLuongTon'] - (float)$stock['soLuongDaGiu']) : 0;
        $destQ = ViTriLuuTru::quantities((string)$p['maViTriDich']);
        $destCon = (float)$p['sucChuaDich'] - $destQ['daDung'];
        $this->render('dieu-chuyen/detail', [
            'p' => $p, 'avail' => max(0, $avail), 'destCon' => max(0, $destCon), 'remaining' => PhieuDieuChuyen::remainingQuantity($p),
            'history' => LichSuBienDongTonKho::byDocument($id),
        ], [
            'title'      => 'Chi tiết phiếu điều chuyển ' . $id,
            'module'     => 'dieu-chuyen',
            'breadcrumb' => [['Điều chuyển nội bộ', 'dieu-chuyen'], ['Chi tiết phiếu', null]],
        ]);
    }

    /** Danh sách phiếu chờ duyệt thuộc kho quản lý. */
    public function approve(): void
    {
        $scope = $this->warehouseScope();
        if ($scope === '') {
            $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.');
            $this->redirect('dashboard');
            return;
        }
        $page = max(1, Request::int('page', 1));
        $perPage = (int)config('per_page', 10);
        $scopeArr = $scope === null ? ['type' => 'all', 'value' => null] : ['type' => 'kho', 'value' => $scope];
        [$rows, $total] = PhieuDieuChuyen::search(['trangThai' => 'Chờ duyệt'], $page, $perPage, $scopeArr);
        $this->render('dieu-chuyen/approve', ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage], [
            'title'      => 'Phiếu điều chuyển chờ duyệt',
            'module'     => 'dieu-chuyen',
            'breadcrumb' => [['Điều chuyển nội bộ', 'dieu-chuyen'], ['Phiếu chờ duyệt', null]],
        ]);
    }

    /** POST dieu-chuyen/approve/save */
    public function approveSave(): void
    {
        $scope = $this->warehouseScope();
        if ($scope === '') {
            $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.');
            $this->redirect('dashboard');
            return;
        }
        $id = (string)Request::post('id');
        $action = (string)Request::post('action');
        if (!in_array($action, ['approve', 'reject'], true)) {
            $this->flash('error', 'Hành động xử lý phiếu không hợp lệ.');
            $this->redirect('dieu-chuyen/detail?id=' . urlencode($id));
            return;
        }
        $reason = (string)Request::post('lyDoTuChoi');
        $back = 'dieu-chuyen/detail?id=' . urlencode($id);
        try {
            $p = PhieuDieuChuyen::find($id);
            if (!$p) {
                throw new \RuntimeException('Phiếu điều chuyển không tồn tại.');
            }
            if ($action === 'approve') {
                PhieuDieuChuyen::approve($id, Auth::staffId(), $scope);
                $this->notifyCreator($p, 'success', 'Phiếu điều chuyển đã được phê duyệt.');
                $this->flash('success', 'Đã phê duyệt phiếu ' . $id . '.');
            } else {
                PhieuDieuChuyen::reject($id, Auth::staffId(), $scope, $reason);
                $this->notifyCreator($p, 'danger', 'Phiếu điều chuyển bị từ chối. Lý do: ' . $reason);
                $this->flash('success', 'Đã từ chối phiếu ' . $id . '.');
            }
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        }
        $this->redirect($back);
    }


    /** POST dieu-chuyen/execute */
    public function execute(): void
    {
        $scope = $this->warehouseScope();
        if ($scope === '') {
            $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.');
            $this->redirect('dashboard');
            return;
        }
        $id = (string)Request::post('id');
        $qty = (float)str_replace(',', '.', (string)Request::post('soLuongThucChuyen'));
        $back = 'dieu-chuyen/detail?id=' . urlencode($id);
        try {
            $r = PhieuDieuChuyen::execute($id, Auth::staffId(), $scope, $qty);
            $msg = $r['hoanTat']
                ? 'Phiếu ' . $id . ' đã hoàn tất: ' . fmt_number($r['luyKe'], 2) . ' kg từ ' . $r['tenNguon'] . ' đến ' . $r['tenDich'] . '.'
                : 'Phiếu ' . $id . ' đã chuyển ' . fmt_number($r['luyKe'], 2) . ' kg, còn ' . fmt_number($r['conLai'], 2) . ' kg cần điều chuyển.';
            try {
                ThongBao::notifyUser((string)$r['nguoiLap'], $r['hoanTat'] ? 'Phiếu điều chuyển đã hoàn tất' : 'Phiếu điều chuyển đã chuyển một phần', $msg, 'dieu-chuyen/detail?id=' . urlencode($id), $r['hoanTat'] ? 'success' : 'info');
                if (!empty($r['nguoiDuyet'])) {
                    ThongBao::notifyUser((string)$r['nguoiDuyet'], $r['hoanTat'] ? 'Phiếu điều chuyển đã hoàn tất' : 'Phiếu điều chuyển đã chuyển một phần', $msg, 'dieu-chuyen/detail?id=' . urlencode($id), $r['hoanTat'] ? 'success' : 'info');
                }
            } catch (\Throwable $e) {
            }
            $this->flash('success', $msg);
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        }
        $this->redirect($back);
    }

    /** POST dieu-chuyen/cancel */
    public function cancel(): void
    {
        $id = (string)Request::post('id');
        $back = 'dieu-chuyen/detail?id=' . urlencode($id);
        try {
            PhieuDieuChuyen::cancel($id, Auth::staffId());
            $this->flash('success', 'Đã hủy phiếu ' . $id . '.');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
        }
        $this->redirect($back);
    }

    private function notifyPending(string $maPhieu): void
    {
        try {
            $p = PhieuDieuChuyen::find($maPhieu);
            if (!$p) {
                return;
            }
            $user = Auth::user();
            $msg = 'Nhân viên ' . ($user['hoTen'] ?? '') . ' lập phiếu ' . $maPhieu . ' điều chuyển '
                 . fmt_number($p['soLuongDuKien'], 2) . ' kg lô ' . $p['maLoHang'] . ' từ ' . $p['tenViTriNguon'] . ' đến ' . $p['tenViTriDich'] . '.';
            foreach (NhanVien::activeManagerIdsByKho((string)$p['maKhoNguon'], Auth::staffId()) as $id) {
                ThongBao::notifyUser($id, 'Phiếu điều chuyển chờ duyệt', $msg, 'dieu-chuyen/detail?id=' . urlencode($maPhieu), 'warning');
            }
        } catch (\Throwable $e) {
        }
    }

    private function notifyCreator(array $p, string $type, string $message): void
    {
        try {
            ThongBao::notifyUser((string)$p['maNhanVienYeuCau'], 'Cập nhật phiếu điều chuyển ' . $p['maPhieuDieuChuyen'], $message, 'dieu-chuyen/detail?id=' . urlencode((string)$p['maPhieuDieuChuyen']), $type);
        } catch (\Throwable $e) {
        }
    }
}

