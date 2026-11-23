// Mở/đóng modal nhập lý do từ chối.
(function () {
    var modal = document.getElementById('rejectModal');
    if (!modal) { return; }
    document.querySelectorAll('[data-open-reject]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            modal.hidden = false;
            var t = document.getElementById('lyDoTuChoi');
            if (t) { t.focus(); }
        });
    });
    modal.querySelectorAll('[data-close-reject]').forEach(function (btn) {
        btn.addEventListener('click', function () { modal.hidden = true; });
    });
})();

