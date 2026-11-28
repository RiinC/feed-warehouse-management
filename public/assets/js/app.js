// JavaScript chung: đóng thông báo
document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-dismiss="alert"]');
    if (btn && btn.parentElement) {
        btn.parentElement.remove();
    }
});

// Ô chọn phụ thuộc trong form lọc (vd. Kho → Khu vực → Vị trí): chỉ hiện lựa chọn thuộc giá trị ô cha
(function () {
    var children = document.querySelectorAll('select[data-parent]');
    function sync(child) {
        var parent = document.getElementById(child.getAttribute('data-parent'));
        if (!parent) {
            return;
        }
        var pv = parent.value;
        Array.prototype.forEach.call(child.options, function (opt) {
            var g = opt.getAttribute('data-group');
            var show = !g || pv === '' || g === pv;
            opt.hidden = !show;
            opt.disabled = !show;
        });
        if (child.selectedOptions.length && child.selectedOptions[0].disabled) {
            child.value = '';
            child.dispatchEvent(new Event('change'));
        }
    }
    Array.prototype.forEach.call(children, function (child) {
        var parent = document.getElementById(child.getAttribute('data-parent'));
        if (parent) {
            parent.addEventListener('change', function () { sync(child); });
            sync(child);
        }
    });
})();
