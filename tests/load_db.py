import re, sqlite3, sys, datetime

import os
ROOT_DIR=os.path.abspath(os.path.join(os.path.dirname(__file__),'..'))+'/'
BASE=ROOT_DIR+'database/'

def convert_schema(sql):
    sql=sql.replace('\r','')
    # bỏ trigger (từ "DROP TRIGGER" trở đi)
    i=sql.find('DROP TRIGGER'); sql=sql[:i] if i>0 else sql
    out=[]
    for line in sql.split('\n'):
        if re.match(r'^(CREATE DATABASE|USE|SET NAMES)',line): continue
        out.append(line)
    sql='\n'.join(out)
    # AUTO_INCREMENT -> INTEGER PRIMARY KEY AUTOINCREMENT, bỏ dòng PRIMARY KEY riêng của bảng đó
    def fix_table(m):
        t=m.group(0)
        ai=re.search(r"^\s+`(\w+)` \w+(?:\(\d+\))? NOT NULL AUTO_INCREMENT,",t,re.M)
        if ai:
            col=ai.group(1)
            t=re.sub(r"^(\s+)`%s` \w+(?:\(\d+\))? NOT NULL AUTO_INCREMENT,"%col,r"\1`%s` INTEGER PRIMARY KEY AUTOINCREMENT,"%col,t,flags=re.M)
            t=re.sub(r"^\s+PRIMARY KEY \(`%s`\),?\n"%col,"",t,flags=re.M)
        return t
    sql=re.sub(r"CREATE TABLE IF NOT EXISTS .*?\n\) ENGINE[^\n]*;",fix_table,sql,flags=re.S)
    sql=re.sub(r"\) ENGINE=[^;]*;",");",sql)
    sql=re.sub(r"^\s+(UNIQUE )?(INDEX|KEY)\s+\w+\s*\([^\n]*\),?\s*$","",sql,flags=re.M)
    sql=re.sub(r"UNIQUE KEY \w+ \(",'UNIQUE (',sql)
    sql=re.sub(r"\s+COMMENT\s+'[^']*'","",sql)
    sql=re.sub(r"\s+ON UPDATE CURRENT_TIMESTAMP","",sql)
    sql=re.sub(r",(\s*\n\s*\))",r"\1",sql)   # dấu phẩy thừa trước ')'
    return sql

def connect(fk=True):
    con=sqlite3.connect(':memory:')
    con.row_factory=sqlite3.Row
    con.create_function('REGEXP',2,lambda p,s: s is not None and re.search(p,str(s)) is not None)
    con.create_function('CHAR_LENGTH',1,lambda s: len(s) if s is not None else None)
    con.create_function('CONCAT',-1,lambda *a: None if any(x is None for x in a) else ''.join(str(x) for x in a))
    con.create_function('CURDATE',0,lambda: datetime.date(2026,11,24).isoformat())
    con.create_function('NOW',0,lambda: '2026-11-24 09:00:00')
    con.create_function('DATE',1,lambda s: str(s)[:10] if s else None)
    con.create_function('RIGHT',2,lambda s,n: str(s)[-int(n):])
    return con

def load(fk=True, seed=True):
    con=connect()
    schema=convert_schema(open(BASE+'schema.sql',encoding='utf-8').read())
    con.executescript(schema)
    errs=[]
    if seed:
        s=open(BASE+'seed_data.sql',encoding='utf-8').read().replace('\r','')
        s=s[:s.index('\nCOMMIT;')]
        s=re.sub(r"^(USE|SET|START TRANSACTION)[^\n]*\n","",s,flags=re.M)
        if fk: con.execute('PRAGMA foreign_keys=ON')
        # chạy từng câu INSERT để biết câu nào lỗi
        stmts=re.split(r";\s*\n(?=-- \[|INSERT)",s)
        for st in stmts:
            st=st.strip().rstrip(';')
            if not st or not 'INSERT' in st: continue
            try: con.execute(st)
            except Exception as e:
                m=re.search(r"INSERT INTO `?(\w+)",st); errs.append((m.group(1) if m else '?',str(e)))
        con.commit()
    return con,errs

if __name__=='__main__':
    con,errs=load()
    print("bảng:",con.execute("select count(*) from sqlite_master where type='table'").fetchone()[0])
    for t in ['HangHoa','LoHang','TonKho','PhieuGiaoNCC','ChiTietPhieuGiaoNCC','PhieuTiepNhan','LichSuBienDongTonKho','ChenhLechKiemKe']:
        print(t, con.execute(f"select count(*) from {t}").fetchone()[0])
    print("LỖI khi nạp seed:",len(errs))
    for e in errs[:15]: print(" ",e)
