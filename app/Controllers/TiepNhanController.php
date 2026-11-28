<?php
namespace App\Controllers;

use App\Models\HangHoa;
use App\Models\PhamViKho;
use App\Models\ThongBao;
use App\Models\TiepNhan;
use Core\Auth;
use Core\Request;

/**
 * TiepNhanController — module "tiep-nhan" (UC06 Tiếp nhận hàng vào kho).
 *
 *   GET  tiep-nhan                       danh sách chứng từ chờ tiếp nhận + tìm theo số chứng từ
 *   GET  tiep-nhan?chung_tu=PG...        màn hình tiếp nhận một chứng từ (ngay trong trang chính)
 *   POST tiep-nhan/xac-nhan              kiểm tra dữ liệu → màn hình xác nhận
 *   POST tiep-nhan/luu                   ghi nhận tiếp nhận (phiếu tiếp nhận + lô hàng + cập nhật chứng từ)
 * Quyền: xem danh sách 'tiep-nhan/view'; tiếp nhận 'tiep-nhan/create'.
 */
class TiepNhanController extends Controller
{
    /** GET tiep-nhan · quyền tiep-nhan/view */
    public function index(): void
    {
        $so = (string)Request::get('chung_tu');
        if ($so === '') {
            $this->showList();
            return;
        }
        if (!can('tiep-nhan', 'create')) {
            (new ErrorController())->show(403);
            return;
        }
        $ct = $this->loadDocument($so);
        $this->showForm($ct, $this->defaults($ct), []);
    }

    /** POST tiep-nhan/xac-nhan · quyền tiep-nhan/create */
    public function confirm(): void
    {
        [$ct, $values, $chon, $errors] = $this->prepare();
        if ($errors) {
            $this->showForm($ct, $values, $errors);
            return;
        }
        $this->showConfirm($ct, $values, $chon, null);
    }

    /** POST tiep-nhan/luu · quyền tiep-nhan/create */
    public function store(): void
    {
        [$ct, $values, $chon, $errors] = $this->prepare();
        if ($errors) {
            $this->showForm($ct, $values, $errors);
            return;
        }
        try {
            $r = TiepNhan::receive($ct['soChungTu'], $values['vi_tri'], Auth::staffId(), $values['ghi_chu'], $chon, $this->kho());
        } catch (\RuntimeException $ex) {
            // Lỗi nghiệp vụ (chứng từ đã được xử lý, vị trí không còn hợp lệ...): quay lại màn hình nhập
            $this->showForm($ct, $values, ['_form' => $ex->getMessage()]);
            return;
        } catch (\Throwable $ex) {
            // Lỗi hệ thống: cho phép Thử lại hoặc Hủy (UC06 ngoại lệ 10.1)
            $this->showConfirm($ct, $values, $chon, 'Tiếp nhận hàng thất bại. Vui lòng thử lại.');
            return;
        }

        try {
            ThongBao::notifyRole(ROLE_INVENTORY, 'Có lô hàng cần kiểm tra chất lượng',
                'Phiếu tiếp nhận ' . $r['maPhieuTiepNhan'] . ' có ' . count($r['lo']) . ' lô hàng chờ kiểm tra.', 'chat-luong', 'warning', Auth::staffId());
        } catch (\Throwable $ex) {
            // Thông báo chỉ là phần phụ, không làm hỏng việc tiếp nhận đã ghi nhận
        }
        $this->flash('success', 'Tiếp nhận hàng thành công. Phiếu tiếp nhận ' . $r['maPhieuTiepNhan'] . ' đã tạo ' . count($r['lo']) . ' lô hàng (' . implode(', ', $r['lo']) . ') chờ kiểm tra chất lượng.');
        $this->redirect('tiep-nhan');
    }

    /* ------------------------------ Hiển thị ------------------------------ */

