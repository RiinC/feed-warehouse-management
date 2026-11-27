<?php
namespace App\Models;

use Core\Model;

/**
 * UC27 (phần đối chiếu) — Bảng chênh lệch kiểm kê (ChenhLechKiemKe).
 * Mỗi dòng KetQuaKiemKe có tối đa một dòng chênh lệch (ràng buộc UNIQUE trong schema).
 */
class ChenhLechKiemKe extends Model
{
    /** Giá trị hợp lệ của cột nguyenNhan (theo CHECK ck_ChenhLechKiemKe_4). */
    public const NGUYEN_NHAN = ['HaoHut', 'SaiSot', 'ThatThoat', 'ChuaCapNhat'];

    public const NGUYEN_NHAN_MAP = [
        'HaoHut'      => 'Hao hụt',
        'SaiSot'      => 'Sai sót',
        'ThatThoat'   => 'Thất thoát',
        'ChuaCapNhat' => 'Chưa cập nhật',
    ];

    public static function normalizeNguyenNhan(?string $nn): ?string
    {
        if ($nn === null || trim($nn) === '') return null;
        $t = trim($nn);
        $map = [
            'Hao hụt'       => 'HaoHut',
            'haohut'        => 'HaoHut',
            'HaoHut'        => 'HaoHut',
            'Sai sót'       => 'SaiSot',
            'saisot'        => 'SaiSot',
            'SaiSot'        => 'SaiSot',
            'Thất thoát'    => 'ThatThoat',
            'thatthoat'     => 'ThatThoat',
            'ThatThoat'     => 'ThatThoat',
            'Chưa cập nhật' => 'ChuaCapNhat',
            'chuacapnhat'   => 'ChuaCapNhat',
            'ChuaCapNhat'   => 'ChuaCapNhat',
        ];
        return $map[$t] ?? (in_array($t, self::NGUYEN_NHAN, true) ? $t : 'ChuaCapNhat');
    }

    public const LOAI = ['Thừa', 'Thiếu', 'Không chênh lệch'];

    /**
     * Toàn bộ dòng kết quả kiểm kê kèm chênh lệch + phương án xử lý mới nhất (nếu có).
     * Sắp xếp: dòng chênh lệch lớn trước để người dùng đối chiếu từ trên xuống.
     */
    public static function rows(string $maBienBan): array
    {
        return static::all(
            "SELECT q.maKetQuaKiemKe, q.maLoHang, q.maViTri, q.soLuongSoSach, q.soLuongThucTe, q.chenhLech,
                    h.maHangHoa, h.tenHangHoa, h.donViTinhChuan, v.tenViTri,
                    c.maChenhLech, c.loaiChenhLech, c.soLuongChenhLech, c.nguyenNhan, c.moTaChiTiet,
                    p.maPhuongAn, p.bienPhapXuLy, p.trangThai AS trangThaiPhuongAn, p.yKienPheDuyet,
                    p.maNhanVienPheDuyet, nvp.hoTen AS tenNguoiPheDuyet
             FROM KetQuaKiemKe q
             JOIN LoHang l ON l.maLoHang = q.maLoHang
             JOIN HangHoa h ON h.maHangHoa = l.maHangHoa
             JOIN ViTriLuuTru v ON v.maViTri = q.maViTri
             LEFT JOIN ChenhLechKiemKe c ON c.maKetQuaKiemKe = q.maKetQuaKiemKe
             LEFT JOIN PhuongAnXuLy p ON p.maPhuongAn = (
                 SELECT p2.maPhuongAn FROM PhuongAnXuLy p2
                 WHERE p2.maChenhLech = c.maChenhLech
                 ORDER BY p2.ngayDeXuat DESC, p2.maPhuongAn DESC LIMIT 1)
             LEFT JOIN NhanVien nvp ON nvp.maNhanVien = p.maNhanVienPheDuyet
             WHERE q.maBienBanKiemKe = ?
             ORDER BY ABS(q.chenhLech) DESC, h.tenHangHoa, q.maLoHang",
            [$maBienBan]
        );
    }

