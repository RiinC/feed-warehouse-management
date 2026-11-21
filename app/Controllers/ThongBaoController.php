<?php
namespace App\Controllers;

use App\Models\ThongBao;
use Core\Auth;
use Core\Request;

/** Thông báo của tôi: danh sách, mở một thông báo, cập nhật chuông (JSON), đánh dấu đã đọc. */
class ThongBaoController extends Controller
{
    /** GET thong-bao — danh sách toàn bộ thông báo (từ chuông bấm "Xem tất cả thông báo") */
    public function index(): void
    {
        $filter  = Request::get('loc') === 'chua-doc' ? 'chua-doc' : '';
        $page    = max(1, Request::int('page', 1));
        $perPage = (int)config('per_page', 10);
        [$rows, $total] = ThongBao::page(Auth::staffId(), $page, $perPage, $filter);

        $readAll = '<form method="post" action="' . e(route('thong-bao/read-all')) . '">'
                 . csrf_field()
                 . '<input type="hidden" name="back" value="thong-bao">'
                 . '<button class="btn" type="submit">Đánh dấu đã đọc tất cả</button></form>';

        $this->render('thong-bao/index', [
            'rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage,
        ], [
            'title'      => 'Thông báo',
            'subtitle'   => 'Bấm vào một thông báo để chuyển tới trang liên quan.',
            'module'     => 'thong-bao',
            'breadcrumb' => [['Thông báo', null]],
            'actions'    => $readAll,
        ]);
    }

    /** GET thong-bao/open?id= — đánh dấu đã đọc rồi chuyển tới trang đích (nếu vai trò có quyền mở trang đó) */
    public function open(): void
    {
        $id = Request::int('id');
        $n = $id > 0 ? ThongBao::find($id, Auth::staffId()) : null;

        if (!$n) {
            $this->flash('warning', 'Thông báo không tồn tại hoặc đã bị xóa.');
            $this->redirect('thong-bao');
        }
        ThongBao::markRead($id, Auth::staffId());

        $link = ThongBao::safeLink($n['duongDan']);
        if ($link !== null && !can_access_route($link)) {
            // Thông báo trỏ tới chức năng mà vai trò hiện tại không có quyền -> không đẩy người dùng vào trang 403
            $this->flash('info', 'Thông báo này liên quan tới chức năng bạn không có quyền sử dụng.');
            $link = null;
        }
        $this->redirect($link ?? 'thong-bao');
    }

    /** GET thong-bao/poll — JSON: số chưa đọc + vài thông báo mới nhất (trình duyệt gọi 30 giây/lần) */
    public function poll(): void
    {
        $id = Auth::staffId();
        $items = [];
        foreach (ThongBao::recent($id, 8) as $n) {
            $items[] = [
                'id'      => (int)$n['maThongBao'],
                'title'   => $n['tieuDe'],
                'message' => (string)$n['noiDung'],
                'type'    => $n['loai'],
                'read'    => (bool)$n['daDoc'],
                'time'    => time_ago($n['thoiDiemTao']),
                'url'     => route('thong-bao/open', ['id' => (int)$n['maThongBao']]),
            ];
        }
        $this->json(['unread' => ThongBao::unreadCount($id), 'items' => $items]);
    }

    /** POST thong-bao/read-all — đánh dấu tất cả đã đọc rồi quay lại trang trước */
    public function readAll(): void
    {
        ThongBao::markAllRead(Auth::staffId());
        $back = ThongBao::safeLink((string)Request::post('back'));
        $this->redirect($back ?? 'thong-bao');
    }
}
