<?php
/**
 * app/Helpers/inventory-mapping.php
 *
 * MAPPING TẬP TRUNG CHO PHÂN HỆ KIỂM KÊ (UC24 - UC29)
 *
 * Nguồn dữ liệu chuẩn: CSDL gốc feed_wms.
 * Tất cả các giá trị ENUM / CHECK trong CSDL và nhãn hiển thị trên giao diện tiếng Việt
 * được định nghĩa duy nhất tại file này để tránh phân tán mã tự viết.
 */

/* ========================================================================== */
/* 1. MAPPING LOẠI KIỂM KÊ (BienBanKiemKe.loaiKiemKe)                          */
/* DB Constraint ck_BienBanKiemKe_2: ('DinhKy', 'DotXuat', 'TheoMatHang')     */
/* ========================================================================== */

const INVENTORY_TYPE_MAP = [
    'DinhKy'      => 'Định kỳ',
    'DotXuat'     => 'Đột xuất',
    'TheoMatHang' => 'Theo mặt hàng',
];

const INVENTORY_TYPE_REVERSE_MAP = [
    'Định kỳ'        => 'DinhKy',
    'Đột xuất'       => 'DotXuat',
    'Theo mặt hàng'  => 'TheoMatHang',
    'DinhKy'         => 'DinhKy',
    'DotXuat'        => 'DotXuat',
    'TheoMatHang'    => 'TheoMatHang',
];

/* ========================================================================== */
/* 2. MAPPING TRẠNG THÁI BIÊN BẢN (BienBanKiemKe.trangThai)                   */
/* DB Constraint ck_BienBanKiemKe_3: ('Chờ xử lý', 'Đang xử lý', 'Hoàn tất',   */
/*                                    'Từ chối', 'Đã hủy')                    */
/* ========================================================================== */

const INVENTORY_STATUS_MAP = [
    'Chờ xử lý'  => 'Chờ kiểm kê',
    'Đang xử lý' => 'Chờ xác nhận',
    'Hoàn tất'   => 'Đã hoàn thành',
    'Từ chối'    => 'Từ chối',
    'Đã hủy'     => 'Đã hủy',
];

const INVENTORY_STATUS_REVERSE_MAP = [
    'Chờ kiểm kê'           => 'Chờ xử lý',
    'Chờ xác nhận'          => 'Đang xử lý',
    'Chờ quản lý xác nhận'  => 'Đang xử lý',
    'Đang kiểm kê'          => 'Đang xử lý',
    'Đã hoàn thành'         => 'Hoàn tất',
    'Bị trả lại'            => 'Từ chối',
    'Từ chối'               => 'Từ chối',
    'Đã hủy'                => 'Đã hủy',
    'Chờ xử lý'             => 'Chờ xử lý',
    'Đang xử lý'            => 'Đang xử lý',
    'Hoàn tất'              => 'Hoàn tất',
];

/* ========================================================================== */
/* 3. MAPPING NGUYÊN NHÂN CHÊNH LỆCH (ChenhLechKiemKe.nguyenNhan)             */
/* DB Constraint ck_ChenhLechKiemKe_4: ('HaoHut', 'SaiSot', 'ThatThoat',       */
/*                                      'ChuaCapNhat')                         */
/* ========================================================================== */

const DISCREPANCY_REASON_MAP = [
    'HaoHut'      => 'Hao hụt tự nhiên',
    'SaiSot'      => 'Sai sót ghi chép',
    'ThatThoat'   => 'Thất thoát',
    'ChuaCapNhat' => 'Chưa cập nhật chứng từ',
];

const DISCREPANCY_REASON_REVERSE_MAP = [
    'Hao hụt tự nhiên'        => 'HaoHut',
    'Sai sót ghi chép'        => 'SaiSot',
    'Thất thoát'              => 'ThatThoat',
    'Chưa cập nhật chứng từ'  => 'ChuaCapNhat',
    'HaoHut'                  => 'HaoHut',
    'SaiSot'                  => 'SaiSot',
    'ThatThoat'               => 'ThatThoat',
    'ChuaCapNhat'             => 'ChuaCapNhat',
];

/* ========================================================================== */
/* 4. MAPPING BIỆN PHÁP XỬ LÝ (PhuongAnXuLy.bienPhapXuLy)                     */
/* DB Constraint ck_PhuongAnXuLy_2:                                           */
/* ('DieuChinhSoSachTang', 'DieuChinhSoSachGiam', 'GhiNhanHaoHut',            */
/*  'XuatTraNhaCungCap', 'TieuHuy', 'HaCapChatLuong')                         */
/* ========================================================================== */

const RESOLUTION_METHOD_MAP = [
    'DieuChinhSoSachTang'  => 'Điều chỉnh sổ sách tăng',
    'DieuChinhSoSachGiam'  => 'Điều chỉnh sổ sách giảm',
    'GhiNhanHaoHut'        => 'Ghi nhận hao hụt',
    'XuatTraNhaCungCap'    => 'Xuất trả nhà cung cấp',
    'TieuHuy'              => 'Tiêu hủy',
    'HaCapChatLuong'       => 'Hạ cấp chất lượng',
];

