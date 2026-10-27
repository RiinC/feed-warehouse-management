<?php
/**
 * Helper nghiệp vụ cho UC24 Kiểm kê kho.
 * Hỗ trợ phân loại riêng biệt: Nguyên liệu (NGUYEN_LIEU) và Thành phẩm (THANH_PHAM).
 * Phạm vi kho phân cấp: Kho -> Khu vực -> Vị trí lưu trữ.
 * Chụp số tồn mốc vào PhamViKiemKe, không dùng tag chuỗi trong ghi chú.
 * Tuân thủ schema QLK05, chuẩn chuỗi VARCHAR(15), prepared statements và transaction.
 */
require_once __DIR__ . '/../../includes/auth.php';

/**
 * Các truy vấn của UC24 luôn chạy trên CSDL nghiệp vụ QLK05.
 * Chỉ những truy vấn tài khoản/nhân viên mới truyền rõ DB_DUNG_CHUNG.
 */
function uc24_db_exec(string $sql, string $types = '', array $params = [], ?string $database = null): mysqli_stmt
{
    return db_exec($sql, $types, $params, $database ?? DB_UC24);
}

function uc24_db_all(string $sql, string $types = '', array $params = [], ?string $database = null): array
{
    return db_all($sql, $types, $params, $database ?? DB_UC24);
}

function uc24_db_one(string $sql, string $types = '', array $params = [], ?string $database = null): ?array
{
    return db_one($sql, $types, $params, $database ?? DB_UC24);
}

/**
 * Tạo mã nghiệp vụ dạng chuỗi VARCHAR(15) theo quy ước thống nhất.
 * Ví dụ: BB2610-0001, KQ2610-0001, CL2610-0001, HBT2610-0001.
 */
function uc24_next_code(string $prefix, string $table, string $column): string
{
    $ym = date('ym');
    $fullPrefix = $prefix . $ym . '-';

    $row = uc24_db_one(
        "SELECT `$column` FROM `$table` WHERE `$column` LIKE ? ORDER BY `$column` DESC LIMIT 1",
        's', [$fullPrefix . '%']
    );

    $nextNum = 1;
    if ($row && !empty($row[$column])) {
        $suffix = substr($row[$column], strlen($fullPrefix));
        if (is_numeric($suffix)) {
            $nextNum = (int)$suffix + 1;
        }
    }

    $code = $fullPrefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
    if (strlen($code) > 15) {
        $code = substr($code, 0, 15);
    }
    return $code;
}

/**
 * Nhãn hiển thị cho loại kiểm kê
 */
function uc24_type_label(string $type): string
{
    return ($type === 'THANH_PHAM' || $type === 'Thành phẩm') ? 'Thành phẩm' : 'Nguyên liệu';
}

function uc24_type_badge(string $type): string
{
    if ($type === 'THANH_PHAM' || $type === 'Thành phẩm') {
        return '<span class="badge-flat-prod">Thành phẩm</span>';
    }
    return '<span class="badge-flat-raw">Nguyên liệu</span>';
}

/**
 * Hiển thị trạng thái kiểm kê dạng chấm màu tối giản (Dot + Label)
 */
function uc24_status_dot(string $st): string
{
    $colors = [
        'Chờ kiểm kê'    => '#2563eb',
        'Đã đối chiếu'   => '#0d9488',
        'Đã hủy'         => '#dc2626',
    ];
    $c = $colors[$st] ?? '#64748b';
    return '<span style="display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 500; color: #1e293b; white-space: nowrap;">'
         . '<span style="width: 8px; height: 8px; border-radius: 50%; background: ' . $c . '; display: inline-block; flex-shrink: 0;"></span>'
         . htmlspecialchars($st, ENT_QUOTES, 'UTF-8')
         . '</span>';
}

/**
 * Lấy danh sách kho có hàng tồn thực tế thuộc loại nguyên liệu hoặc thành phẩm
 */
function uc24_get_warehouses_by_type(string $type): array
{
    $sql = "SELECT DISTINCT k.maKho, k.tenKho, k.loaiKho
            FROM Kho k
            JOIN TonKho tk ON tk.maKho = k.maKho
            JOIN LoHang lh ON lh.maLoHang = tk.maLoHang
            JOIN HangHoa hh ON hh.maHang = lh.maHang
            WHERE hh.phanLoaiHangHoa = ? AND k.trangThai = 'Hoạt động'
            ORDER BY k.tenKho";
    return uc24_db_all($sql, 's', [$type]);
}

/**
 * Lấy danh sách khu vực thuộc kho có hàng tồn thực tế thuộc loại tương ứng
 */