    /**
     * Lưu phân loại + nguyên nhân cho các dòng chênh lệch.
     * $in['dong'][maKetQuaKiemKe] = ['nguyenNhan' => ..., 'moTaChiTiet' => ...].
     * Dòng không chênh lệch được tự động ghi 'Không chênh lệch' (số lượng 0, nguyên nhân NULL).
     */
    public static function save(array $in, string $ma): array
    {
        $bb = BienBanKiemKe::find($ma);
        if (!$bb) {
            throw new \RuntimeException('Không tìm thấy biên bản kiểm kê.');
        }
        if ($bb['trangThai'] !== BienBanKiemKe::TT_DANG) {
            throw new \RuntimeException('Biên bản đã chốt hoặc đã hủy nên không đối chiếu được.');
        }
        return static::transaction(function () use ($in, $ma) {
            $ketQua = [];
            foreach (static::all('SELECT * FROM KetQuaKiemKe WHERE maBienBanKiemKe = ?', [$ma]) as $r) {
                $ketQua[(string)$r['maKetQuaKiemKe']] = $r;
            }
            $out = ['saved' => 0, 'errors' => []];
            foreach ((array)($in['dong'] ?? []) as $maKQ => $val) {
                $kq = $ketQua[(string)$maKQ] ?? null;
                if (!$kq) {
                    $out['errors'][] = 'Một dòng kết quả không thuộc biên bản này, đã bỏ qua.';
                    continue;
                }
                $cl = static::one('SELECT * FROM ChenhLechKiemKe WHERE maKetQuaKiemKe = ?', [$kq['maKetQuaKiemKe']]);
                if ($cl && static::one(
                    "SELECT 1 AS x FROM PhuongAnXuLy WHERE maChenhLech = ? AND trangThai IN ('Đang xử lý', 'Hoàn tất') LIMIT 1",
                    [$cl['maChenhLech']]
                )) {
                    $out['errors'][] = 'Dòng ' . $kq['maLoHang'] . ' @ ' . $kq['maViTri'] . ' đã được phê duyệt/thi hành, không sửa được.';
                    continue;
                }
                $diff = (float)$kq['chenhLech'];
                if (abs($diff) <= 0.000001) {
                    $loai = 'Không chênh lệch';
                    $soLuong = 0.0;
                    $nguyenNhan = null;
                    $moTa = null;
                } else {
                    $loai = $diff > 0 ? 'Thừa' : 'Thiếu';
                    $soLuong = round(abs($diff), 3);
                    $rawNN = trim((string)($val['nguyenNhan'] ?? ''));
                    $nguyenNhan = self::normalizeNguyenNhan($rawNN);
                    $moTa = trim((string)($val['moTaChiTiet'] ?? ''));
                    if (!$nguyenNhan) {
                        $out['errors'][] = 'Dòng ' . $kq['maLoHang'] . ' @ ' . $kq['maViTri'] . ': chọn nguyên nhân chênh lệch.';
                        continue;
                    }
                    if (mb_strlen($moTa) > 255) {
                        $out['errors'][] = 'Dòng ' . $kq['maLoHang'] . ' @ ' . $kq['maViTri'] . ': mô tả tối đa 255 ký tự.';
                        continue;
                    }
                }
                if ($cl) {
                    static::exec(
                        'UPDATE ChenhLechKiemKe SET loaiChenhLech = ?, soLuongChenhLech = ?, nguyenNhan = ?, moTaChiTiet = ? WHERE maChenhLech = ?',
                        [$loai, $soLuong, $nguyenNhan, $moTa !== '' ? $moTa : null, $cl['maChenhLech']]
                    );
                } else {
                    static::exec(
                        'INSERT INTO ChenhLechKiemKe (maChenhLech, maKetQuaKiemKe, loaiChenhLech, soLuongChenhLech, nguyenNhan, moTaChiTiet)
                         VALUES (?, ?, ?, ?, ?, ?)',
                        [MaChungTu::next('ChenhLechKiemKe'), $kq['maKetQuaKiemKe'], $loai, $soLuong, $nguyenNhan, $moTa !== '' ? $moTa : null]
                    );
                }
                $out['saved']++;
            }
            return $out;
        });
    }
}
