<?php
/**
 * UC07 - Tra cứu lô hàng cần kiểm tra.
 * Tích hợp UC08 - Ghi nhận kết quả kiểm tra.
 * Tích hợp UC09 - Xác nhận kết quả kiểm tra chất lượng.
 */

// ==============================================
// 1. KHỞI TẠO DỮ LIỆU
// ==============================================

$danhSachLoHang = $danhSachLoHang ?? [];
$chiTiet = $chiTiet ?? null;

$maLoHang = $maLoHang ?? '';
$tuNgay = $tuNgay ?? '';
$denNgay = $denNgay ?? '';
$maChiTiet = $maChiTiet ?? '';

$thongBao = $thongBao ?? '';

$trang = $trang ?? 1;
$tongSoTrang = $tongSoTrang ?? 1;
$tongSoLoHang = $tongSoLoHang ?? 0;
$soLuongMoiTrang = $soLuongMoiTrang ?? 10;

// Đường dẫn
$baseUrl = '/feed-warehouse-management/public';

$indexUrl = $baseUrl . '/chat-luong';

$recordUrl = $baseUrl . '/chat-luong/record-result';

$confirmUrl = $baseUrl . '/chat-luong/confirm-result';

// Xử lý dữ liệu hiển thị
$e = static function ($value): string {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

// Giữ lại bộ lọc khi phân trang và xem chi tiết
$boLoc = [
    'maLoHang' => $maLoHang,
    'tuNgay' => $tuNgay,
    'denNgay' => $denNgay,
    'page' => $trang
];

// Tạo đường dẫn phân trang
$linkTrang = static function (
    int $page
) use ($indexUrl, $boLoc): string {

    return $indexUrl . '?' .
        http_build_query(
            array_merge(
                $boLoc,
                ['page' => $page]
            )
        );
};

// Tính số thứ tự và số lượng đang hiển thị
$offset = ($trang - 1) * $soLuongMoiTrang;

$batDau = $tongSoLoHang > 0
    ? $offset + 1
    : 0;

$ketThuc = $tongSoLoHang > 0
    ? min(
        $offset + count($danhSachLoHang),
        $tongSoLoHang
    )
    : 0;
?>


<!-- ============================================ -->
<!-- 2. THÔNG TIN TRA CỨU                        -->
<!-- ============================================ -->

<div class="card filter-card">

    <div class="card-head">
        <strong>Thông tin tra cứu</strong>
    </div>

    <form
        method="GET"
        action="<?= $e($indexUrl) ?>"
    >

        <div class="filter-grid"
             style="align-items: end;">

            <!-- Mã lô hàng -->
            <div class="field"
                 style="margin-bottom: 0;">

                <label for="maLoHang">
                    Mã lô hàng
                </label>

                <input
                    type="text"
                    id="maLoHang"
                    name="maLoHang"
                    value="<?= $e($maLoHang) ?>"
                    placeholder="Nhập mã lô hàng"
                    style="
                        height: 40px;
                        min-height: 40px;
                        max-height: 40px;
                        box-sizing: border-box;
                        padding-top: 0;
                        padding-bottom: 0;
                        margin-bottom: 0;
                    "
                >

            </div>

            <!-- Thời gian bắt đầu -->
            <div class="field"
                 style="margin-bottom: 0;">

                <label for="tuNgay">
                    Thời gian bắt đầu
                </label>

                <input
                    type="date"
                    id="tuNgay"
                    name="tuNgay"
                    value="<?= $e($tuNgay) ?>"
                    style="
                        height: 40px;
                        min-height: 40px;
                        max-height: 40px;
                        box-sizing: border-box;
                        padding-top: 0;
                        padding-bottom: 0;
                        margin-bottom: 0;
                    "
                >

            </div>

            <!-- Thời gian kết thúc -->
            <div class="field"
                 style="margin-bottom: 0;">

                <label for="denNgay">
                    Thời gian kết thúc
                </label>

                <input
                    type="date"
                    id="denNgay"
                    name="denNgay"
                    value="<?= $e($denNgay) ?>"
                    style="
                        height: 40px;
                        min-height: 40px;
                        max-height: 40px;
                        box-sizing: border-box;
                        padding-top: 0;
                        padding-bottom: 0;
                        margin-bottom: 0;
                    "
                >

            </div>

            <!-- Nút áp dụng bộ lọc và đặt lại -->
            <div
                class="filter-actions"
                style="
                    margin: 0;
                    padding: 0;
                    align-self: end;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    flex-wrap: nowrap;
                "
            >

                <button
                    type="submit"
                    class="btn btn-primary"
                    style="
                        height: 40px;
                        min-height: 40px;
                        max-height: 40px;
                        box-sizing: border-box;
                        padding: 0 16px;
                        margin: 0;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        white-space: nowrap;
                    "
                >
                    Áp dụng bộ lọc
                </button>

                <a
                    href="<?= $e($indexUrl) ?>"
                    class="btn"
                    style="
                        height: 40px;
                        min-height: 40px;
                        max-height: 40px;
                        box-sizing: border-box;
                        padding: 0 16px;
                        margin: 0;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        white-space: nowrap;
                    "
                >
                    Đặt lại
                </a>

            </div>

        </div>

    </form>

</div>


<!-- ============================================ -->
<!-- 3. THÔNG BÁO                                -->
<!-- ============================================ -->

<?php if ($thongBao !== ''): ?>

    <div class="alert alert-warning">

        <?= $e($thongBao) ?>

    </div>

<?php endif; ?>


<!-- ============================================ -->
<!-- 4. DANH SÁCH LÔ HÀNG CẦN KIỂM TRA            -->
<!-- ============================================ -->

<div class="card">

    <div class="card-head">

        <strong>
            Danh sách lô hàng cần kiểm tra
        </strong>

        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">

            <span class="muted">
                Tổng số: <?= $tongSoLoHang ?> lô hàng
            </span>

            <!-- UC09 - Xác nhận kết quả kiểm tra chất lượng -->
            <?php if (can('chat-luong', 'confirm')): ?>

                <a
                    href="<?= $e($confirmUrl) ?>"
                    class="btn btn-primary"
                >
                    Xác nhận kết quả kiểm tra chất lượng
                </a>

            <?php endif; ?>

        </div>

    </div>

    <div class="table-wrap">

        <table class="table">

            <thead>

                <tr>

                    <th class="w-stt">
                        STT
                    </th>

                    <th>
                        Mã lô hàng
                    </th>

                    <th>
                        Tên lô hàng
                    </th>

                    <th>
                        Trạng thái chất lượng
                    </th>

                    <th>
                        Thời gian tiếp nhận
                    </th>

                    <th class="w-actions">
                        Thao tác
                    </th>

                </tr>

            </thead>

            <tbody>

                <?php if (empty($danhSachLoHang)): ?>

                    <tr>

                        <td
                            colspan="6"
                            class="empty-row"
                        >
                            Không có lô hàng phù hợp.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($danhSachLoHang as $i => $lo): ?>

                        <?php
                        // Đường dẫn xem chi tiết UC07
                        $chiTietUrl = $indexUrl . '?' .
                            http_build_query(
                                array_merge(
                                    $boLoc,
                                    [
                                        'maChiTiet' =>
                                            $lo['maLoHang']
                                    ]
                                )
                            ) . '#chi-tiet';

                        // Trạng thái nghiệp vụ lấy từ Model, không sửa dữ liệu SQL.
                        $coBienBanChoDuyet = (int)($lo['coBienBanChoDuyet'] ?? 0) === 1;
                        $coTheKiemTraMoi = !$coBienBanChoDuyet &&
                            (int)($lo['coTheKiemTraMoi'] ?? 0) === 1;

                        // Đường dẫn ghi nhận hoặc xem danh sách biên bản UC08
                        $ghiNhanUrl = $recordUrl . '?' .
                            http_build_query([
                                'maLoHang' =>
                                    $lo['maLoHang']
                            ]);

                        // Định dạng thời gian tiếp nhận
                        $thoiGian = '';

                        if (!empty($lo['thoiGianTiepNhan'])) {

                            $timestamp = strtotime(
                                $lo['thoiGianTiepNhan']
                            );

                            if ($timestamp !== false) {

                                $thoiGian = date(
                                    'd/m/Y H:i',
                                    $timestamp
                                );
                            }
                        }
                        ?>

                        <tr>

                            <!-- STT -->
                            <td>
                                <?= $offset + $i + 1 ?>
                            </td>

                            <!-- Mã lô hàng -->
                            <td>
                                <?= $e($lo['maLoHang']) ?>
                            </td>

                            <!-- Tên lô hàng -->
                            <td>
                                <?= $e($lo['tenLoHang']) ?>
                            </td>

                            <!-- Trạng thái chất lượng -->
                            <td>

                                <span class="badge badge-warn"<?= $coBienBanChoDuyet ? ' style="color: #1d4ed8; background-color: #dbeafe;"' : '' ?>>

                                    <?= $e(
                                        $lo['trangThaiChatLuong']
                                    ) ?>

                                </span>

                            </td>

                            <!-- Thời gian tiếp nhận -->
                            <td>
                                <?= $e($thoiGian) ?>
                            </td>

                            <!-- Thao tác -->
                            <td class="w-actions">

                                <div class="cell-actions">

                                    <!-- UC07 - Xem chi tiết -->
                                    <a
                                        href="<?= $e($chiTietUrl) ?>"
                                        class="btn btn-sm"
                                    >
                                        Xem chi tiết
                                    </a>

                                    <!-- UC08 - Chỉ kiểm tra mới khi chưa có biên bản chờ duyệt -->
                                    <?php if (can('chat-luong', 'record')): ?>

                                        <?php if ($coBienBanChoDuyet): ?>

                                            <a
                                                href="<?= $e($ghiNhanUrl) ?>"
                                                class="btn btn-sm"
                                            >
                                                Xem kết quả
                                            </a>

                                        <?php elseif ($coTheKiemTraMoi): ?>

                                            <a
                                                href="<?= $e($ghiNhanUrl) ?>"
                                                class="btn btn-primary btn-sm"
                                            >
                                                Ghi nhận kết quả
                                            </a>

                                        <?php endif; ?>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- ======================================== -->
    <!-- 5. PHÂN TRANG                           -->
    <!-- ======================================== -->

    <div class="card-head">

        <span class="muted">

            Hiển thị
            <?= $batDau ?> -
            <?= $ketThuc ?> /
            <?= $tongSoLoHang ?> lô hàng

        </span>

        <nav
            class="pagination"
            aria-label="Phân trang"
        >

            <!-- Trang trước -->
            <?php if ($trang > 1): ?>

                <a
                    href="<?= $e(
                        $linkTrang($trang - 1)
                    ) ?>"
                    aria-label="Trang trước"
                >
                    &laquo;
                </a>

            <?php else: ?>

                <button
                    type="button"
                    class="btn btn-sm"
                    disabled
                    aria-label="Trang trước"
                >
                    &laquo;
                </button>

            <?php endif; ?>


            <!-- Danh sách số trang -->
            <?php for ($p = 1; $p <= $tongSoTrang; $p++): ?>

                <?php if ($p === $trang): ?>

                    <span
                        class="current"
                        aria-current="page"
                    >

                        <?= $p ?>

                    </span>

                <?php else: ?>

                    <a
                        href="<?= $e($linkTrang($p)) ?>"
                    >
                        <?= $p ?>
                    </a>

                <?php endif; ?>

            <?php endfor; ?>


            <!-- Trang tiếp theo -->
            <?php if ($trang < $tongSoTrang): ?>

                <a
                    href="<?= $e(
                        $linkTrang($trang + 1)
                    ) ?>"
                    aria-label="Trang tiếp theo"
                >
                    &raquo;
                </a>

            <?php else: ?>

                <button
                    type="button"
                    class="btn btn-sm"
                    disabled
                    aria-label="Trang tiếp theo"
                >
                    &raquo;
                </button>

            <?php endif; ?>

        </nav>

    </div>