function uc24_get_areas_by_warehouse_and_type(string $maKho, string $type): array
{
    $sql = "SELECT DISTINCT kv.maKhuVuc, kv.tenKhuVuc, kv.loaiKhuVuc
            FROM KhuVuc kv
            JOIN ViTriLuuTru vt ON vt.maKhuVuc = kv.maKhuVuc
            JOIN TonKho tk ON tk.maViTri = vt.maViTri
            JOIN LoHang lh ON lh.maLoHang = tk.maLoHang
            JOIN HangHoa hh ON hh.maHang = lh.maHang
            WHERE kv.maKho = ? AND hh.phanLoaiHangHoa = ? AND kv.trangThai = 'Hoạt động'
            ORDER BY kv.tenKhuVuc";
    return uc24_db_all($sql, 'ss', [$maKho, $type]);
}

/**
 * Lấy danh sách vị trí lưu trữ thuộc khu vực có hàng tồn thực tế thuộc loại tương ứng
 */
function uc24_get_locations_by_area_and_type(string $maKhuVuc, string $type): array
{
    $sql = "SELECT DISTINCT vt.maViTri, vt.tenViTri, vt.sucChuaToiDa, vt.donViSucChua
            FROM ViTriLuuTru vt
            JOIN TonKho tk ON tk.maViTri = vt.maViTri
            JOIN LoHang lh ON lh.maLoHang = tk.maLoHang
            JOIN HangHoa hh ON hh.maHang = lh.maHang
            WHERE vt.maKhuVuc = ? AND hh.phanLoaiHangHoa = ? AND vt.trangThai = 'Hoạt động'
            ORDER BY vt.tenViTri";
    return uc24_db_all($sql, 'ss', [$maKhuVuc, $type]);
}

/**
 * Lấy danh sách tồn kho khớp chính xác phạm vi đã chọn (xem trước phạm vi kiểm kê)
 */
function uc24_get_scope_preview(string $type, string $maKho, ?string $maKhuVuc = null, ?string $maViTri = null): array
{
    if (empty($maKho)) {
        return [];
    }
    $where = ['hh.phanLoaiHangHoa = ?', 'tk.maKho = ?'];
    $types = 'ss';
    $params = [$type, $maKho];

    if (!empty($maKhuVuc)) {
        $where[] = 'vt.maKhuVuc = ?';
        $types .= 's';
        $params[] = $maKhuVuc;
    }

    if (!empty($maViTri)) {
        $where[] = 'tk.maViTri = ?';
        $types .= 's';
        $params[] = $maViTri;
    }

    $sql = "SELECT tk.maTonKho, tk.soLuongTon, tk.soLuongDaGiu, tk.trangThai AS trangThaiTonKho,
                   tk.maLoHang, tk.maKho, tk.maViTri,
                   lh.tenLoHang, lh.donViTinh, lh.hanSuDung, lh.ngaySanXuat, lh.trangThaiChatLuong,
                   hh.maHang, hh.tenHang, hh.nhomHang, hh.quyCachDongGoi, hh.phanLoaiHangHoa,
                   vt.tenViTri, vt.sucChuaToiDa, vt.donViSucChua,
                   kv.maKhuVuc, kv.tenKhuVuc,
                   k.tenKho
            FROM TonKho tk
            JOIN LoHang lh ON lh.maLoHang = tk.maLoHang
            JOIN HangHoa hh ON hh.maHang = lh.maHang
            JOIN ViTriLuuTru vt ON vt.maViTri = tk.maViTri
            JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc AND kv.maKho = tk.maKho
            JOIN Kho k ON k.maKho = tk.maKho
            WHERE " . implode(' AND ', $where) . "
            ORDER BY kv.tenKhuVuc, vt.tenViTri, hh.tenHang, lh.tenLoHang";

    return uc24_db_all($sql, $types, $params);
}

/**
 * Lấy danh sách các dòng tồn kho thuộc phạm vi của biên bản kiểm kê đã tạo.
 * Ưu tiên đọc từ bảng quan hệ PhamViKiemKe (lưu ảnh chụp số tồn mốc ban đầu).
 */
