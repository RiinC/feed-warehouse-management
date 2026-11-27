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

// Popup chuẩn FeedWare (confirm / prompt / alert) — thay thế hộp thoại mặc định của trình duyệt
(function () {
    var tones = {
        info:    { bg: '#eff6ff', color: '#2563eb', btn: '#2563eb', icon: '?' },
        success: { bg: '#dcfce7', color: '#16a34a', btn: '#16a34a', icon: '✓' },
        warn:    { bg: '#fff7ed', color: '#d97706', btn: '#2563eb', icon: '!' },
        danger:  { bg: '#fef2f2', color: '#dc2626', btn: '#dc2626', icon: '!' }
    };

    var backdrop = null;
    var els = {};
    var currentOpts = null;

    function ensureDom() {
        if (backdrop) return;
        if (!document.getElementById('fwModalStyle')) {
            var st = document.createElement('style');
            st.id = 'fwModalStyle';
            st.textContent = '@keyframes fwPopIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}';
            document.head.appendChild(st);
        }
        backdrop = document.createElement('div');
        backdrop.id = 'fwModalBackdrop';
        backdrop.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(2px);align-items:center;justify-content:center;z-index:10050;padding:16px;box-sizing:border-box;';
        backdrop.innerHTML =
            '<div style="background:#ffffff;border-radius:12px;width:440px;max-width:95vw;box-shadow:0 20px 25px -5px rgba(0,0,0,0.15),0 10px 10px -5px rgba(0,0,0,0.05);overflow:hidden;animation:fwPopIn .18s ease-out;">' +
                '<div style="padding:24px 24px 16px;">' +
                    '<div id="fwMIcon" style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:16px;font-size:22px;font-weight:700;">?</div>' +
                    '<h3 id="fwMTitle" style="margin:0 0 8px;font-size:17px;font-weight:700;color:#0f172a;"></h3>' +
                    '<div id="fwMMsg" style="margin:0;font-size:13.5px;color:#475467;line-height:1.5;"></div>' +
                    '<div id="fwMInputWrap" style="display:none;margin-top:14px;">' +
                        '<label id="fwMInputLabel" style="display:block;font-size:12.5px;font-weight:600;color:#334155;margin-bottom:6px;"></label>' +
                        '<textarea id="fwMInput" rows="3" style="width:100%;padding:9px 12px;font-size:13.5px;color:#0f172a;background:#ffffff;border:1px solid #cbd5e1;border-radius:6px;box-sizing:border-box;outline:none;resize:vertical;font-family:inherit;"></textarea>' +
                        '<div id="fwMErr" style="display:none;margin-top:6px;font-size:12.5px;color:#dc2626;"></div>' +
                    '</div>' +
                '</div>' +
                '<div style="padding:16px 24px;background:#f8fafc;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:flex-end;gap:10px;">' +
                    '<button type="button" id="fwMCancel" style="height:36px;padding:0 16px;border:1px solid #d0d5dd;background:#ffffff;color:#344054;border-radius:6px;font-size:13.5px;font-weight:600;cursor:pointer;">Hủy bỏ</button>' +
                    '<button type="button" id="fwMOk" style="height:36px;padding:0 20px;border:none;background:#2563eb;color:#ffffff;border-radius:6px;font-size:13.5px;font-weight:600;cursor:pointer;">Xác nhận</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(backdrop);

        els.icon = document.getElementById('fwMIcon');
        els.title = document.getElementById('fwMTitle');
        els.msg = document.getElementById('fwMMsg');
        els.inputWrap = document.getElementById('fwMInputWrap');
        els.inputLabel = document.getElementById('fwMInputLabel');
        els.input = document.getElementById('fwMInput');
        els.err = document.getElementById('fwMErr');
        els.cancel = document.getElementById('fwMCancel');
        els.ok = document.getElementById('fwMOk');

        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) closeModal();
        });
        els.cancel.addEventListener('click', closeModal);
        els.ok.addEventListener('click', submitModal);
        els.input.addEventListener('input', function () {
            els.err.style.display = 'none';
            els.input.style.borderColor = '#cbd5e1';
        });
        document.addEventListener('keydown', function (e) {
            if (backdrop && backdrop.style.display === 'flex' && e.key === 'Escape') {
                closeModal();
            }
        });
    }

    function closeModal() {
        if (backdrop) backdrop.style.display = 'none';
        currentOpts = null;
    }

    function submitModal() {
        if (!currentOpts) return;
        var val = '';
        if (currentOpts.mode === 'prompt') {
            val = els.input.value.trim();
            if (currentOpts.required && !val) {
                els.err.textContent = currentOpts.requiredMsg || 'Vui lòng nhập thông tin bắt buộc.';
                els.err.style.display = 'block';
                els.input.style.borderColor = '#dc2626';
                els.input.focus();
                return;
            }
        }
        var cb = currentOpts.onConfirm;
        closeModal();
        if (typeof cb === 'function') cb(val);
    }

    function openModal(opts) {
        ensureDom();
        currentOpts = opts || {};
        var t = tones[opts.tone] || tones.info;
        els.icon.textContent = opts.icon || t.icon;
        els.icon.style.background = t.bg;
        els.icon.style.color = t.color;

        els.title.textContent = opts.title || 'Xác nhận thao tác';
        if (opts.htmlMessage) {
            els.msg.innerHTML = opts.htmlMessage;
        } else {
            els.msg.textContent = opts.message || '';
        }

        if (opts.mode === 'prompt') {
            els.inputWrap.style.display = 'block';
            els.inputLabel.innerHTML = (opts.label || 'Nội dung') + (opts.required ? ' <span style="color:#ef4444;">*</span>' : '');
            els.input.value = opts.defaultValue || '';
            els.input.placeholder = opts.placeholder || '';
            els.input.style.borderColor = '#cbd5e1';
            els.err.style.display = 'none';
        } else {
            els.inputWrap.style.display = 'none';
        }

        els.cancel.style.display = opts.mode === 'alert' ? 'none' : 'inline-flex';
        els.cancel.style.alignItems = 'center';
        els.cancel.textContent = opts.cancelText || 'Hủy bỏ';

        els.ok.textContent = opts.okText || (opts.mode === 'alert' ? 'Đã hiểu' : 'Xác nhận');
        els.ok.style.background = opts.okColor || t.btn;

        backdrop.style.display = 'flex';
        if (opts.mode === 'prompt') {
            setTimeout(function () { els.input.focus(); }, 30);
        } else {
            setTimeout(function () { els.ok.focus(); }, 30);
        }
    }

    window.fwModal = {
        confirm: function (opts) {
            openModal(Object.assign({ mode: 'confirm', tone: 'info' }, opts));
        },
        prompt: function (opts) {
            openModal(Object.assign({ mode: 'prompt', tone: 'info' }, opts));
        },
        alert: function (opts) {
            openModal(Object.assign({ mode: 'alert', tone: 'warn', title: 'Thông báo' }, opts));
        },
        postForm: function (action, fields) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = action;
            Object.keys(fields || {}).forEach(function (k) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = k;
                inp.value = fields[k] == null ? '' : String(fields[k]);
                form.appendChild(inp);
            });
            document.body.appendChild(form);
            form.submit();
        }
    };
})();
