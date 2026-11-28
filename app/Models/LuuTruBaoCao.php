<?php
namespace App\Models;

use Core\Model;
use DomainException;
use RuntimeException;

/** Lưu bản chụp báo cáo UC30 bằng JSON. Không ghi vào CSDL. */
final class LuuTruBaoCao extends Model
{
    private const COT = ['tonDauKy', 'nhapTrongKy', 'xuatTrongKy', 'tonCuoiKy'];

    private static function nhanVien(string $ma): array
    {
        $nv = static::one("SELECT maNhanVien, hoTen, vaiTro, maKho FROM NhanVien
            WHERE maNhanVien = ? AND trangThai = 'Đang làm việc'
            AND vaiTro IN ('Quản lý kho', 'Ban giám đốc')", [$ma]);
        if (!$nv || ($nv['vaiTro'] === 'Quản lý kho' && empty($nv['maKho']))) {
            throw new DomainException('Bạn không có quyền truy cập báo cáo đã gửi.');
        }
        return $nv;
    }

    /** Mặc định XAMPP: C:/xampp/feedware-private-storage/<mã dự án>/bao-cao-ton-kho.
     * Có thể cấu hình FEEDWARE_REPORT_STORAGE bằng đường dẫn tuyệt đối ngoài web root.
     */
    private static function thuMuc(): string
    {
        $root = dirname(__DIR__, 2);
        $dir = getenv('FEEDWARE_REPORT_STORAGE');
        if ($dir === false || $dir === '') {
            $dir = dirname($root, 2) . '/feedware-private-storage/'
                . substr(hash('sha256', $root), 0, 16) . '/bao-cao-ton-kho';
        }
        if (!preg_match('~^(?:/|[A-Za-z]:[\\\\/])~', $dir)) {
            throw new RuntimeException('Đường dẫn lưu báo cáo phải là đường dẫn tuyệt đối.');
        }
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Không tạo được thư mục lưu báo cáo.');
        }
        $real = realpath($dir);
        if ($real === false || !is_writable($real)) throw new RuntimeException('Thư mục lưu báo cáo không khả dụng.');
        $normalize = static function ($p) {
            $p = rtrim(str_replace('\\', '/', $p), '/');
            return DIRECTORY_SEPARATOR === '\\' ? strtolower($p) : $p;
        };
        // Chặn cả project root (XAMPP có thể phục vụ toàn bộ htdocs).
        foreach ([$root, $_SERVER['DOCUMENT_ROOT'] ?? ''] as $web) {
            if ($web === '' || !($web = realpath($web))) continue;
            $a = $normalize($real); $b = $normalize($web);
            if ($a === $b || str_starts_with($a, $b . '/')) {
                throw new RuntimeException('Thư mục báo cáo phải nằm ngoài thư mục web.');
            }
        }
        return $real;
    }

