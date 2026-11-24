<?php
namespace App\Controllers;

use App\Models\HangHoa;
use Core\Request;

/**
 * HangHoaController — module "hang-hoa" (UC01 thêm · UC02 sửa · UC03 ngừng sử dụng · UC04 tra cứu).
 *
 * Mọi thao tác nằm trong trang quản lý chính `hang-hoa`:
 *   GET  hang-hoa                      danh sách + tìm kiếm/lọc (UC04)
 *   GET  hang-hoa?mode=view&id=MA      cửa sổ chi tiết (UC04)
 *   GET  hang-hoa?mode=create          cửa sổ thêm mới (UC01)
 *   GET  hang-hoa?mode=edit&id=MA      cửa sổ chỉnh sửa (UC02)
 *   GET  hang-hoa?mode=stop&id=MA      cửa sổ ngừng sử dụng (UC03)
 *   POST hang-hoa/store | update | stop   lưu; lỗi nhập liệu thì hiện lại cửa sổ kèm thông báo
 * Quyền từng thao tác: can('hang-hoa', 'create' | 'edit' | 'stop'); xem: 'view'.
 */
class HangHoaController extends Controller
{
    private const MODES = [
        'view'   => ['view', 'Chi tiết hàng hóa'],
        'create' => ['create', 'Thêm hàng hóa'],
        'edit'   => ['edit', 'Chỉnh sửa hàng hóa'],
        'stop'   => ['stop', 'Ngừng sử dụng hàng hóa'],
    ];

    /** GET hang-hoa · quyền hang-hoa/view */
    public function index(): void
    {
        $mode = (string)Request::get('mode');
        $modal = null;
        if ($mode !== '') {
            if (!isset(self::MODES[$mode])) {
                $this->redirect('hang-hoa');
            }
            if (!can('hang-hoa', self::MODES[$mode][0])) {
                (new ErrorController())->show(403);
                return;
            }
            if ($mode === 'create') {
                $modal = ['mode' => 'create', 'item' => null, 'values' => $this->defaults(), 'errors' => []];
            } else {
                $item = HangHoa::find((string)Request::get('id'));
                if (!$item) {
                    $this->flash('error', 'Không tìm thấy hàng hóa');
                    $this->redirect($this->listUrl());
                }
                if ($mode === 'stop' && $item['trangThai'] === HangHoa::TT_NGUNG) {
                    $this->flash('warning', 'Hàng hóa này đã ngừng sử dụng.');
                    $this->redirect($this->listUrl());
                }
                $modal = ['mode' => $mode, 'item' => $item, 'values' => $this->valuesFrom($item), 'errors' => []];
                if ($mode === 'stop') {
                    $modal['values'] = ['ngayNgung' => date('Y-m-d\TH:i'), 'lyDo' => ''];
                }
            }
        }
        $this->renderIndex($modal);
    }

    /** POST hang-hoa/store · quyền hang-hoa/create (UC01) */
    public function store(): void
    {
        $values = $this->postedValues();
        [$data, $errors] = $this->validate($values, null);
        if ($errors) {
            $this->renderIndex(['mode' => 'create', 'item' => null, 'values' => $values, 'errors' => $errors]);
            return;
        }
        try {
            $ma = HangHoa::create($data);
        } catch (\RuntimeException $ex) {
            $this->renderIndex(['mode' => 'create', 'item' => null, 'values' => $values, 'errors' => ['_form' => $ex->getMessage()]]);
            return;
        } catch (\Throwable $ex) {
            $this->renderIndex(['mode' => 'create', 'item' => null, 'values' => $values, 'errors' => ['_form' => 'Không thể lưu thông tin hàng hóa']]);
            return;
        }
        $this->flash('success', 'Thêm thông tin hàng hóa thành công. Mã hàng hóa: ' . $ma);
        $this->redirect('hang-hoa');
    }

