@extends('layouts.app')

@section('content')
<div class="page-inner relative">
  <div class="absolute right-5 top-3 z-10 flex gap-2">
    <button onclick="zoomIn()"  class="px-3 py-1 bg-blue-600 text-white rounded btn-primary">＋</button>
    <button onclick="zoomOut()" class="px-3 py-1 bg-blue-600 text-white rounded btn-primary">－</button>
    <button onclick="resetZoom()" class="px-3 py-1 text-white rounded btn-black">⟳</button>
    <button id="rotateTreeBtn" class="px-3 py-1 bg-gray-700 text-white rounded btn-black"><i class="fas fa-sync-alt"></i></button>
    <button onclick="navLeft()"  class="px-3 py-1 bg-yellow-600 text-white rounded btn-warning">Prev</button>
    <button onclick="navRight()" class="px-3 py-1 bg-green-600  text-white rounded btn-success">Next</button>
  </div>

  <!-- Penting: position:relative supaya overlay panah relatif ke area tree -->
  <div id="tree-scroll" class="overflow-auto w-full h-[85vh] border" style="position:relative;">
    <div id="tree-container"></div>

    <!-- overlay panah di DALAM area tree -->
    <div class="tree-nav left"><button onclick="navLeft()">◀</button></div>
    <div class="tree-nav right"><button onclick="navRight()">▶</button></div>
    <div class="tree-nav up"><button onclick="navUp()">▲</button></div>
    <div class="tree-nav down"><button onclick="navDown()">▼</button></div>
  </div>
</div>

