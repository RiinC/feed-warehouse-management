<?php
/**
 * UC06 - Tiếp nhận hàng vào kho  |  Actor chính: Nhân viên kho  |  CSDL riêng: QLK01 (DB_UC06)
 * Tên bảng/cột theo tài liệu thiết kế: PhieuGiaoNCC, PhieuYeuCauNhapKho, HangHoa, ViTriLuuTru,
 * PhieuTiepNhan, ChiTietTiepNhan, LoHang ...
 * Hàm dùng chung cho index.php, form.php, xac_nhan.php.
 */
require_once __DIR__ . '/../../includes/auth.php';

// Thông báo đúng theo đặc tả
const UC06_MSG_KHONG_CO_CHUNG_TU = 'Không thể tiếp nhận hàng do không có chứng từ giao hàng hợp lệ';
const UC06_MSG_HANG_CHUA_KHAI_BAO = 'Hàng hóa chưa được khai báo trong hệ thống';
const UC06_MSG_THANH_CONG = 'Tiếp nhận hàng thành công';
const UC06_MSG_THAT_BAI = 'Tiếp nhận hàng thất bại';

const UC06_LOAI_NCC = 'Phiếu giao hàng NCC';
const UC06_LOAI_YC = 'Phiếu yêu cầu nhập thành phẩm';   // khớp cột loaiChungTu của vw_ChungTuChoTiepNhan
const UC06_SO_PHIEU_GAN_DAY = 5;                        // bảng "Phiếu tiếp nhận gần đây" luôn giữ tối đa 5 phiếu mới nhất
const UC06_HSD_MAC_DINH = 90; // số ngày hạn sử dụng nếu HangHoa.hanSuDungMacDinh chưa khai báo

/** Đường dẫn trang UC06 theo thư mục của vai trò đang đăng nhập (xem uc_url() trong includes/auth.php). */
function uc06_url(string $file = 'index.php', array $query = []): string
{
    $u = uc_url('UC06', $file);
    return $query ? $u . '?' . http_build_query($query) : $u;
}

/** Các giá trị của ô lọc "Loại chứng từ". */
function uc06_ds_loai_chung_tu(): array
{
    return [UC06_LOAI_NCC, UC06_LOAI_YC];
}

/**
 * Các giá trị của ô lọc "Nhà cung cấp / nguồn giao": tên nhà cung cấp (NhaCungCap)
 * gộp với nguồn giao (lyDoNhap) của phiếu yêu cầu nhập thành phẩm. UNION tự loại giá trị trùng.
 */
function uc06_ds_nguon_giao(): array
{
    $rows = db_all(
        "SELECT tenNCC AS nguon FROM NhaCungCap
         UNION
         SELECT lyDoNhap FROM PhieuYeuCauNhapKho WHERE loaiNhap = 'Nhập thành phẩm' AND lyDoNhap <> ''
         ORDER BY nguon",
        '', [], DB_UC06
    );
    return array_column($rows, 'nguon');
}

/**
 * Đọc bộ lọc từ query string: q (số chứng từ), loai, nguon, tuNgay, denNgay.
 * Trả về [$loc, $loi]:
 *  - $loc['dungNgay'] = false khi Từ ngày > Đến ngày (khoảng ngày không được áp dụng)
 *  - $loc['dangLoc']  = có ít nhất một tiêu chí đang được áp dụng
 *  - $loi: thông báo lỗi theo từng ô (tuNgay, denNgay)
 */
function uc06_doc_bo_loc(array $get, array $dsNguon): array
{
    $str = fn($k) => is_string($get[$k] ?? null) ? trim($get[$k]) : '';
    $ngayHopLe = function (string $s): bool {
        $d = DateTime::createFromFormat('Y-m-d', $s);
        return $d && $d->format('Y-m-d') === $s;
    };

    $loc = [
        'q' => mb_substr($str('q'), 0, 50),
        'loai' => $str('loai'),
        'nguon' => $str('nguon'),
        'tuNgay' => $str('tuNgay'),
        'denNgay' => $str('denNgay'),
        'dungNgay' => true,
    ];
    $loi = [];

    // Giá trị lạ (không nằm trong dropdown) coi như "Tất cả"
    if (!in_array($loc['loai'], uc06_ds_loai_chung_tu(), true)) $loc['loai'] = '';
    if (!in_array($loc['nguon'], $dsNguon, true)) $loc['nguon'] = '';

    foreach (['tuNgay' => 'Từ ngày', 'denNgay' => 'Đến ngày'] as $k => $nhan) {
        if ($loc[$k] !== '' && !$ngayHopLe($loc[$k])) {
            $loi[$k] = $nhan . ' không hợp lệ.';
            $loc[$k] = '';
        }
    }
    if ($loc['tuNgay'] !== '' && $loc['denNgay'] !== '' && $loc['tuNgay'] > $loc['denNgay']) {
        $loi['denNgay'] = 'Đến ngày phải sau hoặc bằng Từ ngày.';
        $loc['dungNgay'] = false;
    }

    $coNgay = $loc['dungNgay'] && ($loc['tuNgay'] !== '' || $loc['denNgay'] !== '');
    $loc['dangLoc'] = $loc['q'] !== '' || $loc['loai'] !== '' || $loc['nguon'] !== '' || $coNgay;
    return [$loc, $loi];
}

