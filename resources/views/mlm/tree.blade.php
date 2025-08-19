@extends('layouts.app')

@section('content')
<div class="page-inner relative">
  <div class="absolute right-5 top-3 z-10 flex gap-2">
    <button onclick="zoomIn()"  class="px-3 py-1 bg-blue-600 text-white rounded btn-primary">＋</button>
    <button onclick="zoomOut()" class="px-3 py-1 bg-blue-600 text-white rounded btn-primary">－</button>
    <button onclick="navLeft()"  class="px-3 py-1 bg-yellow-600 text-white rounded btn-warning">Prev</button>
    <button onclick="navRight()" class="px-3 py-1 bg-green-600  text-white rounded btn-success">Next</button>
  </div>

  <!-- Kanvas tree -->
  <div id="tree-scroll" class="overflow-auto w-full h-[85vh] border relative">
    <div id="tree-container"></div>

    <!-- Overlay panah -->
    <div class="tree-nav left"><button onclick="navLeft()">◀</button></div>
    <div class="tree-nav right"><button onclick="navRight()">▶</button></div>
    <div class="tree-nav up"><button onclick="navUp()">▲</button></div>
    <div class="tree-nav down"><button onclick="navDown()">▼</button></div>
  </div>
</div>

<!-- Tooltip -->
<div id="tree-tooltip" class="hidden"></div>
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Modal: Clone / Tambah -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Tambah / Clone Member</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        
<ul class="nav nav-tabs" id="addTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="tabCloneBtn" data-bs-toggle="tab" data-bs-target="#tabClone" type="button">Clone</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="tabTambahBtn" data-bs-toggle="tab" data-bs-target="#tabTambah" type="button">
      Tambah
      <span id="pendingCountBadge" class="badge rounded-pill bg-danger ms-2 d-none">0</span>
    </button>
  </li>
</ul>
        </ul>

        <div class="tab-content mt-3">
          {{-- ======== TAB CLONE ======== --}}
          <div class="tab-pane fade show active" id="tabClone" role="tabpanel" aria-labelledby="tabCloneBtn">
            <form id="cloneForm">
              @csrf
              <input type="hidden" name="parent_id" id="cloneParentId">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Posisi</label>
                  <div class="form-control-plaintext">
                    <span id="clonePositionLabel">-</span>
                  </div>
                   
                  <input type="hidden" name="position" id="clonePosition" value="left">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Bagan</label>
                  <select class="form-select" name="bagan" id="cloneBagan" disabled>
                    <option value="1" selected>Bagan 1</option>
                  </select>
                  <input type="hidden" name="bagan" value="1">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Base Cloning</label>
                  <select class="form-select" name="use_login" id="cloneUseLogin">
                    <option value="1" selected>Pakai ID yang login</option>
                    <option value="0">Pakai ID parent</option>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label">Pilih PIN Aktivasi (multi)</label>
                  <select class="form-select" name="pin_codes[]" id="pinCodes" multiple size="8" required></select>
                  <small class="text-muted">Jumlah ID = banyaknya PIN yang dipilih.</small>
                </div>

                <div class="col-12 d-flex align-items-center gap-2">
                  <button type="button" class="btn btn-outline-secondary" id="btnPreview">Preview Username/Referral</button>
                  <div id="clonePreview" class="small"></div>
                </div>

                <div class="col-12">
                  <button class="btn btn-primary" id="btnCloneSubmit">Clone & Tempel</button>
                </div>
              </div>
            </form>
          </div>

          {{-- ======== TAB TAMBAH (dari daftar pending) ======== --}}
          <div class="tab-pane fade" id="tabTambah" role="tabpanel" aria-labelledby="tabTambahBtn">
            <div id="userList" class="list-group"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

