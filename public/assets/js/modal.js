// Hộp thoại xác nhận: gắn thuộc tính data-confirm="Nội dung" vào liên kết hoặc nút submit
(function () {
    var modal = document.getElementById('confirmModal');
    if (!modal) { return; }
    var message = document.getElementById('confirmMessage');
    var okBtn = document.getElementById('confirmOk');
    var onOk = null;

    function open(text, callback) {
        message.textContent = text;
        onOk = callback;
        modal.hidden = false;
        okBtn.focus();
    }
    function close() { modal.hidden = true; onOk = null; }

    document.addEventListener('click', function (ev) {
        var closer = ev.target.closest('[data-modal-close]');
        if (closer) { close(); return; }

        var el = ev.target.closest('[data-confirm]');
        if (!el) { return; }
        ev.preventDefault();
        open(el.getAttribute('data-confirm'), function () {
            if (el.tagName === 'A') {
                window.location.href = el.href;
            } else if (el.form) {
                el.removeAttribute('data-confirm');   // tránh hỏi lại khi submit
                if (el.form.requestSubmit) { el.form.requestSubmit(el); } else { el.form.submit(); }
            }
        });
    });

    okBtn.addEventListener('click', function () {
        var fn = onOk;
        close();
        if (fn) { fn(); }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !modal.hidden) { close(); }
    });
})();
