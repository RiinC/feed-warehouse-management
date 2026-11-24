<?php
namespace App\Models;

use Core\Model;

/**
 * Bảng HangHoa (maHangHoa, tenHangHoa, loaiHangHoa, donViTinhChuan, heSoQuyDoi, moTa, dieuKienBaoQuan,
 * hanSuDungTieuChuanNgay, trangThai). UC01 thêm · UC02 sửa · UC03 ngừng sử dụng · UC04 tra cứu.
 *
 * Quy ước hiển thị theo tài liệu UC: "Đang sử dụng" = trangThai 'Đang kinh doanh'; "Ngừng sử dụng" = 'Đã ngừng sử dụng'.
 * CSDL không có cột lý do/ngày ngừng sử dụng, nên UC03 ghi chúng vào cuối cột moTa dưới dạng
 * " [Ngừng sử dụng dd/mm/yyyy HH:ii: lý do]" (xem stopNote()/stripStopNote()); kích hoạt lại sẽ bỏ phần này.
 */
class HangHoa extends Model
{
    public const TT_DANG_DUNG = 'Đang kinh doanh';
    public const TT_NGUNG = 'Đã ngừng sử dụng';

    /** Nhóm hàng (loaiHangHoa) -> mã nhóm 2 số dùng trong mã hàng HHxx000000 */
    public const LOAI = ['Nguyên liệu' => '01', 'Thành phẩm' => '02', 'Bán thành phẩm' => '03', 'Bao bì' => '04', 'Phụ gia' => '05'];
    public const DON_VI = ['kg', 'bao', 'thùng'];
    public const MO_TA_MAX = 255;

    public static function statusLabel(?string $tt): string
    {
        return $tt === self::TT_NGUNG ? 'Ngừng sử dụng' : 'Đang sử dụng';
    }

    /** Tùy chọn lọc trạng thái: giá trị CSDL => nhãn hiển thị. */
    public static function statusOptions(): array
    {
        return [self::TT_DANG_DUNG => 'Đang sử dụng', self::TT_NGUNG => 'Ngừng sử dụng'];
    }

    /* ------------------------------ Tra cứu (UC04) ------------------------------ */

    public static function escapeLike(string $s): string
    {
        return addcslashes($s, '%_\\');
    }

    private static function where(array $f): array
    {
        $w = ['1 = 1'];
        $p = [];
        if (($f['q'] ?? '') !== '') {
            $like = '%' . self::escapeLike($f['q']) . '%';
            $w[] = '(h.maHangHoa LIKE ? OR h.tenHangHoa LIKE ?)';
            $p[] = $like;
            $p[] = $like;
        }
        if (($f['loai'] ?? '') !== '') {
            $w[] = 'h.loaiHangHoa = ?';
            $p[] = $f['loai'];
        }
        if (($f['trangThai'] ?? '') !== '') {
            $w[] = 'h.trangThai = ?';
            $p[] = $f['trangThai'];
        }
        return [implode(' AND ', $w), $p];
    }

    public static function count(array $f): int
    {
        [$where, $p] = self::where($f);
        $r = static::one("SELECT COUNT(*) AS c FROM HangHoa h WHERE $where", $p);
        return (int)($r['c'] ?? 0);
    }

    /** Danh sách hàng hóa kèm số lượng tồn (kg) cộng từ TonKho của mọi lô. */
    public static function search(array $f, int $limit, int $offset): array
    {
        [$where, $p] = self::where($f);
        $p[] = $limit;
        $p[] = $offset;
        return static::all(
            "SELECT h.*, COALESCE(t.ton, 0) AS soLuongTon
             FROM HangHoa h
             LEFT JOIN (SELECT l.maHangHoa, SUM(tk.soLuongTon) AS ton
                        FROM LoHang l JOIN TonKho tk ON tk.maLoHang = l.maLoHang
                        GROUP BY l.maHangHoa) t ON t.maHangHoa = h.maHangHoa
             WHERE $where
             ORDER BY h.maHangHoa
             LIMIT ? OFFSET ?",
            $p
        );
    }

