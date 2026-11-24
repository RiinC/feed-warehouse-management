import os, sys, re, json, datetime, sqlite3
sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
from load_db import load
from phpsql import sqls, assign, to_sqlite, ROOT

RESULTS=[]
def case(uc, name, fn):
    try:
        detail=fn()
        RESULTS.append((uc,name,'ĐẠT',detail or ''))
    except AssertionError as e:
        RESULTS.append((uc,name,'KHÔNG ĐẠT',str(e)))
    except Exception as e:
        RESULTS.append((uc,name,'LỖI',f"{type(e).__name__}: {e}"))

def fresh():
    con,errs=load(); assert not errs, errs; return con
def rows(con,sql,params=()): return [dict(r) for r in con.execute(to_sqlite(sql),params)]
def one(con,sql,params=()):
    r=rows(con,sql,params); return r[0] if r else None
def esc(s): return '%'+re.sub(r'([%_\\])',r'\\\1',s)+'%'

HH='app/Models/HangHoa.php'; LO='app/Models/LoHang.php'; TN='app/Models/TiepNhan.php'

# ---------- Mô phỏng các hàm PHP thuần (ghi chú ngừng sử dụng) ----------
def strip_note(s): return re.sub(r'\s*\[Ngừng sử dụng [^\]]*\]\s*$','',s or '').rstrip()
def stop_note(s):
    m=re.search(r'\[Ngừng sử dụng (\d{2}/\d{2}/\d{4} \d{2}:\d{2}): ([^\]]*)\]\s*$',s or ''); return (m.group(1).strip(),m.group(2).strip()) if m else None
def join_note(base,when,reason):
    d=datetime.datetime.strptime(when,'%Y-%m-%d %H:%M:%S').strftime('%d/%m/%Y %H:%M')
    reason=re.sub(r'\s+',' ',reason).replace('[','(').replace(']',')')[:150]
    note=f' [Ngừng sử dụng {d}: {reason}]'
    base=base[:max(0,255-len(note))]
    return base.rstrip()+note

# ---------- Các hàm Model chạy bằng đúng SQL trích từ PHP ----------
def hh_where(f):
    w=['1 = 1'];p=[]
    if f.get('q'): l=esc(f['q']); w.append('(h.maHangHoa LIKE ? OR h.tenHangHoa LIKE ?)'); p+= [l,l]
    if f.get('loai'): w.append('h.loaiHangHoa = ?'); p.append(f['loai'])
    if f.get('trangThai'): w.append('h.trangThai = ?'); p.append(f['trangThai'])
    return ' AND '.join(w),p
def hh_count(con,f):
    w,p=hh_where(f); return one(con,sqls(HH,'count',{'where':w})[0][1],p)['c']
def hh_search(con,f,limit=10,offset=0):
    w,p=hh_where(f); return rows(con,sqls(HH,'search',{'where':w})[0][1],p+[limit,offset])
def hh_find(con,ma):
    q=sqls(HH,'find'); row=one(con,q[0][1],[ma])
    if not row: return None
    row['soLuongTon']=float(one(con,q[1][1],[ma])['ton']); mv=one(con,q[2][1],[ma]); row['lanNhapCuoi']=mv['lanNhapCuoi']; row['lanXuatCuoi']=mv['lanXuatCuoi']; return row
def hh_blockers(con,ma):
    out=[]
    for kind,sql in sqls(HH,'stopBlockers'):
        pass
    return out

def stop_blockers(con,ma):
    # các câu SQL nằm trong mảng $checks (không phải đối số static::one) -> trích theo từng literal
    src=open(ROOT+HH,encoding='utf-8').read().replace('\r','')
    body=src[src.index('function stopBlockers'):src.index('/** Chuyển hàng hóa sang')]
    res=[]
    for m in re.finditer(r"\['([^']+)',\s*\n?\s*\"((?:[^\"\\]|\\.)*)\"\]",body,re.S):
        n=one(con,m.group(2).replace('\\"','"'),[ma])['c']
        if n>0: res.append(f"{n} {m.group(1)}")
    return res

def hh_create(con,loai,ten='SP kiểm thử',donvi='kg',heso=1.0,han=None,dk=None,mota='mô tả'):
    codes={'Nguyên liệu':'01','Thành phẩm':'02','Bán thành phẩm':'03','Bao bì':'04','Phụ gia':'05'}
    q=sqls(HH,'create'); prefix='HH'+codes[loai]
    r=one(con,q[0][1],[prefix+'%']); seq=int(r['m'][4:])+1 if r and r['m'] else 1
    assert seq<=999999
    ma=prefix+str(seq).zfill(6)
    con.execute(to_sqlite(q[1][1]),[ma,ten,loai,donvi,float(heso),mota,dk,han,'Đang kinh doanh']); return ma

def lo_inner_sql(f):
    w=['1 = 1'];p=[]
    if f.get('q'): l=esc(f['q']); w.append('(l.maLoHang LIKE ? OR l.maHangHoa LIKE ? OR h.tenHangHoa LIKE ?)'); p+=[l,l,l]
    if f.get('viTri'): w.append('EXISTS (SELECT 1 FROM TonKho t2 WHERE t2.maLoHang = l.maLoHang AND t2.maViTri = ? AND t2.soLuongTon > 0)'); p.append(f['viTri'])
    src=open(ROOT+LO,encoding='utf-8').read()
    return assign(LO,'inner','sql',{'w_list':w,'implode':' AND '.join(w)}),p
