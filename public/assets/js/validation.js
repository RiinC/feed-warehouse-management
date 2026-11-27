// Kiểm tra form phía trình duyệt và ép các ô số lượng / số nguyên chỉ nhận chữ số nguyên (0-9).
(function () {
    var INT_RE = /^\d+$/;

    function parseQtyStrict(raw) {
        var s = String(raw == null ? '' : raw).trim();
        if (!INT_RE.test(s)) {
            return null;
        }
        var n = parseInt(s, 10);
        return isFinite(n) ? n : null;
    }

    function sanitizeIntValue(val) {
        return String(val || '').replace(/[^0-9]/g, '');
    }

    function showFieldError(input, message) {
        var field = input.closest('.field');
        if (field) {
            field.classList.add('invalid');
            var old = field.querySelector('.err-msg');
            if (old) old.remove();
            var msg = document.createElement('span');
            msg.className = 'err-msg';
            msg.textContent = message;
            field.appendChild(msg);
            return;
        }
        input.style.borderColor = '#dc2626';
        input.setAttribute('title', message);
    }

    function clearFieldError(input) {
        var field = input.closest('.field');
        if (field) {
            field.classList.remove('invalid');
            var old = field.querySelector('.err-msg');
            if (old) old.remove();
        } else if (input.style.borderColor === 'rgb(220, 38, 38)' || input.style.borderColor === '#dc2626') {
            input.style.borderColor = '';
        }
    }

    function validateNumericInput(input) {
        var raw = String(input.value || '').trim();
        if (raw === '') {
            if (input.hasAttribute('required')) {
                return 'Trường này là bắt buộc.';
            }
            return '';
        }

        var val = parseQtyStrict(raw);
        if (val === null) {
            return 'Chỉ được nhập số nguyên không âm (không nhập số âm, dấu chấm hoặc dấu phẩy).';
        }

        if (input.hasAttribute('data-int')) {
            var parts = String(input.getAttribute('data-int') || '').split(':');
            var minI = parts[0] !== '' && !isNaN(parts[0]) ? parseInt(parts[0], 10) : 0;
            var maxI = parts[1] !== '' && !isNaN(parts[1]) ? parseInt(parts[1], 10) : null;
            if (val < minI) {
                return 'Giá trị phải lớn hơn hoặc bằng ' + minI + '.';
            }
            if (maxI !== null && val > maxI) {
                return 'Giá trị tối đa là ' + maxI + '.';
            }
            return '';
        }

        var mode = input.getAttribute('data-qty') || (input.min && parseFloat(input.min) > 0 ? 'positive' : 'nonneg');
        if (mode === 'positive' && val <= 0) {
            return 'Số lượng phải là số nguyên lớn hơn 0.';
        }
        if (val < 0) {
            return 'Số lượng không được là số âm.';
        }
        var attrMin = input.getAttribute('data-min') || input.getAttribute('min');
        if (attrMin !== null && attrMin !== '') {
            var minN = Math.ceil(parseFloat(attrMin));
            if (!isNaN(minN) && val < minN) {
                return 'Giá trị tối thiểu là ' + minN.toLocaleString('vi-VN') + '.';
            }
        }
        var attrMax = input.getAttribute('data-max') || input.getAttribute('max');
        if (attrMax !== null && attrMax !== '') {
            var maxN = parseFloat(attrMax);
            if (!isNaN(maxN) && val > maxN + 0.000001) {
                return 'Số lượng tối đa cho phép là ' + maxN.toLocaleString('vi-VN') + '.';
            }
        }
        return '';
    }

    function bindNumericGuards(root) {
        var selector = 'input[data-qty], input[data-int], input[type="number"]';
        (root || document).querySelectorAll(selector).forEach(function (input) {
            if (input.dataset.qtyBound === '1') return;
            input.dataset.qtyBound = '1';

            input.addEventListener('keydown', function (ev) {
                if (ev.ctrlKey || ev.metaKey || ev.altKey) return;
                var allowedNav = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];
                if (allowedNav.indexOf(ev.key) !== -1) return;
                if (!/^[0-9]$/.test(ev.key)) {
                    ev.preventDefault();
                }
            });

            input.addEventListener('input', function () {
                clearFieldError(input);
                var oldVal = String(input.value || '');
                var nextVal = sanitizeIntValue(oldVal);
                if (nextVal !== oldVal) {
                    input.value = nextVal;
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindNumericGuards(document);

        document.querySelectorAll('form').forEach(function (form) {
            var hasCustomValidation = form.hasAttribute('data-validate') || form.querySelector('input[data-qty], input[data-int]');
            if (!hasCustomValidation) return;

            form.addEventListener('submit', function (ev) {
                var ok = true;
                var firstBad = null;
                var firstMsg = '';

                if (form.hasAttribute('data-validate')) {
                    form.querySelectorAll('[required]').forEach(function (input) {
                        clearFieldError(input);
                        if (!String(input.value || '').trim()) {
                            ok = false;
                            showFieldError(input, 'Trường này là bắt buộc.');
                            if (!firstBad) {
                                firstBad = input;
                                firstMsg = 'Vui lòng điền đầy đủ các trường bắt buộc.';
                            }
                        }
                    });
                }

                form.querySelectorAll('input[data-qty], input[data-int]').forEach(function (input) {
                    if (input.disabled) return;
                    var err = validateNumericInput(input);
                    if (err !== '') {
                        ok = false;
                        showFieldError(input, err);
                        if (!firstBad) {
                            firstBad = input;
                            firstMsg = err;
                        }
                    }
                });

                if (!ok) {
                    ev.preventDefault();
                    if (firstBad) {
                        firstBad.focus();
                        if (!firstBad.closest('.field') && window.fwModal && typeof fwModal.alert === 'function') {
                            fwModal.alert({ title: 'Giá trị không hợp lệ', message: firstMsg, tone: 'warn' });
                        }
                    }
                }
            });
        });
    });

    window.fwQty = {
        parse: parseQtyStrict,
        validateInput: validateNumericInput,
        bindGuards: bindNumericGuards
    };
})();
