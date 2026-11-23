// Lọc option của select con theo select cha.
(function () {
    function apply(parent) {
        var target = document.getElementById(parent.getAttribute('data-dependent-target'));
        if (!target) { return; }
        var key = parent.getAttribute('data-dependent-key') || 'kho';
        var value = parent.value;
        var reset = false;
        Array.prototype.forEach.call(target.options, function (opt, i) {
            if (i === 0) { return; }
            var v = opt.getAttribute('data-' + key);
            var hide = value !== '' && v && v !== value;
            opt.hidden = hide;
            opt.disabled = hide;
            if (hide && opt.selected) { reset = true; }
        });
        if (reset) { target.value = ''; target.dispatchEvent(new Event('change')); }
    }
    document.querySelectorAll('[data-dependent-target]').forEach(function (parent) {
        apply(parent);
        parent.addEventListener('change', function () { apply(parent); });
    });
})();