    /** POST hang-hoa/update · quyền hang-hoa/edit (UC02) */
    public function update(): void
    {
        $ma = (string)Request::post('maHangHoa');
        $item = HangHoa::find($ma);
        if (!$item) {
            $this->flash('error', 'Không tìm thấy hàng hóa');
            $this->redirect('hang-hoa');
        }
        $values = $this->postedValues();
        [$data, $errors] = $this->validate($values, $item);
        if ($errors) {
            $this->renderIndex(['mode' => 'edit', 'item' => $item, 'values' => $values, 'errors' => $errors]);
            return;
        }
        try {
            HangHoa::update($ma, $data);
        } catch (\Throwable $ex) {
            $this->renderIndex(['mode' => 'edit', 'item' => $item, 'values' => $values, 'errors' => ['_form' => 'Cập nhật thông tin hàng hóa thất bại']]);
            return;
        }
        $this->flash('success', 'Cập nhật thông tin hàng hóa thành công');
        $this->redirect('hang-hoa?mode=view&id=' . rawurlencode($ma));   // hiển thị lại thông tin sau khi cập nhật
    }

    /** POST hang-hoa/stop · quyền hang-hoa/stop (UC03) */
    public function stop(): void
    {
        $ma = (string)Request::post('maHangHoa');
        $item = HangHoa::find($ma);
        if (!$item) {
            $this->flash('error', 'Không tìm thấy hàng hóa');
            $this->redirect('hang-hoa');
        }
        if ($item['trangThai'] === HangHoa::TT_NGUNG) {
            $this->flash('warning', 'Hàng hóa này đã ngừng sử dụng.');
            $this->redirect('hang-hoa');
        }
        $values = ['ngayNgung' => (string)Request::post('ngayNgung'), 'lyDo' => (string)Request::post('lyDo')];
        $errors = [];
        $when = \DateTime::createFromFormat('Y-m-d\TH:i', $values['ngayNgung']) ?: \DateTime::createFromFormat('Y-m-d\TH:i:s', $values['ngayNgung']);
        if (!$when) {
            $errors['ngayNgung'] = 'Vui lòng chọn ngày/giờ ngừng sử dụng hợp lệ.';
        }
        if ($values['lyDo'] === '') {
            $errors['lyDo'] = 'Vui lòng nhập lý do ngừng sử dụng.';
        } elseif (mb_strlen($values['lyDo']) > 150) {
            $errors['lyDo'] = 'Lý do tối đa 150 ký tự.';
        }
        $blockers = [];
        if (!$errors) {
            try {
                $blockers = HangHoa::stopBlockers($ma);
            } catch (\Throwable $ex) {
                $errors['_form'] = 'Không thể kiểm tra các nghiệp vụ liên quan. Vui lòng thử lại.';
            }
            if ($blockers) {
                $errors['_form'] = 'Không thể ngừng sử dụng: hàng hóa đang liên quan tới ' . implode('; ', $blockers) . '. Hãy hoàn tất các nghiệp vụ này trước.';
            }
        }
        if (!$errors) {
            try {
                HangHoa::stop($ma, $when->format('Y-m-d H:i:s'), $values['lyDo']);
            } catch (\Throwable $ex) {
                $errors['_form'] = 'Ngừng sử dụng hàng hóa thất bại';
            }
        }
        if ($errors) {
            $this->renderIndex(['mode' => 'stop', 'item' => $item, 'values' => $values, 'errors' => $errors]);
            return;
        }
        $this->flash('success', 'Ngừng sử dụng hàng hóa thành công');
        $this->redirect('hang-hoa');
    }

    /* ------------------------------ Hiển thị ------------------------------ */