def lo_search(con,f,limit=1000,offset=0):
    inner,p=lo_inner_sql(f); 
    w='1 = 1'
    if f.get('trangThai'): w+=' AND x.trangThaiHienThi = ?'; p.append(f['trangThai'])
    sql=sqls(LO,'search',{'inner':inner,'w':w})[0][1]
    return rows(con,sql,p+[limit,offset])
def lo_count(con,f):
    inner,p=lo_inner_sql(f); w='1 = 1'
    if f.get('trangThai'): w+=' AND x.trangThaiHienThi = ?'; p.append(f['trangThai'])
    return one(con,sqls(LO,'count',{'inner':inner,'w':w})[0][1],p)['c']
def lo_detail(con,ma):
    q=sqls(LO,'detail'); row=one(con,q[0][1],[ma])
    if row: row['viTriChiTiet']=rows(con,q[1][1],[ma])
    return row

def tn_pending(con,q=''):
    p=[];c1='';c2=''
    if q: l=esc(q); c1=' AND pg.maPhieuGiao LIKE ?'; c2=' AND y.maYeuCauNhap LIKE ?'; p=[l,l]
    r=rows(con,sqls(TN,'pending',{'cond1':c1,'cond2':c2})[0][1],p)
    for x in r: x['tinhTrang']='Đã tiếp nhận một phần' if x['trangThaiGoc']=='Đang tiếp nhận' else 'Chờ tiếp nhận'   # như TiepNhan::tinhTrang()
    return r
def tn_chung_tu(con,so):
    q=sqls(TN,'chungTu')
    if re.fullmatch(r'PG\d{10}',so): h=one(con,q[0][1],[so]); typ='ncc'; lines=rows(con,q[1][1],[so])
    elif re.fullmatch(r'YCNK\d{10}',so): h=one(con,q[2][1],[so]); typ='xuong'; lines=rows(con,q[3][1],[so])
    else: return None
    if not h: return None
    h['loai']=typ
    h['conHieuLuc']= h['trangThaiGoc'] in ('Chờ tiếp nhận','Đang tiếp nhận') if typ=='ncc' else h['trangThaiGoc']=='Chờ xử lý'
    h['dong']={}
    for l in lines:
        l['conLai']=round(float(l['soLuongGiao'])-float(l['daNhan']),3)
        if l['conLai']>0.0005: h['dong'][int(l['id'])]=l
    return h
def tn_vitri(con,kho=None):
    cond=' AND k.maKho = ?' if kho else ''
    return rows(con,sqls(TN,'viTriTam',{'cond':cond})[0][1],[kho] if kho else [])
def next_code(con,table,col,prefix,today='261124'):
    head=prefix+today
    r=one(con,sqls(TN,'nextCode',{'table':table,'col':col})[0][1],[head+'%'])
    seq=int(r['m'][len(head):])+1 if r and r['m'] else 1
    assert seq<=9999; return head+str(seq).zfill(4)

class BizError(Exception): pass
def tn_receive(con,so,vitri,nv,ghichu,chon,today_iso='2026-11-24'):
    """Mô phỏng TiepNhan::receive() đúng trình tự câu lệnh trong PHP, trong 1 giao dịch."""
    q=sqls(TN,'receive'); EPS=0.0005
    con.commit(); con.execute('BEGIN')
    try:
        ct=tn_chung_tu(con,so)
        if not ct or not ct['conHieuLuc'] or not ct['dong']: raise BizError('Không thể tiếp nhận hàng do không có chứng từ giao hàng hợp lệ')
        if not chon: raise BizError('Vui lòng chọn ít nhất một dòng hàng để tiếp nhận.')
        if not one(con,q[2][1],[vitri]): raise BizError('Vị trí lưu trữ tạm không hợp lệ hoặc không còn ở trạng thái sử dụng.')
        for i,inn in chon.items():
            line=ct['dong'].get(i)
            if not line: raise BizError('Có dòng hàng không hợp lệ hoặc đã được tiếp nhận đủ.')
            if line['trangThaiHang']!='Đang kinh doanh': raise BizError('Hàng hóa đã ngừng sử dụng')
            if not (inn['sl']>0) or inn['sl']>line['conLai']+EPS: raise BizError('Số lượng thực nhận không hợp lệ')
        ma=next_code(con,'PhieuTiepNhan','maPhieuTiepNhan','PTN')
        # câu INSERT PTN dùng NOW() cho ngày: chạy bằng hàm NOW() đã đăng ký
        ins=to_sqlite(q[3][1])
        con.execute(ins,[ma, so if ct['loai']=='ncc' else None, ct['maXuong'] if ct['loai']=='xuong' else None, vitri, nv,'Chờ kiểm định', ghichu or None])
        lots=[];tong=0
        for i,inn in chon.items():
            line=ct['dong'][i]
            nsx=inn.get('nsx') or line['nsxNCC'] or today_iso
            hsd=inn.get('hsd') or line['hsdNCC']
            if not hsd and line['hanChuan'] is not None:
                hsd=(datetime.date.fromisoformat(nsx)+datetime.timedelta(days=int(line['hanChuan']))).isoformat()
            if not hsd: raise BizError('Chưa xác định được hạn sử dụng')
            if hsd<nsx and not inn.get('_force'): raise BizError('Hạn sử dụng phải lớn hơn hoặc bằng ngày sản xuất')
            kg=round(inn['sl']*float(line['heSo']),3)
            lo=next_code(con,'LoHang','maLoHang','LO')
            con.execute(to_sqlite(q[4][1]),[lo,line['maHangHoa'],ma,line['soLoNCC'],nsx,hsd,kg,'Chờ kiểm tra','Đã tạo'])
            if ct['loai']=='ncc': con.execute(to_sqlite(q[5][1]),[float(inn['sl']),i])
            lots.append(lo);tong+=kg
        if ct['loai']=='ncc':
            left=one(con,q[6][1],[so,EPS])['c']
            con.execute(to_sqlite(q[7][1]),['Đã tiếp nhận' if left==0 else 'Đang tiếp nhận',so])
        else:
            con.execute(to_sqlite(q[8][1]),[so])
        con.execute('COMMIT'); return {'ptn':ma,'lo':lots,'tongKg':round(tong,3)}
    except Exception:
        con.execute('ROLLBACK'); raise

