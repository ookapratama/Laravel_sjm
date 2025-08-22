@extends('layouts.app')

@section('content')
<div class="page-inner relative">

  {{-- Toolbar cepat --}}
  <div class="absolute right-5 top-3 z-10 flex gap-2">
    <button onclick="zoomIn()"     class="px-3 py-1 bg-blue-600 text-white rounded btn-primary">＋</button>
    <button onclick="zoomOut()"    class="px-3 py-1 bg-blue-600 text-white rounded btn-primary">－</button>
    <button onclick="lessLevels()" class="px-3 py-1 bg-yellow-600 text-white rounded btn-warning">Prev</button>
    <button onclick="moreLevels()" class="px-3 py-1 bg-green-600  text-white rounded btn-success">Next</button>
  </div>

  {{-- Kanvas tree --}}
  <div id="tree-scroll" class="overflow-auto w-full h-[85vh] border relative">
    <div id="tree-container"></div>

    {{-- Navigasi overlay --}}
  
  </div>
</div>

{{-- Tooltip --}}
<div id="tree-tooltip" class="hidden"></div>
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@push('styles')
<style>
.tree-nav{position:absolute;z-index:30;opacity:.9}
.tree-nav.left{left:10px;top:50%;transform:translateY(-50%)}
.tree-nav.right{right:10px;top:50%;transform:translateY(-50%)}
.tree-nav.up{left:50%;top:16px;transform:translateX(-50%)}
.tree-nav.down{left:50%;bottom:10px;transform:translateX(-50%)}
.tree-nav button{background:#60a5fa;border:none;color:#fff;padding:10px 14px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.15)}
@media (max-width:640px){.tree-nav button{padding:8px 10px}}
#tree-tooltip{position:absolute;background:#fff;border:1px solid #ddd;padding:8px 12px;border-radius:6px;font-size:13px;box-shadow:0 2px 8px rgba(0,0,0,.2);pointer-events:none;z-index:40}
</style>
@endpush

@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
(() => {
  'use strict';

  /* ===== Helpers ===== */
  const qs = (s, r=document)=>r.querySelector(s);
  const clamp = (v,a,b)=>Math.max(a,Math.min(b,v));
  const toNum = v => { const n=Number(String(v??'').trim()); return Number.isFinite(n)?n:null; };
  const info = m => (window.toastr?toastr.info(m):alert(m));
  const warn = m => (window.toastr?toastr.warning(m):alert(m));

  /* ===== State ===== */
  const FALLBACK_ROOT = {{ auth()->id() ?? 1 }};
  const SERVER_ROOT   = @json(optional($root)->id);
  const BOOT_ROOT     = Number(window.ROOT_ID ?? SERVER_ROOT ?? FALLBACK_ROOT);

  let currentRootId = Number.isFinite(BOOT_ROOT) ? BOOT_ROOT : 1;
  let levelLimit = 3;

  let lastLoadedData = null;   // raw cleaned data
  let lastMaxDepth   = -1;     // untuk deteksi “sudah tidak ada level lebih dalam”
  let rootOfDepth    = null;   // reset tracker saat root berganti

  // D3 + zoom
  let svgSel = null, g = null;
  let currentZoomTransform = d3.zoomIdentity;
  let isLoading = false, pendingController = null;
let firstRenderDone = false;

  // Navigasi
  const upStack = [];
  const parentCache = new Map();
  let pendingAction = null;    // 'more'|'less'|'navLeft'|'navRight'|'navDown'|'navUp'|null

  /* ===== Public API ===== */
  window.setRoot = id => {
    const n=Number(id); if(!Number.isFinite(n)||n<=0) return;
    currentRootId=n;
    upStack.length=0;
    levelLimit=3; pendingAction=null; rootOfDepth=null; lastMaxDepth=-1;
    loadTree();
  };
  window.loadTree = loadTree;

  /* ===== “Tambah node” killer ===== */
  const isAddNode = n => {
    if (!n || typeof n!=='object') return false;
    const name = String(n.name ?? n.title ?? '').trim().toLowerCase();
    const marker = n.isAddButton===true || n.is_add===true || n.add===true ||
                   n.placeholder===true || n.type==='add' ||
                   name==='tambah' || name==='+ tambah' || name==='+tambah';
    const noId   = (n.id==null || Number.isNaN(Number(n.id))) && (n.user_id==null && n.uid==null);
    return marker || noId;
  };
  const pruneAddNodes = n => {
    if (!n || typeof n!=='object') return n;
    if (isAddNode(n)) return null;
    if (Array.isArray(n.children)) n.children = n.children.map(pruneAddNodes).filter(Boolean);
    return n;
  };

  /* ===== Fetch ===== */
  async function fetchJSON(url, opts={}){
    if (pendingController) pendingController.abort();
    pendingController = new AbortController();
    const res = await fetch(url, { ...opts, signal: pendingController.signal,
      headers:{'X-Requested-With':'XMLHttpRequest', ...(opts.headers||{})}});
    if (!res.ok) throw new Error('HTTP '+res.status);
    return res.json();
  }
  async function fetchTEXT(url){
    const res = await fetch(url, { headers:{'X-Requested-With':'XMLHttpRequest'} });
    return { ok: res.ok, text: await res.text() };
  }
  const tryJSON = x => { if(x==null) return null; if(typeof x==='object') return x; try{return JSON.parse(x);}catch{return null;} };
  function pickParentId(payload){
    const d=tryJSON(payload)??{};
    const arr=[d.parent_id,d.parentId,d.pid,d.parent,d?.data?.parent_id,d?.data?.parentId,d?.user?.upline_id,d?.user?.parent_id,d.upline_id,d.id];
    for(const c of arr){ const n=toNum(c); if(n&&n>0) return n; }
    const nested=toNum(d?.parent?.id ?? d?.parent?.parent_id);
    return (nested&&nested>0)?nested:null;
  }
  async function resolveParentId(cur){
    const id=toNum(cur); if(!id||id<=0) return null;
    if(parentCache.has(id)) return parentCache.get(id);

    // coba pakai data yang sudah ada dulu
    const local=[lastLoadedData?.parent_id,lastLoadedData?.upline_id].map(toNum).find(n=>n&&n>0);
    if(local){ parentCache.set(id,local); return local; }

    const hit=async u=>{ try{ const r=await fetchTEXT(u); if(r.ok){ const pid=pickParentId(r.text); if(pid){ parentCache.set(id,pid); return pid; } } }catch{} return null; };
    return await hit(`/tree/parent/${id}`) || await hit(`/users/ajax/${id}`) || await hit(`/tree/node/${id}`);
  }

  /* ===== Avatar URL ===== */
  function getAvatarUrl(d){
    const p = d?.data?.photo;
    if (!p) return '/assets/img/profile.jpg';
    if (/^(https?:)?\/\//i.test(p)) return p;          // absolute URL
    return `${location.origin}/${String(p).replace(/^\/+/, '')}`; // relative → absolute
  }

  /* ===== Zoom/Pan & Anchor ===== */
  const zoomBehavior = d3.zoom().on('zoom', e=>{
    currentZoomTransform=e.transform;
    if(g) g.attr('transform',currentZoomTransform);
  });
  const bindZoom = svg => { if(!svg.node().__zoom) svg.call(zoomBehavior); };

  window.zoomIn  = ()=>{ if(!svgSel) return; const t=currentZoomTransform.scale(1.2); svgSel.transition().duration(200).call(zoomBehavior.transform,t); currentZoomTransform=t; };
  window.zoomOut = ()=>{ if(!svgSel) return; const t=currentZoomTransform.scale(0.83); svgSel.transition().duration(200).call(zoomBehavior.transform,t); currentZoomTransform=t; };

  function viewportCenter(){
    const rect = qs('#tree-scroll').getBoundingClientRect();
    return [rect.width/2, rect.height/2];
  }
  function anchorBefore(){
    const [cx,cy]=viewportCenter();
    const T = currentZoomTransform || d3.zoomIdentity;
    return { world:T.invert([cx,cy]), k:T.k, screen:[cx,cy] };
  }
  function applyAnchor(a){
    if(!a||!svgSel) return;
    const [wx,wy]=a.world, [sx,sy]=a.screen, k=a.k||1;
    const tx = sx - k*wx, ty = sy - k*wy;
    const T  = d3.zoomIdentity.translate(tx,ty).scale(k);
    currentZoomTransform=T;
    svgSel.call(zoomBehavior).call(zoomBehavior.transform,T);
  }

  /* ===== Depth helper ===== */
  function maxDepth(root){ let m=0; (function dfs(n,d){ m=Math.max(m,d); (n.children||[]).forEach(c=>dfs(c,d+1)); })(root,0); return m; }

  /* ===== Load & Draw ===== */
 async function loadTree(){
  if(isLoading) return; isLoading=true;
  try{
    const a = firstRenderDone ? anchorBefore() : null; // <- ini kuncinya

    const url = `/tree/load/${currentRootId}?limit=${encodeURIComponent(levelLimit)}`;
    const raw = await fetchJSON(url);
    const cleaned = pruneAddNodes(raw) || {};

    if (rootOfDepth !== currentRootId) { rootOfDepth=currentRootId; lastMaxDepth=-1; }

    const d = maxDepth(cleaned);
    if (pendingAction && ['more','navLeft','navRight','navDown'].includes(pendingAction)){
      if (d <= lastMaxDepth) warn('Tidak ada level lebih dalam lagi.');
    }
    lastMaxDepth = Math.max(lastMaxDepth, d);

    lastLoadedData = normalizeIds(sortBinary(cleaned));

    const cur=toNum(currentRootId), pid=toNum(lastLoadedData?.parent_id ?? lastLoadedData?.upline_id);
    if(cur && pid) parentCache.set(cur,pid);

    drawTree(lastLoadedData, { anchor: a });   // render
    firstRenderDone = true;                    // <- tandai sudah pernah render
  }catch(e){
    if(e.name!=='AbortError') (window.toastr?toastr.error('Gagal memuat tree'):console.error(e));
  }finally{
    isLoading=false; pendingAction=null;
  }
}

function topSafeOffset(nodeH = 80){
  const upBtn = document.querySelector('.tree-nav.up button');
  const upH   = upBtn ? upBtn.getBoundingClientRect().height : 32; // tinggi tombol ▲
  return upH + 18 + (nodeH/2); // jarak aman: tinggi tombol + margin + setengah tinggi node
}

  function normalizeIds(n){
    if(!n||typeof n!=='object') return n;
    n.id=toNum(n.id);
    n.parent_id=toNum(n.parent_id ?? n.upline_id);
    if(Array.isArray(n.children)) n.children.forEach(normalizeIds);
    return n;
  }

  function sortBinary(n){
    const dfs=x=>{
      if(x?.children?.length){
        x.children.sort((a,b)=>(a.position==='left'?-1:(a.position==='right'?1:0)));
        x.children.forEach(dfs);
      }
    };
    dfs(n);
    return n;
  }

  function drawTree(treeData,{anchor}={}){
    const board=qs('#tree-scroll'), wrap=qs('#tree-container');
    if(!board||!wrap) return;

    const W=board.clientWidth||1200, H=board.clientHeight||750;
    wrap.innerHTML='';
    const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');
    svg.setAttribute('width',W); svg.setAttribute('height',H);
    wrap.appendChild(svg);

    svgSel=d3.select(svg);
    const defs=svgSel.append('defs');

    // gradiasi gold → hitam
    const gGrad=defs.append('linearGradient').attr('id','grad-gold-black')
      .attr('x1','0%').attr('y1','0%').attr('x2','100%').attr('y2','100%');
    gGrad.append('stop').attr('offset','0%').attr('stop-color','#FFD700');
    gGrad.append('stop').attr('offset','100%').attr('stop-color','#000');

    defs.append('clipPath').attr('id','avatarClip').attr('clipPathUnits','objectBoundingBox')
      .append('circle').attr('cx',0.5).attr('cy',0.5).attr('r',0.5);

    g = svgSel.append('g'); 
    bindZoom(svgSel);

    // Ukuran responsif
    const NODE_W = clamp(Math.floor(W/8), 80, 120);
    const NODE_H = clamp(Math.floor(NODE_W*0.9), 60, 110);
    const RADIUS = clamp(Math.floor(NODE_W*0.16), 8, 14);
    const AVA    = Math.floor(NODE_W*0.38);
    const H_GAP  = clamp(Math.floor(W/14), 24, 60);
    const V_GAP  = clamp(Math.floor(H/10), 70, 120);

    const root = d3.hierarchy(treeData);

    // batasi kedalaman sesuai levelLimit (3,6,9,...)
    root.eachBefore(d => { if (d.depth >= (levelLimit-1)) d.children = null; });

    const layout = d3.tree().nodeSize([H_GAP + NODE_W, V_GAP + NODE_H]);
    layout(root);

    /* --- Center root di tengah canvas saat load pertama (tanpa anchor) --- */
    if (!anchor) {
  const [cx, cy] = [W / 2, topSafeOffset(NODE_H)]; // ← kunci di tengah‑atas
  const T = d3.zoomIdentity
    .translate(cx - root.x, cy - root.y)  // geser world agar root tepat di (cx, cy)
    .scale(1);
  currentZoomTransform = T;
  svgSel.call(zoomBehavior).call(zoomBehavior.transform, T);
}

    /* --- Edges: elbow / siku --- */
    const elbow = d => {
      const x0=d.source.x, y0=d.source.y;
      const x1=d.target.x, y1=d.target.y;
      const ym=(y0+y1)/2;
      return `M${x0},${y0} V${ym} H${x1} V${y1}`;
    };
    g.append('g')
      .attr('fill','none')
      .attr('stroke','#cbd5e1')
      .attr('stroke-opacity',0.9)
      .attr('stroke-width',1.6)
      .attr('stroke-linejoin','round')
      .selectAll('path')
      .data(root.links())
      .join('path')
      .attr('d', elbow);

    /* --- Nodes --- */
    const node = g.append('g').selectAll('g')
      .data(root.descendants())
      .join('g')
      .attr('transform', d=>`translate(${d.x},${d.y})`)
      .on('mouseover', onHover)
      .on('mouseout', ()=>qs('#tree-tooltip')?.classList.add('hidden'));

    node.append('rect')
      .attr('x',-NODE_W/2).attr('y',-NODE_H/2)
      .attr('width',NODE_W).attr('height',NODE_H).attr('rx',RADIUS)
      .attr('fill','url(#grad-gold-black)');

    node.append('image')
      .attr('href', d=>getAvatarUrl(d))
      .attr('xlink:href', d=>getAvatarUrl(d))
      .attr('x',-AVA/2).attr('y',-NODE_H/2+6)
      .attr('width',AVA).attr('height',AVA)
      .attr('preserveAspectRatio','xMidYMid slice')
      .attr('clip-path','url(#avatarClip)');

    node.append('circle')
      .attr('cx',0).attr('cy',-NODE_H/2+6+AVA/2)
      .attr('r',AVA/2).attr('fill','none').attr('stroke','#fff')
      .attr('stroke-width',Math.max(2,Math.ceil(AVA*0.08)));

    const short=(s,m)=>!s?'':(s.length>m?s.slice(0,m)+'…':s);
    node.append('text')
      .attr('y',NODE_H/2-8).attr('text-anchor','middle')
      .text(d=>short(d.data.name||d.data.username||'', NODE_W<=90?8:12))
      .attr('fill','#fff').style('font-size',Math.max(11,Math.floor(NODE_W*0.12))+'px');

    // kembalikan posisi semula (anchor) bila ada
    if(anchor) applyAnchor(anchor);
  }

  function onHover(e,d){
    if (isAddNode(d.data)) return;
    const tip=qs('#tree-tooltip'); if(!tip) return;
    tip.innerHTML = `<strong>${d.data.name||d.data.username||'-'}</strong><br>
                     Status: ${d.data.status ?? '-'}<br>
                     Pairing: ${d.data.pairing_count ?? 0}<br>
                     Kiri: ${d.data.left_count ?? 0} • Kanan: ${d.data.right_count ?? 0}`;
    const box=qs('#tree-scroll').getBoundingClientRect();
    tip.style.left=`${e.clientX-box.left+10}px`;
    tip.style.top =`${e.clientY-box.top +10}px`;
    tip.classList.remove('hidden');
  }

  /* ===== Controls ===== */
  window.moreLevels = ()=>{
    pendingAction='more';
    levelLimit = Math.max(1, levelLimit + 3);
    loadTree();
  };
  window.lessLevels = ()=>{
    pendingAction='less';
    if (levelLimit <= 3) return info('Sudah 3 level minimal');
    levelLimit = Math.max(3, levelLimit - 3);
    loadTree();
  };

  // ambil anak kiri/kanan nyata
  const pickChild = side => (lastLoadedData?.children||[])
    .filter(c=>!isAddNode(c))
    .find(c=>c?.position===side && Number.isFinite(Number(c.id))) || null;

  function goDown(id, tag){
    const to=toNum(id); if(!to) return;
    if(currentRootId!==to) upStack.push(currentRootId);
    currentRootId=to; pendingAction=tag;
    levelLimit=Math.max(1,levelLimit+3);
    rootOfDepth=null; lastMaxDepth=-1;
    loadTree();
  }
  window.navLeft  = ()=>{ const L=pickChild('left');  if(!L) return info('Tidak ada anak kiri');  goDown(L.id,'navLeft'); };
  window.navRight = ()=>{ const R=pickChild('right'); if(!R) return info('Tidak ada anak kanan'); goDown(R.id,'navRight'); };
  window.navDown  = ()=>{
    const L=pickChild('left'), R=pickChild('right');
    const kids=[...(L?.children||[]), ...(R?.children||[])]
      .filter(n=>!isAddNode(n) && Number.isFinite(Number(n.id)));
    if(!kids.length) return info('Tidak ada cucu');
    goDown((kids[Math.floor(kids.length/2)]||kids[0]).id, 'navDown');
  };
  window.navUp = async ()=>{
    pendingAction='navUp';
    if(upStack.length){
      currentRootId=upStack.pop();
      rootOfDepth=null; lastMaxDepth=-1;
      return loadTree();
    }
    const pid=await resolveParentId(currentRootId);
    if(!pid || pid===currentRootId) return info('Tidak ada upline');
    currentRootId=pid; rootOfDepth=null; lastMaxDepth=-1; loadTree();
  };

  /* ===== Boot ===== */
  document.addEventListener('DOMContentLoaded', ()=>{
    loadTree();

    // keyboard
    window.addEventListener('keydown', e=>{
      if(e.key==='ArrowLeft')  navLeft();
      if(e.key==='ArrowRight') navRight();
      if(e.key==='ArrowUp')    navUp();
      if(e.key==='ArrowDown')  navDown();
      if((e.ctrlKey||e.metaKey) && (e.key==='='||e.key==='+')) zoomIn(); // Ctrl/Cmd + +
      if((e.ctrlKey||e.metaKey) && e.key==='-')   zoomOut();             // Ctrl/Cmd + -
    });

    // pertahankan anchor saat resize
    let rAF=null;
    window.addEventListener('resize', ()=>{
      if(!lastLoadedData) return;
      if (rAF) cancelAnimationFrame(rAF);
      rAF = requestAnimationFrame(()=>{
        const a=anchorBefore();
        drawTree(lastLoadedData,{anchor:a});
      });
    });
  });
})();
</script>
@endpush