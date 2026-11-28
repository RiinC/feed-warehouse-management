<?php
namespace App\Models;

use Core\Auth;
use Core\Model;

/**
 * Phạm vi dữ liệu theo kho (đơn vị) của người dùng đang đăng nhập.
 *
 * Quản lý kho và Nhân viên kho chỉ thao tác trên dữ liệu thuộc kho của mình (NhanVien.maKho): tồn kho, lô hàng,
 * vị trí lưu trữ. Các vai trò khác (Quản trị viên, Ban giám đốc, QC) không bị giới hạn theo kho.
 * Kho của một dữ liệu được xác định qua vị trí lưu trữ: ViTriLuuTru -> KhuVuc.maKho.
 */
class PhamViKho extends Model
{
    /** @var string|null|false cache trong một request; false = chưa tính */
    private static $cache = false;

    /** Vai trò bị giới hạn theo kho. */
    public static function limited(): bool
    {
        return in_array(Auth::role(), [ROLE_MANAGER, ROLE_WAREHOUSE], true);
    }

    /**
     * Mã kho được phép thao tác.
     *  - null : không giới hạn theo kho (quản trị viên, ban giám đốc, QC)
     *  - ''   : vai trò bị giới hạn nhưng chưa được gán kho → không thấy dữ liệu kho nào
     *  - 'K...': chỉ kho này
     */
    public static function maKho(): ?string
    {
        if (!self::limited()) {
            return null;
        }
        if (self::$cache === false) {
            $r = static::one('SELECT maKho FROM NhanVien WHERE maNhanVien = ?', [Auth::staffId()]);
            self::$cache = (string)($r['maKho'] ?? '');
        }
        return self::$cache;
    }

    /** @var array|null|false cache nhóm hàng theo nghiệp vụ kho; false = chưa tính */
    private static $nhomCache = false;

    /**
     * Nhóm hàng (HangHoa.loaiHangHoa) thuộc nghiệp vụ kho của người dùng, suy ra từ Kho.loaiKho.
     *  - null : không giới hạn nhóm hàng (vai trò không bị giới hạn theo kho, hoặc kho Tổng hợp / Hàng lỗi)
     *  - []   : chưa được gán kho → không thấy hàng hóa nào
     *  - ['Nguyên liệu'] ...: chỉ nhóm hàng đúng nghiệp vụ kho
     */
    public static function nhomHang(): ?array
    {
        $ma = self::maKho();
        if ($ma === null) {
            return null;
        }
        if (self::$nhomCache === false) {
            if ($ma === '') {
                self::$nhomCache = [];
            } else {
                $r = static::one('SELECT loaiKho FROM Kho WHERE maKho = ?', [$ma]);
                $loai = (string)($r['loaiKho'] ?? '');
                self::$nhomCache = isset(HangHoa::LOAI[$loai]) ? [$loai] : null;
            }
        }
        return self::$nhomCache;
    }

    /** Tên kho để hiển thị; null nếu không bị giới hạn theo kho. */
    public static function tenKho(): ?string
    {
        $ma = self::maKho();
        if ($ma === null) {
            return null;
        }
        $r = static::one('SELECT tenKho FROM Kho WHERE maKho = ?', [$ma]);
        return $r['tenKho'] ?? 'chưa được gán kho';
    }
}
