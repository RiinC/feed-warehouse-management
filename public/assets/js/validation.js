// Kiểm tra form phía trình duyệt: thêm data-validate vào <form>; trường bắt buộc dùng thuộc tính required
(function () {
    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            var ok = true;
            form.querySelectorAll('[required]').forEach(function (input) {
                var field = input.closest('.field');
                var old = field && field.querySelector('.err-msg');
                if (old) { old.remove(); }
                if (field) { field.classList.remove('invalid'); }
                if (!String(input.value).trim()) {
                    ok = false;
                    if (field) {
                        field.classList.add('invalid');
                        var msg = document.createElement('span');
                        msg.className = 'err-msg';
                        msg.textContent = 'Trường này là bắt buộc.';
                        field.appendChild(msg);
                    }
                }
            });
            if (!ok) { ev.preventDefault(); }
        });
    });
})();
