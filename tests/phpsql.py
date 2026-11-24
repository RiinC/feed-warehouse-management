"""Trích các câu SQL (static::one/all/exec) từ file PHP, theo từng phương thức, và đổi sang cú pháp SQLite."""
import re
import os
ROOT=os.path.abspath(os.path.join(os.path.dirname(__file__),'..'))+'/'

def read(path): return open(ROOT+path,encoding='utf-8').read().replace('\r','')

def consts(src):
    out={}
    for m in re.finditer(r"const (\w+) = (\"(?:[^\"\\]|\\.)*\"|'(?:[^'\\]|\\.)*');",src,re.S):
        out[m.group(1)]=unq(m.group(2))
    return out

def unq(tok):
    if tok[0]=="'":
        return tok[1:-1].replace("\\'","'").replace('\\\\','\\')
    body=tok[1:-1]
    return body.replace('\\"','"').replace('\\\\','\\')

def method_body(src,name):
    m=re.search(r"function %s\(.*?\n    \}\n"%name,src,re.S)
    if not m: raise KeyError(name)
    return m.group(0)

def eval_expr(expr,cst,vars_):
    """Ghép các literal nối bằng dấu chấm; hỗ trợ self::CONST, implode(' AND ', $w), biến nội suy trong "..."."""
    parts=[]
    for m in re.finditer(r"\"(?:[^\"\\]|\\.)*\"|'(?:[^'\\]|\\.)*'|self::(\w+)|implode\([^)]*\)",expr,re.S):
        t=m.group(0)
        if t.startswith('self::'): parts.append(cst[m.group(1)])
        elif t.startswith('implode'): parts.append(vars_.get('implode',' AND '.join(vars_.get('w_list',['1 = 1']))))
        elif t[0]=='"':
            s=unq(t)
            s=re.sub(r"\$(\w+)",lambda mm: str(vars_.get(mm.group(1),'')),s)
            parts.append(s)
        else: parts.append(unq(t))
    return ''.join(parts)

def sqls(path,method,vars_=None):
    src=read(path); cst=consts(src); body=method_body(src,method)
    out=[]
    # tìm các lời gọi static::one|all|exec( ... ) lấy đối số đầu tiên (biểu thức chuỗi)
    for m in re.finditer(r"static::(one|all|exec)\(\s*",body):
        i=m.end(); depth=0; j=i; instr=None
        while j<len(body):
            c=body[j]
            if instr:
                if c=='\\': j+=2; continue
                if c==instr: instr=None
            else:
                if c in "'\"": instr=c
                elif c in '([': depth+=1
                elif c in ')]':
                    if depth==0: break
                    depth-=1
                elif c==',' and depth==0: break
            j+=1
        out.append((m.group(1),eval_expr(body[i:j],cst,vars_ or {})))
    return out

def to_sqlite(sql):
    sql=re.sub(r"\s+FOR UPDATE","",sql)
    sql=re.sub(r"\s+ORDER BY tk\.maViTri SEPARATOR ', '\)",")",sql)
    return sql

def assign(path,method,var='sql',vars_=None):
    """Lấy biểu thức gán $var = ...; trong một phương thức (dùng cho SQL dựng bằng biến)."""
    src=read(path); cst=consts(src); body=method_body(src,method)
    i=body.index('$%s = '%var)+len('$%s = '%var); j=i; instr=None
    while j<len(body):
        c=body[j]
        if instr:
            if c=='\\': j+=2; continue
            if c==instr: instr=None
        elif c in "'\"": instr=c
        elif c==';': break
        j+=1
    return eval_expr(body[i:j],cst,vars_ or {})