const RESOLUTION_METHOD_REVERSE_MAP = [
    'Điều chỉnh sổ sách tăng'  => 'DieuChinhSoSachTang',
    'Điều chỉnh sổ sách giảm'  => 'DieuChinhSoSachGiam',
    'Ghi nhận hao hụt'         => 'GhiNhanHaoHut',
    'Xuất trả nhà cung cấp'    => 'XuatTraNhaCungCap',
    'Tiêu hủy'                 => 'TieuHuy',
    'Hạ cấp chất lượng'        => 'HaCapChatLuong',
    'DieuChinhSoSachTang'      => 'DieuChinhSoSachTang',
    'DieuChinhSoSachGiam'      => 'DieuChinhSoSachGiam',
    'GhiNhanHaoHut'            => 'GhiNhanHaoHut',
    'XuatTraNhaCungCap'        => 'XuatTraNhaCungCap',
    'TieuHuy'                  => 'TieuHuy',
    'HaCapChatLuong'           => 'HaCapChatLuong',
];

/* ========================================================================== */
/* 5. MAPPING LOẠI ĐIỀU CHỈNH TỒN KHO (PhieuDieuChinhTonKho.loaiDieuChinh)    */
/* DB Constraint ck_PhieuDieuChinhTon_2: ('Tang', 'Giam')                     */
/* ========================================================================== */

const ADJUSTMENT_TYPE_MAP = [
    'Tang' => 'Điều chỉnh tăng',
    'Giam' => 'Điều chỉnh giảm',
];

const ADJUSTMENT_TYPE_REVERSE_MAP = [
    'Điều chỉnh tăng' => 'Tang',
    'Điều chỉnh giảm' => 'Giam',
    'Tăng'            => 'Tang',
    'Giảm'            => 'Giam',
    'Tang'            => 'Tang',
    'Giam'            => 'Giam',
];

/* ========================================================================== */
/* 5b. MAPPING TRẠNG THÁI PHƯƠNG ÁN XỬ LÝ (PhuongAnXuLy.trangThai)            */
/* DB Constraint ck_PhuongAnXuLy_3:                                           */
/* ('Chờ xử lý', 'Đang xử lý', 'Hoàn tất', 'Từ chối', 'Đã hủy')              */
/* ========================================================================== */

const PROPOSAL_STATUS_MAP = [
    'Chờ xử lý'  => 'Chờ duyệt',
    'Đang xử lý' => 'Chờ thi hành',
    'Hoàn tất'   => 'Đã thi hành',
    'Từ chối'    => 'Từ chối',
    'Đã hủy'     => 'Đã hủy',
];

const PROPOSAL_STATUS_REVERSE_MAP = [
    'Chờ duyệt'     => 'Chờ xử lý',
    'Chờ phê duyệt' => 'Chờ xử lý',
    'Chờ xử lý'     => 'Chờ xử lý',
    'Chờ thi hành'  => 'Đang xử lý',
    'Đã phê duyệt'  => 'Đang xử lý',
    'Đang xử lý'    => 'Đang xử lý',
    'Đã thi hành'   => 'Hoàn tất',
    'Hoàn tất'      => 'Hoàn tất',
    'Đã từ chối'    => 'Từ chối',
    'Từ chối'       => 'Từ chối',
    'Đã hủy'        => 'Đã hủy',
];

/* ========================================================================== */
/* 6. HÀM CHUYỂN ĐỔI VÀ HIỂN THỊ DÙNG CHUNG                                  */
/* ========================================================================== */

/** Lấy nhãn tiếng Việt của loại kiểm kê từ mã DB */
function inventory_type_label(?string $code): string
{
    if ($code === null || $code === '') return '—';
    return INVENTORY_TYPE_MAP[$code] ?? $code;
}

/** Lấy mã DB của loại kiểm kê từ nhãn tiếng Việt hoặc mã */
function inventory_type_code(?string $val): string
{
    if ($val === null || $val === '') return 'DinhKy';
    return INVENTORY_TYPE_REVERSE_MAP[$val] ?? 'DinhKy';
}

/** Danh sách lựa chọn loại kiểm kê cho <select> [db_code => vi_label] */
function inventory_type_options(): array
{
    return INVENTORY_TYPE_MAP;
}

/** Lấy nhãn tiếng Việt của trạng thái biên bản kiểm kê từ mã DB */
function inventory_status_label(?string $code): string
{
    if ($code === null || $code === '') return '—';
    return INVENTORY_STATUS_MAP[$code] ?? $code;
}

/** Lấy mã DB của trạng thái biên bản kiểm kê từ nhãn tiếng Việt hoặc mã */
function inventory_status_code(?string $val): string
{
    if ($val === null || $val === '') return 'Chờ xử lý';
    return INVENTORY_STATUS_REVERSE_MAP[$val] ?? 'Chờ xử lý';
}

/** Danh sách lựa chọn trạng thái biên bản cho <select> [db_code => vi_label] */
function inventory_status_options(): array
{
    return INVENTORY_STATUS_MAP;
}

