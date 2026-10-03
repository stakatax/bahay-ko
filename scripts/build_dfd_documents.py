"""Build documentation diagrams/Word packages from the reviewed DFD model.

Standard library only. No database access or application mutations.
Use --documents after rendering the SVG files to matching PNG files.
"""
from pathlib import Path
import argparse
import html
import json
import re
import textwrap
import zipfile
import struct

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'docs' / 'dfd-rebuilt'
MODEL = json.loads((OUT / 'model.json').read_text(encoding='utf-8'))
STORES = MODEL['stores']
ESC = html.escape


def text(x, y, label, size=19, width=28, anchor='middle', bold=False):
    lines = textwrap.wrap(str(label), width=width) or ['']
    start = y - (len(lines) - 1) * (size + 3) / 2
    return ''.join(f'<text x="{x}" y="{start + i * (size + 3)}" text-anchor="{anchor}" font-size="{size}" font-weight="{700 if bold else 400}">{ESC(line)}</text>' for i, line in enumerate(lines))


def entity(x, y, label, width=230, height=94):
    return f'<rect x="{x}" y="{y}" width="{width}" height="{height}" fill="#d5e2f4" stroke="#222"/>' + text(x+width/2, y+height/2+6, label, width=22)


def process(x, y, number, label, width=400, height=104):
    cid = 'clip' + re.sub(r'\W', '', str(number))
    return (f'<defs><clipPath id="{cid}"><rect x="{x}" y="{y}" width="{width}" height="{height}" rx="14"/></clipPath></defs>'
            f'<rect x="{x}" y="{y}" width="{width}" height="{height}" rx="14" fill="white" stroke="#222"/>'
            f'<rect x="{x}" y="{y}" width="{width}" height="30" fill="#d5e2f4" clip-path="url(#{cid})"/>'
            f'<path d="M{x},{y+30} H{x+width}" stroke="#222"/>'
            + text(x+width/2, y+22, number, size=18)
            + text(x+width/2, y+70, label, size=20, width=34))


def store(x, y, ids, width=270, height=94):
    codes = [s.strip() for s in ids.split('/')]
    label = ' / '.join(STORES[c][0] for c in codes)
    idwidth = 65 if len(codes)>1 else 43
    return (f'<rect x="{x}" y="{y}" width="{width}" height="{height}" fill="#d5e2f4"/>'
            f'<path d="M{x+width},{y} H{x} V{y+height} H{x+width} M{x+idwidth},{y} V{y+height}" fill="none" stroke="#222"/>'
            + text(x+idwidth/2, y+height/2+6, '/'.join(codes), size=16, width=6)
            + text(x+idwidth+(width-idwidth)/2, y+height/2+6, label, size=17, width=21))


def arrow(x1, y1, x2, y2, label='', lx=None, ly=None, width=24):
    path = f'<path d="M{x1},{y1} L{x2},{y2}" fill="none" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)"/>'
    if label:
        path += text(lx if lx is not None else (x1+x2)/2, ly if ly is not None else (y1+y2)/2-13, label, size=16, width=width)
    return path


def svg(title, subtitle, body, height, width=1300):
    return (f'<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}">'
            '<defs><marker id="arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0 L10 5 L0 10 Z" fill="#222"/></marker></defs>'
            '<rect width="100%" height="100%" fill="white"/><g font-family="Arial, sans-serif" fill="#161616">'
            + text(width/2, 35, title, size=25, width=75, bold=True)
            + text(width/2, 67, subtitle, size=17, width=110)
            + body + '</g></svg>')


def context():
    left = [('Guest','Registration, credentials and recovery data','Public pages and entry status'),
            ('Student','Own profile, answers and engagement','Eligible content and own status'),
            ('Parent','Child claim and eligible participation','Verification and child-scope content'),
            ('Faculty','Personal details and scoped submissions','Review outcome and scoped reports')]
    right = [('Administrator','Approvals, content and configuration','Management results and reports'),
             ('Trusted Government Source','Official page and source information','Permitted official page request'),
             ('Email and Push Providers','Delivery receipts and failures','Eligible delivery payloads'),
             ('Operational Scheduler','Due release and dispatch trigger','Operational completion status')]
    body = process(450,115,'0','OLSHCO Digital Hub',width=400,height=650)
    for entries, x, side in [(left,20,'left'),(right,1050,'right')]:
        for n,(actor,inp,out) in enumerate(entries):
            y=130+n*150; body+=entity(x,y,actor)
            if side=='left':
                body+=arrow(x+230,y+20,450,y+20,inp,lx=350,ly=y+3,width=24)
                body+=arrow(450,y+76,x+230,y+76,out,lx=350,ly=y+108,width=24)
            else:
                body+=arrow(x,y+20,850,y+20,inp,lx=950,ly=y+3,width=24)
                body+=arrow(850,y+76,x,y+76,out,lx=950,ly=y+108,width=24)
    body+=text(650,435,'School information, governed publication and eligible delivery',size=23,width=28,bold=True)
    return svg('Context Diagram','Gane-Sarson notation | Entire application boundary | 2 October 2026',body,830)