<style>
.tree-nav{position:absolute;z-index:30;opacity:.9}
.tree-nav.left{left:10px;top:50%;transform:translateY(-50%)}
.tree-nav.right{right:10px;top:50%;transform:translateY(-50%)}
.tree-nav.up{
  left:50%;
  top:calc( var(--node-height, 80px) + 10px );
  transform:translateX(-50%);
}
.tree-nav.down{left:50%;bottom:10px;transform:translateX(-50%)}
.tree-nav button{background:#60a5fa;border:none;color:#fff;padding:10px 14px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.15)}
@media (max-width:640px){.tree-nav button{padding:8px 10px}}
#tree-tooltip{position:absolute;background:#fff;border:1px solid #ddd;padding:8px 12px;border-radius:6px;font-size:13px;box-shadow:0 2px 8px rgba(0,0,0,.2);pointer-events:none;z-index:40}
</style>

@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
(() => {
  "use strict";

  /* =============== STATE =============== */
  const AUTH_USER_ID = {{ auth()->user()->id }};
  window.currentRootId = {{ $root->id }};
  window.currentBagan  = Number(localStorage.getItem('selectedBagan') || 1);

  let lastLoadedData = null;
  let svgSel = null, g = null;
  let currentZoomTransform = d3.zoomIdentity;

  let isLoading = false;
  let pendingController = null;

  const upStack = [];                    // riwayat untuk NAV UP
  const parentCache = new Map();         // cache parent: id -> parentId

  /* =============== UTIL =============== */
  const clamp = (v,lo,hi)=>Math.max(lo,Math.min(hi,v));
  const toNum = (v) => { const n = Number(String(v ?? '').trim()); return Number.isFinite(n) ? n : null; };

  function activeBagansFrom(d){
    if (!d || typeof d!=='object') return [];
    if (Array.isArray(d.active_bagans)) return d.active_bagans.map(Number);
    return Object.keys(d).filter(k => k.startsWith('is_active_bagan_') && d[k]==1)
      .map(k => parseInt(k.replace('is_active_bagan_',''),10));
  }
  function isActiveOnSelected(d){ return activeBagansFrom(d).includes(Number(window.currentBagan)); }

  function topSafeOffset(nodeH){
    const upBtn = document.querySelector('.tree-nav.up button');
    const upH = upBtn ? upBtn.getBoundingClientRect().height : 36;
    return upH + 18 + (nodeH/2);
  }
  function shortName(s,max){ if(!s) return ''; return s.length>max ? s.slice(0,max)+'…' : s; }

  // Normalisasi id & parent
  function normalizeIds(node){
    if (!node || typeof node !== 'object') return node;

    node.id = toNum(node.id);
    node.parent_id = toNum(node.parent_id ?? node.upline_id);

    if (node.data && typeof node.data === 'object') {
      node.data.id = toNum(node.data.id);
      node.data.parent_id = toNum(node.data.parent_id ?? node.data.upline_id);
    }

    if (Array.isArray(node.children)) {
      node.children.forEach(c=>{
        if (!c || typeof c!=='object') return;
        c.id = toNum(c.id);
        c.parent_id = toNum(c.parent_id ?? c.upline_id);
        if (c.data && typeof c.data === 'object') {
          c.data.id = toNum(c.data.id);
          c.data.parent_id = toNum(c.data.parent_id ?? c.data.upline_id);
        }
        if (Array.isArray(c.children)) {
          c.children.forEach(g=>{
            if (!g || typeof g!=='object') return;
            g.id = toNum(g.id);
            g.parent_id = toNum(g.parent_id ?? g.upline_id);
            if (g.data && typeof g.data === 'object') {
              g.data.id = toNum(g.data.id);
              g.data.parent_id = toNum(g.data.parent_id ?? g.data.upline_id);
            }
          });
        }
      });
    }
    return node;
  }

  async function fetchJSON(url, opts={}){
    if (pendingController) pendingController.abort();
    pendingController = new AbortController();
    const res = await fetch(url, { ...opts, signal: pendingController.signal, headers: { 'X-Requested-With':'XMLHttpRequest', ...(opts.headers||{}) } });
    if (!res.ok) throw new Error('HTTP '+res.status);
    return res.json();
  }
  async function fetchTEXT(url){
    const res = await fetch(url, { headers:{'X-Requested-With':'XMLHttpRequest'} });
    const text = await res.text();
    return { ok: res.ok, text, status: res.status };
  }
  function tryJSON(any){
    if (any == null) return null;
    if (typeof any === 'object') return any;
    try { return JSON.parse(any); } catch { return null; }
  }
  function pickParentId(payload){
    const d = tryJSON(payload) ?? {};
    const arr = [
      d.parent_id, d.parentId, d.pid, d.parent,
      d?.data?.parent_id, d?.data?.parentId,
      d?.user?.upline_id, d?.user?.parent_id,
      d.upline_id,
      d.id
    ];
    for (const c of arr){
      const n = toNum(c);
      if (n && n > 0) return n;
    }
    const nested = toNum(d?.parent?.id ?? d?.parent?.parent_id);
    return (nested && nested > 0) ? nested : null;
  }

  /* =============== PARENT RESOLVER =============== */
  async function resolveParentId(currentId){
    const cur = toNum(currentId);
    if (!cur || cur <= 0) return null;

    if (parentCache.has(cur)) return parentCache.get(cur); // cache

    const local = [
      lastLoadedData?.parent_id,
      lastLoadedData?.upline_id,
      lastLoadedData?.data?.parent_id,
      lastLoadedData?.data?.upline_id
    ].map(toNum).find(n => n && n > 0);
    if (local){ parentCache.set(cur, local); return local; }

    try{
      const r = await fetchTEXT(`/tree/parent/${cur}`);
      if (r.ok){
        const pid = pickParentId(r.text);
        if (pid && pid > 0){ parentCache.set(cur, pid); return pid; }
      }
    }catch{}

    try{
      const r = await fetchTEXT(`/users/ajax/${cur}`);
      if (r.ok){
        const pid = pickParentId(r.text);
        if (pid && pid > 0){ parentCache.set(cur, pid); return pid; }
      }
    }catch{}

    try{
      const r = await fetchTEXT(`/tree/node/${cur}`);
      if (r.ok){
        const pid = pickParentId(r.text);
        if (pid && pid > 0){ parentCache.set(cur, pid); return pid; }
      }
    }catch{}

    return null;
  }

  /* =============== ZOOM =============== */
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

  /* =============== LOAD =============== */
  async function loadTree(){
    if (isLoading) return;
    isLoading = true;

    const prev = document.querySelector('#tree-container svg');
    const keepT = prev ? d3.zoomTransform(prev) : null;

    try{
      const data = await fetchJSON(`/tree/load/${window.currentRootId}?limit=3`);
      if (data && data.parent_id == null && data.upline_id != null) data.parent_id = data.upline_id;
      lastLoadedData = normalizeIds(data);

      const cur = toNum(window.currentRootId);
      const pid = toNum(lastLoadedData?.parent_id ?? lastLoadedData?.upline_id);
      if (cur && pid && pid > 0) parentCache.set(cur, pid);

      drawTree(lastLoadedData, true, (keepT && keepT.k) ? keepT : null);
    }catch(e){
      if (e.name !== 'AbortError') toastr?.error?.('Gagal memuat tree');
    }finally{
      isLoading = false;
    }
  }
  window.loadTree = loadTree;

  /* =============== SKIN =============== */
  function appendGradients(sel){
    const defs = sel.append('defs');
    const grad = (id, from, to)=>{
      const g = defs.append('linearGradient').attr('id',id)
        .attr('x1','0%').attr('y1','0%').attr('x2','100%').attr('y2','100%');
      g.append('stop').attr('offset','0%').attr('stop-color',from);
      g.append('stop').attr('offset','100%').attr('stop-color',to);
    };
    grad('goldGradient','#FFD700','#000');
    grad('greenGradient','#00c853','#003300');
    grad('blueGradient','#66ccff','#003366');
    grad('grayGradient','#9aa5b1','#3c4a57');
  }
  function getNodeColor(d){
    if (d.isAddButton) return 'url(#blueGradient)';
    return isActiveOnSelected(d) ? 'url(#greenGradient)' : 'url(#grayGradient)';
  }

  /* =============== DRAW =============== */
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

    // reset container → render ulang
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
      if (d.depth >= 2) d.children = null; // tampil 3 level
    });

    const getStarCount = () => {
      const n = parseInt(String(window.currentBagan).trim(), 10);
      return Math.max(0, Math.min(5, Number.isFinite(n) ? (n - 1) : 0));
    };

    const layout = d3.tree().nodeSize([hGap+NODE_W, vGap+NODE_H]);
    layout(root);

    // edges
    g.append('g').attr('fill','none').attr('stroke','#cbd5e1').attr('stroke-opacity',0.65).attr('stroke-width',1.2)
      .selectAll('path').data(root.links()).join('path')
      .attr('d', d3.linkVertical().x(d=>d.x).y(d=>d.y));

    // nodes
    const node = g.append('g').selectAll('g').data(root.descendants()).join('g')
      .attr('transform', d=>`translate(${d.x},${d.y})`)
      .on('mouseover', showTooltip).on('mouseout', hideTooltip);

    node.append('rect')
      .attr('x',-NODE_W/2).attr('y',-NODE_H/2)
      .attr('width',NODE_W).attr('height',NODE_H).attr('rx',RADIUS)
      .attr('fill', d=>getNodeColor(d.data));

    node.filter(d=>!d.data.isAddButton).append('image')
    .attr("xlink:href", d => d.data.photo 
      ? `/${d.data.photo}` 
      : `/assets/img/profile.jpg`)
      .attr('x',-AVA/2).attr('y',-NODE_H/2+6)
      .attr('width',AVA).attr('height',AVA)
      .attr('clip-path', `circle(${AVA/2}px at ${AVA/2}px ${AVA/2}px)`);

    node.filter(d=>!d.data.isAddButton).append('text')
      .attr('y', 10).attr('text-anchor','middle')
      .text(()=> '⭐️'.repeat(getStarCount()))
      .style('font-size', Math.max(9, Math.floor(NODE_W*0.11)) + 'px')
      .attr('fill','gold');

    node.filter(d=>!d.data.isAddButton).append('text')
      .attr('y', NODE_H/2 - 8).attr('text-anchor','middle')
      .text(d=> shortName(d.data.name || d.data.username || '', NODE_W<=80 ? 7 : 9))
      .attr('fill', d=> isActiveOnSelected(d.data) ? '#fff' : '#cbd5e1')
      .style('font-size', Math.max(10, Math.floor(NODE_W*0.12)) + 'px');

    // Tombol + Tambah (buka modal: default tab Clone)
    const addNodes = node.filter(d=>d.data.isAddButton);
    addNodes.style('cursor','pointer').on('click', (e,d)=>{
      e.stopPropagation();
      const pos = d.data.position || d.parent?.data?.position || 'left';
      const up  = d.data.parent_id ?? d.parent?.data?.id ?? null;
      if (!up){ toastr?.warning?.('Upline tidak terdeteksi.'); return; }
      openAddModalUnified({ parentId: up, position: pos, mode: 'clone' });
    });
    addNodes.append('text').attr('y',2).attr('text-anchor','middle')
      .text('+ Tambah').style('font-size', Math.max(10, Math.floor(NODE_W*0.12)) + 'px').attr('fill','#fff');
  }

  /* =============== TOOLTIP =============== */
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

  /* =============== NAV =============== */
  function realChild(side){
    const kids = (lastLoadedData?.children || [])
      .filter(c => c?.position===side && !c.isAddButton && Number.isFinite(c.id));
    return kids.length ? kids[0] : null;
  }
  function goDown(toId){
    const to = toNum(toId);
    if (!to || to <= 0) return;
    if (Number.isFinite(window.currentRootId) && window.currentRootId !== to){
      upStack.push(window.currentRootId);
    }
    window.currentRootId = to;
    loadTree();
  }
  window.navUp = async function(){
    if (upStack.length){
      window.currentRootId = upStack.pop();
      loadTree();
      return;
    }
    const cur = toNum(window.currentRootId);
    if (!cur || cur <= 0){ toastr?.info?.('Root tidak valid.'); return; }
    const pid = await resolveParentId(cur);
    if (!pid || pid <= 0){ toastr?.info?.('Tidak ada upline.'); return; }
    if (pid === cur){ toastr?.info?.('Sudah di upline yang sama.'); return; }
    window.currentRootId = pid;
    loadTree();
  };
  window.navLeft  = ()=>{ const L=realChild('left');  if(!L){toastr?.info?.('Tidak ada anak kiri.');return;}  goDown(L.id); };
  window.navRight = ()=>{ const R=realChild('right'); if(!R){toastr?.info?.('Tidak ada anak kanan.');return;} goDown(R.id); };
  window.navDown  = ()=>{
    const L=realChild('left'), R=realChild('right');
    const kids = [
      ...((L?.children||[]).filter(n=>!n.isAddButton && Number.isFinite(n.id))),
      ...((R?.children||[]).filter(n=>!n.isAddButton && Number.isFinite(n.id)))
    ];
    if(!kids.length){ toastr?.info?.('Tidak ada cucu.'); return; }
    const mid = kids[Math.floor(kids.length/2)] || kids[0];
    goDown(mid.id);
  };

  /* =============== MENU BAGAN =============== */
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

  /* =============== TAMBAH (DAFTAR PENDING) =============== */
  function ensureAddUserModal(){
    return { modal: document.getElementById('addUserModal'), list: document.getElementById('userList') };
  }
  window.submitAddUser = function(userId, position, uplineId){
   
    fetch(`/tree/${userId}`, {
      method:'PUT',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ user_id:userId, position, upline_id:uplineId })
    })
    .then(r=>r.json())
    .then(() => {
      toastr?.success?.('User berhasil dipasang');
      loadTree();
      bootstrap.Modal.getInstance(document.getElementById('addUserModal'))?.hide();
    })
    .catch(()=> toastr?.error?.('Gagal memasang user'));
  };

  /* =============== CLONE HELPERS =============== */
  async function refreshPendingBadge() {
  const badge = document.getElementById('pendingCountBadge');
  if (!badge) return;
  try {
    const r = await fetch(`/tree/available-users/${AUTH_USER_ID}/count`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    });
    const { count } = await r.json();
    if (Number(count) > 0) {
      badge.textContent = count;
      badge.classList.remove('d-none');
    } else {
      badge.textContent = '0';
      badge.classList.add('d-none');
    }
  } catch {
    // kalau gagal, sembunyikan saja biar nggak mengganggu
    badge.classList.add('d-none');
  }
}

  async function loadUnusedPinsIntoSelect() {
    const sel = document.getElementById('pinCodes');
    if (!sel) return;
    sel.innerHTML = '<option disabled>Memuat PIN...</option>';
    try {
      const r = await fetch(`{{ route('pins.unused') }}`, { headers: { 'Accept': 'application/json' }});
      if (!r.ok) throw 0;
      const { pins } = await r.json();
      sel.innerHTML = '';
      if (!pins || !pins.length) {
        sel.innerHTML = '<option disabled>Tidak ada PIN tersedia</option>';
      } else {
        pins.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.code;
          opt.textContent = p.code;
          sel.appendChild(opt);
        });
      }
    } catch {
      sel.innerHTML = '<option disabled>Gagal memuat PIN</option>';
    }
  }
  async function previewCloneCandidates() {
  const parentId = document.getElementById('cloneParentId').value; // ini angka murni
  const useLogin = document.getElementById('cloneUseLogin').value === '1';
  const count = Array.from(document.getElementById('pinCodes').selectedOptions).length;

  if (!count) {
    toastr?.info?.('Pilih PIN dulu'); 
    return;
  }

  const params = new URLSearchParams({ count: String(count) });
  if (!useLogin) params.set('base_user_id', String(parentId)); // <-- tanpa titik dua

  const url = `{{ route('tree.clone.preview') }}?${params.toString()}`;

  const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
  const data = await res.json();

  if (!res.ok) {
    toastr?.error?.(data?.message || 'Gagal memuat preview');
    return;
  }

  const box = document.getElementById('clonePreview');
  box.innerHTML = (data.candidates || [])
    .map((c, i) => `${i+1}. <code>${c.username}</code> / <code>${c.sponsor_code ?? c.referral_code ?? '-'}</code>`)
    .join('<br>');
}

  async function submitCloneForm(e) {
    e.preventDefault();
    const form = document.getElementById('cloneForm');
    const fd   = new FormData(form);

    const res = await fetch(`{{ route('tree.clone.store') }}`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
      body: fd
    });

    if (!res.ok) {
      const t = await res.text();
      console.error('Clone failed:', t);
      toastr?.error?.('Gagal clone: ' + t);
      return;
    }
    const { ok, message } = await res.json();
    if (ok) {
      toastr?.success?.(message || 'Berhasil clone & pasang');
      loadTree();
      bootstrap.Modal.getInstance(document.getElementById('addUserModal'))?.hide();
    }
  }

  // open modal terintegrasi
