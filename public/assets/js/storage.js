// UC16 - chọn lô, cố định kho, lọc khu vực/vị trí, điền số lượng còn lại.
(function () {
    var form = document.getElementById('assignForm');
    if (!form) { return; }
    var area = document.getElementById('assignArea');
    var pos = document.getElementById('assignPos');
    var qty = document.getElementById('assignQty');
    var kho = document.getElementById('assignKho');
    var hint = document.getElementById('assignHint');
    var lotTxt = document.getElementById('selLot');
    var hangTxt = document.getElementById('selHang');
    var khoTxt = document.getElementById('selKho');
    var hsdTxt = document.getElementById('selHsd');
    var conLaiTxt = document.getElementById('selConLai');
    var lotMax = 0;
    var currentKho = '';
    function updateHint() {
        if (!pos) { return; }
        var o = pos.selectedOptions[0];
        var rem = o && o.value ? parseFloat(o.getAttribute('data-conlai') || '0') : null;
        var max = rem === null ? lotMax : Math.min(lotMax, rem);
        if (qty) { qty.max = max > 0 ? max : '' ; if (max > 0 && parseFloat(qty.value || '0') > max) { qty.value = max; } }
        if (hint) { hint.textContent = rem === null ? 'Chọn vị trí để xem sức chứa còn lại.' : 'Sức chứa còn lại: ' + rem.toFixed(2) + ' kg. Có thể gán tối đa ' + max.toFixed(2) + ' kg.'; }
    }
    function filterPositions() {
        if (!pos) { return; }
        var a = area ? area.value : '';
        Array.prototype.forEach.call(pos.options, function (opt, i) {
            if (i === 0) { return; }
            var hide = (currentKho && opt.getAttribute('data-kho') !== currentKho) || (a !== '' && opt.getAttribute('data-khuvuc') !== a);
            opt.hidden = hide; opt.disabled = hide;
        });
        if (pos.selectedOptions[0] && pos.selectedOptions[0].hidden) { pos.value = ''; }
        updateHint();
    }
    document.querySelectorAll('.lot-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (!this.checked) { return; }
            document.querySelectorAll('#lotTable tr').forEach(function (tr) { tr.classList.remove('selected'); });
            var tr = this.closest('tr'); if (tr) { tr.classList.add('selected'); }
            lotMax = parseFloat(this.getAttribute('data-conlai') || '0');
            currentKho = this.getAttribute('data-kho') || '';
            if (kho) { kho.textContent = this.getAttribute('data-kho-ten') || this.getAttribute('data-kho') || ''; }
            if (lotTxt) { lotTxt.textContent = this.value; }
            if (hangTxt) { hangTxt.textContent = this.getAttribute('data-hang') || ''; }
            if (khoTxt) { khoTxt.textContent = this.getAttribute('data-kho-ten') || ''; }
            if (hsdTxt) { hsdTxt.textContent = this.getAttribute('data-hsd') || ''; }
            if (conLaiTxt) { conLaiTxt.textContent = lotMax.toFixed(2) + ' kg'; }
            if (area) {
                var selectedKho = this.getAttribute('data-kho');
                Array.prototype.forEach.call(area.options, function (opt, i) {
                    if (i === 0) { return; }
                    var hide = opt.getAttribute('data-kho') !== selectedKho;
                    opt.hidden = hide; opt.disabled = hide;
                });
                area.value = '';
            }
            if (pos) { pos.value = ''; }
            if (qty) { qty.value = lotMax; qty.max = lotMax; }
            filterPositions();
        });
    });
    if (area) { area.addEventListener('change', filterPositions); }
    if (pos) { pos.addEventListener('change', updateHint); }
    filterPositions();
})();