def level_one(module):
    num,title,actor,inp,out,stores,steps=module
    reads={c.strip() for st in steps if st[2] for c in st[2].split("/")}
    writes={c.strip() for st in steps if st[4] for c in st[4].split("/")}
    stores=[c for c in stores if c in reads or c in writes]
    height=max(800,300+len(stores)*125)
    body=entity(25,135,actor,height=150)
    body+=process(450,145,num+'.0',title,width=400,height=height-300)
    body+=arrow(255,162,450,162,inp,lx=350,ly=115,width=25)
    body+=entity(25,height-240,actor,height=105)
    body+=arrow(450,height-190,255,height-190,out,lx=350,ly=height-270,width=25)
    for i,code in enumerate(stores):
        y=180+i*125; body+=store(1030,y,code)
        if code in reads: body+=arrow(1030,y+22,850,y+22,'Current '+STORES[code][0].lower(),lx=940,ly=y+2,width=23)
        # These store interactions are constrained further in the decomposition.
        if code in writes:
            body+=arrow(850,y+75,1030,y+75,'Authorized '+STORES[code][0].lower()+' data',lx=940,ly=y+108,width=23)
    return svg('DFD Level 1 - '+num+'.0 '+title,'Module view; role, ownership and eligibility conditions apply',body,height)


def decomposition(number, title, actor, inp, out, steps, level, offset=0):
    gap = 245 if level == 2 else 190
    height=310+len(steps)*gap
    body = ''
    if level == 3:
        body=entity(20,120,actor,height=120)
        body+=arrow(250,170,360,170,inp,lx=302,ly=111,width=18)
    for i,step in enumerate(steps):
        name,data,read,rlabel,write,wlabel=step
        y=120+i*gap; code=number+'.'+str(i+1+offset)
        body+=process(360,y,code,name)
        if level == 2:
            who = MODEL.get('role_overrides',{}).get(number+'.'+str(i+1+offset),actor)
            body+=entity(20,y,who,height=104)
            body+=arrow(250,y+22,360,y+22,data,lx=302,ly=y-10,width=16)
            body+=arrow(360,y+85,250,y+85,wlabel or rlabel or out,lx=302,ly=y+149,width=16)
        if i and level == 3:
            previous=steps[i-1][0]
            label=('Saved ' if previous.startswith(('Save','Record','Commit','Queue')) else 'Validated ')+'request data'
            body+=arrow(560,y-86,560,y,label,lx=670,ly=y-41,width=23)
        codes=[]
        for c in (read,write):
            if c:
                for k in c.split('/'):
                    if k.strip() not in codes: codes.append(k.strip())
        if codes:
            body+=store(1030,y+4,' / '.join(codes),height=108)
            if read: body+=arrow(1030,y+27,760,y+27,rlabel,lx=895,ly=y-9,width=31)
            if write: body+=arrow(760,y+84,1030,y+84,wlabel,lx=895,ly=y+126,width=31)
    y=120+(len(steps)-1)*gap
    if level == 3:
        body+=entity(20,y+90,actor,height=120)
        body+=f'<path d="M360,{y+82} H310 V{y+150} H250" fill="none" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)"/>'
        body+=text(160,y+247,out,size=18,width=26)
    return svg(f'DFD Level {level} - {number} {title}','Subprocess data interactions; independent actions are not a required sequence' if level == 2 else 'Data transformations for the applicable request; optional and conditional steps remain conditional',body,height)


def expanded_step(title,parent,index,count):
    _,data,read,rlabel,write,wlabel=parent
    # Store reads occur at initial validation/load; final persistence carries writes.
    return [title,data,read if index==0 else None,rlabel if index==0 else None,write if index==count-1 else None,wlabel if index==count-1 else None]


