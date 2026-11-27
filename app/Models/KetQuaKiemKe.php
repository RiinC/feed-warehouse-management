<?php
namespace App\Models;

use Core\Model;

/**
 * UC25 — Ghi nhận số lượng thực tế kiểm kê (KetQuaKiemKe).
 *
 * Nguồn dữ liệu chuẩn: CSDL gốc feed_wms.
 * Bảng: KetQuaKiemKe
 * Khóa chính: maKetQuaKiemKe (VARCHAR(16), định dạng ^KQKK[0-9]{12}$)
 * Cột chenhLech: GENERATED ALWAYS AS (soLuongThucTe - soLuongSoSach) STORED
 */
class KetQuaKiemKe extends Model
{
    /** Bảng danh sách phạm vi kiểm kê kèm kết quả đã ghi (nếu có). */
    public static function grid(string $ma): array
    {
        return BienBanKiemKe::scopeItems($ma);
    }

    /**
     * Lưu kết quả kiểm kê thực tế theo từng dòng mặt hàng:
     * - $in['items'][key][maLoHang, maViTri, soLuongSoSach, soLuongThucTe, ghiChu]
     * Trả về ['saved' => int, 'updated' => int, 'errors' => array]
     */
    public static function save(array $in, string $ma, string $maNhanVien): array
    {
        $bb = BienBanKiemKe::find($ma);
        if (!$bb) {
            throw new \RuntimeException('Không tìm thấy biên bản kiểm kê.');
        }
        if (!in_array($bb['trangThai'], ['Chờ xử lý', 'Từ chối'], true)) {
            throw new \RuntimeException('Biên bản đang ở trạng thái "' . inventory_status_label($bb['trangThai']) . '" nên không thể ghi nhận kết quả.');
        }

        // RBAC: Chỉ nhân viên phụ trách kiểm kê (hoặc admin/superadmin) mới được ghi nhận kết quả
        $userRole = \Core\Auth::role();
        if (!in_array($userRole, ['admin', 'superadmin'], true) && !empty($bb['maNhanVienTruongDoan'])) {
            if ($bb['maNhanVienTruongDoan'] !== $maNhanVien) {
                throw new \RuntimeException('Chỉ nhân viên được phân công phụ trách kiểm kê (' . ($bb['tenTruongDoan'] ?? $bb['maNhanVienTruongDoan']) . ') mới có quyền ghi nhận kết quả.');
            }
        }

        return static::transaction(function () use ($in, $ma, $maNhanVien) {
            $existing = [];
            foreach (static::all('SELECT * FROM KetQuaKiemKe WHERE maBienBanKiemKe = ?', [$ma]) as $r) {
                $existing[$r['maLoHang'] . '|' . $r['maViTri']] = $r;
            }

            $out = ['saved' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => []];
            $items = (array)($in['items'] ?? []);

            foreach ($items as $val) {
                $maLoHang = trim((string)($val['maLoHang'] ?? ''));
                $maViTri = trim((string)($val['maViTri'] ?? ''));
                if ($maLoHang === '' || $maViTri === '') {
                    continue;
                }

                $rawThucTe = trim((string)($val['soLuongThucTe'] ?? ''));
                $rawSoSach = (float)($val['soLuongSoSach'] ?? 0);
                $ghiChu = trim((string)($val['ghiChu'] ?? ''));
                $key = $maLoHang . '|' . $maViTri;

                if ($rawThucTe === '') {
                    continue;
                }

                $parsedThucTe = parse_qty($rawThucTe, true);
                if ($parsedThucTe === null) {
                    $out['errors'][] = "Lô $maLoHang tại $maViTri: số lượng thực tế chỉ được nhập số nguyên không âm.";
                    continue;
                }

                // Kiểm tra chống trùng vị trí với các biên bản đang hoạt động khác
                $vtConflict = static::one(
                    "SELECT b.maBienBanKiemKe, vt.tenViTri
                     FROM KetQuaKiemKe kq
                     JOIN BienBanKiemKe b ON b.maBienBanKiemKe = kq.maBienBanKiemKe
                     JOIN ViTriLuuTru vt ON vt.maViTri = kq.maViTri
                     WHERE kq.maViTri = ? AND kq.maBienBanKiemKe <> ? AND b.trangThai IN ('Chờ xử lý', 'Đang xử lý', 'Từ chối')
                     LIMIT 1",
                    [$maViTri, $ma]
                );
                if ($vtConflict) {
                    $out['errors'][] = "Vị trí {$vtConflict['tenViTri']} đang có biên bản {$vtConflict['maBienBanKiemKe']} chưa hoàn tất.";
                    continue;
                }

                $soLuongThucTe = $parsedThucTe;
                $cur = $existing[$key] ?? null;

                if ($cur) {
                    if (self::hasOpenProposal($cur['maKetQuaKiemKe'])) {
                        $out['errors'][] = "Lô $maLoHang tại $maViTri đã có phương án xử lý nên không thể sửa.";
                        continue;
                    }
                    // BẢO TOÀN SỐ LIỆU SỔ SÁCH: TUYỆT ĐỐI KHÔNG GHI ĐÈ soLuongSoSach KHI SỬA
                    static::exec(
                        'UPDATE KetQuaKiemKe 
                         SET soLuongThucTe = ?, maNhanVienKiemKe = ?, ghiChu = ?
                         WHERE maKetQuaKiemKe = ?',
                        [$soLuongThucTe, $maNhanVien, $ghiChu !== '' ? $ghiChu : null, $cur['maKetQuaKiemKe']]
                    );
                    $kqCode = $cur['maKetQuaKiemKe'];
                    $curSoSach = (float)$cur['soLuongSoSach'];
                    $out['updated']++;
                } else {
                    // Lần đầu ghi nhận dòng này: Lấy số lượng sổ sách từ TonKho tại thời điểm ghi
                    $tk = static::one(
                        'SELECT soLuongTon FROM TonKho WHERE maLoHang = ? AND maViTri = ? LIMIT 1',
                        [$maLoHang, $maViTri]
                    );
                    $curSoSach = $tk ? (float)$tk['soLuongTon'] : $rawSoSach;

                    $kqCode = MaChungTu::next('KetQuaKiemKe');
                    static::exec(
                        'INSERT INTO KetQuaKiemKe (maKetQuaKiemKe, maBienBanKiemKe, maLoHang, maViTri, soLuongSoSach, soLuongThucTe, maNhanVienKiemKe, ghiChu)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $kqCode,
                            $ma,
                            $maLoHang,
                            $maViTri,
                            $curSoSach,
                            $soLuongThucTe,
                            $maNhanVien,
                            $ghiChu !== '' ? $ghiChu : null
                        ]
                    );
                    $out['saved']++;
                }

                // Tự động đồng bộ dòng chênh lệch vào ChenhLechKiemKe để Quản lý kho xem xét đối chiếu
                $diff = round($soLuongThucTe - $curSoSach, 3);
                if (abs($diff) > 0.000001) {
                    $loaiCL = $diff > 0 ? 'Thừa' : 'Thiếu';
                    $slCL = round(abs($diff), 3);
                    $existingCL = static::one('SELECT maChenhLech FROM ChenhLechKiemKe WHERE maKetQuaKiemKe = ?', [$kqCode]);
                    if ($existingCL) {
                        static::exec(
                            'UPDATE ChenhLechKiemKe SET loaiChenhLech = ?, soLuongChenhLech = ? WHERE maChenhLech = ?',
                            [$loaiCL, $slCL, $existingCL['maChenhLech']]
                        );
                    } else {
                        static::exec(
                            'INSERT INTO ChenhLechKiemKe (maChenhLech, maKetQuaKiemKe, loaiChenhLech, soLuongChenhLech, nguyenNhan, moTaChiTiet)
                             VALUES (?, ?, ?, ?, "ChuaCapNhat", NULL)',
                            [MaChungTu::next('ChenhLechKiemKe'), $kqCode, $loaiCL, $slCL]
                        );
                    }
                } else {
                    static::exec('DELETE FROM ChenhLechKiemKe WHERE maKetQuaKiemKe = ?', [$kqCode]);
                }
            }

            return $out;
        });
    }

    /** Kiểm tra xem dòng kết quả kiểm kê đã có phương án xử lý còn hiệu lực chưa */
    public static function hasOpenProposal(string $maKetQua): bool
    {
        return (bool)static::one(
            "SELECT 1 AS x FROM ChenhLechKiemKe c JOIN PhuongAnXuLy p ON p.maChenhLech = c.maChenhLech
             WHERE c.maKetQuaKiemKe = ? AND p.trangThai IN ('Chờ phê duyệt', 'Đã phê duyệt', 'Đã thi hành') LIMIT 1",
            [$maKetQua]
        );
    }
}