/**
 * Ghép mệnh đề WHERE từ bộ lọc. Tên cột truyền vào là hằng nội bộ (không lấy từ người dùng);
 * giá trị người dùng luôn đi qua tham số ?.
 * Chuỗi cố định trong view/CASE và tham số có thể khác collation (tùy lúc import CSDL và cấu hình kết nối).
 * Tham số bind của mysqli có thể bị MySQL xem là kiểu binary, vì vậy không đặt COLLATE trực tiếp
 * lên dấu ?; luôn CONVERT tham số sang utf8mb4 trước khi so sánh.
 * Khoảng ngày: tuNgay <= cột ngày < denNgay + 1 ngày (tính trọn ngày cuối).
 * Trả về [$where, $types, $params].
 */
function uc06_menh_de_loc(array $loc, string $cotSo, string $cotLoai, string $cotNguon, string $cotNgay): array
{
    $w = [];
    $types = '';
    $p = [];

    if (($loc['q'] ?? '') !== '') {
        $w[] = "$cotSo COLLATE utf8mb4_unicode_ci LIKE CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci";
        $types .= 's';
        $p[] = '%' . addcslashes($loc['q'], '%_\\') . '%';
    }
    if (($loc['loai'] ?? '') !== '') {
        $w[] = "$cotLoai COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci";
        $types .= 's';
        $p[] = $loc['loai'];
    }
    if (($loc['nguon'] ?? '') !== '') {
        $w[] = "$cotNguon COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci";
        $types .= 's';
        $p[] = $loc['nguon'];
    }
    if (!empty($loc['dungNgay'])) {
        if (($loc['tuNgay'] ?? '') !== '') {
            $w[] = "$cotNgay >= ?";
            $types .= 's';
            $p[] = $loc['tuNgay'] . ' 00:00:00';
        }
        if (($loc['denNgay'] ?? '') !== '') {
            $w[] = "$cotNgay < ?";
            $types .= 's';
            $p[] = date('Y-m-d', strtotime($loc['denNgay'] . ' +1 day')) . ' 00:00:00';
        }
    }
    return [$w ? ' WHERE ' . implode(' AND ', $w) : '', $types, $p];
}

/** Bước 2-3: chứng từ "Chờ tiếp nhận" / "Đã tiếp nhận một phần", lọc theo Số chứng từ, Loại, Nguồn giao, Ngày giao. */
function uc06_ds_chung_tu(array $loc = []): array
{
    [$where, $types, $params] = uc06_menh_de_loc($loc, 'soChungTu', 'loaiChungTu', 'nguonGiao', 'ngayGiao');
    return db_all(
        "SELECT soChungTu, loaiChungTu, nguonGiao, ngayGiao, tinhTrang FROM vw_ChungTuChoTiepNhan$where
         ORDER BY ngayGiao, soChungTu",
        $types, $params, DB_UC06
    );
}

/**
 * Bảng "Phiếu tiếp nhận gần đây": lọc cùng bộ lọc với bảng chứng từ chờ
 * (Số chứng từ, Loại, Nguồn giao suy ra từ chứng từ gốc của phiếu; khoảng ngày theo NGÀY NHẬN của phiếu).
 * Luôn giữ $gioiHan phiếu mới nhất, kèm tổng số phiếu phù hợp.
 * Trả về ['rows' => [...], 'tong' => int].
 */