<div id="tree-tooltip" class="hidden"></div>
<meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-bold">Member Yang Belum Masuk Jaringan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="userList" class="list-group"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
<style>
.tree-nav{position:absolute;z-index:30;opacity:.9}
.tree-nav.left{left:10px;top:50%;transform:translateY(-50%)}
.tree-nav.right{right:10px;top:50%;transform:translateY(-50%)}
.tree-nav.up{left:50%;top:10px;transform:translateX(-50%)}
.tree-nav.down{left:50%;bottom:10px;transform:translateX(-50%)}
.tree-nav button{background:#60a5fa;border:none;color:#fff;padding:10px 14px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.15)}
@media (max-width:640px){.tree-nav button{padding:8px 10px}}
#tree-tooltip{position:absolute;background:#fff;border:1px solid #ddd;padding:8px 12px;border-radius:6px;font-size:13px;box-shadow:0 2px 8px rgba(0,0,0,.2);pointer-events:none;z-index:40}
</style>


@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
/* ====== CONFIG ====== */
const VIEW_LEVELS = 3;
const NODE_W_MIN  = 70;
const NODE_W_MAX  = 110;
const NODE_ASPECT = 0.90;

const AUTH_USER_ID = {{ auth()->user()->id }};
const rootId       = {{ $root->id }};
window.currentRootId = rootId;

/* ====== STATE ====== */
let isVertical = true;
let lastLoadedData = null;
let svgSel = null;       // d3 selection dari SVG yg SUDAH ditempel ke DOM
let g = null;
let currentZoomTransform = d3.zoomIdentity;

/* ====== HELPERS ====== */
const clamp = (v,lo,hi)=>Math.max(lo,Math.min(hi,v));
function topSafeOffset(nodeH){
  const upBtn = document.querySelector('.tree-nav.up button');
  const upH   = upBtn ? upBtn.getBoundingClientRect().height : 36;
  const pad   = 18;
  return upH + pad + (nodeH/2);
}
function getNodeColor(d){
  if (d.isAddButton) return 'url(#blueGradient)';
  if ((d.pairing_count ?? 0) >= 5) return 'url(#goldGradient)';
  return 'url(#greenGradient)';
}
function ensureNavOverlay(){
  const wrap = document.getElementById('tree-scroll');
  if (!wrap) return;
  if (!wrap.querySelector('.tree-nav.left')){
    wrap.insertAdjacentHTML('beforeend', `
      <div class="tree-nav left"><button onclick="navLeft()">◀</button></div>
      <div class="tree-nav right"><button onclick="navRight()">▶</button></div>
      <div class="tree-nav up"><button onclick="navUp()">▲</button></div>
      <div class="tree-nav down"><button onclick="navDown()">▼</button></div>
    `);
  }
}

/* ====== MODAL (GLOBAL) ====== */
window.openAddModal = function(sponsorId, position, uplineId){
  console.debug('[MODAL] openAddModal', { sponsorId, position, uplineId });

  const modalEl = document.getElementById('addUserModal');
  const userList = document.getElementById('userList');
  if (!modalEl || !userList) { toastr.error('Modal element tidak ditemukan'); return; }

  userList.innerHTML = 'Memuat...';
  fetch(`/tree/available-users/${sponsorId}`, { headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r => { if(!r.ok) throw new Error('Gagal ambil data'); return r.json(); })
    .then(users => {
      userList.innerHTML = '';
      if (!Array.isArray(users) || !users.length){
        userList.innerHTML = '<div class="text-center text-muted">Tidak ada user tersedia.</div>'; return;
      }
      users.forEach(u=>{
        const div = document.createElement('div');
        div.className = 'list-group-item d-flex justify-content-between align-items-center';
        div.innerHTML = `<div><strong>${u.username}</strong><br><small>${u.name}</small></div>
                         <button class="btn btn-sm btn-primary">Pasang</button>`;
        div.querySelector('button').onclick = () => window.submitAddUser(u.id, position, uplineId);
        userList.appendChild(div);
      });
    })
    .catch(err => {
      console.error('Gagal load available-users:', err);
      userList.innerHTML = '<div class="text-center text-danger">Gagal memuat data user.</div>';
    })
    .finally(()=>{
      if (window.bootstrap?.Modal) {
        new bootstrap.Modal(modalEl).show();
      } else {
        console.error('Bootstrap Modal tidak tersedia');
      }
    });
};


window.submitAddUser = function(userId, position, uplineId){
  fetch(`/tree/${userId}`, {
    method:'PUT',
    headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
    body: JSON.stringify({ user_id:userId, position, upline_id:uplineId })
  })
  .then(r=>r.json())
  .then(data=>{
    updateNode(uplineId, position, data.id, data.name);
    bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
  })
  .catch(()=> toastr?.error?.('Gagal memasang user'));
};

window.updateNode = function(parentId, position, id, name){
  const stack = [lastLoadedData];
  while (stack.length){
    const n = stack.pop();
    if (n.id == parentId){
      n.children = (n.children || []).filter(c => !(c.isAddButton && c.position === position));
      n.children.push({ id, name, parent_id: parentId, position, isAddButton:false, level:(n.level??0)+1, children:[] });
      break;
    }
    (n.children || []).forEach(c=>stack.push(c));
  }
  drawTree(lastLoadedData, true, currentZoomTransform);
  toastr?.success?.(`User berhasil dipasang di posisi ${position}`);
};

/* ====== BACKEND HELPERS ====== */
async function getParentIdFromAPI(id){
  try{
    const r = await fetch(`/tree/parent/${id}`, { headers:{'X-Requested-With':'XMLHttpRequest'} });
    if(!r.ok) return null; const j = await r.json(); return j?.id ?? null;
  }catch{ return null; }
}

/* ====== ZOOM ====== */
const zoomBehavior = d3.zoom().on("zoom", e => {
  currentZoomTransform = e.transform;
  if (g) g.attr("transform", currentZoomTransform);
});

function hasSvg() { return !!(svgSel && svgSel.node()); }
function ensureZoomBound(){
  if (!hasSvg()) return false;
  // kalau belum pernah di-apply zoom, apply dulu.
  if (!svgSel.node().__zoom) svgSel.call(zoomBehavior);
  return true;
}

window.zoomIn  = () => {
  if (!ensureZoomBound()) return;
  const t=currentZoomTransform.scale(1.2);
  svgSel.transition().duration(300).call(zoomBehavior.transform, t);
  currentZoomTransform=t;
};
window.zoomOut = () => {
  if (!ensureZoomBound()) return;
  const t=currentZoomTransform.scale(0.83);
  svgSel.transition().duration(300).call(zoomBehavior.transform, t);
  currentZoomTransform=t;
};
window.resetZoom = () => { currentZoomTransform = d3.zoomIdentity; loadTree(); };

/* ====== LOAD TREE (AMAN) ====== */
async function loadTree(){
  const prevSvg = document.querySelector("#tree-container svg");
  const keepT = prevSvg ? d3.zoomTransform(prevSvg) : null;

  const url = `/tree/load/${window.currentRootId}?limit=${VIEW_LEVELS}`;
  try{
    const res = await fetch(url, { headers:{'X-Requested-With':'XMLHttpRequest'} });
    if(!res.ok){ const txt = await res.text(); console.error('loadTree failed', res.status, txt.slice(0,120)); toastr?.error?.('Gagal memuat tree'); return; }
    const data = await res.json();

    // normalisasi parent untuk tombol ↑
    if (data && data.parent_id == null && data.upline_id != null) data.parent_id = data.upline_id;
    if (data && (data.parent_id == null || data.parent_id === undefined)){
      data.parent_id = await getParentIdFromAPI(window.currentRootId);
    }

    lastLoadedData = data;
    drawTree(data, true, (keepT && keepT.k) ? keepT : null);
  }catch(e){ console.error(e); toastr?.error?.('Koneksi bermasalah'); }
}
window.loadTree = loadTree;

/* ====== GRADIENT ====== */
function appendGradients(sel){
  const defs = sel.append("defs");
  defs.append("linearGradient").attr("id","goldGradient").attr("x1","0%").attr("y1","0%").attr("x2","100%").attr("y2","100%")
    .selectAll("stop").data([{offset:"0%",color:"#FFD700"},{offset:"100%",color:"#000"}])
    .enter().append("stop").attr("offset",d=>d.offset).attr("stop-color",d=>d.color);
  defs.append("linearGradient").attr("id","greenGradient").attr("x1","0%").attr("y1","0%").attr("x2","100%").attr("y2","100%")
    .selectAll("stop").data([{offset:"0%",color:"#00c853"},{offset:"100%",color:"#003300"}])
    .enter().append("stop").attr("offset",d=>d.offset).attr("stop-color",d=>d.color);
  defs.append("linearGradient").attr("id","blueGradient").attr("x1","0%").attr("y1","0%").attr("x2","100%").attr("y2","100%")
    .selectAll("stop").data([{offset:"0%",color:"#66ccff"},{offset:"100%",color:"#003366"}])
    .enter().append("stop").attr("offset",d=>d.offset).attr("stop-color",d=>d.color);
}

/* ====== DRAW ====== */
function drawTree(data, preserveZoom=false, zoomOverride=null){
  if (!data) return;

  const board = document.getElementById('tree-scroll');
  const W = board.clientWidth  || 1200;
  const H = board.clientHeight || 750;

  const maxCols = Math.pow(2, VIEW_LEVELS - 1);
  const hGap  = clamp(Math.floor(W/(maxCols + 4)), 16, 48);
  const vGap  = clamp(Math.floor(H/(VIEW_LEVELS + 3)), 60, 110);
  const NODE_W = clamp(Math.floor((W - (maxCols + 1) * hGap) / maxCols), NODE_W_MIN, NODE_W_MAX);
  const NODE_H = clamp(Math.floor(NODE_W * NODE_ASPECT), 60, 100);
  const RADIUS = clamp(Math.floor(NODE_W * 0.16), 8, 14);

  // Bersihkan & BUAT SVG → TEMPEL ke DOM lebih dulu
  const container = document.getElementById("tree-container");
  container.innerHTML = "";
  const svgEl = document.createElementNS("http://www.w3.org/2000/svg", "svg");
  svgEl.setAttribute("width", W);
  svgEl.setAttribute("height", H);
  container.appendChild(svgEl);

  // simpan selection dari elemen yg sudah ditempel
  svgSel = d3.select(svgEl);
  appendGradients(svgSel);
  g = svgSel.append("g");

  // posisi awal aman (root di bawah panah ↑)
  const centerX = W/2;
  const centerY = topSafeOffset(NODE_H);

  if (preserveZoom && zoomOverride){
    currentZoomTransform = zoomOverride;
    if (isVertical && currentZoomTransform.y < centerY){
      currentZoomTransform = d3.zoomIdentity.translate(centerX, centerY).scale(zoomOverride.k);
    }
  } else {
    currentZoomTransform = isVertical
      ? d3.zoomIdentity.translate(centerX, centerY)
      : d3.zoomIdentity.translate(36, H/2);
  }

  // BARU apply zoom & transform (elemen sudah ada → tidak error __zoom)
  svgSel.call(zoomBehavior).call(zoomBehavior.transform, currentZoomTransform);

  const root = d3.hierarchy(data);
  root.eachBefore(d=>{
    if (d.children){
      d.children.sort((a,b)=>{
        if (a.data.position==='left')  return -1;
        if (a.data.position==='right') return 1;
        return 0;
      });
    }
    if (d.depth >= VIEW_LEVELS - 1) d.children = null;
  });

  const treeLayout = d3.tree().nodeSize(isVertical ? [hGap + NODE_W, vGap + NODE_H] : [vGap + NODE_H, hGap + NODE_W]);
  treeLayout(root);

  g.append("g").attr("fill","none").attr("stroke","#cbd5e1").attr("stroke-opacity",0.65).attr("stroke-width",1.2)
    .selectAll("path").data(root.links()).join("path")
    .attr("d", isVertical ? d3.linkVertical().x(d=>d.x).y(d=>d.y) : d3.linkHorizontal().x(d=>d.y).y(d=>-d.x));

  const node = g.append("g").selectAll("g").data(root.descendants()).join("g")
    .attr("transform", d => isVertical ? `translate(${d.x},${d.y})` : `translate(${d.y},${-d.x})`)
    .on("mouseover", showTooltip).on("mouseout", hideTooltip);

  node.append("rect")
    .attr("x",-NODE_W/2).attr("y",-NODE_H/2)
    .attr("width",NODE_W).attr("height",NODE_H)
    .attr("rx",RADIUS).attr("fill", d => getNodeColor(d.data));

  const AVA = Math.floor(NODE_W * 0.38);
  node.filter(d=>!d.data.isAddButton).append("image")
    .attr("xlink:href","/assets/img/profile.jpg")
    .attr("x",-AVA/2).attr("y",-NODE_H/2 + 6)
    .attr("width",AVA).attr("height",AVA)
    .attr("clip-path", `circle(${AVA/2}px at ${AVA/2}px ${AVA/2}px)`);

  node.filter(d=>!d.data.isAddButton).append("text")
    .attr("y", 4).attr("text-anchor","middle")
    .text(d=>{
      const c = [d.data.is_active_bagan_1,d.data.is_active_bagan_2,d.data.is_active_bagan_3,d.data.is_active_bagan_4,d.data.is_active_bagan_5].filter(v=>v==1).length;
      return '⭐️'.repeat(c);
    })
    .style("font-size", Math.max(9, Math.floor(NODE_W*0.11)) + "px")
    .attr("fill","gold");

  function shortName(s){ if(!s) return ''; const maxChars = NODE_W <= 80 ? 7 : 9; return s.length>maxChars ? s.slice(0,maxChars)+'…' : s; }
  node.filter(d=>!d.data.isAddButton).append("text")
    .attr("y", NODE_H/2 - 8).attr("text-anchor","middle")
    .text(d=> shortName(d.data.name || d.data.username || ''))
    .attr("fill","#fff").style("font-size", Math.max(10, Math.floor(NODE_W*0.12)) + "px");

  const addNodes = node.filter(d=>d.data.isAddButton);
  addNodes.style("cursor","pointer").on("click",(e,d)=>{ hideTooltip(); 
    window.openAddModal(AUTH_USER_ID, d.data.position, d.data.parent_id); });
  addNodes.append("text").attr("y",2).attr("text-anchor","middle")
    .text("+ Tambah").style("font-size", Math.max(10, Math.floor(NODE_W*0.12)) + "px").attr("fill","#fff");
}

/* ====== TOOLTIP ====== */
function showTooltip(event, d){
  const el = document.getElementById('tree-tooltip'); if (!el || d.data.isAddButton) return;
  el.innerHTML = `<strong>${d.data.name}</strong><br>Status: ${d.data.status}<br>Posisi: ${d.data.position}<br>Pairing: ${d.data.pairing_count ?? '-'}<br>Anak Kiri: ${d.data.left_count ?? 0}<br>Anak Kanan: ${d.data.right_count ?? 0}`;
  const box = document.getElementById('tree-scroll').getBoundingClientRect();
  el.style.left = `${event.clientX - box.left + 10}px`; el.style.top = `${event.clientY - box.top + 10}px`;
  el.classList.remove('hidden');
}
function hideTooltip(){ document.getElementById('tree-tooltip')?.classList.add('hidden'); }

/* ====== NAVIGASI (hindari +Tambah) ====== */
function realChild(side){
  return (lastLoadedData?.children || [])
    .find(c => c.position === side && !c.isAddButton && Number.isFinite(c.id));
}
window.navUp = async function(){
  let pid = lastLoadedData?.parent_id ?? null;
  if (pid == null) pid = await getParentIdFromAPI(window.currentRootId);
  if (!pid){ toastr?.info?.('Tidak ada upline.'); return; }
  window.currentRootId = pid; loadTree();
};
window.navLeft  = function(){ const L = realChild('left');  if(!L){toastr?.info?.('Tidak ada anak kiri.');return;}  window.currentRootId=L.id; loadTree(); };
window.navRight = function(){ const R = realChild('right'); if(!R){toastr?.info?.('Tidak ada anak kanan.');return;} window.currentRootId=R.id; loadTree(); };
window.navDown  = function(){
  const L = realChild('left'); const R = realChild('right');
  const kids = [ ...(L?.children||[]).filter(n=>!n.isAddButton && Number.isFinite(n.id)),
                 ...(R?.children||[]).filter(n=>!n.isAddButton && Number.isFinite(n.id)) ];
  if(!kids.length){ toastr?.info?.('Tidak ada cucu.'); return; }
  const mid = kids[Math.floor(kids.length/2)] || kids[0];
  window.currentRootId = mid.id; loadTree();
};
// tombol lama
window.prevLevel = () => window.navLeft();
window.nextLevel = () => window.navRight();

/* ====== BOOT ====== */
document.getElementById('rotateTreeBtn')?.addEventListener('click', ()=>{ isVertical = !isVertical; loadTree(); });
let _rszTimer;
window.addEventListener('resize', ()=>{ clearTimeout(_rszTimer); _rszTimer = setTimeout(()=>drawTree(lastLoadedData, false, null), 120); });
document.addEventListener('DOMContentLoaded', ()=>{ ensureNavOverlay(); loadTree(); });
</script>
@endpush

