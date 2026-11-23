<?php
namespace App\Models;

use Core\Model;

/**
 * Bảng PhieuDieuChuyen: phiếu điều chuyển nội bộ có phê duyệt.
 * Luồng mới: Chờ duyệt -> Đã duyệt / Đã từ chối -> Đã hoàn tất. Giữ trạng thái cũ của dữ liệu mẫu.
 */
class PhieuDieuChuyen extends Model
{
    private const EPS = 0.000001;

    public static function lock(string $maPhieu): array
    {
        $p = static::one("SELECT * FROM PhieuDieuChuyen WHERE maPhieuDieuChuyen = ? FOR UPDATE", [$maPhieu]);
        if (!$p) {
            throw new \RuntimeException('Phiếu điều chuyển không tồn tại.');
        }
        return $p;
    }

    public static function find(string $maPhieu): ?array
    {
        return static::one("SELECT p.*, l.maHangHoa, l.ngayHetHan, l.soLuongBanDau, l.trangThaiQC, l.trangThaiVongDoi,
                hh.tenHangHoa, hh.donViTinhChuan,
                vn.tenViTri AS tenViTriNguon, vn.sucChuaToiDa AS sucChuaNguon, vn.trangThai AS trangThaiNguon,
                kn.tenKhuVuc AS tenKhuVucNguon, kn.maKho AS maKhoNguon, khoN.tenKho AS tenKhoNguon,
                vd.tenViTri AS tenViTriDich, vd.sucChuaToiDa AS sucChuaDich, vd.trangThai AS trangThaiDich,
                kd.tenKhuVuc AS tenKhuVucDich, kd.maKho AS maKhoDich, khoD.tenKho AS tenKhoDich,
                nvl.hoTen AS nguoiLap, nvd.hoTen AS nguoiDuyet, nvt.hoTen AS nguoiThucHien
            FROM PhieuDieuChuyen p
            JOIN LoHang l ON l.maLoHang = p.maLoHang
            JOIN HangHoa hh ON hh.maHangHoa = l.maHangHoa
            JOIN ViTriLuuTru vn ON vn.maViTri = p.maViTriNguon
            JOIN KhuVuc kn ON kn.maKhuVuc = vn.maKhuVuc
            JOIN Kho khoN ON khoN.maKho = kn.maKho
            JOIN ViTriLuuTru vd ON vd.maViTri = p.maViTriDich
            JOIN KhuVuc kd ON kd.maKhuVuc = vd.maKhuVuc
            JOIN Kho khoD ON khoD.maKho = kd.maKho
            LEFT JOIN NhanVien nvl ON nvl.maNhanVien = p.maNhanVienYeuCau
            LEFT JOIN NhanVien nvd ON nvd.maNhanVien = p.maNhanVienDuyet
            LEFT JOIN NhanVien nvt ON nvt.maNhanVien = p.maNhanVienThucHien
            WHERE p.maPhieuDieuChuyen = ?", [$maPhieu]);
    }

    /** Quản trị viên xem tất cả; người lập xem phiếu mình; quản lý chỉ xem phiếu thuộc kho mình. */
    public static function canView(array $p, string $staffId, string $role, ?string $maKho): bool
    {
        if ($role === ROLE_ADMIN) {
            return true;
        }
        if ((string)$p['maNhanVienYeuCau'] === $staffId) {
            return true;
        }
        return $role === ROLE_MANAGER && $maKho !== null && $maKho !== '' && (string)$p['maKhoNguon'] === $maKho;
    }

    private static function withCodeLock(callable $fn)
    {
        $r = static::one("SELECT GET_LOCK(?, 5) AS ok", ['fw_m3_pdc']);
        if ((int)($r['ok'] ?? 0) !== 1) {
            throw new \RuntimeException('Hệ thống đang bận sinh mã phiếu, vui lòng thử lại.');
        }
        try {
            return $fn();
        } finally {
            try {
                static::one("SELECT RELEASE_LOCK(?) AS r", ['fw_m3_pdc']);
            } catch (\Throwable $e) {
            }
        }
    }

    private static function nextCode(): string
    {
        return self::withCodeLock(function () {
            $prefix = 'PDC' . date('ymd');
            $r = static::one("SELECT maPhieuDieuChuyen AS m FROM PhieuDieuChuyen WHERE maPhieuDieuChuyen LIKE ? ORDER BY maPhieuDieuChuyen DESC LIMIT 1 FOR UPDATE", [$prefix . '%']);
            $last = (string)($r['m'] ?? '' );
            $seq = $last !== '' ? (int)substr($last, -4) : 0;
            if ($seq >= 9999) {
                throw new \RuntimeException('Đã hết dãy mã phiếu điều chuyển trong ngày.');
            }
            return $prefix . str_pad((string)($seq + 1), 4, '0', STR_PAD_LEFT);
        });
    }


    /** @return array{0:array,1:int} $scope: [type => all|own|kho, value => ...] */
    public static function search(array $f, int $page, int $perPage, array $scope): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (($scope['type'] ?? 'all') === 'own') {
            $where[] = 'p.maNhanVienYeuCau = ?';
            $params[] = $scope['value'];
        } elseif (($scope['type'] ?? 'all') === 'kho') {
            $where[] = 'kn.maKho = ?';
            $params[] = $scope['value'];
        }
        if (!empty($f['trangThai'])) {
            $where[] = 'p.trangThai = ?';
            $params[] = $f['trangThai'];
        }
        if (!empty($f['maLoHang'])) {
            $where[] = 'p.maLoHang = ?';
            $params[] = $f['maLoHang'];
        }
        if (!empty($f['maViTriNguon'])) {
            $where[] = 'p.maViTriNguon = ?';
            $params[] = $f['maViTriNguon'];
        }
        if (!empty($f['maViTriDich'])) {
            $where[] = 'p.maViTriDich = ?';
            $params[] = $f['maViTriDich'];
        }
        if (!empty($f['tuNgay'])) {
            $where[] = 'p.ngayYeuCau >= ?';
            $params[] = $f['tuNgay'] . ' 00:00:00';
        }
        if (!empty($f['denNgay'])) {
            $where[] = 'p.ngayYeuCau <= ?';
            $params[] = $f['denNgay'] . ' 23:59:59';
        }
        if (!empty($f['q'])) {
            $where[] = '(p.maPhieuDieuChuyen LIKE ? OR p.maLoHang LIKE ? OR nvl.hoTen LIKE ?)';
            $like = '%' . $f['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($f['nguoiLap'])) {
            $where[] = '(nvl.hoTen LIKE ? OR p.maNhanVienYeuCau LIKE ?)';
            $like2 = '%' . $f['nguoiLap'] . '%';
            $params[] = $like2;
            $params[] = $like2;
        }
        $w = implode(' AND ', $where);
        $from = "FROM PhieuDieuChuyen p
            JOIN LoHang l ON l.maLoHang = p.maLoHang
            JOIN HangHoa hh ON hh.maHangHoa = l.maHangHoa
            JOIN ViTriLuuTru vn ON vn.maViTri = p.maViTriNguon
            JOIN KhuVuc kn ON kn.maKhuVuc = vn.maKhuVuc
            JOIN Kho khoN ON khoN.maKho = kn.maKho
            JOIN ViTriLuuTru vd ON vd.maViTri = p.maViTriDich
            JOIN KhuVuc kd ON kd.maKhuVuc = vd.maKhuVuc
            JOIN Kho khoD ON khoD.maKho = kd.maKho
            LEFT JOIN NhanVien nvl ON nvl.maNhanVien = p.maNhanVienYeuCau
            LEFT JOIN NhanVien nvd ON nvd.maNhanVien = p.maNhanVienDuyet
            LEFT JOIN NhanVien nvt ON nvt.maNhanVien = p.maNhanVienThucHien";
        $total = (int)(static::one("SELECT COUNT(*) AS c " . $from . " WHERE $w", $params)['c'] ?? 0);
        $sql = "SELECT p.maPhieuDieuChuyen, p.maLoHang, p.maViTriNguon, p.maViTriDich, p.soLuongDuKien,
                       p.soLuongThucChuyen, p.maNhanVienYeuCau, p.maNhanVienDuyet, p.maNhanVienThucHien,
                       (p.soLuongDuKien - IFNULL(p.soLuongThucChuyen, 0)) AS conLai,
                       p.ngayYeuCau, p.ngayDuyet, p.ngayHoanTat, p.trangThai, p.lyDoDieuChuyen, p.lyDoTuChoi,
                       l.maHangHoa, hh.tenHangHoa, hh.donViTinhChuan, l.ngayHetHan,
                       vn.tenViTri AS tenViTriNguon, kn.tenKhuVuc AS tenKhuVucNguon, kn.maKho AS maKhoNguon,
                       khoN.tenKho AS tenKhoNguon,
                       vd.tenViTri AS tenViTriDich, kd.tenKhuVuc AS tenKhuVucDich, kd.maKho AS maKhoDich,
                       khoD.tenKho AS tenKhoDich, nvl.hoTen AS nguoiLap, nvd.hoTen AS nguoiDuyet,
                       nvt.hoTen AS nguoiThucHien
                   " . $from . " WHERE $w
            ORDER BY p.ngayYeuCau DESC, p.maPhieuDieuChuyen DESC
            LIMIT ? OFFSET ?";
        $p2 = $params;
        $p2[] = $perPage;
        $p2[] = max(0, ($page - 1) * $perPage);
        return [static::all($sql, $p2), $total];
    }

    /** @return array{0:array,1:int} */
    public static function pendingForManager(string $maKho, int $page, int $perPage): array
    {
        return self::search(['trangThai' => 'Chờ duyệt'], $page, $perPage, ['type' => 'kho', 'value' => $maKho]);
    }


    /** Lập phiếu Chờ duyệt. Trả về mã phiếu. */
    public static function createRequest(array $d, string $staffId, ?string $scopeKho): string
    {
        return static::transaction(function () use ($d, $staffId, $scopeKho) {
            $qty = (float)$d['soLuong'];
            $lyDo = trim((string)$d['lyDoDieuChuyen']);
            if ($qty <= 0) {
                throw new \RuntimeException('Số lượng điều chuyển phải lớn hơn 0.');
            }
            if ($lyDo === '') {
                throw new \RuntimeException('Vui lòng nhập lý do điều chuyển.');
            }
            if (mb_strlen($lyDo) > 200) {
                $lyDo = mb_substr($lyDo, 0, 200);
            }
            $src = ViTriLuuTru::find((string)$d['maViTriNguon']);
            $dst = ViTriLuuTru::find((string)$d['maViTriDich']);
            if (!$src || !$dst) {
                throw new \RuntimeException('Vị trí nguồn hoặc vị trí đích không tồn tại.');
            }
            if ((string)$src['maViTri'] === (string)$dst['maViTri']) {
                throw new \RuntimeException('Vị trí đích phải khác vị trí nguồn.');
            }
            if ((string)$src['maKho'] !== (string)$dst['maKho']) {
                throw new \RuntimeException('Chỉ hỗ trợ điều chuyển trong cùng một kho.');
            }
            if ($scopeKho !== null && $scopeKho !== '' && (string)$src['maKho'] !== $scopeKho) {
                throw new \RuntimeException('Vị trí nguồn không thuộc kho bạn phụ trách.');
            }
            $codes = [(string)$src['maViTri'], (string)$dst['maViTri']];
            sort($codes);
            $locked = [];
            foreach ($codes as $c) {
                $locked[$c] = ViTriLuuTru::lockForUpdate($c);
            }
            $sv = $locked[(string)$src['maViTri']];
            $dv = $locked[(string)$dst['maViTri']];
            if (in_array($sv['trangThai'], ['Đã khóa', 'Đang bảo trì'], true)) {
                throw new \RuntimeException('Vị trí nguồn không ở trạng thái sử dụng.');
            }
            if (in_array($dv['trangThai'], ['Đã khóa', 'Đang bảo trì'], true)) {
                throw new \RuntimeException('Vị trí đích không ở trạng thái sử dụng.');
            }
            if ($sv['khuTrangThai'] !== 'Đang hoạt động' || $sv['khoTrangThai'] !== 'Đang hoạt động') {
                throw new \RuntimeException('Kho hoặc khu vực của vị trí nguồn đã ngừng hoạt động.');
            }
            if ($dv['khuTrangThai'] !== 'Đang hoạt động' || $dv['khoTrangThai'] !== 'Đang hoạt động') {
                throw new \RuntimeException('Kho hoặc khu vực của vị trí đích đã ngừng hoạt động.');
            }
            if (!LoHang::find((string)$d['maLoHang'])) {
                throw new \RuntimeException('Lô hàng không tồn tại.');
            }
            $stock = TonKho::lockRow((string)$d['maLoHang'], (string)$src['maViTri']);
            if (!$stock) {
                throw new \RuntimeException('Lô hàng không có tồn tại vị trí nguồn.');
            }
            $avail = (float)$stock['soLuongTon'] - (float)$stock['soLuongDaGiu'];
            if ($qty > $avail + self::EPS) {
                throw new \RuntimeException('Số lượng vượt quá tồn khả dụng tại vị trí nguồn (' . round(max(0, $avail), 2) . ' kg).');
            }
            $destQ = ViTriLuuTru::quantities((string)$dst['maViTri']);
            $con = (float)$dv['sucChuaToiDa'] - $destQ['daDung'];
            if ($qty > $con + self::EPS) {
                throw new \RuntimeException('Vị trí đích chỉ còn ' . round(max(0, $con), 2) . ' kg, không đủ sức chứa.');
            }
            $code = self::nextCode();
            static::exec("INSERT INTO PhieuDieuChuyen
                (maPhieuDieuChuyen, maLoHang, maViTriNguon, maViTriDich, soLuongDuKien, maNhanVienYeuCau, trangThai, lyDoDieuChuyen)
                VALUES (?, ?, ?, ?, ?, ?, 'Chờ duyệt', ?)", [
                $code, $d['maLoHang'], $d['maViTriNguon'], $d['maViTriDich'], $qty, $staffId, $lyDo
            ]);
            return $code;
        });
    }