function uc06_ds_phieu_gan_day(array $loc = [], int $gioiHan = UC06_SO_PHIEU_GAN_DAY): array
{
    $goc = "SELECT p.maTiepNhan, p.thoiGianTiepNhan,
                   COALESCE(p.maPhieuGiao, p.maPhieuYeuCauNhap) AS soChungTu,
                   CASE WHEN p.maPhieuGiao IS NOT NULL THEN '" . UC06_LOAI_NCC . "' ELSE '" . UC06_LOAI_YC . "' END AS loaiChungTu,
                   COALESCE(n.tenNCC, y.lyDoNhap) AS nguonGiao,
                   p.maNhanVien, v.tenViTri, p.trangThaiTiepNhan
            FROM PhieuTiepNhan p
            JOIN ViTriLuuTru v ON v.maViTri = p.maViTri
            LEFT JOIN PhieuGiaoNCC g ON g.maPhieuGiao = p.maPhieuGiao
            LEFT JOIN NhaCungCap n ON n.maNCC = g.maNCC
            LEFT JOIN PhieuYeuCauNhapKho y ON y.maPhieuYeuCauNhap = p.maPhieuYeuCauNhap";

    [$where, $types, $params] = uc06_menh_de_loc($loc, 'soChungTu', 'loaiChungTu', 'nguonGiao', 'thoiGianTiepNhan');

    $tong = db_one("SELECT COUNT(*) AS c FROM ($goc) t$where", $types, $params, DB_UC06);
    $rows = db_all(
        "SELECT * FROM ($goc) t$where ORDER BY thoiGianTiepNhan DESC, maTiepNhan DESC LIMIT " . max(1, $gioiHan),
        $types, $params, DB_UC06
    );
    return ['rows' => $rows, 'tong' => (int)($tong['c'] ?? 0)];
}

/** Dữ liệu popup "Chi tiết phiếu tiếp nhận": thông tin phiếu (người nhận tra tên từ QLK00). */
function uc06_chi_tiet_phieu(string $ma): ?array
{
    $r = db_one(
        "SELECT p.maTiepNhan, p.thoiGianTiepNhan, p.trangThaiTiepNhan, p.ghiChu, p.maNhanVien, v.tenViTri
         FROM PhieuTiepNhan p JOIN ViTriLuuTru v ON v.maViTri = p.maViTri
         WHERE p.maTiepNhan = ?",
        's', [$ma], DB_UC06
    );
    if (!$r) return null;
    $r['tenNguoiNhan'] = uc06_ten_nhan_vien([$r['maNhanVien']])[$r['maNhanVien']] ?? $r['maNhanVien'];
    return $r;
}

/** In phần nội dung chi tiết phiếu (dùng cho popup và cho trang chi_tiet.php khi không có JavaScript). */
function uc06_in_chi_tiet_phieu(array $p): void
{
    ?>
    <dl class="kv">
        <dt>Mã phiếu tiếp nhận</dt><dd><b><?= e($p['maTiepNhan']) ?></b></dd>
        <dt>Ngày nhận</dt><dd><?= e(fmt_date($p['thoiGianTiepNhan'])) ?></dd>
        <dt>Giờ nhận</dt><dd><?= e(fmt_time($p['thoiGianTiepNhan'])) ?></dd>
        <dt>Người nhận</dt><dd><?= e($p['tenNguoiNhan']) ?></dd>
        <dt>Vị trí lưu trữ</dt><dd><?= e($p['tenViTri']) ?></dd>
        <dt>Tình trạng</dt><dd><?= status_badge($p['trangThaiTiepNhan']) ?></dd>
        <dt>Ghi chú</dt><dd><?= trim((string)$p['ghiChu']) !== '' ? nl2br(e($p['ghiChu'])) : '<span class="muted">—</span>' ?></dd>
    </dl>
    <?php
}

function uc06_chung_tu(string $so): ?array
{
    return db_one(
        "SELECT soChungTu, loaiChungTu, nguonGiao, ngayGiao, tinhTrang FROM vw_ChungTuChoTiepNhan WHERE soChungTu = ?",
        's', [$so], DB_UC06
    );
}

/** Các dòng hàng còn phải tiếp nhận của chứng từ (số lượng chứng từ > số lượng đã nhận). */
function uc06_dong_hang(string $so): array
{
    return db_all(
        "SELECT d.maHangHoa, h.tenHangHoa, h.donViTinh, h.trangThai AS trangThaiHang, h.hanSuDungMacDinh,
                d.maDonHang, d.maKeHoach, d.soLuongChungTu, d.soLuongDaNhan,
                (d.soLuongChungTu - d.soLuongDaNhan) AS conLai
         FROM vw_DongChungTu d
         LEFT JOIN HangHoa h ON h.maHangHoa = d.maHangHoa
         WHERE d.soChungTu = ? AND d.soLuongChungTu > d.soLuongDaNhan
         ORDER BY d.maHangHoa",
        's', [$so], DB_UC06
    );
}

