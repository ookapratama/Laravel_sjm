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
/* ========= STATE ========= */
const AUTH_USER_ID = {{ auth()->user()->id }};
window.currentRootId = {{ $root->id }};
window.currentBagan  = Number(localStorage.getItem('selectedBagan') || 1);

let lastLoadedData = null;
let svgSel = null, g = null;
let currentZoomTransform = d3.zoomIdentity;
let isVertical = true;

/* ========= UTIL ========= */
const clamp = (v,lo,hi)=>Math.max(lo,Math.min(hi,v));
function activeBagansFrom(d){
  if (Array.isArray(d.active_bagans)) return d.active_bagans.map(Number);
  // fallback old flags
  return Object.keys(d).filter(k => k.startsWith('is_active_bagan_') && d[k]==1)
    .map(k => parseInt(k.replace('is_active_bagan_',''),10));
}
function isActiveOnSelected(d){
  return activeBagansFrom(d).includes(Number(window.currentBagan));
}
function topSafeOffset(nodeH){
  const upBtn = document.querySelector('.tree-nav.up button');
  const upH   = upBtn ? upBtn.getBoundingClientRect().height : 36;
  return upH + 18 + (nodeH/2);
}

/* ========= MODAL ========= */
function ensureAddUserModal(){
  let m = document.getElementById('addUserModal');
  if (!m){
    document.body.insertAdjacentHTML('beforeend', `
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Member Yang Belum Masuk Jaringan</h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body"><div id="userList" class="list-group"></div></div>
    </div>
  </div>
</div>`);
    m = document.getElementById('addUserModal');
  }
  return { modal: m, list: document.getElementById('userList') };
}
window.openAddModal = function(sponsorId, position, uplineId){
  const { modal, list } = ensureAddUserModal();
  if (!modal || !list) return;
  list.innerHTML = 'Memuat...';
  fetch(`/tree/available-users/${sponsorId}`, { headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r=>{ if(!r.ok) throw 0; return r.json(); })
    .then(users=>{
      list.innerHTML='';
      if (!Array.isArray(users) || !users.length){
        list.innerHTML = '<div class="text-center text-muted">Tidak ada user tersedia.</div>'; return;
      }
      users.forEach(u=>{
        const row = document.createElement('div');
        row.className='list-group-item d-flex justify-content-between align-items-center';
        row.innerHTML = `<div><strong>${u.username}</strong><br><small>${u.name}</small></div>
                         <button class="btn btn-sm btn-primary">Pasang</button>`;
        row.querySelector('button').onclick = ()=>window.submitAddUser(u.id, position, uplineId);
        list.appendChild(row);
      });
    })
    .catch(()=> list.innerHTML = '<div class="text-center text-danger">Gagal memuat data user.</div>')
    .finally(()=> new bootstrap.Modal(modal).show());
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
  const st=[lastLoadedData];
  while(st.length){
    const n=st.pop();
    if(n.id==parentId){
      n.children = (n.children||[]).filter(c=>!(c.isAddButton && c.position===position));
      n.children.push({ id, name, parent_id: parentId, position, isAddButton:false, children:[] });
      break;
    }
    (n.children||[]).forEach(c=>st.push(c));
  }
  drawTree(lastLoadedData, true, currentZoomTransform);
  toastr?.success?.(`User dipasang di ${position}`);
};

/* ========= ZOOM ========= */
const zoomBehavior = d3.zoom().on('zoom', e=>{
  currentZoomTransform = e.transform;
  if (g) g.attr('transform', currentZoomTransform);
});
function bindZoomIfNeeded(){
  if (!svgSel || !svgSel.node()) return false;
  if (!svgSel.node().__zoom) svgSel.call(zoomBehavior);
  return true;
}
window.zoomIn = ()=>{ if(!bindZoomIfNeeded()) return; const t=currentZoomTransform.scale(1.2); svgSel.transition().duration(300).call(zoomBehavior.transform,t); currentZoomTransform=t; };
window.zoomOut= ()=>{ if(!bindZoomIfNeeded()) return; const t=currentZoomTransform.scale(0.83);svgSel.transition().duration(300).call(zoomBehavior.transform,t); currentZoomTransform=t; };
window.resetZoom= ()=>{ currentZoomTransform=d3.zoomIdentity; loadTree(); };

/* ========= LOAD ========= */
async function loadTree(){
  const prev = document.querySelector('#tree-container svg');
  const keepT = prev ? d3.zoomTransform(prev) : null;

  try{
    const res = await fetch(`/tree/load/${window.currentRootId}?limit=3`, { headers:{'X-Requested-With':'XMLHttpRequest'} });
    if(!res.ok){ toastr?.error?.('Gagal memuat tree'); return; }
    const data = await res.json();
    if (data && data.parent_id == null && data.upline_id != null) data.parent_id = data.upline_id;
    lastLoadedData = data;
    drawTree(data, true, (keepT && keepT.k) ? keepT : null);
  }catch{ toastr?.error?.('Koneksi bermasalah'); }
}
window.loadTree = loadTree;

/* ========= GRADIENTS ========= */
function appendGradients(sel){
  const defs = sel.append('defs');
  defs.append('linearGradient').attr('id','goldGradient')
    .attr('x1','0%').attr('y1','0%').attr('x2','100%').attr('y2','100%')
    .selectAll('stop').data([{offset:'0%',color:'#FFD700'},{offset:'100%',color:'#000'}])
    .enter().append('stop').attr('offset',d=>d.offset).attr('stop-color',d=>d.color);

  defs.append('linearGradient').attr('id','greenGradient')
    .attr('x1','0%').attr('y1','0%').attr('x2','100%').attr('y2','100%')
    .selectAll('stop').data([{offset:'0%',color:'#00c853'},{offset:'100%',color:'#003300'}])
    .enter().append('stop').attr('offset',d=>d.offset).attr('stop-color',d=>d.color);

  defs.append('linearGradient').attr('id','blueGradient')
    .attr('x1','0%').attr('y1','0%').attr('x2','100%').attr('y2','100%')
    .selectAll('stop').data([{offset:'0%',color:'#66ccff'},{offset:'100%',color:'#003366'}])
    .enter().append('stop').attr('offset',d=>d.offset).attr('stop-color',d=>d.color);

  defs.append('linearGradient').attr('id','grayGradient')
    .attr('x1','0%').attr('y1','0%').attr('x2','100%').attr('y2','100%')
    .selectAll('stop').data([{offset:'0%',color:'#9aa5b1'},{offset:'100%',color:'#3c4a57'}])
    .enter().append('stop').attr('offset',d=>d.offset).attr('stop-color',d=>d.color);
}

/* ========= COLOR & STAR ========= */
function getNodeColor(d){
  if (d.isAddButton) return 'url(#blueGradient)';
  return isActiveOnSelected(d) ? 'url(#greenGradient)' : 'url(#grayGradient)';
}

/* ========= DRAW ========= */
function drawTree(data, preserveZoom=false, zoomOverride=null){
  if (!data) return;

  const board = document.getElementById('tree-scroll');
  const W = board.clientWidth||1200, H = board.clientHeight||750;

  const maxCols = Math.pow(2, 3-1);
  const hGap = clamp(Math.floor(W/(maxCols+4)), 16, 48);
  const vGap = clamp(Math.floor(H/(3+3)),  60, 110);
  const NODE_W = clamp(Math.floor((W - (maxCols + 1)*hGap)/maxCols), 72, 110);
  const NODE_H = clamp(Math.floor(NODE_W*0.9), 60, 100);
  const RADIUS = clamp(Math.floor(NODE_W*0.16), 8, 14);
  const AVA = Math.floor(NODE_W*0.38);

  // reset container → buat SVG → tempel (hindari __zoom null)
  const container = document.getElementById('tree-container');
  container.innerHTML = '';
  const svgEl = document.createElementNS('http://www.w3.org/2000/svg','svg');
  svgEl.setAttribute('width', W); svgEl.setAttribute('height', H);
  container.appendChild(svgEl);

  svgSel = d3.select(svgEl);
  appendGradients(svgSel);
  g = svgSel.append('g');

  const centerX = W/2, centerY = topSafeOffset(NODE_H);
  if (preserveZoom && zoomOverride){
    currentZoomTransform = zoomOverride;
    if (currentZoomTransform.y < centerY) currentZoomTransform = d3.zoomIdentity.translate(centerX, centerY).scale(zoomOverride.k);
  } else {
    currentZoomTransform = d3.zoomIdentity.translate(centerX, centerY);
  }
  svgSel.call(zoomBehavior).call(zoomBehavior.transform, currentZoomTransform);

  const root = d3.hierarchy(data);
  root.eachBefore(d=>{
    if (d.children){
      d.children.sort((a,b)=>{
        if (a.data.position==='left') return -1;
        if (a.data.position==='right') return 1;
        return 0;
      });
    }
    if (d.depth >= 2) d.children = null; // tampilkan 3 level saja
  });

  const layout = d3.tree().nodeSize([hGap+NODE_W, vGap+NODE_H]);
  layout(root);

  g.append('g').attr('fill','none').attr('stroke','#cbd5e1').attr('stroke-opacity',0.65).attr('stroke-width',1.2)
    .selectAll('path').data(root.links()).join('path')
    .attr('d', d3.linkVertical().x(d=>d.x).y(d=>d.y));

  const node = g.append('g').selectAll('g').data(root.descendants()).join('g')
    .attr('transform', d=>`translate(${d.x},${d.y})`)
    .on('mouseover', showTooltip).on('mouseout', hideTooltip);

  node.append('rect')
    .attr('x',-NODE_W/2).attr('y',-NODE_H/2)
    .attr('width',NODE_W).attr('height',NODE_H).attr('rx',RADIUS)
    .attr('fill', d=>getNodeColor(d.data));

  node.filter(d=>!d.data.isAddButton).append('image')
    .attr('xlink:href','/assets/img/profile.jpg')
    .attr('x',-AVA/2).attr('y',-NODE_H/2+6)
    .attr('width',AVA).attr('height',AVA)
    .attr('clip-path', `circle(${AVA/2}px at ${AVA/2}px ${AVA/2}px)`);

  // ⭐ sesuai bagan terpilih
  node.filter(d=>!d.data.isAddButton).append('text')
    .attr('y', 4).attr('text-anchor','middle')
    .text(()=> '⭐️'.repeat(Math.min(Number(window.currentBagan)||1,5)))
    .style('font-size', Math.max(9, Math.floor(NODE_W*0.11)) + 'px')
    .attr('fill','gold');

  function shortName(s){ if(!s) return ''; const max = NODE_W<=80 ? 7:9; return s.length>max ? s.slice(0,max)+'…' : s; }
  node.filter(d=>!d.data.isAddButton).append('text')
    .attr('y', NODE_H/2 - 8).attr('text-anchor','middle')
    .text(d=> shortName(d.data.name || d.data.username || ''))
    .attr('fill', d=> isActiveOnSelected(d.data) ? '#fff' : '#cbd5e1')
    .style('font-size', Math.max(10, Math.floor(NODE_W*0.12)) + 'px');

  // + Tambah
  const addNodes = node.filter(d=>d.data.isAddButton);
  function onAddClick(e,d){
    e.stopPropagation();
    const pos = d.data.position || d.parent?.data?.position || 'left';
    const up  = d.data.parent_id ?? d.parent?.data?.id ?? null;
    if (!up){ toastr?.warning?.('Upline tidak terdeteksi.'); return; }
    window.openAddModal(AUTH_USER_ID, pos, up);
  }
  addNodes.style('cursor','pointer').on('click', onAddClick);
  addNodes.append('text').attr('y',2).attr('text-anchor','middle')
    .text('+ Tambah').style('font-size', Math.max(10, Math.floor(NODE_W*0.12)) + 'px').attr('fill','#fff');
}

/* ========= TOOLTIP ========= */
function showTooltip(event, d){
  const el = document.getElementById('tree-tooltip'); if(!el || d.data.isAddButton) return;
  const aktif = isActiveOnSelected(d.data) ? 'Ya' : 'Tidak';
  el.innerHTML = `
    <strong>${d.data.name}</strong><br>
    Bagan P${window.currentBagan}: <b>${aktif}</b><br>
    Status: ${d.data.status}<br>
    Pairing: ${d.data.pairing_count ?? '-'}<br>
    Kiri: ${d.data.left_count ?? 0} • Kanan: ${d.data.right_count ?? 0}
  `;
  const box = document.getElementById('tree-scroll').getBoundingClientRect();
  el.style.left = `${event.clientX - box.left + 10}px`;
  el.style.top  = `${event.clientY - box.top + 10}px`;
  el.classList.remove('hidden');
}
function hideTooltip(){ document.getElementById('tree-tooltip')?.classList.add('hidden'); }

/* ========= NAV PANAH ========= */
function realChild(side){
  return (lastLoadedData?.children || [])
    .find(c => c.position===side && !c.isAddButton && Number.isFinite(c.id));
}
window.navUp    = async function(){ const pid = lastLoadedData?.parent_id; if (!pid){ toastr?.info?.('Tidak ada upline.'); return; } window.currentRootId=pid; loadTree(); };
window.navLeft  = function(){ const L=realChild('left');  if(!L){toastr?.info?.('Tidak ada anak kiri.');return;}  window.currentRootId=L.id; loadTree(); };
window.navRight = function(){ const R=realChild('right'); if(!R){toastr?.info?.('Tidak ada anak kanan.');return;} window.currentRootId=R.id; loadTree(); };
window.navDown  = function(){
  const L=realChild('left'), R=realChild('right');
  const kids = [ ...(L?.children||[]).filter(n=>!n.isAddButton && Number.isFinite(n.id)),
                 ...(R?.children||[]).filter(n=>!n.isAddButton && Number.isFinite(n.id)) ];
  if(!kids.length){ toastr?.info?.('Tidak ada cucu.'); return; }
  const mid = kids[Math.floor(kids.length/2)] || kids[0];
  window.currentRootId=mid.id; loadTree();
};

/* ========= MENU BAGAN (P1–P6) ========= */
function bindBaganMenu(){
  const items = document.querySelectorAll('.menu-bagan[data-bagan]');
  items.forEach(a=>{
    a.addEventListener('click', e=>{
      e.preventDefault();
      const n = parseInt(a.dataset.bagan,10);
      if (!Number.isFinite(n)) return;
      window.currentBagan=n;
      localStorage.setItem('selectedBagan', String(n));
      items.forEach(x=>x.classList.toggle('active', x===a));
      if (lastLoadedData) drawTree(lastLoadedData, true, currentZoomTransform);
    });
    a.classList.toggle('active', Number(a.dataset.bagan)===window.currentBagan);
  });
}

/* ========= BOOT ========= */
document.addEventListener('DOMContentLoaded', ()=>{
  bindBaganMenu();
  loadTree();
});
</script>
@endpush