    /** Một khóa chung cho đọc/ghi, tạo mã và kiểm tra trùng. */
    private static function khoa(bool $ghi, callable $fn)
    {
        $dir = self::thuMuc();
        $fp = fopen($dir . '/.lock', 'c+b');
        if (!$fp) throw new RuntimeException('Không mở được khóa lưu trữ báo cáo.');
        try {
            if (!flock($fp, $ghi ? LOCK_EX : LOCK_SH)) throw new RuntimeException('Không khóa được lưu trữ báo cáo.');
            return $fn($dir);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    private static function maHopLe(string $ma): bool
    {
        return preg_match('/\ABC[0-9]{6}\z/', $ma) === 1;
    }

    private static function doc(string $dir, string $ma): array
    {
        if (!self::maHopLe($ma)) throw new DomainException('Mã báo cáo không hợp lệ.');
        $path = $dir . '/' . $ma . '.json';
        if (!is_file($path) || is_link($path)) throw new DomainException('Không tìm thấy báo cáo.');
        $raw = file_get_contents($path);
        if ($raw === false) throw new RuntimeException('Không đọc được báo cáo.');
        $bc = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($bc) || ($bc['maBaoCao'] ?? null) !== $ma || !isset($bc['maKho'], $bc['baoCao'], $bc['nguoiDaXem'])) {
            throw new RuntimeException('File báo cáo không hợp lệ.');
        }
        return $bc;
    }

    private static function ghi(string $dir, array $bc): void
    {
        $json = json_encode($bc, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        $tmp = tempnam($dir, 'bc-');
        if ($tmp === false) throw new RuntimeException('Không tạo được file tạm.');
        try {
            @chmod($tmp, 0600);
            if (file_put_contents($tmp, $json) !== strlen($json)) throw new RuntimeException('Không ghi đủ dữ liệu báo cáo.');
            // Không xóa file đích trước khi đổi tên; nếu lỗi, giữ nguyên bản cũ.
            if (!@rename($tmp, $dir . '/' . $bc['maBaoCao'] . '.json')) throw new RuntimeException('Không lưu được báo cáo.');
        } finally {
            if (is_file($tmp)) @unlink($tmp);
        }
    }

    private static function danhSachMa(string $dir): array
    {
        $files = glob($dir . '/BC*.json');
        if ($files === false) throw new RuntimeException('Không đọc được thư mục báo cáo.');
        $ma = [];
        foreach ($files as $file) {
            $id = basename($file, '.json');
            if (self::maHopLe($id)) $ma[] = $id;
        }
        rsort($ma, SORT_STRING);
        return $ma;
    }

    /** Chỉ nhận snapshot do Model BaoCao tạo trên server, không nhận số liệu POST.
     * Controller phải giữ bản chụp trong session và kiểm tra CSRF trước khi gọi.
     */
    public static function guiBaoCao(string $maNhanVien, array $baoCao, string $ghiChu = ''): array
    {
        $nv = self::nhanVien($maNhanVien);
        $maKho = $baoCao['kho']['maKho'] ?? '';
        if ($nv['vaiTro'] !== 'Quản lý kho' || $maKho !== $nv['maKho']) {
            throw new DomainException('Bạn chỉ được gửi báo cáo của kho phụ trách.');
        }
        if (empty($baoCao['danhSach']) || !is_array($baoCao['danhSach'])) throw new DomainException('Không có dữ liệu báo cáo để gửi.');
        if (!preg_match('//u', $ghiChu) || preg_match_all('/./us', $ghiChu) > 500) throw new DomainException('Ghi chú tối đa 500 ký tự.');
        if (!isset($baoCao['thoiGianKetThuc'], $baoCao['tongCong'], $baoCao['canhBao'], $baoCao['coCanhBao'])) throw new DomainException('Bản báo cáo không hợp lệ.');
        foreach ($baoCao['danhSach'] as $dong) {
            foreach (self::COT as $cot) {
                if (!array_key_exists($cot, $dong) || ($dong[$cot] !== null && (!is_numeric($dong[$cot]) || !is_finite((float)$dong[$cot])))) {
                    throw new DomainException('Số liệu báo cáo không hợp lệ.');
                }
            }
        }
        // Cảnh báo vẫn lưu nguyên trong snapshot, người nhận thấy rõ dữ liệu thiếu.
        $ghiChu = trim($ghiChu);
        $fingerprint = hash('sha256', json_encode([$maNhanVien, $maKho, $baoCao, $ghiChu], JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR));
        return self::khoa(true, static function ($dir) use ($nv, $baoCao, $maKho, $ghiChu, $fingerprint) {
            $ids = self::danhSachMa($dir);
            foreach ($ids as $id) {
                $old = self::doc($dir, $id);
                if (($old['dauVanTay'] ?? '') === $fingerprint) return $old;
            }
            // Không tái sử dụng mã của báo cáo đã thu hồi.
            $max = $ids ? (int)substr($ids[0], 2) : 0;
            $daXoa = glob($dir . '/BC*.withdrawn');
            if ($daXoa === false) throw new RuntimeException('Không đọc được lịch sử thu hồi.');
            foreach ($daXoa as $file) {
                $id = basename($file, '.withdrawn');
                if (self::maHopLe($id)) $max = max($max, (int)substr($id, 2));
            }
            $next = $max + 1;
            if ($next > 999999) throw new RuntimeException('Đã hết dải mã báo cáo.');
            $bc = [
                'maBaoCao' => sprintf('BC%06d', $next),
                'maKho' => $maKho,
                'tenKho' => $baoCao['kho']['tenKho'] ?? '',
                'tenBaoCao' => 'Báo cáo tồn kho - ' . ($baoCao['kho']['tenKho'] ?? $maKho),
                'maNhanVienGui' => $nv['maNhanVien'], 'tenNhanVienGui' => $nv['hoTen'],
                'tuNgay' => $baoCao['tuNgay'] ?? '', 'denNgay' => $baoCao['denNgay'] ?? '',
                'thoiGianGui' => (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s'),
                'ghiChu' => $ghiChu, 'baoCao' => $baoCao,
                'nguoiDaXem' => [], 'dauVanTay' => $fingerprint
            ];
            self::ghi($dir, $bc);
            return $bc;
        });
    }

    private static function duocXem(array $nv, array $bc): bool
    {
        return $nv['vaiTro'] === 'Ban giám đốc' || $nv['maKho'] === $bc['maKho'];
    }

    private static function trangThai(array $nv, array $bc): string
    {
        if ($nv['vaiTro'] === 'Ban giám đốc') return isset($bc['nguoiDaXem'][$nv['maNhanVien']]) ? 'Đã xem' : 'Chưa xem';
        return $bc['nguoiDaXem'] ? 'Đã xem' : 'Đã gửi';
    }

    /** Trả về metadata danh sách; Controller lọc và phân trang. */
    public static function layDanhSach(string $maNhanVien): array
    {
        $nv = self::nhanVien($maNhanVien);
        return self::khoa(false, static function ($dir) use ($nv) {
            $ds = [];
            foreach (self::danhSachMa($dir) as $id) {
                $bc = self::doc($dir, $id);
                if (!self::duocXem($nv, $bc)) continue;
                $bc['trangThai'] = self::trangThai($nv, $bc);
                unset($bc['baoCao'], $bc['dauVanTay'], $bc['nguoiDaXem']);
                $ds[] = $bc;
            }
            return $ds;
        });
    }

    /** Đọc snapshot; không đánh dấu xem bằng GET. */
    public static function layChiTiet(string $maNhanVien, string $maBaoCao): array
    {
        $nv = self::nhanVien($maNhanVien);
        return self::khoa(false, static function ($dir) use ($nv, $maBaoCao) {
            $bc = self::doc($dir, $maBaoCao);
            if (!self::duocXem($nv, $bc)) throw new DomainException('Không tìm thấy báo cáo hoặc bạn không có quyền xem.');
            $bc['trangThai'] = self::trangThai($nv, $bc);
            unset($bc['dauVanTay']);
            return $bc;
        });
    }

    /** Thu hồi báo cáo chưa được xem. Controller phải kiểm tra POST và CSRF.
     * Cùng khóa độc quyền với danhDauDaXem để tránh xóa sau khi đã ghi nhận xem.
     */
    public static function xoaBaoCao(string $maNhanVien, string $maBaoCao): void
    {
        $nv = self::nhanVien($maNhanVien);
        if ($nv['vaiTro'] !== 'Quản lý kho') {
            throw new DomainException('Bạn không có quyền xóa báo cáo.');
        }
        self::khoa(true, static function ($dir) use ($nv, $maBaoCao) {
            $bc = self::doc($dir, $maBaoCao);
            if ($bc['maKho'] !== $nv['maKho'] || ($bc['maNhanVienGui'] ?? null) !== $nv['maNhanVien']) {
                throw new DomainException('Bạn chỉ được xóa báo cáo do mình gửi thuộc kho phụ trách.');
            }
            if (!is_array($bc['nguoiDaXem']) || $bc['nguoiDaXem'] !== []) {
                throw new DomainException('Báo cáo đã được xem hoặc không xác định được trạng thái xem. Không thể xóa.');
            }
            // Thu hồi khỏi danh sách, giữ bản nội bộ để truy vết và tránh trùng mã.
            $dich = $dir . '/' . $maBaoCao . '.withdrawn';
            if (file_exists($dich) || is_link($dich) || !@rename($dir . '/' . $maBaoCao . '.json', $dich)) {
                throw new RuntimeException('Không thể xóa báo cáo.');
            }
        });
    }

    /** Controller gọi qua POST có CSRF sau khi người nhận mở báo cáo. */
    public static function danhDauDaXem(string $maNhanVien, string $maBaoCao): void
    {
        $nv = self::nhanVien($maNhanVien);
        if ($nv['vaiTro'] !== 'Ban giám đốc') throw new DomainException('Bạn không có quyền đánh dấu báo cáo đã xem.');
        self::khoa(true, static function ($dir) use ($nv, $maBaoCao) {
            $bc = self::doc($dir, $maBaoCao);
            if (!isset($bc['nguoiDaXem'][$nv['maNhanVien']])) {
                $bc['nguoiDaXem'][$nv['maNhanVien']] = [
                    'hoTen' => $nv['hoTen'],
                    'thoiGianXem' => (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s')
                ];
                self::ghi($dir, $bc);
            }
        });
    }
}