/** Exception 4.1 / tiền điều kiện 3: hàng không có trong danh mục hoặc không "Đang sử dụng". */
function uc06_hang_chua_khai_bao(array $dong): array
{
    $ds = [];
    foreach ($dong as $r) {
        if (($r['trangThaiHang'] ?? null) !== 'Đang sử dụng') {
            $ds[] = $r['maHangHoa'] . (!empty($r['tenHangHoa']) ? ' - ' . $r['tenHangHoa'] : '');
        }
    }
    return $ds;
}

/** Tiền điều kiện 4: chỉ vị trí lưu trữ "Đang sử dụng". */
function uc06_vi_tri_dang_dung(): array
{
    return db_all(
        "SELECT maViTri, tenViTri FROM ViTriLuuTru WHERE trangThai = 'Đang sử dụng' ORDER BY maViTri",
        '', [], DB_UC06
    );
}

function uc06_ten_vi_tri(string $ma): string
{
    $r = db_one("SELECT tenViTri FROM ViTriLuuTru WHERE maViTri = ?", 's', [$ma], DB_UC06);
    return $r['tenViTri'] ?? $ma;
}

/** Họ tên người nhận: PhieuTiepNhan chỉ lưu maNhanVien, tên lấy từ CSDL dùng chung QLK00 (bảng NhanVien). */
function uc06_ten_nhan_vien(array $dsMa): array
{
    $dsMa = array_values(array_unique(array_filter($dsMa)));
    if (!$dsMa) return [];
    $in = implode(',', array_fill(0, count($dsMa), '?'));
    $rows = db_all("SELECT maNV, hoTen FROM NhanVien WHERE maNV IN ($in)", str_repeat('s', count($dsMa)), $dsMa, DB_DUNG_CHUNG);
    return array_column($rows, 'hoTen', 'maNV');
}

/** Số thứ tự tiếp theo cho mã dạng <tiền tố><số 4 chữ số>. $bang, $cot là hằng nội bộ (không lấy từ người dùng). */
function uc06_so_tiep_theo(string $tienTo, string $bang, string $cot, bool $khoa = false): int
{
    $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING($cot, ?) AS UNSIGNED)), 0) + 1 AS n FROM $bang WHERE $cot LIKE ?" . ($khoa ? ' FOR UPDATE' : '');
    $r = db_one($sql, 'is', [strlen($tienTo) + 1, $tienTo . '%'], DB_UC06);
    return (int)$r['n'];
}

/** Mã phiếu hiển thị ở bước 4 (dự kiến; mã thật được cấp khi lưu). */
function uc06_ma_phieu_du_kien(): string
{
    $tienTo = 'PTN' . date('Y') . '-';
    return $tienTo . str_pad((string)uc06_so_tiep_theo($tienTo, 'PhieuTiepNhan', 'maTiepNhan'), 4, '0', STR_PAD_LEFT);
}

/**
 * Bước 6: kiểm tra trường bắt buộc (*) và Số lượng thực nhận > 0.
 * Trả về [$loi, $nhap]. $loi rỗng nghĩa là hợp lệ.
 */
function uc06_kiem_tra(string $so, array $dong, array $post): array
{
    $loi = [];
    $str = fn($k) => is_string($post[$k] ?? null) ? trim($post[$k]) : '';

    $ngay = $str('ngayNhan');
    $d = DateTime::createFromFormat('Y-m-d', $ngay);
    if (!$d || $d->format('Y-m-d') !== $ngay) $loi['ngayNhan'] = 'Vui lòng nhập Ngày nhận hợp lệ.';

    $gio = $str('gioNhan');
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $gio)) $loi['gioNhan'] = 'Vui lòng nhập Giờ nhận hợp lệ.';

    $vt = $str('maViTri');
    if (!in_array($vt, array_column(uc06_vi_tri_dang_dung(), 'maViTri'), true)) {
        $loi['maViTri'] = 'Vui lòng chọn Vị trí lưu trữ.';
    }

    $slIn = is_array($post['soLuongThucNhan'] ?? null) ? $post['soLuongThucNhan'] : [];
    $gcIn = is_array($post['ghiChuDong'] ?? null) ? $post['ghiChuDong'] : [];
    $nhapDong = [];
    foreach ($dong as $r) {
        $ma = $r['maHangHoa'];
        $raw = is_string($slIn[$ma] ?? null) ? trim($slIn[$ma]) : '';
        $sl = ($raw !== '' && is_numeric($raw)) ? (float)$raw : 0.0;
        if ($sl <= 0 || $sl > 1000000000) {
            $loi['dong'][$ma] = 'Phải > 0';
        }
        $gc = mb_substr(is_string($gcIn[$ma] ?? null) ? trim($gcIn[$ma]) : '', 0, 255);
        $nhapDong[$ma] = ['soLuong' => $sl, 'raw' => $raw, 'ghiChu' => $gc];
    }

    $nhap = [
        'soChungTu' => $so,
        'ngayNhan' => $ngay,
        'gioNhan' => $gio,
        'maViTri' => $vt,
        'ghiChu' => mb_substr($str('ghiChu'), 0, 500),
        'dong' => $nhapDong,
    ];
    return [$loi, $nhap];
}

