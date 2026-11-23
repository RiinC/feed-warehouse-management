// Chọn dòng bằng cách click vào bất kỳ vị trí nào trên hàng (trừ link/nút/ô nhập).
// Hàng cần có class="selectable-row" và chứa input[type="radio"].
(function () {
    document.querySelectorAll('tr.selectable-row').forEach(function (row) {
        var radio = row.querySelector('input[type="radio"]');
        if (!radio) { return; }

        function markSelected() {
            document.querySelectorAll('tr.selectable-row.selected').forEach(function (r) {
                r.classList.remove('selected');
            });
            if (radio.checked) { row.classList.add('selected'); }
        }

        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, input, select, textarea, label')) { return; }
            radio.checked = true;
            radio.dispatchEvent(new Event('change', { bubbles: true }));
            markSelected();
        });

        radio.addEventListener('change', markSelected);
        markSelected();
    });
})();
