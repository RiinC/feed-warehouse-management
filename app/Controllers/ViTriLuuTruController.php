<?php
namespace App\Controllers;

use App\Models\Kho;
use App\Models\KhuVuc;
use App\Models\LichSuBienDongTonKho;
use App\Models\LuuKho;
use App\Models\ViTriLuuTru;
use Core\Auth;
use Core\Request;

/** Module vị trí lưu trữ: UC12 thêm, UC13 sửa, UC14 ngừng sử dụng, UC15 tra cứu. */
class ViTriLuuTruController extends Controller
{
    private const STATUSES = ['Đang để trống', 'Đang chứa hàng', 'Đã lấp đầy', 'Đang bảo trì', 'Đã khóa'];

    /** UC15 - Tra cứu vị trí lưu trữ. */
    public function index(): void
    {
        $scope = $this->warehouseScope();
        if ($scope === '') {
            $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.');
            $this->redirect('dashboard');
        }
        $f = [
            'q'         => (string)Request::get('q'),
            'maKho'     => (string)Request::get('maKho'),
            'maKhuVuc'  => (string)Request::get('maKhuVuc'),
            'trangThai' => (string)Request::get('trangThai'),
            'tinhTrang' => (string)Request::get('tinhTrang'),
        ];
        $page = max(1, Request::int('page', 1));
        $perPage = (int)config('per_page', 10);
        [$rows, $total] = ViTriLuuTru::search($f, $page, $perPage, $scope);
        $this->render('vi-tri-luu-tru/index', [
            'rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage,
            'scope' => $scope, 'khoOptions' => Kho::activeOptions($scope),
            'khuOptions' => KhuVuc::optionsWithKho($scope), 'statuses' => self::STATUSES,
        ], [
            'title'      => 'Tra cứu vị trí lưu trữ',
            'module'     => 'vi-tri-luu-tru',
            'breadcrumb' => [['Vị trí lưu trữ', null]],
            'actions'    => action_buttons([
                ['vi-tri-luu-tru', 'create', 'Thêm vị trí lưu trữ', 'vi-tri-luu-tru/create', 'btn btn-primary'],
            ]),
        ]);
    }

    /** UC12 - Form thêm vị trí. */
    public function create(): void
    {
        $scope = $this->warehouseScope();
        if ($scope === '') {
            $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.');
            $this->redirect('dashboard');
        }
        $this->renderCreate([], '', $scope);
    }

    /** UC12 - Lưu vị trí mới. */
    public function store(): void
    {
        $scope = $this->warehouseScope();
        if ($scope === '') {
            $this->flash('error', 'Tài khoản của bạn chưa được phân công kho.');
            $this->redirect('dashboard');
        }
        $old = $this->readForm();
        if ($scope !== null) {
            $old['maKho'] = $scope;
        }
        if ($old['maKhuVuc'] === '__new__') {
            if (Auth::role() !== ROLE_ADMIN) {
                $this->renderCreate($old, 'Chỉ quản trị viên được tạo khu vực mới.', $scope);
                return;
            }
            try {
                $old['maKhuVuc'] = KhuVuc::createInKho($old['maKho'], $old['tenKhuVucMoi'], $old['loaiKhuVucMoi'] ?: 'Lưu trữ chính');
            } catch (\RuntimeException $ex) {
                $this->renderCreate($old, $ex->getMessage(), $scope);
                return;
            }
        }
        $error = $this->validateCreate($old);
        if ($error !== '') {
            $this->renderCreate($old, $error, $scope);
            return;
        }
        try {
            $code = ViTriLuuTru::create($old);
            $this->flash('success', 'Đã thêm vị trí lưu trữ ' . $code . '.');
            $this->redirect('vi-tri-luu-tru');
        } catch (\RuntimeException $ex) {
            $this->renderCreate($old, $ex->getMessage(), $scope);
        }
    }

    private function renderCreate(array $old, string $error, ?string $scope): void
    {
        $this->render('vi-tri-luu-tru/create', [
            'old' => $old, 'error' => $error, 'fixedKho' => $scope,
            'khoOptions' => Kho::activeOptions($scope), 'khuOptions' => KhuVuc::optionsWithKho($scope), 'allowNewArea' => Auth::role() === ROLE_ADMIN,
        ], [
            'title'      => 'Thêm thông tin vị trí lưu trữ',
            'module'     => 'vi-tri-luu-tru',
            'breadcrumb' => [['Vị trí lưu trữ', 'vi-tri-luu-tru'], ['Thêm thông tin vị trí lưu trữ', null]],
        ]);
    }