</div>


<!-- ============================================ -->
<!-- 6. CHI TIẾT LÔ HÀNG                         -->
<!-- ============================================ -->

<?php if ($chiTiet !== null): ?>

    <?php
    // Định dạng thời gian tiếp nhận
    $thoiGianChiTiet = '';

    if (!empty($chiTiet['thoiGianTiepNhan'])) {

        $timestamp = strtotime(
            $chiTiet['thoiGianTiepNhan']
        );

        if ($timestamp !== false) {

            $thoiGianChiTiet = date(
                'd/m/Y H:i',
                $timestamp
            );
        }
    }

    // Đường dẫn đóng chi tiết
    $dongChiTietUrl = $indexUrl . '?' .
        http_build_query($boLoc);

    // Thông tin chi tiết lô hàng
    $thongTinChiTiet = [

        'Mã lô hàng' =>
            $chiTiet['maLoHang'],

        'Tên lô hàng' =>
            $chiTiet['tenLoHang'],

        'Số lượng tiếp nhận' =>
            $chiTiet['soLuongTiepNhan'],

        'Đơn vị tính' =>
            $chiTiet['donViTinh'],

        'Thời gian tiếp nhận' =>
            $thoiGianChiTiet,

        'Trạng thái lô' =>
            $chiTiet['trangThaiLo'],

        'Trạng thái chất lượng' =>
            $chiTiet['trangThaiChatLuong']
    ];
    ?>

    <style>
        #chi-tiet .uc07-fixed-field:disabled {
            background-color: #f5f7fa;
            color: #334155;
            -webkit-text-fill-color: #334155;
            opacity: 1;
            cursor: default;
            pointer-events: none;
        }
    </style>

    <div class="card" id="chi-tiet">

        <div class="card-head">

            <strong>
                Chi tiết lô hàng
            </strong>

            <a
                href="<?= $e($dongChiTietUrl) ?>"
                class="btn btn-sm"
            >
                Đóng
            </a>

        </div>

        <div class="table-wrap">
            <table class="table" style="width:100%; table-layout:fixed;">
                <colgroup><col style="width:35%;"><col style="width:65%;"></colgroup>
                <thead>
                    <tr><th scope="col">Thông tin</th><th scope="col">Giá trị</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($thongTinChiTiet as $nhan => $giaTri): ?>
                        <tr>
                            <td><strong><?= $e($nhan) ?></strong></td>
                            <td style="overflow-wrap:anywhere;"><?= $e($giaTri) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><strong>Ghi chú</strong></td>
                        <td style="white-space:pre-wrap; overflow-wrap:anywhere;"><?= $e($chiTiet['ghiChu'] ?? '') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

<?php endif; ?>