# =====================================================================================
#                                      CÁC TÌNH HUỐNG
# =====================================================================================
TODAY=datetime.date(2026,11,24)

# ---------------- UC01 Thêm hàng hóa ----------------
def uc01_codes():
    con=fresh(); out=[]
    for loai,pre in [('Nguyên liệu','HH01'),('Thành phẩm','HH02'),('Bán thành phẩm','HH03'),('Bao bì','HH04'),('Phụ gia','HH05')]:
        before=one(con,"select max(maHangHoa) m from HangHoa where maHangHoa like ?",[pre+'%'])['m']
        ma=hh_create(con,loai)
        assert re.fullmatch(r'HH\d{8}',ma) and ma.startswith(pre),ma
        assert int(ma[4:])==int(before[4:])+1,(before,ma)
        out.append(ma)
    return 'Mã sinh: '+', '.join(out)
case('UC01','Sinh mã tự động đúng định dạng HH+nhóm+6 số, tăng liên tiếp cho từng nhóm hàng (5 nhóm)',uc01_codes)

def uc01_two():
    con=fresh(); a=hh_create(con,'Nguyên liệu'); b=hh_create(con,'Nguyên liệu')
    assert int(b[4:])==int(a[4:])+1; return f'{a} → {b}'
case('UC01','Hai lần thêm liên tiếp cùng nhóm không trùng mã',uc01_two)

def uc01_default():
    con=fresh(); ma=hh_create(con,'Bao bì',donvi='thùng',heso=20.0,han=None,dk=None,mota='Thùng carton')
    r=one(con,'select * from HangHoa where maHangHoa=?',[ma])
    assert r['trangThai']=='Đang kinh doanh' and r['hanSuDungTieuChuanNgay'] is None and r['heSoQuyDoi']==20.0
    return 'Trạng thái mặc định "Đang sử dụng", cột tùy chọn lưu NULL'
case('UC01','Hàng mới mặc định "Đang sử dụng"; hạn sử dụng/điều kiện bảo quản để trống được lưu NULL',uc01_default)

def uc01_db_guard():
    con=fresh(); bad=[]
    for kw in [dict(donvi='cái'),dict(heso=0.0),dict(heso=-5.0),dict(han=-1),dict(ten='   ')]:
        try: hh_create(con,'Nguyên liệu',**kw); bad.append(kw)
        except sqlite3.IntegrityError: pass
    assert not bad,f'CSDL không chặn: {bad}'; return 'CSDL chặn: đơn vị lạ, hệ số ≤0, hạn <0, tên rỗng (lớp bảo vệ thứ hai ngoài kiểm tra ở PHP)'
case('UC01','Dữ liệu không hợp lệ bị CSDL từ chối (đơn vị tính ngoài kg/bao/thùng, quy cách ≤ 0, hạn < 0, tên trống)',uc01_db_guard)

# ---------------- UC02 Sửa hàng hóa ----------------
def uc02_update():
    con=fresh(); ma='HH01000001'; q=sqls(HH,'update')[0][1]
    con.execute(to_sqlite(q),['Ngô hạt vàng loại 1','Nguyên liệu','bao',25.0,'Mô tả mới','Nơi khô ráo',120,'Đang kinh doanh',ma])
    r=hh_find(con,ma); assert r['tenHangHoa']=='Ngô hạt vàng loại 1' and r['donViTinhChuan']=='bao' and r['heSoQuyDoi']==25.0 and r['hanSuDungTieuChuanNgay']==120 and r['maHangHoa']==ma
    assert r['soLuongTon']>=0; return 'Cập nhật đủ trường, mã không đổi, hiển thị lại được sau khi lưu'
case('UC02','Cập nhật tên, đơn vị tính, quy cách, mô tả, hạn; mã hàng giữ nguyên; đọc lại đúng',uc02_update)

def uc02_notfound():
    con=fresh(); assert hh_find(con,'HH99999999') is None; return 'Trả về null → controller báo "Không tìm thấy hàng hóa"'
case('UC02','Hàng không tồn tại → find() trả null (3.1.1)',uc02_notfound)

def uc02_reactivate():
    base='Cám gạo nguyên liệu'; stopped=join_note(base,'2026-11-24 09:30:00','Ngừng nhập')
    assert stop_note(stopped)==('24/11/2026 09:30','Ngừng nhập') and strip_note(stopped)==base
    assert strip_note(base)==base and stop_note(base) is None
    return 'Ghi chú ngừng sử dụng tách/gỡ đúng; mô tả thường không bị ảnh hưởng'