function uc24_get_bb_inventory(array $bb): array
{
    $bbCode = $bb['maBienBanKiemKe'];

    // 1. Đọc từ PhamViKiemKe
    $sqlPv = "SELECT pv.maTonKho, pv.soLuongTonChup AS soLuongTon, pv.maLoHang, pv.maViTri,
                     lh.tenLoHang, lh.donViTinh, lh.hanSuDung, lh.ngaySanXuat, lh.trangThaiChatLuong,
                     hh.maHang, hh.tenHang, hh.nhomHang, hh.quyCachDongGoi, hh.phanLoaiHangHoa,
                     vt.tenViTri, vt.sucChuaToiDa, vt.donViSucChua,
                     kv.maKhuVuc, kv.tenKhuVuc,
                     k.maKho, k.tenKho
              FROM PhamViKiemKe pv
              JOIN TonKho tk ON tk.maTonKho = pv.maTonKho
              JOIN LoHang lh ON lh.maLoHang = pv.maLoHang
              JOIN HangHoa hh ON hh.maHang = lh.maHang
              JOIN ViTriLuuTru vt ON vt.maViTri = pv.maViTri
              JOIN KhuVuc kv ON kv.maKhuVuc = vt.maKhuVuc AND kv.maKho = tk.maKho
              JOIN Kho k ON k.maKho = tk.maKho
              WHERE pv.maBienBanKiemKe = ?
              ORDER BY kv.tenKhuVuc, vt.tenViTri, hh.tenHang, lh.tenLoHang";
    $rows = uc24_db_all($sqlPv, 's', [$bbCode]);
    if (!empty($rows)) {
        return $rows;
    }

    // 2. Dự phòng (nếu là biên bản cũ chưa có bản ghi trong PhamViKiemKe)
    $type = $bb['loaiKiemKe'] ?? 'NGUYEN_LIEU';
    return uc24_get_scope_preview($type, $bb['maKho'], $bb['maKhuVuc'] ?? null, $bb['maViTri'] ?? null);
}

/**
 * Lấy hồ sơ nhân viên từ schema QLK00
 */
function uc24_get_employee(string $maNhanVien): ?array
{
    try {
        return uc24_db_one("SELECT * FROM NhanVien WHERE maNV = ?", 's', [$maNhanVien], DB_DUNG_CHUNG);
    } catch (Throwable $e) {
        return null;
    }
}

function uc24_get_employee_name(string $maNhanVien): string
{
    static $cache = [];
    if (isset($cache[$maNhanVien])) {
        return $cache[$maNhanVien];
    }
    $emp = uc24_get_employee($maNhanVien);
    if ($emp && !empty($emp['hoTen'])) {
        $cache[$maNhanVien] = $emp['hoTen'];
        return $emp['hoTen'];
    }
    $cache[$maNhanVien] = $maNhanVien;
    return $maNhanVien;
}

/**
 * Đồng bộ tiến độ kiểm kê cho biên bản.
 * Không tự tạo/chuyển sang trạng thái không có trong database hoặc tài liệu đã duyệt.
 */
function uc24_sync_progress(string $maBienBan): array
{
    $bb = uc24_db_one("SELECT * FROM BienBanKiemKe WHERE maBienBanKiemKe = ?", 's', [$maBienBan]);
    if (!$bb) {
        return ['total' => 0, 'counted' => 0, 'progress' => 0, 'status' => ''];
    }

    $inScope = uc24_get_bb_inventory($bb);
    $total = count($inScope);

    $countedRow = uc24_db_one(
        "SELECT COUNT(DISTINCT maTonKho) AS c FROM KetQuaKiemKe WHERE maBienBanKiemKe = ?",
        's', [$maBienBan]
    );
    $counted = (int)($countedRow['c'] ?? 0);

    $progress = ($total > 0) ? min(100.0, round(($counted / $total) * 100, 1)) : 0.0;

    uc24_db_exec(
        "UPDATE BienBanKiemKe SET tienDoKiemKe = ? WHERE maBienBanKiemKe = ?",
        'ds', [$progress, $maBienBan]
    );

    return ['total' => $total, 'counted' => $counted, 'progress' => $progress, 'status' => $bb['trangThai']];
}

/**
 * Lấy toàn bộ dữ liệu chi tiết (kèm kết quả kiểm đếm, chênh lệch, hàng bất thường)
 * của một biên bản để phục vụ hiển thị bảng Detail kiểu Master-Detail.
 */
