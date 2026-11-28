<?php
namespace App\Controllers;

use App\Models\LoHang;
use App\Models\PhamViKho;
use Core\Request;

/**
 * LoHangController — module "lo-hang" (UC05 Tra cứu thông tin lô hàng).
 *   GET lo-hang                    danh sách + tìm kiếm/lọc
 *   GET lo-hang?mode=view&id=MA    cửa sổ chi tiết lô hàng (ngay trong trang chính)
 */
class LoHangController extends Controller
{
    /** GET lo-hang · quyền lo-hang/view */
    public function index(): void
    {
        $kho = PhamViKho::maKho();               // null = mọi kho; còn lại chỉ dữ liệu của kho này
        $filters = [
            'q'         => (string)Request::get('q'),
            'kho'       => $kho === null ? (string)Request::get('kho') : '',   // bị giới hạn: kho cố định theo phân quyền
            'khu'       => (string)Request::get('khu'),
            'viTri'     => (string)Request::get('viTri'),
            'trangThai' => (string)Request::get('trangThai'),
        ];
        $page = max(1, (int)Request::get('page', 1));
        $perPage = (int)config('per_page', 10);
        // Phạm vi theo phân quyền: kho của người dùng + nhóm hàng đúng nghiệp vụ kho đó
        $scoped = ['q' => $filters['q'], 'locKho' => $filters['kho'], 'khu' => $filters['khu'], 'viTri' => $filters['viTri'],
                   'trangThai' => $filters['trangThai'], 'kho' => $kho, 'nhom' => PhamViKho::nhomHang()];

        $rows = [];
        $total = 0;
        $error = null;
        $viTriOptions = ['kho' => [], 'khu' => [], 'khuGroup' => [], 'viTri' => [], 'viTriGroup' => []];
        $detail = null;

        try {
            $viTriOptions = LoHang::viTriOptions($kho);
        } catch (\Throwable $ex) {
            $error = 'Không thể thực hiện tra cứu';
        }

        // Tiêu chí tìm kiếm không hợp lệ (UC05 ngoại lệ 5.2)
        $invalid = null;
        if (!preg_match('/^[\p{L}\p{N}\s._\-]{0,60}$/u', $filters['q'])) {
            $invalid = 'Tiêu chí tìm kiếm không hợp lệ. Mã lô hoặc tên hàng chỉ gồm chữ, số, dấu cách, gạch ngang, gạch dưới, dấu chấm (tối đa 60 ký tự).';
        } elseif ($filters['trangThai'] !== '' && !in_array($filters['trangThai'], LoHang::TRANG_THAI, true)) {
            $invalid = 'Trạng thái lô hàng không hợp lệ. Vui lòng chọn lại.';
        } elseif (!$error && (($filters['kho'] !== '' && !isset($viTriOptions['kho'][$filters['kho']]))
                || ($filters['khu'] !== '' && !isset($viTriOptions['khu'][$filters['khu']]))
                || ($filters['viTri'] !== '' && !isset($viTriOptions['viTri'][$filters['viTri']])))) {
            $invalid = 'Vị trí lưu trữ không hợp lệ. Vui lòng chọn lại.';
        }

        if (!$error && !$invalid) {
            try {
                $total = LoHang::count($scoped);
                $page = min($page, max(1, (int)ceil($total / $perPage)));
                $rows = LoHang::search($scoped, $perPage, ($page - 1) * $perPage);
            } catch (\Throwable $ex) {
                $error = 'Không thể thực hiện tra cứu';
            }
        }

        $mode = (string)Request::get('mode');
        if ($mode === 'view') {
            try {
                $detail = LoHang::detail((string)Request::get('id'), $kho);
                $nhom = PhamViKho::nhomHang();
                if ($detail && $nhom !== null && !in_array($detail['loaiHangHoa'], $nhom, true)) {
                    $detail = null;   // hàng hóa không thuộc nghiệp vụ kho của người dùng
                }
            } catch (\Throwable $ex) {
                $detail = null;
                $error = $error ?: 'Không thể thực hiện tra cứu';
            }
            if (!$detail && !$error) {
                $this->flash('error', 'Không tìm thấy lô hàng');
                $this->redirect($this->listUrl($filters));
            }
        }

        $query = array_filter($filters, fn($v) => $v !== '');
        if ($page > 1) {
            $query['page'] = $page;
        }

        $this->render('lo-hang/index', [
            'filters'      => $filters,
            'rows'         => $rows,
            'total'        => $total,
            'page'         => $page,
            'perPage'      => $perPage,
            'error'        => $error,
            'invalid'      => $invalid,
            'hasFilter'    => (bool)array_filter($filters, fn($v) => $v !== ''),
            'viTriOptions' => $viTriOptions,
            'tenKhoCoDinh' => PhamViKho::tenKho(),
            'trangThaiOptions' => array_combine(LoHang::TRANG_THAI, LoHang::TRANG_THAI),
            'detail'       => $detail,
            'closeUrl'     => route('lo-hang', $query),
            'listQuery'    => $query,
        ], [
            'title'      => 'Tra cứu thông tin lô hàng',
            'subtitle'   => 'Tìm lô hàng theo mã lô, tên hàng, vị trí lưu trữ và trạng thái' . (PhamViKho::tenKho() !== null ? ' — dữ liệu của ' . PhamViKho::tenKho() : ''),
            'module'     => 'lo-hang',
            'breadcrumb' => $detail ? [['Lô hàng', 'lo-hang'], ['Chi tiết lô hàng', null]] : [['Lô hàng', null]],
        ]);
    }

    private function listUrl(array $filters): string
    {
        $q = array_filter($filters, fn($v) => $v !== '');
        return 'lo-hang' . ($q ? '?' . http_build_query($q) : '');
    }
}
