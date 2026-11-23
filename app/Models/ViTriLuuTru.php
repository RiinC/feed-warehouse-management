<?php
namespace App\Models;

use Core\Model;

/**
 * Bảng ViTriLuuTru (mã vị trí, khu vực, tọa độ, sức chứa, trạng thái).
 * Số lượng đang chiếm chỗ = tồn thực tế (TonKho) + đã phân bổ (LuuKho).
 */
class ViTriLuuTru extends Model
{
    private const EPS = 0.000001;

    public static function find(string $maViTri): ?array
    {
        return static::one("SELECT v.*, k.tenKhuVuc, k.maKho, kho.tenKho
            FROM ViTriLuuTru v
            JOIN KhuVuc k ON k.maKhuVuc = v.maKhuVuc
            JOIN Kho kho ON kho.maKho = k.maKho
            WHERE v.maViTri = ?", [$maViTri]);
    }

    /** @return array{ton:float,giu:float,phanBo:float,daDung:float} */
    public static function quantities(string $maViTri): array
    {
        $t = static::one("SELECT IFNULL(SUM(soLuongTon), 0) AS ton, IFNULL(SUM(soLuongDaGiu), 0) AS giu
            FROM TonKho WHERE maViTri = ?", [$maViTri]);
        $l = static::one("SELECT IFNULL(SUM(soLuongPhanBo), 0) AS pb FROM LuuKho WHERE maViTri = ?", [$maViTri]);
        $ton = (float)($t['ton'] ?? 0);
        $giu = (float)($t['giu'] ?? 0);
        $pb = (float)($l['pb'] ?? 0);
        return ['ton' => $ton, 'giu' => $giu, 'phanBo' => $pb, 'daDung' => $ton + $pb];
    }

    /** @return array{0:string,1:array} */
    private static function whereClause(array $f, ?string $scope): array
    {
        $w = ['1 = 1'];
        $p = [];
        if ($scope !== null && $scope !== '') {
            $w[] = 'k.maKho = ?';
            $p[] = $scope;
        }
        if (!empty($f['maKho'])) {
            $w[] = 'k.maKho = ?';
            $p[] = $f['maKho'];
        }
        if (!empty($f['maKhuVuc'])) {
            $w[] = 'v.maKhuVuc = ?';
            $p[] = $f['maKhuVuc'];
        }
        if (!empty($f['q'])) {
            $w[] = '(v.maViTri LIKE ? OR v.tenViTri LIKE ?)';
            $like = '%' . $f['q'] . '%';
            $p[] = $like;
            $p[] = $like;
        }
        if (!empty($f['trangThai'])) {
            $w[] = 'v.trangThai = ?';
            $p[] = $f['trangThai'];
        }
        if (!empty($f['tinhTrang'])) {
            $w[] = $f['tinhTrang'] === 'ngung-su-dung' ? "v.trangThai = 'Đã khóa'" : "v.trangThai <> 'Đã khóa'";
        }
        return [implode(' AND ', $w), $p];
    }

    /** @return array{0:array,1:int} */
    public static function search(array $f, int $page, int $perPage, ?string $scope): array
    {
        [$where, $params] = self::whereClause($f, $scope);
        $total = (int)(static::one("SELECT COUNT(*) AS c
            FROM ViTriLuuTru v
            JOIN KhuVuc k ON k.maKhuVuc = v.maKhuVuc
            JOIN Kho kho ON kho.maKho = k.maKho
            WHERE $where", $params)['c'] ?? 0);
        $sql = "SELECT v.maViTri, v.tenViTri, v.maKhuVuc, v.day, v.ke, v.tang, v.o,
                       v.sucChuaToiDa, v.trangThai, k.tenKhuVuc, k.maKho, kho.tenKho,
                       IFNULL(tk.ton, 0) AS tonThuc, IFNULL(lk.pb, 0) AS phanBo,
                       IFNULL(tk.ton, 0) + IFNULL(lk.pb, 0) AS daDung,
                       GREATEST(0, v.sucChuaToiDa - IFNULL(tk.ton, 0) - IFNULL(lk.pb, 0)) AS conLai
            FROM ViTriLuuTru v
            JOIN KhuVuc k ON k.maKhuVuc = v.maKhuVuc
            JOIN Kho kho ON kho.maKho = k.maKho
            LEFT JOIN (SELECT maViTri, SUM(soLuongTon) AS ton FROM TonKho GROUP BY maViTri) tk
                   ON tk.maViTri = v.maViTri
            LEFT JOIN (SELECT maViTri, SUM(soLuongPhanBo) AS pb FROM LuuKho GROUP BY maViTri) lk
                   ON lk.maViTri = v.maViTri
            WHERE $where
            ORDER BY kho.maKho, k.maKhuVuc, v.maViTri
            LIMIT ? OFFSET ?";
        $p2 = $params;
        $p2[] = $perPage;
        $p2[] = max(0, ($page - 1) * $perPage);
        return [static::all($sql, $p2), $total];
    }


    /** Danh sách vị trí đang sử dụng (có thể chỉ lấy vị trí còn sức chứa). @return array<int,array> */
    public static function positionOptions(?string $scope, bool $onlyAvailable = false, ?string $maKhuVuc = null): array
    {
        $sql = "SELECT v.maViTri, v.tenViTri, v.sucChuaToiDa, v.trangThai, v.day, v.ke, v.tang, v.o,
                       k.maKhuVuc, k.tenKhuVuc, k.maKho, kho.tenKho,
                       IFNULL(tk.ton, 0) + IFNULL(lk.pb, 0) AS daDung
            FROM ViTriLuuTru v
            JOIN KhuVuc k ON k.maKhuVuc = v.maKhuVuc
            JOIN Kho kho ON kho.maKho = k.maKho
            LEFT JOIN (SELECT maViTri, SUM(soLuongTon) AS ton FROM TonKho GROUP BY maViTri) tk
                   ON tk.maViTri = v.maViTri
            LEFT JOIN (SELECT maViTri, SUM(soLuongPhanBo) AS pb FROM LuuKho GROUP BY maViTri) lk
                   ON lk.maViTri = v.maViTri
            WHERE v.trangThai IN ('Đang để trống', 'Đang chứa hàng')
              AND k.trangThai = 'Đang hoạt động' AND kho.trangThai = 'Đang hoạt động'";
        $params = [];
        if ($scope !== null && $scope !== '') {
            $sql .= " AND k.maKho = ?";
            $params[] = $scope;
        }
        $sql .= " ORDER BY kho.maKho, k.maKhuVuc, v.maViTri";
        $rows = static::all($sql, $params);
        if ($onlyAvailable) {
            $rows = array_values(array_filter($rows, function ($r) {
                return (float)$r['daDung'] + self::EPS < (float)$r['sucChuaToiDa'];
            }));
        }
        return $rows;
    }

    /** Khóa dòng vị trí trong transaction và trả kèm thông tin kho/khu vực. */
    public static function lockForUpdate(string $maViTri): array
    {
        $v = static::one("SELECT * FROM ViTriLuuTru WHERE maViTri = ? FOR UPDATE", [$maViTri]);
        if (!$v) {
            throw new \RuntimeException('Vị trí lưu trữ không tồn tại.');
        }
        $k = static::one("SELECT k.maKho, k.tenKhuVuc, k.trangThai AS khuTrangThai,
                kho.tenKho, kho.trangThai AS khoTrangThai
            FROM KhuVuc k JOIN Kho kho ON kho.maKho = k.maKho
            WHERE k.maKhuVuc = ?", [$v['maKhuVuc']]);
        if (!$k) {
            throw new \RuntimeException('Khu vực của vị trí không tồn tại.');
        }
        return array_merge($v, $k);
    }

    public static function existsCoordinates(string $maKhuVuc, string $day, string $ke, string $tang, string $o, ?string $except = null): bool
    {
        $sql = "SELECT maViTri FROM ViTriLuuTru
            WHERE maKhuVuc = ? AND day = ? AND ke = ? AND tang = ? AND o = ?";
        $p = [$maKhuVuc, $day, $ke, $tang, $o];
        if ($except !== null && $except !== '') {
            $sql .= " AND maViTri <> ?";
            $p[] = $except;
        }
        return (bool)static::one($sql . " LIMIT 1", $p);
    }

    public static function nextCode(string $day, string $ke, string $tang, string $o): string
    {
        $r = static::one("SELECT maViTri AS m FROM ViTriLuuTru ORDER BY CAST(RIGHT(maViTri, 4) AS UNSIGNED) DESC LIMIT 1 FOR UPDATE");
        $seq = ($r ? ((int)substr((string)$r['m'], -4)) : 0) + 1;
        if ($seq > 9999) {
            throw new \RuntimeException('Đã hết dãy mã vị trí (tối đa 9999).');
        }
        return 'VT' . $day . $ke . $tang . $o . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }

    /** Tạo vị trí mới; mã sinh tự động theo tọa độ + số thứ tự, trạng thái Đang để trống. */
    public static function create(array $d): string
    {
        for ($i = 0; $i < 5; $i++) {
            $code = self::nextCode($d['day'], $d['ke'], $d['tang'], $d['o']);
            try {
                static::exec("INSERT INTO ViTriLuuTru
                    (maViTri, tenViTri, maKhuVuc, day, ke, tang, o, sucChuaToiDa, trangThai)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Đang để trống')", [
                    $code, $d['tenViTri'], $d['maKhuVuc'], $d['day'], $d['ke'], $d['tang'], $d['o'], $d['sucChuaToiDa']
                ]);
                return $code;
            } catch (\mysqli_sql_exception $ex) {
                if ((int)$ex->getCode() === 1062) {
                    if (self::existsCoordinates($d['maKhuVuc'], $d['day'], $d['ke'], $d['tang'], $d['o'])) {
                        throw new \RuntimeException('Tọa độ (dãy/kệ/tầng/ô) đã có vị trí trong khu vực này.');
                    }
                    continue;
                }
                throw $ex;
            }
        }
        throw new \RuntimeException('Không sinh được mã vị trí, vui lòng thử lại.');
    }


    /** Cập nhật tên và sức chứa. Không cho giảm thấp hơn lượng đang lưu trữ. */
    public static function updateInfo(string $maViTri, string $tenViTri, float $sucChua): void
    {
        static::transaction(function () use ($maViTri, $tenViTri, $sucChua) {
            self::lockForUpdate($maViTri);
            if ($sucChua <= 0) {
                throw new \RuntimeException('Sức chứa phải lớn hơn 0.');
            }
            $q = self::quantities($maViTri);
            if ($sucChua + self::EPS < $q['daDung']) {
                throw new \RuntimeException('Sức chứa mới (' . round($sucChua, 2) . ' kg) thấp hơn số lượng đang lưu trữ (' . round($q['daDung'], 2) . ' kg).');
            }
            static::exec("UPDATE ViTriLuuTru SET tenViTri = ?, sucChuaToiDa = ? WHERE maViTri = ?", [$tenViTri, $sucChua, $maViTri]);
            self::refreshStatus($maViTri);
        });
    }

    /** Cập nhật trạng thái theo lượng đang chiếm chỗ (bỏ qua vị trí đang bảo trì/đã khóa). */
    public static function refreshStatus(string $maViTri): void
    {
        $v = static::one("SELECT sucChuaToiDa, trangThai FROM ViTriLuuTru WHERE maViTri = ?", [$maViTri]);
        if (!$v) {
            return;
        }
        if (in_array($v['trangThai'], ['Đang bảo trì', 'Đã khóa'], true)) {
            return;
        }
        $used = self::quantities($maViTri)['daDung'];
        if ($used <= self::EPS) {
            $new = 'Đang để trống';
        } elseif ($used + self::EPS >= (float)$v['sucChuaToiDa']) {
            $new = 'Đã lấp đầy';
        } else {
            $new = 'Đang chứa hàng';
        }
        if ($new !== $v['trangThai']) {
            static::exec("UPDATE ViTriLuuTru SET trangThai = ? WHERE maViTri = ?", [$new, $maViTri]);
        }
    }

    /** Trả về lý do không thể ngừng sử dụng (null nếu có thể ngừng). */
    public static function blockingReason(string $maViTri): ?string
    {
        $q = self::quantities($maViTri);
        if ($q['ton'] > self::EPS) {
            return 'Vị trí đang chứa ' . round($q['ton'], 2) . ' kg hàng. Không thể ngừng sử dụng.';
        }
        if ($q['giu'] > self::EPS) {
            return 'Vị trí đang giữ chỗ ' . round($q['giu'], 2) . ' kg hàng cho xuất kho. Không thể ngừng sử dụng.';
        }
        if ($q['phanBo'] > self::EPS) {
            return 'Vị trí đang có ' . round($q['phanBo'], 2) . ' kg hàng được phân bổ. Không thể ngừng sử dụng.';
        }
        $n = (int)(static::one("SELECT COUNT(*) AS c FROM PhieuDieuChuyen
            WHERE (maViTriNguon = ? OR maViTriDich = ?)
              AND trangThai IN ('Chờ duyệt', 'Đã duyệt', 'Chờ điều chuyển', 'Đang thực hiện')", [$maViTri, $maViTri])['c'] ?? 0);
        if ($n > 0) {
            return 'Vị trí đang có phiếu điều chuyển chưa hoàn tất.';
        }
        $n = (int)(static::one("SELECT COUNT(*) AS c FROM ChiTietPhieuNhapKho c
            JOIN PhieuNhapKho p ON p.maPhieuNhapKho = c.maPhieuNhapKho
            WHERE c.maViTri = ? AND p.trangThai = 'Đang xếp kệ'", [$maViTri])['c'] ?? 0);
        if ($n > 0) {
            return 'Vị trí đang có phiếu nhập kho chưa hoàn tất.';
        }
        $n = (int)(static::one("SELECT COUNT(*) AS c FROM ChiTietPhieuXuatKho c
            JOIN PhieuXuatKho p ON p.maPhieuXuat = c.maPhieuXuat
            WHERE c.maViTri = ? AND p.trangThai = 'Đang bốc hàng'", [$maViTri])['c'] ?? 0);
        if ($n > 0) {
            return 'Vị trí đang có phiếu xuất kho chưa hoàn tất.';
        }
        $n = (int)(static::one("SELECT COUNT(*) AS c FROM DeXuatLoHang WHERE maViTri = ? AND trangThai = 'Đã giữ chỗ'", [$maViTri])['c'] ?? 0);
        if ($n > 0) {
            return 'Vị trí đang giữ chỗ cho yêu cầu xuất kho.';
        }
        $n = (int)(static::one("SELECT COUNT(*) AS c FROM PhieuTiepNhan
            WHERE maViTriTam = ? AND trangThai IN ('Chờ kiểm định', 'Đang kiểm định')", [$maViTri])['c'] ?? 0);
        if ($n > 0) {
            return 'Vị trí đang được dùng làm vị trí tạm cho phiếu tiếp nhận chưa xong.';
        }
        return null;
    }

    /** Xóa mềm: chuyển trạng thái Đã khóa sau khi kiểm tra toàn bộ ràng buộc. */
    public static function stop(string $maViTri): void
    {
        static::transaction(function () use ($maViTri) {
            $v = self::lockForUpdate($maViTri);
            if ($v['trangThai'] === 'Đã khóa') {
                throw new \RuntimeException('Vị trí này đã ngừng sử dụng trước đó.');
            }
            $reason = self::blockingReason($maViTri);
            if ($reason !== null) {
                throw new \RuntimeException($reason);
            }
            static::exec("UPDATE ViTriLuuTru SET trangThai = 'Đã khóa' WHERE maViTri = ?", [$maViTri]);
        });
    }

    /** Tên gợi nhớ mặc định theo tọa độ. */
    public static function defaultName(string $day, string $ke, string $tang, string $o): string
    {
        if ($ke === '00' && $tang === '00') {
            return 'Sàn - Ô ' . $o;
        }
        return 'Kệ ' . $ke . ' - Tầng ' . $tang . ' - Ô ' . $o;
    }

    /** Các lô đang có tồn thực tế tại vị trí. */
    public static function lotsAtPosition(string $maViTri): array
    {
        return static::all("SELECT t.maLoHang, t.soLuongTon, t.soLuongDaGiu, t.ngayCapNhatCuoi,
                l.maHangHoa, hh.tenHangHoa, hh.donViTinhChuan, l.ngayHetHan, l.trangThaiQC
            FROM TonKho t
            JOIN LoHang l ON l.maLoHang = t.maLoHang
            JOIN HangHoa hh ON hh.maHangHoa = l.maHangHoa
            WHERE t.maViTri = ?
            ORDER BY l.ngayHetHan, t.maLoHang", [$maViTri]);
    }
}