    /** Quản lý kho phê duyệt phiếu Chờ duyệt. */
    public static function approve(string $maPhieu, string $managerId, ?string $managerKho): void
    {
        static::transaction(function () use ($maPhieu, $managerId, $managerKho) {
            $p = self::lock($maPhieu);
            if ((string)$p['trangThai'] !== 'Chờ duyệt') {
                throw new \RuntimeException('Chỉ phiếu đang chờ duyệt mới được phê duyệt.');
            }
            if ((string)$p['maNhanVienYeuCau'] === $managerId) {
                throw new \RuntimeException('Bạn không được tự duyệt phiếu do chính mình lập.');
            }
            $row = self::find($maPhieu);
            if (!$row) {
                throw new \RuntimeException('Phiếu điều chuyển không tồn tại.');
            }
            if ($managerKho !== null && $managerKho !== '' && (string)$row['maKhoNguon'] !== $managerKho) {
                throw new \RuntimeException('Phiếu không thuộc kho bạn phụ trách.');
            }
            $codes = [(string)$p['maViTriNguon'], (string)$p['maViTriDich']];
            sort($codes);
            $locked = [];
            foreach ($codes as $c) {
                $locked[$c] = ViTriLuuTru::lockForUpdate($c);
            }
            $stock = TonKho::findRow((string)$p['maLoHang'], (string)$p['maViTriNguon']);
            $avail = $stock ? ((float)$stock['soLuongTon'] - (float)$stock['soLuongDaGiu']) : 0;
            if ($avail + self::EPS < (float)$p['soLuongDuKien']) {
                throw new \RuntimeException('Tồn khả dụng tại vị trí nguồn không còn đủ (' . round(max(0, $avail), 2) . ' kg). Hãy từ chối phiếu.');
            }
            $dv = $locked[(string)$p['maViTriDich']];
            $dq = ViTriLuuTru::quantities((string)$p['maViTriDich']);
            $con = (float)$dv['sucChuaToiDa'] - $dq['daDung'];
            if ((float)$p['soLuongDuKien'] > $con + self::EPS) {
                throw new \RuntimeException('Sức chứa còn lại của vị trí đích không đủ (còn ' . round(max(0, $con), 2) . ' kg). Hãy từ chối phiếu.');
            }
            static::exec("UPDATE PhieuDieuChuyen
                SET trangThai = 'Đã duyệt', maNhanVienDuyet = ?, ngayDuyet = NOW()
                WHERE maPhieuDieuChuyen = ?", [$managerId, $maPhieu]);
        });
    }

    /** Quản lý kho từ chối phiếu, bắt buộc nhập lý do. */
    public static function reject(string $maPhieu, string $managerId, ?string $managerKho, string $lyDo): void
    {
        static::transaction(function () use ($maPhieu, $managerId, $managerKho, $lyDo) {
            $lyDo = trim($lyDo);
            if ($lyDo === '') {
                throw new \RuntimeException('Vui lòng nhập lý do từ chối.');
            }
            if (mb_strlen($lyDo) > 255) {
                $lyDo = mb_substr($lyDo, 0, 255);
            }
            $p = self::lock($maPhieu);
            if ((string)$p['trangThai'] !== 'Chờ duyệt') {
                throw new \RuntimeException('Chỉ phiếu đang chờ duyệt mới được từ chối.');
            }
            if ((string)$p['maNhanVienYeuCau'] === $managerId) {
                throw new \RuntimeException('Bạn không được xử lý phiếu do chính mình lập.');
            }
            $row = self::find($maPhieu);
            if (!$row) {
                throw new \RuntimeException('Phiếu điều chuyển không tồn tại.');
            }
            if ($managerKho !== null && $managerKho !== '' && (string)$row['maKhoNguon'] !== $managerKho) {
                throw new \RuntimeException('Phiếu không thuộc kho bạn phụ trách.');
            }
            static::exec("UPDATE PhieuDieuChuyen
                SET trangThai = 'Đã từ chối', maNhanVienDuyet = ?, ngayDuyet = NOW(), lyDoTuChoi = ?
                WHERE maPhieuDieuChuyen = ?", [$managerId, $lyDo, $maPhieu]);
        });
    }


    /**
     * Thực hiện điều chuyển. Cho phép phiếu Đã duyệt; dữ liệu mẫu cũ (Chờ điều chuyển/Đang thực hiện)
     * vẫn được thực hiện theo yêu cầu demo. Toàn bộ nằm trong một transaction.
     * @return array<string,mixed>
     */
    public static function execute(string $maPhieu, string $staffId, ?string $staffKho, float $qty): array
    {
        return static::transaction(function () use ($maPhieu, $staffId, $staffKho, $qty) {
            $p = self::lock($maPhieu);
            $status = (string)$p['trangThai'];
            if ($status === 'Đã hoàn tất') {
                throw new \RuntimeException('Phiếu này đã được thực hiện, không thể thực hiện lại.');
            }
            if (!in_array($status, ['Đã duyệt', 'Chờ điều chuyển', 'Đang thực hiện'], true)) {
                throw new \RuntimeException('Chỉ phiếu đã được duyệt mới được thực hiện điều chuyển.');
            }
            $row = self::find($maPhieu);
            if (!$row) {
                throw new \RuntimeException('Phiếu điều chuyển không tồn tại.');
            }
            if ($staffKho !== null && $staffKho !== '' && (string)$row['maKhoNguon'] !== $staffKho) {
                throw new \RuntimeException('Phiếu không thuộc kho bạn phụ trách.');
            }
            $planned = (float)$p['soLuongDuKien'];
            $already = (float)($p['soLuongThucChuyen'] ?? 0);
            $remaining = max(0, $planned - $already);
            $qty = round($qty, 2);
            if ($qty <= 0) {
                throw new \RuntimeException('Số lượng thực chuyển phải lớn hơn 0.');
            }
            if ($remaining <= self::EPS) {
                throw new \RuntimeException('Phiếu đã được điều chuyển đủ số lượng.');
            }
            if ($qty > $remaining + self::EPS) {
                throw new \RuntimeException('Số lượng thực chuyển vượt phần còn lại (' . round($remaining, 2) . ' kg).');
            }
            $codes = [(string)$p['maViTriNguon'], (string)$p['maViTriDich']];
            sort($codes);
            $locked = [];
            foreach ($codes as $c) {
                $locked[$c] = ViTriLuuTru::lockForUpdate($c);
            }
            $sv = $locked[(string)$p['maViTriNguon']];
            $dv = $locked[(string)$p['maViTriDich']];
            if (in_array($sv['trangThai'], ['Đã khóa', 'Đang bảo trì'], true)) {
                throw new \RuntimeException('Vị trí nguồn không ở trạng thái sử dụng.');
            }
            if (in_array($dv['trangThai'], ['Đã khóa', 'Đang bảo trì'], true)) {
                throw new \RuntimeException('Vị trí đích không ở trạng thái sử dụng.');
            }
            if ($sv['khuTrangThai'] !== 'Đang hoạt động' || $sv['khoTrangThai'] !== 'Đang hoạt động') {
                throw new \RuntimeException('Kho hoặc khu vực của vị trí nguồn đã ngừng hoạt động.');
            }
            if ($dv['khuTrangThai'] !== 'Đang hoạt động' || $dv['khoTrangThai'] !== 'Đang hoạt động') {
                throw new \RuntimeException('Kho hoặc khu vực của vị trí đích đã ngừng hoạt động.');
            }
            $stock = TonKho::lockRow((string)$p['maLoHang'], (string)$p['maViTriNguon']);
            if (!$stock) {
                throw new \RuntimeException('Không tìm thấy tồn của lô tại vị trí nguồn.');
            }
            $avail = (float)$stock['soLuongTon'] - (float)$stock['soLuongDaGiu'];
            if ($qty > $avail + self::EPS) {
                throw new \RuntimeException('Tồn thực tế tại vị trí nguồn không đủ (còn ' . round(max(0, $avail), 2) . ' kg).');
            }
            $dQ = ViTriLuuTru::quantities((string)$p['maViTriDich']);
            $con = (float)$dv['sucChuaToiDa'] - $dQ['daDung'];
            if ($qty > $con + self::EPS) {
                throw new \RuntimeException('Sức chứa vị trí đích không đủ (còn ' . round(max(0, $con), 2) . ' kg).');
            }
            $destRow = TonKho::lockRow((string)$p['maLoHang'], (string)$p['maViTriDich']);
            $tonNguonTruoc = (float)$stock['soLuongTon'];
            $tonDichTruoc = $destRow ? (float)$destRow['soLuongTon'] : 0.0;
            TonKho::decrement((string)$p['maLoHang'], (string)$p['maViTriNguon'], $qty);
            TonKho::increment((string)$p['maLoHang'], (string)$p['maViTriDich'], $qty);
            $histCodes = LichSuBienDongTonKho::nextCodes(2);
            LichSuBienDongTonKho::record([
                'maBienDong' => $histCodes[0], 'maLoHang' => $p['maLoHang'], 'maViTri' => $p['maViTriNguon'],
                'loaiGiaoDich' => 'Điều chuyển đi', 'soLuongBienDong' => -$qty,
                'soLuongTonTruoc' => $tonNguonTruoc, 'soLuongTonSau' => $tonNguonTruoc - $qty,
                'maChungTuLienQuan' => $maPhieu, 'maNhanVienThucHien' => $staffId,
                'ghiChu' => 'Điều chuyển đến vị trí ' . $p['maViTriDich'],
            ]);
            LichSuBienDongTonKho::record([
                'maBienDong' => $histCodes[1], 'maLoHang' => $p['maLoHang'], 'maViTri' => $p['maViTriDich'],
                'loaiGiaoDich' => 'Điều chuyển đến', 'soLuongBienDong' => $qty,
                'soLuongTonTruoc' => $tonDichTruoc, 'soLuongTonSau' => $tonDichTruoc + $qty,
                'maChungTuLienQuan' => $maPhieu, 'maNhanVienThucHien' => $staffId,
                'ghiChu' => 'Điều chuyển từ vị trí ' . $p['maViTriNguon'],
            ]);
            $cumulative = min($planned, $already + $qty);
            $done = $cumulative + self::EPS >= $planned;
            $newStatus = $done ? 'Đã hoàn tất' : 'Đang thực hiện';
            $completedAt = $done ? date('Y-m-d H:i:s') : null;
            static::exec("UPDATE PhieuDieuChuyen
                SET trangThai = ?, soLuongThucChuyen = ?, maNhanVienThucHien = ?, ngayHoanTat = ?
                WHERE maPhieuDieuChuyen = ?", [$newStatus, $cumulative, $staffId, $completedAt, $maPhieu]);
            ViTriLuuTru::refreshStatus((string)$p['maViTriNguon']);
            ViTriLuuTru::refreshStatus((string)$p['maViTriDich']);
            return ['maPhieu' => $maPhieu, 'maLoHang' => $p['maLoHang'], 'soLuong' => $qty, 'luyKe' => $cumulative, 'conLai' => max(0, $planned - $cumulative), 'hoanTat' => $done,
                'nguoiLap' => $row['maNhanVienYeuCau'], 'nguoiDuyet' => $row['maNhanVienDuyet'],
                'tenNguon' => $row['tenViTriNguon'], 'tenDich' => $row['tenViTriDich']];
        });
    }

    /** Hủy phiếu khi còn Chờ duyệt, chỉ người lập được hủy. */
    public static function cancel(string $maPhieu, string $staffId): void
    {
        static::transaction(function () use ($maPhieu, $staffId) {
            $p = self::lock($maPhieu);
            if ((string)$p['trangThai'] !== 'Chờ duyệt') {
                throw new \RuntimeException('Chỉ hủy được phiếu đang chờ duyệt.');
            }
            if ((string)$p['maNhanVienYeuCau'] !== $staffId) {
                throw new \RuntimeException('Chỉ người lập phiếu được hủy phiếu.');
            }
            static::exec("UPDATE PhieuDieuChuyen SET trangThai = 'Đã hủy' WHERE maPhieuDieuChuyen = ?", [$maPhieu]);
        });
    }
    public static function remainingQuantity(array $p): float
    {
        return max(0, (float)$p['soLuongDuKien'] - (float)($p['soLuongThucChuyen'] ?? 0));
    }

}

