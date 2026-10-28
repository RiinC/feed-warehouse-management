/* =========================================================
   app.js - JavaScript thuần cho toàn hệ thống
   ========================================================= */
(function () {
    'use strict';

    /* ---------- Sidebar (mobile) ---------- */
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var toggle = document.getElementById('menuToggle');
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('show');
    }
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (backdrop) backdrop.classList.toggle('show');
        });
    }
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    /* ---------- Menu người dùng ---------- */
    var userBtn = document.getElementById('userBtn');
    var userDropdown = document.getElementById('userDropdown');
    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', function (ev) {
            ev.stopPropagation();
            userDropdown.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            userDropdown.classList.remove('open');
        });
    }

    /* ---------- Xác nhận trước khi submit (form có data-confirm) ---------- */
    document.addEventListener('submit', function (ev) {
        var msg = ev.target && ev.target.getAttribute && ev.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) ev.preventDefault();
    });

    /* ---------- Tự submit khi đổi select (đổi trạng thái) ---------- */
    document.querySelectorAll('.js-auto-submit').forEach(function (sel) {
        sel.addEventListener('change', function () {
            if (sel.form) sel.form.submit();
        });
    });

    /* ---------- Form chứng từ: thêm/xóa dòng hàng, tự tính thành tiền & tổng ---------- */
    function initDocForm() {
        var form = document.getElementById('docForm');
        if (!form) return;

        var hang = JSON.parse(form.getAttribute('data-hang') || '[]');
        var initial = JSON.parse(form.getAttribute('data-rows') || '[]');
        var readonly = form.getAttribute('data-readonly') === '1';
        var tbody = form.querySelector('#lines tbody');
        var grandTotal = document.getElementById('grandTotal');
        var lineCount = document.getElementById('lineCount');
        var addBtn = document.getElementById('addRow');

        var byId = {};
        hang.forEach(function (h) { byId[h.id] = h; });

        var nf = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 });
        var trashSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';

        function makeInput(type, name, value, extra) {
            var i = document.createElement('input');
            i.type = type;
            i.name = name;
            i.value = value;
            i.disabled = readonly;
            if (extra) Object.keys(extra).forEach(function (k) { i.setAttribute(k, extra[k]); });
            return i;
        }

        function cell(cls) {
            var td = document.createElement('td');
            if (cls) td.className = cls;
            return td;
        }

        function addRow(d) {
            d = d || { hang_hoa_id: 0, so_luong: '', don_gia: '', ghi_chu: '' };
            var tr = document.createElement('tr');

            var tdStt = cell('stt');

            var tdHang = cell();
            var sel = document.createElement('select');
            sel.name = 'hang_hoa_id[]';
            sel.disabled = readonly;
            sel.add(new Option('— Chọn hàng hóa —', '0'));
            hang.forEach(function (h) { sel.add(new Option(h.ma + ' — ' + h.ten, String(h.id))); });
            sel.value = String(d.hang_hoa_id || 0);
            tdHang.appendChild(sel);

            var tdDvt = cell('cell-dvt');
            var tdSl = cell();
            var sl = makeInput('number', 'so_luong[]', d.so_luong === '' ? '' : d.so_luong, { min: '0', step: 'any', placeholder: '0' });
            tdSl.appendChild(sl);

            var tdDg = cell();
            var dg = makeInput('number', 'don_gia[]', d.don_gia === '' ? '' : d.don_gia, { min: '0', step: 'any', placeholder: '0' });
            tdDg.appendChild(dg);

            var tdTotal = cell('cell-total');

            var tdGc = cell();
            tdGc.appendChild(makeInput('text', 'ghi_chu_dong[]', d.ghi_chu || '', { maxlength: '255', placeholder: 'Ghi chú' }));

            tr.appendChild(tdStt);
            tr.appendChild(tdHang);
            tr.appendChild(tdDvt);
            tr.appendChild(tdSl);
            tr.appendChild(tdDg);
            tr.appendChild(tdTotal);
            tr.appendChild(tdGc);

            if (!readonly) {
                var tdDel = cell();
                var del = document.createElement('button');
                del.type = 'button';
                del.className = 'act act-danger';
                del.title = 'Xóa dòng';
                del.innerHTML = trashSvg;
                del.addEventListener('click', function () {
                    tr.parentNode.removeChild(tr);
                    if (!tbody.children.length) addRow();
                    recalc();
                });
                tdDel.appendChild(del);
                tr.appendChild(tdDel);
            }

            sel.addEventListener('change', function () {
                var h = byId[sel.value];
                if (h) {
                    dg.value = h.gia;
                    if (!sl.value) sl.value = 1;
                }
                recalc();
            });
            sl.addEventListener('input', recalc);
            dg.addEventListener('input', recalc);

            tbody.appendChild(tr);
            return tr;
        }

        function recalc() {
            var total = 0;
            var count = 0;
            Array.prototype.forEach.call(tbody.children, function (tr, idx) {
                var sel = tr.querySelector('select');
                var inputs = tr.querySelectorAll('input[type=number]');
                var h = byId[sel.value];
                var q = parseFloat(inputs[0].value) || 0;
                var p = parseFloat(inputs[1].value) || 0;
                var line = Math.round(q * p * 100) / 100;
                tr.querySelector('.stt').textContent = idx + 1;
                tr.querySelector('.cell-dvt').textContent = h ? h.dv : '—';
                tr.querySelector('.cell-total').textContent = nf.format(line);
                total += line;
                if (h && q > 0) count++;
            });
            if (grandTotal) grandTotal.textContent = nf.format(total);
            if (lineCount) lineCount.textContent = count;
        }

        initial.forEach(function (r) { addRow(r); });
        if (!initial.length && !readonly) addRow();
        recalc();

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var tr = addRow();
                recalc();
                var s = tr.querySelector('select');
                if (s) s.focus();
            });
        }

        if (!readonly) {
            form.addEventListener('submit', function (ev) {
                var ok = false;
                Array.prototype.forEach.call(tbody.children, function (tr) {
                    var sel = tr.querySelector('select');
                    var q = parseFloat(tr.querySelector('input[type=number]').value) || 0;
                    if (sel.value !== '0' && q > 0) ok = true;
                });
                if (!ok) {
                    ev.preventDefault();
                    window.alert('Vui lòng chọn ít nhất một hàng hóa và nhập số lượng lớn hơn 0.');
                }
            });
        }
    }

    initDocForm();
})();