/**
 * Bước 9-10: lưu PhieuTiepNhan + ChiTietTiepNhan, tạo LoHang, cập nhật chứng từ / đơn hàng / kế hoạch sản xuất.
 * Tất cả trong một transaction: lỗi thì hoàn tác toàn bộ (hậu điều kiện "lưu không thành công").
 *  - DomainException: lỗi nghiệp vụ (chứng từ không còn hợp lệ, hàng chưa khai báo...)
 *  - Throwable khác : lỗi lưu dữ liệu -> Exception 10.1 (Tiếp nhận hàng thất bại, Thử lại / Hủy)
 */
function uc06_luu(array $user, array $nhap): array
{
    $c = db(DB_UC06);
    $c->begin_transaction();
    try {
        $so = $nhap['soChungTu'];
        $ct = uc06_chung_tu($so);
        if (!$ct) throw new DomainException(UC06_MSG_KHONG_CO_CHUNG_TU);

        // Chứng từ thuộc bảng nào: PhieuGiaoNCC hay PhieuYeuCauNhapKho (tên bảng/cột là hằng nội bộ)
        $laNCC = $ct['loaiChungTu'] === UC06_LOAI_NCC;
        $bang = $laNCC ? 'PhieuGiaoNCC' : 'PhieuYeuCauNhapKho';
        $cotMa = $laNCC ? 'maPhieuGiao' : 'maPhieuYeuCauNhap';
        $bangCT = $laNCC ? 'ChiTietPhieuGiaoNCC' : 'ChiTietPhieuYeuCauNhapKho';
        $cotSL = $laNCC ? 'soLuongGiao' : 'soLuongYeuCau';

        // Khóa dòng chứng từ rồi kiểm tra lại (tránh hai người cùng tiếp nhận)
        $khoa = db_one("SELECT trangThai FROM $bang WHERE $cotMa = ? FOR UPDATE", 's', [$so], DB_UC06);
        if (!$khoa || !in_array($khoa['trangThai'], ['Chờ tiếp nhận', 'Đã tiếp nhận một phần'], true)) {
            throw new DomainException(UC06_MSG_KHONG_CO_CHUNG_TU);
        }

        $dong = uc06_dong_hang($so);
        if (!$dong) throw new DomainException(UC06_MSG_KHONG_CO_CHUNG_TU);
        if (uc06_hang_chua_khai_bao($dong)) throw new DomainException(UC06_MSG_HANG_CHUA_KHAI_BAO);

        $vt = db_one("SELECT maViTri, tenViTri FROM ViTriLuuTru WHERE maViTri = ? AND trangThai = 'Đang sử dụng'", 's', [$nhap['maViTri']], DB_UC06);
        if (!$vt) throw new DomainException('Vị trí lưu trữ không còn ở trạng thái "Đang sử dụng"');

        $thoiGian = $nhap['ngayNhan'] . ' ' . $nhap['gioNhan'] . ':00';
        $nam = date('Y', strtotime($nhap['ngayNhan']));

        $tong = 0.0;
        foreach ($dong as $r) {
            $sl = (float)($nhap['dong'][$r['maHangHoa']]['soLuong'] ?? 0);
            if ($sl <= 0) throw new DomainException('Số lượng thực nhận của hàng ' . $r['maHangHoa'] . ' không hợp lệ');
            $tong += $sl;
        }

        $tienToPhieu = 'PTN' . $nam . '-';
        $maTiepNhan = $tienToPhieu . str_pad((string)uc06_so_tiep_theo($tienToPhieu, 'PhieuTiepNhan', 'maTiepNhan', true), 4, '0', STR_PAD_LEFT);

        db_exec(
            "INSERT INTO PhieuTiepNhan (maTiepNhan, thoiGianTiepNhan, soLuongThucNhan, ghiChu, maPhieuGiao, maPhieuYeuCauNhap, maNhanVien, maViTri)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            'ssdsssss',
            [$maTiepNhan, $thoiGian, $tong, $nhap['ghiChu'] !== '' ? $nhap['ghiChu'] : null,
                $laNCC ? $so : null, $laNCC ? null : $so, $user['id'], $vt['maViTri']],
            DB_UC06
        );

        $tienToLo = 'LH' . $nam . '-';
        $soLo = uc06_so_tiep_theo($tienToLo, 'LoHang', 'maLoHang', true);
        $dsLo = [];

        foreach ($dong as $r) {
            $ma = $r['maHangHoa'];
            $sl = (float)$nhap['dong'][$ma]['soLuong'];
            $gc = $nhap['dong'][$ma]['ghiChu'] ?? '';

            // Bước 10: tạo lô hàng. tinhTrang 'Chờ kiểm tra'; trangThaiChatLuong mặc định 'Chờ kiểm tra chất lượng'
            $maLo = $tienToLo . str_pad((string)$soLo++, 4, '0', STR_PAD_LEFT);
            $ngayHan = (int)($r['hanSuDungMacDinh'] ?: UC06_HSD_MAC_DINH);
            $hanSuDung = date('Y-m-d H:i:s', strtotime($thoiGian . ' +' . $ngayHan . ' days'));
            db_exec(
                "INSERT INTO LoHang (maLoHang, maHangHoa, soLo, hanSuDung, viTriLuuTru, tinhTrang, tenLoHang, soLuongTiepNhan, donViTinh, thoiGianTiepNhan)
                 VALUES (?, ?, ?, ?, ?, 'Chờ kiểm tra', ?, ?, ?, ?)",
                'ssssssdss',
                [$maLo, $ma, $maLo, $hanSuDung, $vt['tenViTri'], 'Lô ' . $r['tenHangHoa'], $sl, $r['donViTinh'], $thoiGian],
                DB_UC06
            );
            $dsLo[] = $maLo;

            // Chi tiết phiếu tiếp nhận (trangThai mặc định "Chờ kiểm định")
            db_exec(
                "INSERT INTO ChiTietTiepNhan (maTiepNhan, maHangHoa, maLoHang, soLuongThucNhan, ghiChu) VALUES (?, ?, ?, ?, ?)",
                'sssds', [$maTiepNhan, $ma, $maLo, $sl, $gc !== '' ? $gc : null], DB_UC06
            );

            // Số lượng đã nhận: trên chứng từ, và trên Đơn hàng / Kế hoạch sản xuất
            db_exec("UPDATE $bangCT SET soLuongDaNhan = soLuongDaNhan + ? WHERE $cotMa = ? AND maHangHoa = ?", 'dss', [$sl, $so, $ma], DB_UC06);
            if (!empty($r['maDonHang'])) {
                db_exec("UPDATE ChiTietDonHang SET soLuongDaNhan = soLuongDaNhan + ? WHERE maDonHang = ? AND maHangHoa = ?", 'dss', [$sl, $r['maDonHang'], $ma], DB_UC06);
            }
            if (!empty($r['maKeHoach'])) {
                db_exec("UPDATE ChiTietKeHoachSanXuat SET soLuongDaNhan = soLuongDaNhan + ? WHERE maKeHoach = ? AND maHangHoa = ?", 'dss', [$sl, $r['maKeHoach'], $ma], DB_UC06);
            }
        }

        // Tình trạng chứng từ: đủ hàng -> "Đã tiếp nhận", còn thiếu -> "Đã tiếp nhận một phần"
        $thieu = db_one("SELECT COUNT(*) AS c FROM $bangCT WHERE $cotMa = ? AND soLuongDaNhan < $cotSL", 's', [$so], DB_UC06);
        $tinhTrang = ((int)$thieu['c'] > 0) ? 'Đã tiếp nhận một phần' : 'Đã tiếp nhận';
        db_exec("UPDATE $bang SET trangThai = ? WHERE $cotMa = ?", 'ss', [$tinhTrang, $so], DB_UC06);

        $c->commit();
        return ['maTiepNhan' => $maTiepNhan, 'dsLo' => $dsLo, 'tinhTrangChungTu' => $tinhTrang];
    } catch (Throwable $ex) {
        $c->rollback();
        throw $ex;
    }
}
