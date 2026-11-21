// Đóng/mở sidebar trên màn hình nhỏ + mở/đóng từng nhóm menu
(function () {
    var shell = document.getElementById('appShell');
    var toggle = document.getElementById('sidebarToggle');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!shell) { return; }
    if (toggle) {
        toggle.addEventListener('click', function () { shell.classList.toggle('sidebar-open'); });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () { shell.classList.remove('sidebar-open'); });
    }
})();

(function () {
    var toggles = document.querySelectorAll('.nav-toggle');
    Array.prototype.forEach.call(toggles, function (btn) {
        btn.addEventListener('click', function () {
            var group = btn.closest('.nav-group');
            var open = group.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
})();