/* ---------- UC06: popup chi tiết phiếu tiếp nhận ---------- */
(function () {
    function initDetailModal() {
        var modal = document.getElementById('detailModal');
        var body = document.getElementById('detailModalBody');
        if (!modal || !body) return;

        var lastFocus = null;
        var reqId = 0;

        function focusables() {
            return Array.prototype.slice.call(modal.querySelectorAll('button, [href], input, select, textarea'));
        }

        function close() {
            reqId++;                       // bỏ qua kết quả của yêu cầu đang chạy
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            if (lastFocus && lastFocus.focus) lastFocus.focus();
        }

        function open(url, trigger) {
            lastFocus = trigger || document.activeElement;
            body.innerHTML = '<p class="muted" style="padding:14px 0">Đang tải chi tiết phiếu...</p>';
            modal.hidden = false;
            document.body.classList.add('modal-open');
            var closeBtn = modal.querySelector('.modal-foot [data-modal-close]');
            if (closeBtn) closeBtn.focus();

            var id = ++reqId;
            fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
                .then(function (res) {
                    return res.text().then(function (text) { return { ok: res.ok, text: text }; });
                })
                .then(function (r) {
                    if (id !== reqId) return;
                    if (r.ok) {
                        body.innerHTML = r.text;
                    } else {
                        body.innerHTML = '<div class="alert alert-error" role="alert">Không tải được chi tiết phiếu tiếp nhận. Vui lòng thử lại.</div>';
                    }
                })
                .catch(function () {
                    if (id !== reqId) return;
                    body.innerHTML = '<div class="alert alert-error" role="alert">Không tải được chi tiết phiếu tiếp nhận. Vui lòng thử lại.</div>';
                });
        }

        // Nhấn vào dòng (hoặc vào mã phiếu) -> mở popup; liên kết khác trong dòng giữ nguyên hành vi
        document.addEventListener('click', function (ev) {
            var row = ev.target.closest ? ev.target.closest('.js-row-detail') : null;
            if (!row) return;
            var a = ev.target.closest('a');
            if (a && !a.classList.contains('js-detail-link')) return;
            ev.preventDefault();
            open(row.getAttribute('data-url'), row.querySelector('.js-detail-link') || row);
        });

        modal.addEventListener('click', function (ev) {
            if (ev.target.closest('[data-modal-close]')) close();
        });

        document.addEventListener('keydown', function (ev) {
            if (modal.hidden) return;
            if (ev.key === 'Escape') {
                close();
            } else if (ev.key === 'Tab') {          // giữ con trỏ Tab trong popup
                var f = focusables();
                if (!f.length) return;
                var first = f[0], last = f[f.length - 1];
                if (ev.shiftKey && document.activeElement === first) { ev.preventDefault(); last.focus(); }
                else if (!ev.shiftKey && document.activeElement === last) { ev.preventDefault(); first.focus(); }
            }
        });
    }

    initDetailModal();
})();