    /** Một hàng hóa kèm số lượng tồn, lần nhập và lần xuất gần nhất; null nếu không có. */
    public static function find(string $ma): ?array
    {
        $row = static::one('SELECT * FROM HangHoa WHERE maHangHoa = ?', [$ma]);
        if (!$row) {
            return null;
        }
        $ton = static::one(
            'SELECT COALESCE(SUM(tk.soLuongTon), 0) AS ton
             FROM LoHang l JOIN TonKho tk ON tk.maLoHang = l.maLoHang WHERE l.maHangHoa = ?',
            [$ma]
        );
        $mov = static::one(
            "SELECT MAX(CASE WHEN b.loaiGiaoDich = 'Nhập kho' THEN b.thoiGianPhatSinh END) AS lanNhapCuoi,
                    MAX(CASE WHEN b.loaiGiaoDich = 'Xuất kho' THEN b.thoiGianPhatSinh END) AS lanXuatCuoi
             FROM LichSuBienDongTonKho b JOIN LoHang l ON l.maLoHang = b.maLoHang
             WHERE l.maHangHoa = ?",
            [$ma]
        );
        $row['soLuongTon'] = (float)($ton['ton'] ?? 0);
        $row['lanNhapCuoi'] = $mov['lanNhapCuoi'] ?? null;
        $row['lanXuatCuoi'] = $mov['lanXuatCuoi'] ?? null;
        return $row;
    }

    /* ------------------------------ Thêm / sửa (UC01, UC02) ------------------------------ */