/** Render badge hiển thị trạng thái biên bản kiểm kê */
function inventory_status_badge(?string $val): string
{
    $code = inventory_status_code($val);
    $label = inventory_status_label($code);

    $classes = [
        'Chờ xử lý'  => 'badge badge-info',
        'Đang xử lý' => 'badge badge-warn',
        'Hoàn tất'   => 'badge badge-ok',
        'Từ chối'    => 'badge badge-danger',
        'Đã hủy'     => 'badge',
    ];

    $cls = $classes[$code] ?? 'badge';
    return '<span class="' . e($cls) . '">' . e($label) . '</span>';
}

/** Render badge hiển thị loại kiểm kê */
function inventory_type_badge(?string $val): string
{
    $code = inventory_type_code($val);
    $label = inventory_type_label($code);

    $classes = [
        'DinhKy'      => 'badge',
        'DotXuat'     => 'badge badge-warn',
        'TheoMatHang' => 'badge badge-info',
    ];

    $cls = $classes[$code] ?? 'badge';
    return '<span class="' . e($cls) . '">' . e($label) . '</span>';
}

/** Render số chênh lệch kiểm kê dạng chữ kèm màu */
function inventory_diff_badge(float $diff): string
{
    if (abs($diff) < 0.000001) {
        return '<span style="color: #16a34a; font-weight: 500;">0</span>';
    }
    if ($diff > 0) {
        return '<span style="color: #0284c7; font-weight: 500;">+' . fmt_qty($diff) . '</span>';
    }
    return '<span style="color: #dc2626; font-weight: 500;">-' . fmt_qty(abs($diff)) . '</span>';
}

/** Lấy nhãn tiếng Việt của biện pháp xử lý từ mã DB */
function resolution_method_label(?string $code): string
{
    if ($code === null || $code === '') return '—';
    return RESOLUTION_METHOD_MAP[$code] ?? $code;
}

/** Chuẩn hóa biện pháp xử lý về mã DB */
function resolution_method_code(?string $val): string
{
    if ($val === null) return '';
    $t = trim($val);
    return RESOLUTION_METHOD_REVERSE_MAP[$t] ?? $t;
}

/** Lấy nhãn tiếng Việt của trạng thái phương án xử lý từ mã DB */
function proposal_status_label(?string $code): string
{
    if ($code === null || $code === '') return '—';
    return PROPOSAL_STATUS_MAP[$code] ?? $code;
}

/** Chuẩn hóa trạng thái phương án xử lý về mã DB */
function proposal_status_code(?string $val): string
{
    if ($val === null) return '';
    $t = trim($val);
    return PROPOSAL_STATUS_REVERSE_MAP[$t] ?? $t;
}

/** Render badge trạng thái phương án xử lý */
function proposal_status_badge(?string $val): string
{
    $code = proposal_status_code($val);
    $label = proposal_status_label($code);
    $classes = [
        'Chờ xử lý'  => 'badge badge-warn',
        'Đang xử lý' => 'badge badge-info',
        'Hoàn tất'   => 'badge badge-ok',
        'Từ chối'    => 'badge badge-danger',
        'Đã hủy'     => 'badge',
    ];
    $cls = $classes[$code] ?? 'badge';
    return '<span class="' . e($cls) . '">' . e($label) . '</span>';
}

/** Lấy nhãn tiếng Việt của loại phiếu điều chỉnh tồn kho từ mã DB */
function adjustment_type_label(?string $code): string
{
    if ($code === null || $code === '') return '—';
    if ($code === 'Tang' || $code === 'Tăng') return 'Tăng';
    if ($code === 'Giam' || $code === 'Giảm') return 'Giảm';
    return $code;
}

/* ========================================================================== */
/* 7. MAPPING LOẠI HÀNG HÓA & LOẠI GIAO DỊCH TỒN KHO                         */
/* ========================================================================== */

const PRODUCT_TYPE_MAP = [
    'NguyenLieu'   => 'Nguyên liệu',
    'BanThanhPham' => 'Bán thành phẩm',
    'ThanhPham'    => 'Thành phẩm',
    'PhuGia'       => 'Phụ gia',
    'BaoBi'        => 'Bao bì',
];

function product_type_label(?string $code): string
{
    if ($code === null || $code === '') return '—';
    return PRODUCT_TYPE_MAP[$code] ?? $code;
}

const STOCK_TX_TYPE_MAP = [
    'NhapKho'       => 'Nhập kho',
    'XuatKho'       => 'Xuất kho',
    'DieuChuyenDen' => 'Điều chuyển đến',
    'DieuChuyenDi'  => 'Điều chuyển đi',
    'DieuChinhTang' => 'Điều chỉnh tăng',
    'DieuChinhGiam' => 'Điều chỉnh giảm',
    'HaoHut'        => 'Hao hụt',
];

function stock_tx_type_label(?string $code): string
{
    if ($code === null || $code === '') return '—';
    return STOCK_TX_TYPE_MAP[$code] ?? $code;
}

function stock_tx_badge(?string $code): string
{
    $label = stock_tx_type_label($code);
    if (function_exists('giao_dich_badge')) {
        return giao_dich_badge($label);
    }
    return '<span class="badge">' . e($label) . '</span>';
}