function openAddModalUnified({ parentId, position = 'left', mode = 'clone' }) {
  const modalEl = document.getElementById('addUserModal');
  if (!modalEl) return;

  // isi form clone
  document.getElementById('cloneParentId').value = parentId;
  document.getElementById('clonePosition').value = position;
  document.getElementById('clonePositionLabel').textContent = 
  position === 'right' ? 'Right' : 'Left';
  
  document.getElementById('cloneBagan').value = String(window.currentBagan || 1);
  document.getElementById('cloneUseLogin').value = '1';
  document.getElementById('clonePreview').innerHTML = '';

  // muat PIN untuk tab Clone
  loadUnusedPinsIntoSelect();

  // siapkan loader pending
  const loadPendingList = () => {
    const sponsorId = AUTH_USER_ID;
    const list = document.getElementById('userList');
    list.innerHTML = 'Memuat...';
    fetch(`/tree/available-users/${sponsorId}`, {
      headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
      credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(users => {
      list.innerHTML = '';
      if (!Array.isArray(users) || !users.length) {
        list.innerHTML = '<div class="text-center text-muted">Tidak ada user pending.</div>';
        return;
      }
      users.forEach(u => {
        const row = document.createElement('div');
        row.className = 'list-group-item d-flex justify-content-between align-items-center';
        row.innerHTML = `<div><strong>${u.username}</strong><br><small>${u.name ?? ''}</small></div>
                         <button class="btn btn-sm btn-primary">Pasang</button>`;
        row.querySelector('button').onclick = () => window.submitAddUser(u.id, position, parentId);
        list.appendChild(row);
      });
    })
    .catch(() => list.innerHTML = '<div class="text-center text-danger">Gagal memuat data pending.</div>');
  };

  // pastikan switching tab pakai Bootstrap API
  const tabCloneBtn  = document.getElementById('tabCloneBtn');
  const tabTambahBtn = document.getElementById('tabTambahBtn');

  // tiap kali tab Tambah AKTIF, load pending
  tabTambahBtn?.addEventListener('shown.bs.tab', () => {
    loadPendingList();
  }, { once: false });

  // buka modal, lalu setelah MODAL tampil baru pilih tab sesuai mode
  const modal = new bootstrap.Modal(document.getElementById('addUserModal'));
document.getElementById('addUserModal')
  .addEventListener('shown.bs.modal', () => { refreshPendingBadge(); }, { once: true });
  modalEl.addEventListener('shown.bs.modal', () => {
    if (mode === 'tambah') {
      bootstrap.Tab.getOrCreateInstance(tabTambahBtn).show();
      
      // shown.bs.tab akan memanggil loadPendingList()
    } else {
      bootstrap.Tab.getOrCreateInstance(tabCloneBtn).show();
    }
  }, { once: true });

  modal.show();
}

  window.openAddModalUnified = openAddModalUnified; // expose (optional)

  /* =============== BIND EVENTS =============== */
  document.addEventListener('click', (ev) => {
    if (ev.target?.id === 'btnPreview') previewCloneCandidates();
  });
  document.addEventListener('submit', (ev) => {
    if (ev.target?.id === 'cloneForm') submitCloneForm(ev);
  });

  /* =============== BOOT =============== */
  document.addEventListener('DOMContentLoaded', ()=>{
    bindBaganMenu();
    loadTree();
  });

})();
</script>
@endpush