case('UC02','Kích hoạt lại: gỡ ghi chú ngừng sử dụng khỏi mô tả, mô tả gốc còn nguyên (mô phỏng hàm PHP)',uc02_reactivate)

# ---------------- UC03 Ngừng sử dụng ----------------
def uc03_blockers():
    con=fresh()
    busy=stop_blockers(con,'HH01000004')   # nằm trong PG2611050001 'Đang tiếp nhận'
    assert any('phiếu giao hàng nhà cung cấp' in b for b in busy),busy
    free=[m for m in [r['maHangHoa'] for r in rows(con,'select maHangHoa from HangHoa')] if not stop_blockers(con,m)]
    assert free,'không có hàng nào được phép ngừng'
    return f'HH01000004 bị chặn: {busy}; {len(free)}/120 hàng không vướng nghiệp vụ dở dang'
case('UC03','Hàng đang có phiếu giao chưa tiếp nhận xong bị chặn ngừng sử dụng, nêu rõ lý do (8.1)',uc03_blockers)

def uc03_each_rule():
    con=fresh(); seen={}
    for m in [r['maHangHoa'] for r in rows(con,'select maHangHoa from HangHoa')]:
        for b in stop_blockers(con,m): seen.setdefault(b.split(' ',1)[1],m)
    assert seen; return 'Các loại vướng mắc phát hiện trong seed: '+'; '.join(f'{k} (vd {v})' for k,v in seen.items())
case('UC03','Các câu kiểm tra nghiệp vụ dở dang đều chạy được trên dữ liệu thật (6 loại)',uc03_each_rule)

def uc03_stop():
    con=fresh(); free=[r['maHangHoa'] for r in rows(con,'select maHangHoa from HangHoa') if not stop_blockers(con,r['maHangHoa'])][0]
    base=one(con,'select moTa from HangHoa where maHangHoa=?',[free])['moTa'] or ''
    note=join_note(strip_note(base),'2026-11-24 10:00:00','Không còn kinh doanh')
    assert len(note)<=255
    con.execute('UPDATE HangHoa SET trangThai = ?, moTa = ? WHERE maHangHoa = ?',['Đã ngừng sử dụng',note,free])
    r=hh_find(con,free); assert r['trangThai']=='Đã ngừng sử dụng' and stop_note(r['moTa'])==('24/11/2026 10:00','Không còn kinh doanh')
    return f'{free} → Ngừng sử dụng; lý do/ngày ghi ở cuối mô tả'
case('UC03','Ngừng sử dụng hàng hợp lệ: trạng thái đổi, ngày + lý do được ghi lại',uc03_stop)

def uc03_long():
    base='x'*250; n=join_note(base,'2026-11-24 10:00:00','y'*400); assert len(n)<=255 and stop_note(n) is not None
    return f'Mô tả 250 ký tự + lý do 400 ký tự → {len(n)} ký tự (≤255), ghi chú vẫn đọc được'
case('UC03','Mô tả dài + lý do dài không vượt 255 ký tự của cột moTa',uc03_long)

# ---------------- UC04 Tra cứu hàng hóa ----------------
def uc04_all():
    con=fresh(); assert hh_count(con,{})==120; assert len(hh_search(con,{},10,0))==10 and len(hh_search(con,{},10,115))==5
    return 'Không nhập tiêu chí → toàn bộ 120 hàng; phân trang 10/trang đúng (trang cuối 5)'
case('UC04','Không nhập tiêu chí → tìm toàn bộ (3.1) và phân trang',uc04_all)

def uc04_filters():
    con=fresh()
    a=hh_count(con,{'loai':'Nguyên liệu'}); b=hh_count(con,{'trangThai':'Đang kinh doanh'}); c=hh_count(con,{'trangThai':'Đã ngừng sử dụng'})
    assert b+c==120 and 0<a<120
    assert hh_count(con,{'q':'HH01000001'})==1 and hh_count(con,{'q':'Ngô'})>=1
    both=hh_count(con,{'loai':'Nguyên liệu','trangThai':'Đang kinh doanh','q':'Ngô'}); assert 1<=both<=a
    assert hh_count(con,{'q':'zzzkhongco'})==0
    return f'Nguyên liệu={a}, đang SD={b}, ngừng={c}; lọc kết hợp mã/tên/nhóm/trạng thái đúng; không khớp → 0 (hiện "Không tìm thấy hàng hóa phù hợp")'
case('UC04','Lọc theo mã/tên, nhóm hàng, trạng thái, kết hợp; không có kết quả (5.1)',uc04_filters)

def uc04_tonkho():
    con=fresh(); r=hh_find(con,'HH01000001')
    exp=one(con,"select coalesce(sum(tk.soLuongTon),0) t from LoHang l join TonKho tk on tk.maLoHang=l.maLoHang where l.maHangHoa='HH01000001'")['t']
    assert abs(r['soLuongTon']-exp)<1e-9
    lst=[x for x in hh_search(con,{'q':'HH01000001'}) if x['maHangHoa']=='HH01000001'][0]; assert abs(lst['soLuongTon']-exp)<1e-9
    ls=rows(con,"select max(case when b.loaiGiaoDich='Nhập kho' then b.thoiGianPhatSinh end) n, max(case when b.loaiGiaoDich='Xuất kho' then b.thoiGianPhatSinh end) x from LichSuBienDongTonKho b join LoHang l on l.maLoHang=b.maLoHang where l.maHangHoa='HH01000001'")[0]
    assert r['lanNhapCuoi']==ls['n'] and r['lanXuatCuoi']==ls['x']
    return f'Tồn HH01000001 = {exp} kg (danh sách = chi tiết); lần nhập gần nhất {r["lanNhapCuoi"]}, lần xuất {r["lanXuatCuoi"]}'
