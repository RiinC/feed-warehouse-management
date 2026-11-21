// JavaScript chung: đóng thông báo
document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-dismiss="alert"]');
    if (btn && btn.parentElement) {
        btn.parentElement.remove();
    }
});