    /** UC13 - Form sửa vị trí. */
    public function edit(): void
    {
        $scope = $this->warehouseScope();
        $pos = $this->findInScope((string)Request::get('id'), $scope);
        if (!$pos) {
            return;
        }
        $this->renderEdit($pos, $pos, '', $scope);
    }

    /** UC13 - Cập nhật tên và sức chứa. */
    public function update(): void
    {
        $scope = $this->warehouseScope();
        $pos = $this->findInScope((string)Request::post('id'), $scope);
        if (!$pos) {
            return;
        }
        $old = [
            'maViTri' => (string)$pos['maViTri'],
            'tenViTri' => (string)Request::post('tenViTri'),
            'sucChuaToiDa' => parse_qty(Request::post('sucChuaToiDa'), false, 2) ?? 0.0,
            'tenKhuVucMoi' => (string)Request::post('tenKhuVucMoi'),
            'loaiKhuVucMoi' => (string)Request::post('loaiKhuVucMoi', 'Lưu trữ chính'),
        ];
        $error = $this->validateUpdate($old);
        if ($error !== '') {
            $this->renderEdit($pos, $old, $error, $scope);
            return;
        }
        try {
            ViTriLuuTru::updateInfo((string)$pos['maViTri'], $old['tenViTri'], $old['sucChuaToiDa']);
            $this->flash('success', 'Đã cập nhật vị trí lưu trữ ' . $pos['maViTri'] . '.');
            $this->redirect('vi-tri-luu-tru');
        } catch (\RuntimeException $ex) {
            $this->renderEdit($pos, $old, $ex->getMessage(), $scope);
        }
    }

    private function renderEdit(array $pos, array $old, string $error, ?string $scope): void
    {
        $this->render('vi-tri-luu-tru/edit', [
            'pos' => $pos, 'old' => $old, 'error' => $error,
            'used' => ViTriLuuTru::quantities((string)$pos['maViTri']), 'fixedKho' => $scope, 'defaultName' => ViTriLuuTru::defaultName((string)$pos['day'], (string)$pos['ke'], (string)$pos['tang'], (string)$pos['o']),
        ], [
            'title'      => 'Sửa thông tin vị trí lưu trữ',
            'module'     => 'vi-tri-luu-tru',
            'breadcrumb' => [['Vị trí lưu trữ', 'vi-tri-luu-tru'], ['Sửa thông tin vị trí lưu trữ', null]],
        ]);
    }

    /** UC14 - Trang xác nhận ngừng sử dụng. */
    public function stopUsing(): void
    {
        $scope = $this->warehouseScope();
        $pos = $this->findInScope((string)Request::get('id'), $scope);
        if (!$pos) {
            return;
        }
        $this->render('vi-tri-luu-tru/stop-using', [
            'pos' => $pos, 'used' => ViTriLuuTru::quantities((string)$pos['maViTri']),
            'reason' => ViTriLuuTru::blockingReason((string)$pos['maViTri']),
        ], [
            'title'      => 'Ngừng sử dụng vị trí lưu trữ',
            'module'     => 'vi-tri-luu-tru',
            'breadcrumb' => [['Vị trí lưu trữ', 'vi-tri-luu-tru'], ['Ngừng sử dụng vị trí lưu trữ', null]],
        ]);
    }

    /** UC14 - Thực hiện xóa mềm. */
    public function stop(): void
    {
        $scope = $this->warehouseScope();
        $pos = $this->findInScope((string)Request::post('id'), $scope);
        if (!$pos) {
            return;
        }
        try {
            ViTriLuuTru::stop((string)$pos['maViTri']);
            $this->flash('success', 'Đã ngừng sử dụng vị trí ' . $pos['maViTri'] . '.');
            $this->redirect('vi-tri-luu-tru');
        } catch (\RuntimeException $ex) {
            $this->flash('error', $ex->getMessage());
            $this->redirect('vi-tri-luu-tru/stop-using?id=' . urlencode((string)$pos['maViTri']));
        }
    }



