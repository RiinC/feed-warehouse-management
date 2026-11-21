// Chuông thông báo: mở/đóng hộp thả xuống và tự cập nhật số chưa đọc + danh sách mỗi 30 giây
(function () {
    var root = document.getElementById('notifRoot');
    if (!root) { return; }
    var toggle = document.getElementById('notifToggle');
    var panel = document.getElementById('notifPanel');
    var list = document.getElementById('notifList');
    var count = document.getElementById('notifCount');
    var pollUrl = root.getAttribute('data-poll-url');

    function setOpen(open) {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    toggle.addEventListener('click', function (ev) {
        ev.stopPropagation();
        setOpen(panel.hidden);
    });
    document.addEventListener('click', function (ev) {
        if (!root.contains(ev.target)) { setOpen(false); }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') { setOpen(false); }
    });

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text) { node.textContent = text; }
        return node;
    }

    function render(data) {
        if (data.unread > 0) {
            count.textContent = data.unread > 99 ? '99+' : String(data.unread);
            count.hidden = false;
        } else {
            count.hidden = true;
        }
        list.textContent = '';
        if (!data.items.length) {
            list.appendChild(el('li', 'notif-empty', 'Chưa có thông báo nào.'));
            return;
        }
        data.items.forEach(function (n) {
            var li = document.createElement('li');
            var a = el('a', 'notif-item ' + (n.read ? 'read' : 'unread') + ' type-' + n.type);
            a.href = n.url;
            var body = el('span', 'notif-body');
            body.appendChild(el('b', '', n.title));
            if (n.message) { body.appendChild(el('span', 'notif-msg', n.message)); }
            body.appendChild(el('small', '', n.time));
            a.appendChild(el('span', 'notif-dot'));
            a.appendChild(body);
            li.appendChild(a);
            list.appendChild(li);
        });
    }

    function poll() {
        if (document.hidden || !pollUrl) { return; }
        fetch(pollUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) { if (data && data.items) { render(data); } })
            .catch(function () { /* mất mạng: bỏ qua, lần sau thử lại */ });
    }

    setInterval(poll, 30000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { poll(); } });
})();