    private function renderIndex(?array $modal): void
    {
        $filters = $this->filters();
        $page = max(1, (int)Request::get('page', 1));
        $perPage = (int)config('per_page', 10);
        $rows = [];
        $total = 0;
        $error = null;
        try {
            $total = HangHoa::count($filters);
            $pages = max(1, (int)ceil($total / $perPage));
            $page = min($page, $pages);
            $rows = HangHoa::search($filters, $perPage, ($page - 1) * $perPage);
        } catch (\Throwable $ex) {
            $error = 'Không thể thực hiện tra cứu';
        }
        $hasFilter = (bool)array_filter($filters, fn($v) => $v !== '');

        $breadcrumb = [['Quản lý hàng hóa', $modal ? 'hang-hoa' : null]];
        if ($modal) {
            $breadcrumb[] = [self::MODES[$modal['mode']][1], null];
        }
        $actions = can('hang-hoa', 'create')
            ? '<a class="btn btn-primary" href="' . e(route('hang-hoa', ['mode' => 'create'] + $this->listQuery())) . '">' . icon('plus', 16) . ' Thêm hàng hóa</a>'
            : '';

        $this->render('hang-hoa/index', [
            'filters'    => $filters,
            'rows'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'error'      => $error,
            'hasFilter'  => $hasFilter,
            'modal'      => $modal,
            'closeUrl'   => route('hang-hoa', $this->listQuery()),
            'listQuery'  => $this->listQuery(),
            'loaiOptions' => array_combine(array_keys(HangHoa::LOAI), array_keys(HangHoa::LOAI)),
            'donViOptions' => HangHoa::DON_VI,
            'statusOptions' => HangHoa::statusOptions(),
        ], [
            'title'      => 'Quản lý hàng hóa',
            'subtitle'   => 'Tra cứu, thêm mới, chỉnh sửa và ngừng sử dụng hàng hóa',
            'module'     => 'hang-hoa',
            'breadcrumb' => $breadcrumb,
            'actions'    => $actions,
        ]);
    }

    private function filters(): array
    {
        return [
            'q'         => (string)Request::get('q'),
            'loai'      => (string)Request::get('loai'),
            'trangThai' => (string)Request::get('trangThai'),
        ];
    }

    /** Tham số lọc/phân trang đang dùng (chỉ giữ giá trị không rỗng) để quay lại đúng danh sách. */
    private function listQuery(): array
    {
        $q = array_filter($this->filters(), fn($v) => $v !== '');
        $page = (int)Request::get('page', 1);
        if ($page > 1) {
            $q['page'] = $page;
        }
        return $q;
    }

    private function listUrl(): string
    {
        $q = $this->listQuery();
        return 'hang-hoa' . ($q ? '?' . http_build_query($q) : '');
    }

    /* ------------------------------ Dữ liệu form ------------------------------ */

    private function defaults(): array
    {
        return ['tenHangHoa' => '', 'loaiHangHoa' => '', 'donViTinhChuan' => 'kg', 'heSoQuyDoi' => '1', 'hanSuDungTieuChuanNgay' => '',
                'dieuKienBaoQuan' => '', 'moTa' => '', 'trangThai' => HangHoa::TT_DANG_DUNG];
    }

    private function valuesFrom(array $item): array
    {
        return [
            'tenHangHoa'             => (string)$item['tenHangHoa'],
            'loaiHangHoa'            => (string)$item['loaiHangHoa'],
            'donViTinhChuan'         => (string)$item['donViTinhChuan'],
            'heSoQuyDoi'             => rtrim(rtrim(number_format((float)$item['heSoQuyDoi'], 3, '.', ''), '0'), '.'),
            'hanSuDungTieuChuanNgay' => $item['hanSuDungTieuChuanNgay'] === null ? '' : (string)$item['hanSuDungTieuChuanNgay'],
            'dieuKienBaoQuan'        => (string)($item['dieuKienBaoQuan'] ?? ''),
            'moTa'                   => HangHoa::stripStopNote($item['moTa'] ?? ''),
            'trangThai'              => (string)$item['trangThai'],
        ];
    }

    private function postedValues(): array
    {
        $v = $this->defaults();
        foreach (array_keys($v) as $k) {
            $v[$k] = (string)Request::post($k, $v[$k]);
        }
        return $v;
    }