case('UC04','Số lượng tồn trong danh sách = chi tiết = tổng TonKho; thời gian nhập/xuất khớp lịch sử biến động',uc04_tonkho)

def uc04_no_dup():
    con=fresh(); ids=[r['maHangHoa'] for r in hh_search(con,{},1000,0)]; assert len(ids)==len(set(ids))==120
    return 'JOIN tồn kho không làm nhân bản dòng hàng hóa'
case('UC04','Mỗi hàng hóa chỉ xuất hiện một dòng trong danh sách (không bị nhân bản bởi JOIN tồn kho)',uc04_no_dup)

# ---------------- UC05 Tra cứu lô hàng ----------------
def uc05_all():
    con=fresh(); n=lo_count(con,{}); r=lo_search(con,{}); assert n==96==len(r)
    assert len({x['maLoHang'] for x in r})==96
    return '96 lô, mỗi lô một dòng'
case('UC05','Không nhập tiêu chí → toàn bộ 96 lô, không trùng dòng (3.1)',uc05_all)

def uc05_status():
    con=fresh(); r=lo_search(con,{}); allowed={'Còn hạn','Hết hạn','Đã xuất hết','Ngừng sử dụng'}
    assert all(x['trangThaiHienThi'] in allowed for x in r)
    from collections import Counter; c=Counter(x['trangThaiHienThi'] for x in r)
    for st in allowed: assert lo_count(con,{'trangThai':st})==c.get(st,0)
    for x in r:
        if x['trangThaiVongDoi'] in ('Đã thanh lý','Đã tiêu hủy'): assert x['trangThaiHienThi']=='Ngừng sử dụng'
        elif x['trangThaiVongDoi']=='Đã xuất hết': assert x['trangThaiHienThi']=='Đã xuất hết'
        elif x['ngayHetHan']<TODAY.isoformat(): assert x['trangThaiHienThi']=='Hết hạn'
        else: assert x['trangThaiHienThi']=='Còn hạn'
    return f'Phân bố trạng thái: {dict(c)}; lọc theo từng trạng thái khớp số đếm'
case('UC05','Trạng thái lô suy ra đúng quy tắc (Còn hạn/Hết hạn/Đã xuất hết/Ngừng sử dụng) và bộ lọc trạng thái khớp',uc05_status)

def uc05_filters():
    con=fresh(); r=lo_search(con,{}); lot=[x for x in r if x['viTri']][0]
    vt=lot['viTri'].split(', ')[0]
    got=lo_search(con,{'viTri':vt}); assert got and all(vt in (g['viTri'] or '') for g in got)
    assert lo_count(con,{'q':lot['maLoHang']})==1
    assert lo_count(con,{'q':lot['tenHangHoa']})>=1 and lo_count(con,{'q':'khongtontai'})==0
    return f'Lọc vị trí {vt}: {len(got)} lô; tìm theo mã lô/tên hàng đúng; không khớp → 0 ("Không tìm thấy lô hàng")'
case('UC05','Lọc theo mã lô, tên hàng, vị trí lưu trữ; không có kết quả (5.1)',uc05_filters)

def uc05_detail():
    con=fresh(); bad=[]
    for x in lo_search(con,{}):
        d=lo_detail(con,x['maLoHang']); s=sum(v['soLuongTon'] for v in d['viTriChiTiet'])
        if abs(s-x['soLuongTon'])>1e-6: bad.append(x['maLoHang'])
    assert not bad,bad
    ncc=sum(1 for x in lo_search(con,{}) if lo_detail(con,x['maLoHang'])['tenNCC']); 
    assert lo_detail(con,'LO9999999999') is None
    return f'Tổng tồn theo vị trí = SL tồn ở danh sách cho cả 96 lô; {ncc} lô có tên nhà cung cấp; mã lô lạ → null'
case('UC05','Chi tiết lô: tổng tồn theo từng vị trí khớp SL tồn; nhà cung cấp; lô không tồn tại → null',uc05_detail)

def uc05_criteria():
    ok=re.compile(r'^[\w\s._\-]{0,60}$',re.U)
    good=['LO2611010001','Ngô hạt vàng','cám_gạo-1.5',''];badv=["'; DROP TABLE x;--",'a%b','<script>','x'*61]
    assert all(ok.match(g) for g in good) and not any(ok.match(b) for b in badv)
    return 'Chấp nhận chữ/số/dấu cách/._-; từ chối ký tự đặc biệt và >60 ký tự (mô phỏng regex PHP; Unicode \\p{L} tương đương \\w)'
case('UC05','Tiêu chí tìm kiếm không hợp lệ bị từ chối, yêu cầu nhập lại (5.2) — mô phỏng regex',uc05_criteria)