    private function showList(): void
    {
        $q = (string)Request::get('q');
        $page = max(1, (int)Request::get('page', 1));
        $perPage = (int)config('per_page', 10);
        $rows = [];
        $error = null;
        $invalid = null;
        if (!preg_match('/^[A-Za-z0-9\s._\-]{0,40}$/', $q)) {
            $invalid = 'Số chứng từ không hợp lệ. Vui lòng nhập lại.';
        } else {
            try {
                $rows = TiepNhan::pending($q);
            } catch (\Throwable $ex) {
                $error = 'Không thể tải danh sách chứng từ chờ tiếp nhận.';
            }
        }
        $total = count($rows);
        $page = min($page, max(1, (int)ceil($total / $perPage)));
        $this->render('tiep-nhan/index', [
            'step'    => 'list',
            'q'       => $q,
            'rows'    => array_slice($rows, ($page - 1) * $perPage, $perPage),
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'error'   => $error,
            'invalid' => $invalid,
            'canReceive' => can('tiep-nhan', 'create'),
        ], [
            'title'      => 'Tiếp nhận hàng vào kho',
            'subtitle'   => 'Chứng từ giao hàng đang chờ tiếp nhận (phiếu giao nhà cung cấp, yêu cầu nhập thành phẩm từ xưởng)' . $this->khoNote(),
            'module'     => 'tiep-nhan',
            'breadcrumb' => [['Tiếp nhận hàng vào kho', null]],
        ]);
    }

    private function showForm(array $ct, array $values, array $errors): void
    {
        $this->renderStep('form', $ct, $values, $errors, [], null);
    }

    private function showConfirm(array $ct, array $values, array $chon, ?string $failure): void
    {
        $this->renderStep('confirm', $ct, $values, [], $chon, $failure);
    }

    private function renderStep(string $step, array $ct, array $values, array $errors, array $chon, ?string $failure): void
    {
        $viTri = TiepNhan::viTriTam($this->kho());
        $this->render('tiep-nhan/index', [
            'step'      => $step,
            'ct'        => $ct,
            'values'    => $values,
            'errors'    => $errors,
            'chon'      => $chon,
            'failure'   => $failure,
            'viTriOptions' => $viTri,
            'viTriNhan' => $viTri[$values['vi_tri']] ?? $values['vi_tri'],
            'nguoiNhan' => (string)(Auth::user()['hoTen'] ?? ''),
            'loaiLabel' => $ct['loai'] === TiepNhan::NCC ? 'Phiếu giao hàng nhà cung cấp' : 'Phiếu yêu cầu nhập thành phẩm',
        ], [
            'title'      => 'Tiếp nhận hàng vào kho',
            'subtitle'   => 'Chứng từ ' . $ct['soChungTu'] . $this->khoNote(),
            'module'     => 'tiep-nhan',
            'breadcrumb' => [['Tiếp nhận hàng vào kho', 'tiep-nhan'], [$step === 'confirm' ? 'Xác nhận tiếp nhận' : 'Tiếp nhận chứng từ ' . $ct['soChungTu'], null]],
        ]);
    }

    /* ------------------------------ Dữ liệu ------------------------------ */

    /** Phạm vi kho của người dùng: null = không giới hạn; '' = chưa gán kho (không có vị trí nào); 'K...' = kho của mình. */
    private function kho(): ?string
    {
        return PhamViKho::maKho();
    }

    private function khoNote(): string
    {
        $ten = PhamViKho::tenKho();
        return $ten === null ? '' : ' — tiếp nhận vào ' . $ten;
    }

    /** Đọc chứng từ cần tiếp nhận; không hợp lệ thì báo lỗi và quay về danh sách. */
    private function loadDocument(string $so): array
    {
        $fail = 'Không thể tiếp nhận hàng do không có chứng từ giao hàng hợp lệ';
        try {
            $ct = TiepNhan::chungTu($so);
        } catch (\Throwable $ex) {
            $ct = null;
            $fail = 'Không thể tải chứng từ. Vui lòng thử lại.';
        }
        if (!$ct || !$ct['conHieuLuc'] || !$ct['dong']) {
            $this->flash('error', $fail);
            $this->redirect('tiep-nhan');
        }
        return $ct;
    }