    /**
     * Thêm hàng hóa mới, mã tự sinh dạng HH + mã nhóm (2 số) + số thứ tự 6 số. Trả về mã hàng mới.
     * @throws \RuntimeException khi không cấp được mã (UC01 ngoại lệ 8.2)
     */
    public static function create(array $d): string
    {
        return static::transaction(function () use ($d) {
            $prefix = 'HH' . self::LOAI[$d['loaiHangHoa']];
            $row = static::one('SELECT MAX(maHangHoa) AS m FROM HangHoa WHERE maHangHoa LIKE ? FOR UPDATE', [$prefix . '%']);
            $seq = !empty($row['m']) ? (int)substr((string)$row['m'], 4) + 1 : 1;
            if ($seq > 999999) {
                throw new \RuntimeException('Không thể tạo Mã hàng tự động. Vui lòng thử lại sau');
            }
            $ma = $prefix . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);
            static::exec(
                'INSERT INTO HangHoa (maHangHoa, tenHangHoa, loaiHangHoa, donViTinhChuan, heSoQuyDoi, moTa, dieuKienBaoQuan, hanSuDungTieuChuanNgay, trangThai)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$ma, $d['tenHangHoa'], $d['loaiHangHoa'], $d['donViTinhChuan'], (float)$d['heSoQuyDoi'],
                 $d['moTa'], $d['dieuKienBaoQuan'], $d['hanSuDungTieuChuanNgay'], self::TT_DANG_DUNG]
            );
            return $ma;
        });
    }

    /** Cập nhật thông tin hàng hóa (mã hàng không đổi). */
    public static function update(string $ma, array $d): void
    {
        static::exec(
            'UPDATE HangHoa SET tenHangHoa = ?, loaiHangHoa = ?, donViTinhChuan = ?, heSoQuyDoi = ?, moTa = ?,
                    dieuKienBaoQuan = ?, hanSuDungTieuChuanNgay = ?, trangThai = ?
             WHERE maHangHoa = ?',
            [$d['tenHangHoa'], $d['loaiHangHoa'], $d['donViTinhChuan'], (float)$d['heSoQuyDoi'], $d['moTa'],
             $d['dieuKienBaoQuan'], $d['hanSuDungTieuChuanNgay'], $d['trangThai'], $ma]
        );
    }

    /* ------------------------------ Ngừng sử dụng (UC03) ------------------------------ */

    /** Các nghiệp vụ chưa hoàn tất liên quan tới hàng hóa; rỗng nếu được phép ngừng sử dụng (UC03 ngoại lệ 8.1). */
    public static function stopBlockers(string $ma): array
    {
        $checks = [
            ['phiếu giao hàng nhà cung cấp chưa tiếp nhận xong',
             "SELECT COUNT(DISTINCT p.maPhieuGiao) AS c FROM ChiTietPhieuGiaoNCC c JOIN PhieuGiaoNCC p ON p.maPhieuGiao = c.maPhieuGiao
              WHERE c.maHangHoa = ? AND p.trangThai IN ('Chờ tiếp nhận', 'Đang tiếp nhận')"],
            ['yêu cầu nhập kho chưa hoàn tất',
             "SELECT COUNT(DISTINCT y.maYeuCauNhap) AS c FROM ChiTietYeuCauNhapKho c JOIN PhieuYeuCauNhapKho y ON y.maYeuCauNhap = c.maYeuCauNhap
              WHERE c.maHangHoa = ? AND y.trangThai IN ('Chờ xử lý', 'Đang nhập kho')"],
            ['yêu cầu xuất kho chưa hoàn tất',
             "SELECT COUNT(DISTINCT y.maYeuCauXuat) AS c FROM ChiTietYeuCauXuatKho c JOIN PhieuYeuCauXuatKho y ON y.maYeuCauXuat = c.maYeuCauXuat
              WHERE c.maHangHoa = ? AND y.trangThai IN ('Chờ duyệt', 'Đã duyệt', 'Đang giữ chỗ', 'Đang xuất')"],
            ['đơn hàng chưa xuất đủ',
             "SELECT COUNT(DISTINCT d.maDonHang) AS c FROM ChiTietDonHang c JOIN DonHang d ON d.maDonHang = c.maDonHang
              WHERE c.maHangHoa = ? AND d.trangThai IN ('Chờ xuất kho', 'Đang xuất kho')"],
            ['kế hoạch sản xuất chưa hoàn thành',
             "SELECT COUNT(DISTINCT k.maKeHoach) AS c FROM ChiTietKeHoachSanXuat c JOIN KeHoachSanXuat k ON k.maKeHoach = c.maKeHoach
              WHERE c.maHangHoa = ? AND k.trangThai IN ('Chờ cấp nguyên liệu', 'Đang sản xuất')"],
            ['lô hàng đang chờ kiểm tra chất lượng',
             "SELECT COUNT(*) AS c FROM LoHang WHERE maHangHoa = ? AND trangThaiQC IN ('Chờ kiểm tra', 'Chờ tái kiểm')
              AND trangThaiVongDoi NOT IN ('Đã thanh lý', 'Đã tiêu hủy')"],
        ];
        $out = [];
        foreach ($checks as [$label, $sql]) {
            $n = (int)(static::one($sql, [$ma])['c'] ?? 0);
            if ($n > 0) {
                $out[] = $n . ' ' . $label;
            }
        }
        return $out;
    }

    /** Chuyển hàng hóa sang "Ngừng sử dụng", ghi ngày/giờ và lý do vào cuối mô tả. */
    public static function stop(string $ma, string $thoiDiem, string $lyDo): void
    {
        static::transaction(function () use ($ma, $thoiDiem, $lyDo) {
            $row = static::one('SELECT moTa FROM HangHoa WHERE maHangHoa = ? FOR UPDATE', [$ma]);
            $base = self::stripStopNote((string)($row['moTa'] ?? ''));
            static::exec(
                'UPDATE HangHoa SET trangThai = ?, moTa = ? WHERE maHangHoa = ?',
                [self::TT_NGUNG, self::joinStopNote($base, $thoiDiem, $lyDo), $ma]
            );
        });
    }

    /** Ghép mô tả gốc với ghi chú ngừng sử dụng, bảo đảm tổng độ dài không vượt MO_TA_MAX. */
    public static function joinStopNote(string $base, string $thoiDiem, string $lyDo): string
    {
        $head = ' [Ngừng sử dụng ' . date('d/m/Y H:i', (int)strtotime($thoiDiem)) . ': ';
        $lyDo = str_replace(['[', ']'], ['(', ')'], preg_replace('/\s+/u', ' ', $lyDo));
        $lyDo = mb_substr($lyDo, 0, 150);
        $note = $head . $lyDo . ']';
        $base = mb_substr($base, 0, max(0, self::MO_TA_MAX - mb_strlen($note)));
        return rtrim($base) . $note;
    }

    /** Bỏ ghi chú ngừng sử dụng khỏi mô tả (để hiển thị/sửa mô tả gốc). */
    public static function stripStopNote(?string $moTa): string
    {
        return rtrim((string)preg_replace('/\s*\[Ngừng sử dụng [^\]]*\]\s*$/u', '', (string)$moTa));
    }

    /** Phần ghi chú ngừng sử dụng ở cuối mô tả (gồm khoảng trắng đầu); chuỗi rỗng nếu không có. */
    public static function stopSuffix(?string $moTa): string
    {
        $moTa = (string)$moTa;
        return self::stopNote($moTa) === null ? '' : mb_substr($moTa, mb_strlen(self::stripStopNote($moTa)));
    }

    /** [thời điểm, lý do] đã ghi ở cuối mô tả; null nếu không có. */
    public static function stopNote(?string $moTa): ?array
    {
        if (preg_match('/\[Ngừng sử dụng (\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}): ([^\]]*)\]\s*$/u', (string)$moTa, $m)) {
            return [trim($m[1]), trim($m[2])];
        }
        return null;
    }
}