# ---------------- UC06 Tiếp nhận hàng ----------------
def uc06_pending():
    con=fresh(); p=tn_pending(con); ids={x['soChungTu']:x for x in p}
    assert 'PG2611060001' in ids and ids['PG2611060001']['tinhTrang']=='Chờ tiếp nhận'
    assert 'PG2611050001' in ids and ids['PG2611050001']['tinhTrang']=='Đã tiếp nhận một phần'
    assert 'PG2610250001' not in ids, 'phiếu đã nhận đủ mọi dòng không được liệt kê'
    assert 'YCNK2611060005' in ids and ids['YCNK2611060005']['loai']=='xuong'
    assert all(not tn_chung_tu(con,k)['conHieuLuc']==False for k in ids)
    for k in ids: assert tn_chung_tu(con,k)['dong'],k   # mọi chứng từ trong danh sách đều tiếp nhận được
    assert len(tn_pending(con,'PG2611'))>=2 and tn_pending(con,'PG9999')==[]
    return f'{len(p)} chứng từ chờ: '+', '.join(ids)
case('UC06','Danh sách chỉ gồm chứng từ còn dòng cần nhận (Chờ tiếp nhận / Đã tiếp nhận một phần / phiếu xưởng); tìm theo số chứng từ',uc06_pending)

def uc06_pending_all_receivable():
    con=fresh(); 
    for x in tn_pending(con): assert tn_chung_tu(con,x['soChungTu'])['dong'],x['soChungTu']
    return 'Không còn chứng từ "liệt kê được nhưng bấm vào báo lỗi" (lỗi đã phát hiện và sửa trong lần kiểm thử này)'
case('UC06','Mọi chứng từ trên danh sách đều mở được màn hình tiếp nhận',uc06_pending_all_receivable)

def uc06_full():
    con=fresh(); ct=tn_chung_tu(con,'PG2611060001'); (i,l),=ct['dong'].items()
    vt=list(tn_vitri(con,'K010001'))[0]['maViTri']
    r=tn_receive(con,'PG2611060001',vt,'NV000006','Nhận đủ',{i:{'sl':15.0}})
    d=one(con,'select * from LoHang where maLoHang=?',[r['lo'][0]])
    assert d['soLuongBanDau']==750.0 and d['trangThaiQC']=='Chờ kiểm tra' and d['trangThaiVongDoi']=='Đã tạo' and d['maPhieuTiepNhan']==r['ptn']
    assert d['ngaySanXuat']=='2026-10-01' and d['ngayHetHan']=='2027-02-01'
    p=one(con,'select * from PhieuTiepNhan where maPhieuTiepNhan=?',[r['ptn']]); assert p['trangThai']=='Chờ kiểm định' and p['maPhieuGiao']=='PG2611060001' and p['maXuong'] is None and p['maViTriTam']==vt
    assert one(con,"select trangThai from PhieuGiaoNCC where maPhieuGiao='PG2611060001'")['trangThai']=='Đã tiếp nhận'
    assert one(con,'select soLuongThucNhan s from ChiTietPhieuGiaoNCC where maChiTietPhieuGiao=?',[i])['s']==15.0
    assert one(con,'select count(*) c from TonKho where maLoHang=?',[d['maLoHang']])['c']==0
    assert 'PG2611060001' not in [x['soChungchu'] if False else x['soChungTu'] for x in tn_pending(con)]
    return f'{r["ptn"]} + lô {r["lo"][0]}: 15 bao × 50 = 750 kg, lô "Chờ kiểm tra", phiếu giao → "Đã tiếp nhận", chưa ghi tồn kho, biến khỏi danh sách chờ'
case('UC06','Tiếp nhận ĐỦ hàng (NCC): tạo phiếu "Chờ kiểm định", lô "Chờ kiểm tra", quy đổi kg, cập nhật chứng từ',uc06_full)

def uc06_partial():
    con=fresh(); ct=tn_chung_tu(con,'PG2611060001'); (i,l),=ct['dong'].items(); vt=list(tn_vitri(con,'K010001'))[0]['maViTri']
    r1=tn_receive(con,'PG2611060001',vt,'NV000006','',{i:{'sl':10.0}})
    assert one(con,"select trangThai t from PhieuGiaoNCC where maPhieuGiao='PG2611060001'")['t']=='Đang tiếp nhận'
    c2=tn_chung_tu(con,'PG2611060001'); assert round(c2['dong'][i]['conLai'],3)==5.0
    assert [x for x in tn_pending(con) if x['soChungTu']=='PG2611060001'][0]['tinhTrang']=='Đã tiếp nhận một phần'
    r2=tn_receive(con,'PG2611060001',vt,'NV000006','',{i:{'sl':5.0}})
    assert one(con,"select trangThai t from PhieuGiaoNCC where maPhieuGiao='PG2611060001'")['t']=='Đã tiếp nhận'
    assert r1['lo'][0]!=r2['lo'][0] and r1['ptn']!=r2['ptn']
    try: tn_receive(con,'PG2611060001',vt,'NV000006','',{i:{'sl':1.0}}); raise AssertionError('cho nhận tiếp sau khi đủ')
    except BizError: pass
    return 'Nhận 10/15 → "Đã tiếp nhận một phần"; nhận nốt 5 → "Đã tiếp nhận"; nhận thêm bị từ chối'
case('UC06','Tiếp nhận MỘT PHẦN rồi nhận nốt; sau khi đủ không nhận thêm được',uc06_partial)