    /** Xem chi tiết vị trí. */
    public function detail(): void
    {
        $scope = $this->warehouseScope();
        $pos = $this->findInScope((string)Request::get('id'), $scope);
        if (!$pos) { return; }
        $this->render('vi-tri-luu-tru/detail', [
            'pos' => $pos,
            'used' => ViTriLuuTru::quantities((string)$pos['maViTri']),
            'lots' => ViTriLuuTru::lotsAtPosition((string)$pos['maViTri']),
            'allocations' => LuuKho::atPosition((string)$pos['maViTri']),
            'history' => LichSuBienDongTonKho::historyAtPosition((string)$pos['maViTri'], 20),
        ], [
            'title' => 'Chi tiết vị trí ' . $pos['maViTri'],
            'module' => 'vi-tri-luu-tru',
            'breadcrumb' => [['Vị trí lưu trữ', 'vi-tri-luu-tru'], ['Chi tiết vị trí', null]],
        ]);
    }

    private function findInScope(string $id, ?string $scope): ?array
    {
        if ($id === '') {
            $this->flash('warning', 'Vui lòng chọn một vị trí lưu trữ.');
            $this->redirect('vi-tri-luu-tru');
            return null;
        }
        $pos = ViTriLuuTru::find($id);
        if (!$pos) {
            $this->flash('warning', 'Vị trí lưu trữ không tồn tại.');
            $this->redirect('vi-tri-luu-tru');
            return null;
        }
        if (!$this->positionInScope($pos, $scope)) {
            $this->forbidden();
            return null;
        }
        return $pos;
    }

    private function positionInScope(array $pos, ?string $scope): bool
    {
        if ($scope === null) {
            return true;
        }
        return $scope !== '' && (string)$pos['maKho'] === $scope;
    }

    private function readForm(): array
    {
        return [
            'maKho' => (string)Request::post('maKho'),
            'maKhuVuc' => (string)Request::post('maKhuVuc'),
            'tenViTri' => (string)Request::post('tenViTri'),
            'day' => (string)Request::post('day'),
            'ke' => (string)Request::post('ke'),
            'tang' => (string)Request::post('tang'),
            'o' => (string)Request::post('o'),
            'sucChuaToiDa' => parse_qty(Request::post('sucChuaToiDa'), false, 2) ?? 0.0,
            'tenKhuVucMoi' => (string)Request::post('tenKhuVucMoi'),
            'loaiKhuVucMoi' => (string)Request::post('loaiKhuVucMoi', 'Lưu trữ chính'),
        ];
    }

    private function validateCreate(array $d): string
    {
        if ($d['maKho'] === '' || !array_key_exists($d['maKho'], Kho::activeOptions(null))) {
            return 'Vui lòng chọn kho đang hoạt động.';
        }
        if ($d['maKhuVuc'] === '' || !KhuVuc::belongsToActiveKho($d['maKhuVuc'], $d['maKho'])) {
            return 'Vui lòng chọn khu vực đang hoạt động thuộc đúng kho.';
        }
        if (trim($d['tenViTri']) === '') {
            return 'Vui lòng nhập tên vị trí.';
        }
        if (mb_strlen(trim($d['tenViTri'])) > 60) {
            return 'Tên vị trí tối đa 60 ký tự.';
        }
        foreach (['day', 'ke', 'tang', 'o'] as $k) {
            if (!preg_match('/^[0-9]{2}$/', $d[$k])) {
                return 'Dãy, kệ, tầng, ô phải gồm đúng 2 chữ số.';
            }
        }
        if ($d['ke'] === '00' && $d['tang'] !== '00') {
            return 'Kệ 00 chỉ hợp lệ khi tầng là 00.';
        }
        if ($d['ke'] !== '00' && !in_array($d['tang'], ['01', '02'], true)) {
            return 'Tầng chỉ nhận giá trị 01 hoặc 02.';
        }
        if ($d['sucChuaToiDa'] <= 0) {
            return 'Sức chứa phải lớn hơn 0.';
        }
        if (ViTriLuuTru::existsCoordinates($d['maKhuVuc'], $d['day'], $d['ke'], $d['tang'], $d['o'])) {
            return 'Tọa độ (dãy/kệ/tầng/ô) đã có vị trí trong khu vực này.';
        }
        return '';
    }

    private function validateUpdate(array $d): string
    {
        if (trim($d['tenViTri']) === '') {
            return 'Vui lòng nhập tên vị trí.';
        }
        if (mb_strlen(trim($d['tenViTri'])) > 60) {
            return 'Tên vị trí tối đa 60 ký tự.';
        }
        if ($d['sucChuaToiDa'] <= 0) {
            return 'Sức chứa phải lớn hơn 0.';
        }
        return '';
    }
}