function uc24_get_bb_detail_payload(string $maBienBan): ?array
{
    $bb = uc24_db_one(
        "SELECT b.*, k.tenKho, kv.tenKhuVuc
         FROM BienBanKiemKe b
         JOIN Kho k ON k.maKho = b.maKho
         LEFT JOIN KhuVuc kv ON kv.maKhuVuc = b.maKhuVuc
         WHERE b.maBienBanKiemKe = ?",
        's', [$maBienBan]
    );
    if (!$bb) {
        return null;
    }

    $items = uc24_get_bb_inventory($bb);
    $results = uc24_db_all("SELECT * FROM KetQuaKiemKe WHERE maBienBanKiemKe = ?", 's', [$maBienBan]);
    $resMap = [];
    foreach ($results as $r) {
        $resMap[$r['maTonKho']] = $r;
    }

    $clList = uc24_db_all(
        "SELECT cl.*, kq.maTonKho 
         FROM ChenhLechKiemKe cl 
         JOIN KetQuaKiemKe kq ON kq.maKetQuaKiemKe = cl.maKetQuaKiemKe 
         WHERE kq.maBienBanKiemKe = ?",
        's', [$maBienBan]
    );
    $clMap = [];
    foreach ($clList as $c) {
        $clMap[$c['maTonKho']] = $c;
    }

    $hbtList = uc24_db_all(
        "SELECT hbt.*, kq.maTonKho 
         FROM HangBatThuong hbt 
         JOIN KetQuaKiemKe kq ON kq.maKetQuaKiemKe = hbt.maKetQuaKiemKe 
         WHERE kq.maBienBanKiemKe = ?",
        's', [$maBienBan]
    );
    $hbtMap = [];
    foreach ($hbtList as $h) {
        $hbtMap[$h['maTonKho']] = $h;
    }

    $lotDetails = [];
    $countedCount = 0;
    $diffCount = 0;
    $abnormalCount = 0;

    foreach ($items as $idx => $it) {
        $kq = $resMap[$it['maTonKho']] ?? null;
        $isCounted = ($kq !== null);
        if ($isCounted) {
            $countedCount++;
        }

        $sysQty = $kq ? (float)$kq['soLuongHeThong'] : (float)$it['soLuongTon'];
        $actQty = $kq ? (float)$kq['soLuongThucTe'] : null;
        $diff = $kq ? (float)$kq['chenhLech'] : 0.0;
        if ($isCounted && $diff != 0) {
            $diffCount++;
        }

        $cond = $kq ? $kq['tinhTrangHangHoa'] : 'Bình thường';
        $cl = $clMap[$it['maTonKho']] ?? null;
        $hbt = $hbtMap[$it['maTonKho']] ?? null;
        if ($hbt) {
            $abnormalCount++;
        }

        $ketLuan = '';
        if ($isCounted) {
            if ($diff == 0) {
                $ketLuan = 'Khớp';
            } elseif ($diff > 0) {
                $ketLuan = 'Thừa';
            } else {
                $ketLuan = 'Thiếu';
            }
        }

        $lotDetails[] = [
            'stt' => $idx + 1,
            'maTonKho' => $it['maTonKho'],
            'maLoHang' => $it['maLoHang'],
            'tenLoHang' => $it['tenLoHang'] ?? '',
            'maHang' => $it['maHang'] ?? '',
            'tenHang' => $it['tenHang'] ?? '',
            'tenKhuVuc' => $it['tenKhuVuc'] ?? '',
            'tenViTri' => $it['tenViTri'] ?? '',
            'viTri' => ($it['tenViTri'] ?? '') . (!empty($it['tenKhuVuc']) ? ' (' . $it['tenKhuVuc'] . ')' : ''),
            'donViTinh' => $it['donViTinh'] ?? 'kg',
            'soLuongHeThong' => $sysQty,
            'soLuongThucTe' => $actQty,
            'chenhLech' => $isCounted ? $diff : null,
            'ketLuan' => $ketLuan,
            'tinhTrangHangHoa' => $cond,
            'soLuongBiAnhHuong' => $hbt ? (float)$hbt['soLuongBiAnhHuong'] : null,
            'trangThaiXuLy' => $cl['trangThai'] ?? ($hbt['trangThai'] ?? ($isCounted && $diff != 0 ? 'Chưa xử lý' : '-')),
            'ghiChu' => $kq['ghiChu'] ?? '',
            'isCounted' => $isCounted,
        ];
    }

    $employeeName = uc24_get_employee_name($bb['maNhanVien']);

    return [
        'bb' => array_merge($bb, [
            'tenNhanVien' => $employeeName,
            'thoiDiemBatDauFmt' => date('d/m/Y H:i', strtotime($bb['thoiDiemBatDau'])),
            'thoiDiemKetThucFmt' => date('d/m/Y H:i', strtotime(!empty($bb['thoiDiemKetThucThucTe']) ? $bb['thoiDiemKetThucThucTe'] : $bb['thoiDiemKetThucDuKien'])),
            'ngayLapFmt' => date('d/m/Y', strtotime($bb['ngayLap'])),
        ]),
        'items' => $lotDetails,
        'stats' => [
            'totalLots' => count($items),
            'countedLots' => $countedCount,
            'diffLots' => $diffCount,
            'abnormalLots' => $abnormalCount,
            'progress' => (float)$bb['tienDoKiemKe'],
        ]
    ];
}