    /**
     * Kiểm tra dữ liệu form. $current = hàng hóa đang sửa (null khi thêm mới).
     * @return array [dữ liệu sạch để lưu, lỗi theo trường]
     */
    private function validate(array $v, ?array $current): array
    {
        $e = [];
        $ten = preg_replace('/\s+/u', ' ', $v['tenHangHoa']);
        if ($ten === '') {
            $e['tenHangHoa'] = 'Vui lòng nhập tên hàng.';
        } elseif (mb_strlen($ten) > 120) {
            $e['tenHangHoa'] = 'Tên hàng tối đa 120 ký tự.';
        }
        if (!isset(HangHoa::LOAI[$v['loaiHangHoa']])) {
            $e['loaiHangHoa'] = 'Vui lòng chọn nhóm hàng.';
        }
        if (!in_array($v['donViTinhChuan'], HangHoa::DON_VI, true)) {
            $e['donViTinhChuan'] = 'Vui lòng chọn đơn vị tính (kg, bao hoặc thùng).';
        }
        $heSo = str_replace(',', '.', $v['heSoQuyDoi']);
        if ($heSo === '' || !is_numeric($heSo) || (float)$heSo <= 0 || (float)$heSo > 100000) {
            $e['heSoQuyDoi'] = 'Quy cách đóng gói (số kg trên một đơn vị) phải là số lớn hơn 0.';
        }
        $han = null;
        if ($v['hanSuDungTieuChuanNgay'] !== '') {
            if (!ctype_digit($v['hanSuDungTieuChuanNgay']) || (int)$v['hanSuDungTieuChuanNgay'] > 65535) {
                $e['hanSuDungTieuChuanNgay'] = 'Hạn sử dụng tiêu chuẩn phải là số ngày nguyên từ 0 đến 65535.';
            } else {
                $han = (int)$v['hanSuDungTieuChuanNgay'];
            }
        }
        if (mb_strlen($v['dieuKienBaoQuan']) > 150) {
            $e['dieuKienBaoQuan'] = 'Điều kiện bảo quản tối đa 150 ký tự.';
        }
        if (!$current && $v['moTa'] === '') {
            $e['moTa'] = 'Vui lòng nhập mô tả.';   // UC01 luồng 5.1: mô tả nằm trong các trường phải bổ sung
        }
        $currentNote = $current ? HangHoa::stopNote($current['moTa'] ?? '') : null;
        $keepNote = $current && $v['trangThai'] === HangHoa::TT_NGUNG && $currentNote !== null;
        $suffix = $keepNote ? HangHoa::stopSuffix($current['moTa']) : '';
        $moTaMax = HangHoa::MO_TA_MAX - mb_strlen($suffix);
        if (mb_strlen($v['moTa']) > $moTaMax) {
            $e['moTa'] = 'Mô tả tối đa ' . $moTaMax . ' ký tự.';
        }

        // Quy tắc theo ngữ cảnh
        if ($current) {
            if ($v['trangThai'] !== HangHoa::TT_DANG_DUNG && $v['trangThai'] !== HangHoa::TT_NGUNG) {
                $e['trangThai'] = 'Trạng thái sử dụng không hợp lệ.';
            } elseif ($current['trangThai'] === HangHoa::TT_DANG_DUNG && $v['trangThai'] === HangHoa::TT_NGUNG) {
                $e['trangThai'] = 'Để ngừng sử dụng hãy dùng chức năng "Ngừng sử dụng" (cần nhập lý do và kiểm tra nghiệp vụ liên quan).';
            }
        }
        if ($e) {
            return [[], $e];
        }

        $trangThai = $current ? $v['trangThai'] : HangHoa::TT_DANG_DUNG;
        $moTa = $v['moTa'];
        if ($keepNote) {
            $moTa = rtrim($moTa) . $suffix;
        }
        return [[
            'tenHangHoa'             => $ten,
            'loaiHangHoa'            => $v['loaiHangHoa'],
            'donViTinhChuan'         => $v['donViTinhChuan'],
            'heSoQuyDoi'             => (float)$heSo,
            'hanSuDungTieuChuanNgay' => $han,
            'dieuKienBaoQuan'        => $v['dieuKienBaoQuan'] !== '' ? $v['dieuKienBaoQuan'] : null,
            'moTa'                   => $moTa !== '' ? $moTa : null,
            'trangThai'              => $trangThai,
        ], []];
    }
}