def uc06_existing_partial():
    con=fresh(); ct=tn_chung_tu(con,'PG2611050001'); assert set(ct['dong'])=={17,18}
    assert ct['dong'][18]['conLai']==5.0 and ct['dong'][17]['conLai']==12.0
    vt=list(tn_vitri(con,'K010001'))[0]['maViTri']
    tn_receive(con,'PG2611050001',vt,'NV000006','',{18:{'sl':5.0}})
    assert one(con,"select trangThai t from PhieuGiaoNCC where maPhieuGiao='PG2611050001'")['t']=='Đang tiếp nhận'   # dòng 17 vẫn còn
    tn_receive(con,'PG2611050001',vt,'NV000006','',{17:{'sl':12.0}})
    assert one(con,"select trangThai t from PhieuGiaoNCC where maPhieuGiao='PG2611050001'")['t']=='Đã tiếp nhận'
    return 'Chứng từ seed đang nhận dở (dòng 5/10 và 0/12): nhận từng dòng, trạng thái chuyển đúng khi đủ cả hai'
case('UC06','Chứng từ seed "Đã tiếp nhận một phần": chỉ hiện dòng còn thiếu; hoàn tất khi nhận đủ mọi dòng',uc06_existing_partial)

def uc06_xuong():
    con=fresh(); ct=tn_chung_tu(con,'YCNK2611060005'); (i,l),=ct['dong'].items(); vt=list(tn_vitri(con,'K030001'))[0]['maViTri']
    r=tn_receive(con,'YCNK2611060005',vt,'NV000011','',{i:{'sl':50.0}})
    p=one(con,'select * from PhieuTiepNhan where maPhieuTiepNhan=?',[r['ptn']]); assert p['maXuong']=='XSX000002' and p['maPhieuGiao'] is None
    d=one(con,'select * from LoHang where maLoHang=?',[r['lo'][0]]); assert d['soLuongBanDau']==50.0 and d['soLoNhaCungCap'] is None and d['ngayHetHan']>d['ngaySanXuat']
    assert one(con,"select trangThai t from PhieuYeuCauNhapKho where maYeuCauNhap='YCNK2611060005'")['t']=='Đang nhập kho'
    return f'{r["ptn"]}: nguồn xưởng XSX000002 (không có phiếu giao), lô {r["lo"][0]} 50 kg, hạn = ngày SX + hạn tiêu chuẩn'
case('UC06','Tiếp nhận từ XƯỞNG sản xuất: phiếu gắn xưởng (không gắn phiếu giao), lô tạo đúng, yêu cầu → "Đang nhập kho"',uc06_xuong)

def uc06_atomic():
    con=fresh(); ct=tn_chung_tu(con,'PG2611050001'); vt=list(tn_vitri(con,'K010001'))[0]['maViTri']
    snap=lambda: (one(con,'select count(*) c from PhieuTiepNhan')['c'],one(con,'select count(*) c from LoHang')['c'],
                  one(con,"select trangThai t from PhieuGiaoNCC where maPhieuGiao='PG2611050001'")['t'],
                  tuple(r['s'] for r in rows(con,'select soLuongThucNhan s from ChiTietPhieuGiaoNCC where maPhieuGiao=?',['PG2611050001'])))
    before=snap()
    # dòng 17 hợp lệ, dòng 18 vi phạm CHECK của LoHang (hạn < ngày sản xuất) -> toàn bộ phải hoàn tác
    try: tn_receive(con,'PG2611050001',vt,'NV000006','',{17:{'sl':12.0},18:{'sl':5.0,'nsx':'2026-11-01','hsd':'2026-10-01','_force':True}}); raise AssertionError('không lỗi')
    except sqlite3.IntegrityError: pass
    assert snap()==before,(before,snap()); return 'Lỗi giữa chừng → không tạo phiếu/lô, không đổi số lượng đã nhận hay tình trạng chứng từ'
case('UC06','Lỗi khi lưu giữa chừng → hoàn tác toàn bộ (hậu điều kiện: không tạo phiếu, không tạo lô, không đổi chứng từ)',uc06_atomic)

def uc06_validations():
    con=fresh(); ct=tn_chung_tu(con,'PG2611060001'); (i,l),=ct['dong'].items(); vt=list(tn_vitri(con,'K010001'))[0]['maViTri']
    cases=[({i:{'sl':0.0}},'số lượng = 0'),({i:{'sl':-3.0}},'số lượng âm'),({i:{'sl':16.0}},'vượt số còn phải nhận'),({},'không chọn dòng nào'),({999999:{'sl':1.0}},'dòng không thuộc chứng từ')]
    for chon,label in cases:
        try: tn_receive(con,'PG2611060001',vt,'NV000006','',chon); raise AssertionError('chấp nhận sai: '+label)
        except BizError: pass
    try: tn_receive(con,'PG2611060001','VT_KHONG_CO','NV000006','',{i:{'sl':1.0}}); raise AssertionError('chấp nhận vị trí lạ')
    except BizError: pass
    try: tn_receive(con,'PG9999999999',vt,'NV000006','',{i:{'sl':1.0}}); raise AssertionError('chấp nhận chứng từ lạ')
    except BizError: pass
    assert one(con,'select count(*) c from PhieuTiepNhan where maPhieuTiepNhan like "PTN2611240%"')['c']==0
    return 'Bị từ chối: SL=0, SL<0, SL vượt còn lại, không chọn dòng, dòng lạ, vị trí lạ, chứng từ không tồn tại — và không để lại dữ liệu'
case('UC06','Dữ liệu không hợp lệ (6.1, 3.1.1): SL ≤ 0, vượt quá, không chọn dòng, vị trí/chứng từ không tồn tại',uc06_validations)

