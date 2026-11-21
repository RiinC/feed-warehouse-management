<?php
/**
 * Hộp thoại xác nhận dùng chung (được nạp sẵn ở footer). Cách dùng:
 *   - Nút/liên kết:  <a href="..." data-confirm="Bạn có chắc muốn ngừng sử dụng?">Ngừng</a>
 *   - Nút trong form: <button type="submit" data-confirm="Xác nhận lưu?">Lưu</button>
 * Xử lý ở assets/js/modal.js
 */
function render_confirm_modal(): void
{
    ?>
    <div class="modal" id="confirmModal" hidden>
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
            <h3 id="confirmTitle">Xác nhận</h3>
            <p id="confirmMessage">Bạn có chắc chắn muốn thực hiện thao tác này?</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" data-modal-close>Hủy</button>
                <button type="button" class="btn btn-primary" id="confirmOk">Đồng ý</button>
            </div>
        </div>
    </div>
    <?php
}