def build_svgs():
    assets=OUT/'assets'; assets.mkdir(exist_ok=True)
    groups={'Context':[], 'Level_1':[], 'Level_2':[], 'Level_3':[]}
    def add(group,name,label,content):
        (assets/(name+'.svg')).write_text(content,encoding='utf-8')
        groups[group].append({'name':name,'title':label})
    add('Context','context','Context diagram',context())
    for mod in MODEL['modules']:
        num,title,actor,inp,out,stores,steps=mod
        add('Level_1','level1-'+num,num+'.0 '+title,level_one(mod))
        chunks=[steps[j:j+3] for j in range(0,len(steps),3)]
        for part,chunk in enumerate(chunks,1):
            label=title+(f' (part {part}/{len(chunks)})' if len(chunks)>1 else '')
            add('Level_2','level2-'+num+'-part'+str(part),num+'.0 '+label,decomposition(num,label,actor,inp,out,chunk,2,offset=(part-1)*3))
        for i,step in enumerate(steps,1):
            code=num+'.'+str(i)
            if code in MODEL['expansions']:
                names=MODEL['expansions'][code]
                expanded=[expanded_step(t,step,j,len(names)) for j,t in enumerate(names)]
                for pos, changes in MODEL.get('detail_overrides',{}).get(code,{}).items():
                    for key,value in changes.items(): expanded[int(pos)][{'read':2,'rlabel':3,'write':4,'wlabel':5}[key]]=value
                add('Level_3','level3-'+code,code+' '+step[0],decomposition(code,step[0],MODEL.get('role_overrides',{}).get(code,actor),step[1],step[5] or out,expanded,3))
    (OUT/'manifest.json').write_text(json.dumps(groups,indent=2)+'\n',encoding='utf-8')
    print('Built',sum(map(len,groups.values())),'SVG diagrams.')


def paragraph(value,size=22,bold=False):
    props=f'<w:rPr><w:sz w:val="{size}"/>'+('<w:b/>' if bold else '')+'</w:rPr>'
    return '<w:p><w:r>'+props+'<w:t xml:space="preserve">'+ESC(value)+'</w:t></w:r></w:p>'


def build_docx(group,items):
    ns='xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"'
    body=paragraph('OLSHCO Digital Hub - '+group.replace('_',' ')+' DFD',32,True)
    body+=paragraph('Rebuilt 2 October 2026. Gane-Sarson: rectangular entity, directed data flow, divided rounded process, open-ended data store.')
    body+=paragraph('Based on current code and metadata. Conceptual domain views, not SQL execution traces. Local actor labels retain narrower role, scope and ownership restrictions.')
    if group=='Level_3': body+=paragraph('Selected detailed decompositions of important Level 2 processes; numbering is consistent with this rebuilt set.')
    rels=[]; media=[]
    for n,item in enumerate(items,1):
        if n>1: body+='<w:p><w:r><w:br w:type="page"/></w:r></w:p>'
        body+=paragraph(item['title'],26,True)
        p=OUT/'assets'/(item['name']+'.png')
        blob=p.read_bytes(); width,height=struct.unpack('>II',blob[16:24]); cx=int(10.2*914400); cy=int(cx*height/width)
        if cy>12.9*914400: cy=int(12.9*914400); cx=int(cy*width/height)
        rid='rId'+str(n); media.append(('word/media/image'+str(n)+'.png',blob))
        rels.append(f'<Relationship Id="{rid}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image{n}.png"/>')
        body+=f'<w:p><w:r><w:drawing><wp:inline><wp:extent cx="{cx}" cy="{cy}"/><wp:docPr id="{n}" name="{ESC(item["title"])}" descr="Gane-Sarson DFD"/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="{n}" name="image{n}.png"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="{rid}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="{cx}" cy="{cy}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>'
    body+=paragraph('Data store reference',28,True)
    for code,(label,physical) in STORES.items(): body+=paragraph(code+' - '+label+': '+physical,20)
    body+='<w:sectPr><w:pgSz w:w="16838" w:h="23811"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720"/></w:sectPr>'
    with zipfile.ZipFile(OUT/('OLSHCO_DFD_'+group+'_Rebuilt.docx'),'w',zipfile.ZIP_DEFLATED) as z:
        z.writestr('[Content_Types].xml','<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>')
        z.writestr('_rels/.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>')
        z.writestr('word/document.xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document '+ns+'><w:body>'+body+'</w:body></w:document>')
        z.writestr('word/_rels/document.xml.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'+''.join(rels)+'</Relationships>')
        for path,blob in media: z.writestr(path,blob)
    print('Built Word file:',group)


if __name__=='__main__':
    parser=argparse.ArgumentParser(); parser.add_argument('--documents',action='store_true'); args=parser.parse_args()
    if args.documents:
        for group,items in json.loads((OUT/'manifest.json').read_text()).items(): build_docx(group,items)
    else: build_svgs()