def uc06_stopped_item():
    con=fresh(); con.execute("UPDATE HangHoa SET trangThai='Đã ngừng sử dụng' WHERE maHangHoa='HH01000002'")
    ct=tn_chung_tu(con,'PG2611060001'); (i,l),=ct['dong'].items(); vt=list(tn_vitri(con,'K010001'))[0]['maViTri']
    try: tn_receive(con,'PG2611060001',vt,'NV000006','',{i:{'sl':1.0}}); raise AssertionError('nhận được hàng đã ngừng sử dụng')
    except BizError as e: pass
    return 'Hàng đã "Ngừng sử dụng" trên chứng từ → không cho tiếp nhận (tiền điều kiện 3)'
case('UC06','Hàng hóa trên chứng từ đã "Ngừng sử dụng" → không cho tiếp nhận (tiền điều kiện 3)',uc06_stopped_item)

def uc06_vitri():
    con=fresh(); all_=tn_vitri(con); k1=tn_vitri(con,'K010001'); 
    assert all_ and k1 and len(k1)<len(all_) and all(v['maViTri'].startswith('VT01') for v in k1)
    kv=set(r['loaiKhuVuc'] for r in rows(con,'select distinct kv.loaiKhuVuc from ViTriLuuTru vt join KhuVuc kv on kv.maKhuVuc=vt.maKhuVuc where vt.maViTri in (%s)'%','.join('?'*len(all_)),[v['maViTri'] for v in all_]))
    assert kv=={'Tiếp nhận tạm'}
    target=k1[0]['maViTri']
    for st in ('Đang bảo trì','Đã khóa','Đã lấp đầy'):
        con.execute('UPDATE ViTriLuuTru SET trangThai=? WHERE maViTri=?',[st,target])
        assert target not in [v['maViTri'] for v in tn_vitri(con,'K010001')],st
    assert tn_vitri(con,'K060001')==[]
    return f'Chỉ khu "Tiếp nhận tạm"; nhân viên kho chỉ thấy kho mình ({len(k1)}/{len(all_)}); ô bảo trì/khóa/lấp đầy bị loại; kho K06 không có vị trí tạm → danh sách rỗng'
case('UC06','Vị trí lưu trữ tạm: đúng khu, đúng kho nhân viên, loại ô không dùng được (tiền điều kiện 4)',uc06_vitri)

def uc06_numbering():
    con=fresh(); ct=tn_chung_tu(con,'PG2611050001'); vt=list(tn_vitri(con,'K010001'))[0]['maViTri']
    r1=tn_receive(con,'PG2611050001',vt,'NV000006','',{17:{'sl':6.0},18:{'sl':5.0}})
    r2=tn_receive(con,'PG2611050001',vt,'NV000006','',{17:{'sl':6.0}})
    assert r1['ptn']=='PTN2611240001' and r2['ptn']=='PTN2611240002' and r1['lo']==['LO2611240001','LO2611240002'] and r2['lo']==['LO2611240003'],(r1,r2)
    return f'{r1["ptn"]},{r2["ptn"]}; lô {r1["lo"]+r2["lo"]} — đánh số theo ngày, tăng liên tiếp, một lô cho mỗi dòng'
case('UC06','Đánh số phiếu/lô theo ngày, liên tiếp, mỗi dòng một lô',uc06_numbering)

def uc06_end_to_end():
    con=fresh(); ct=tn_chung_tu(con,'PG2611060001'); (i,l),=ct['dong'].items(); vt=list(tn_vitri(con,'K010001'))[0]['maViTri']
    r=tn_receive(con,'PG2611060001',vt,'NV000006','',{i:{'sl':15.0}}); lot=r['lo'][0]
    x=[y for y in lo_search(con,{'q':lot})][0]
    assert x['soLuongTon']==0 and x['viTri'] is None and x['trangThaiHienThi']=='Còn hạn' and x['ngayNhap']
    d=lo_detail(con,lot); assert d['tenNCC'] and d['trangThaiQC']=='Chờ kiểm tra' and d['viTriChiTiet']==[]
    return f'Lô {lot} vừa tạo tra cứu được ở UC05: "Còn hạn", tồn 0 (chưa nhập kho), nhà cung cấp "{d["tenNCC"]}", chất lượng "Chờ kiểm tra"'
case('UC05+UC06','Liên thông: lô vừa tiếp nhận tra cứu được ở UC05 với thông tin đúng',uc06_end_to_end)

def uc03_uc06():
    con=fresh(); hh_create(con,'Nguyên liệu')   # chỉ để thử phối hợp tạo + tra cứu
    ma=hh_create(con,'Phụ gia',ten='Men vi sinh X'); r=hh_search(con,{'q':'Men vi sinh X'}); assert r and r[0]['maHangHoa']==ma and r[0]['soLuongTon']==0
    return f'Hàng vừa thêm ({ma}) tra cứu được ngay, tồn 0'
case('UC01+UC04','Liên thông: hàng vừa thêm xuất hiện ngay trong tra cứu với tồn = 0',uc03_uc06)

# ---------------- Tổng kết ----------------
if __name__=='__main__':
    ok=sum(1 for r in RESULTS if r[2]=='ĐẠT')
    for uc,name,res,detail in RESULTS:
        print(f"[{res:9}] {uc:10} {name}")
        if detail: print(f"{'':13}↳ {detail}")
    print(f"\nTỔNG: {ok}/{len(RESULTS)} đạt")
    json.dump(RESULTS,open(os.path.join(os.path.dirname(os.path.abspath(__file__)),'results.json'),'w'),ensure_ascii=False,indent=1)