    private function defaults(array $ct): array
    {
        $lines = [];
        foreach ($ct['dong'] as $id => $l) {
            $nsx = $l['nsxNCC'] ?: date('Y-m-d');
            $hsd = $l['hsdNCC'] ?: ($l['hanChuan'] !== null ? date('Y-m-d', strtotime($nsx . ' +' . (int)$l['hanChuan'] . ' days')) : '');
            $lines[$id] = ['chon' => '1', 'sl' => $this->num($l['conLai']), 'nsx' => $nsx, 'hsd' => (string)$hsd];
        }
        return ['vi_tri' => '', 'ghi_chu' => '', 'lines' => $lines];
    }

    private function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
    }

    /**
     * Đọc & kiểm tra dữ liệu gửi lên cho cả bước xác nhận và bước lưu.
     * @return array [chứng từ, giá trị form, dòng được chọn đã chuẩn hóa, lỗi]
     */
    private function prepare(): array
    {
        $ct = $this->loadDocument((string)Request::post('chung_tu'));
        $values = ['vi_tri' => (string)Request::post('vi_tri'), 'ghi_chu' => (string)Request::post('ghi_chu'), 'lines' => []];
        $errors = [];

        $allowed = TiepNhan::viTriTam($this->kho());
        if ($values['vi_tri'] === '') {
            $errors['vi_tri'] = 'Vui lòng chọn vị trí lưu trữ tạm.';
        } elseif (!isset($allowed[$values['vi_tri']])) {
            $errors['vi_tri'] = 'Vị trí lưu trữ tạm không hợp lệ hoặc không còn ở trạng thái sử dụng.';
        }
        if (mb_strlen($values['ghi_chu']) > 255) {
            $errors['ghi_chu'] = 'Ghi chú tối đa 255 ký tự.';
        }

        $post = isset($_POST['lines']) && is_array($_POST['lines']) ? $_POST['lines'] : [];
        $chon = [];
        foreach ($ct['dong'] as $id => $line) {
            $in = isset($post[$id]) && is_array($post[$id]) ? $post[$id] : [];
            $row = [
                'chon' => !empty($in['chon']) ? '1' : '',
                'sl'   => trim((string)($in['sl'] ?? '')),
                'nsx'  => trim((string)($in['nsx'] ?? '')),
                'hsd'  => trim((string)($in['hsd'] ?? '')),
            ];
            $values['lines'][$id] = $row;
            if ($row['chon'] === '') {
                continue;
            }
            $sl = str_replace(',', '.', $row['sl']);
            if ($line['trangThaiHang'] !== HangHoa::TT_DANG_DUNG) {
                $errors['line'][$id] = 'Hàng hóa đã ngừng sử dụng, không thể tiếp nhận.';
            } elseif ($sl === '' || !is_numeric($sl) || (float)$sl <= 0) {
                $errors['line'][$id] = 'Số lượng thực nhận phải là số lớn hơn 0.';
            } elseif ((float)$sl > $line['conLai'] + 0.0005) {
                $errors['line'][$id] = 'Số lượng thực nhận không được vượt quá ' . $this->num($line['conLai']) . ' ' . $line['donViTinh'] . ' còn phải nhận.';
            } elseif (($row['nsx'] !== '' && !$this->isDate($row['nsx'])) || ($row['hsd'] !== '' && !$this->isDate($row['hsd']))) {
                $errors['line'][$id] = 'Ngày sản xuất / hạn sử dụng không hợp lệ.';
            } else {
                try {
                    [$nsx, $hsd] = TiepNhan::resolveDates($line, $row['nsx'] ?: null, $row['hsd'] ?: null);
                    $chon[$id] = ['sl' => (float)$sl, 'nsx' => $nsx, 'hsd' => $hsd];
                } catch (\RuntimeException $ex) {
                    $errors['line'][$id] = $ex->getMessage();
                }
            }
        }
        if (!$chon && empty($errors['line'])) {
            $errors['_form'] = 'Vui lòng chọn ít nhất một dòng hàng để tiếp nhận.';
        }
        return [$ct, $values, $chon, $errors];
    }

    private function isDate(string $s): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $s);
        return $d && $d->format('Y-m-d') === $s;
    }
}
