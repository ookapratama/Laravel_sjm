@extends('layouts.app')

@section('content')
<div class="page-inner relative">
  <div class="absolute right-5 top-3 z-10 flex gap-2">
    <button onclick="zoomIn()" class="px-3 py-1 bg-blue-600 text-white rounded btn-primary">＋</button>
    <button onclick="zoomOut()" class="px-3 py-1 bg-blue-600 text-white rounded btn-primary">－</button>
    <button onclick="resetZoom()" class="px-3 py-1  text-white rounded btn-black">⟳</button>
    <button id="rotateTreeBtn" class="px-3 py-1 bg-gray-700 text-white rounded btn-black"><i class="fas fa-sync-alt"></i></button>
    <button onclick="prevLevel()" class="px-3 py-1 bg-yellow-600 text-white rounded btn-warning">Prev</button>
    <button onclick="nextLevel()" class="px-3 py-1 bg-green-600 text-white rounded btn-success">Next</button>
    <label for="hSpacing" class="text-sm ">Spacing X:</label>
    <input type="range" id="hSpacing" min="60" max="300" step="10" />
    <label for="vSpacing" class="text-sm ">Spacing Y:</label>
    <input type="range" id="vSpacing" min="60" max="300" step="10" />
  </div>
  <div id="tree-scroll" class="overflow-auto w-full h-[85vh] border">
    <div id="tree-container"></div>
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

<style>
  .star-glow {
  animation: glow 1.5s ease-in-out infinite alternate;
  fill: gold;
}

@keyframes glow {
  0% {
    opacity: 0.6;
    transform: scale(1);
  }
  100% {
    opacity: 1;
    transform: scale(1.2);
  }
}
.star-glow {
  animation: glow 1.5s ease-in-out infinite alternate;
}
@keyframes glow {
  0% { opacity: 0.7; transform: scale(1); }
  100% { opacity: 1; transform: scale(1.15); }
}
#tree-tooltip {
  position: absolute;
  background: white;
  border: 1px solid #ccc;
  padding: 8px 12px;
  border-radius: 4px;
  font-size: 13px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  pointer-events: none;
  z-index: 1000;
}
</style>
@endsection

@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
let rootId = {{ $root->id }};
let currentRootId = rootId;
let levelLimit = 3;
let isVertical = true;
let lastLoadedData = null;
let svg, g;
let currentZoomTransform = d3.zoomIdentity;
const AUTH_USER_ID = {{ auth()->user()->id }};
const width = 1600;
const height = 900;
let horizontalSpacing = parseInt(localStorage.getItem('hSpacing')) || 160;
let verticalSpacing = parseInt(localStorage.getItem('vSpacing')) || 100;

document.getElementById('nodeSpacing')?.addEventListener('input', (e) => {
  const val = parseInt(e.target.value);
  horizontalSpacing = val;
  verticalSpacing = val;
  drawTree(lastLoadedData, true, currentZoomTransform);
});
document.addEventListener('DOMContentLoaded', () => {
  setupControls();
  loadTree();
});

function getNodeColor(data) {
  console.log(data.name, data.pairing_count)
  if (data.isAddButton) return 'url(#blueGradient)';
  if ((data.pairing_count ?? 0) >= 5) return 'url(#goldGradient)';
  return 'url(#greenGradient)';

}

function drawProgressBar(node) {
  node.filter(d => !d.data.isAddButton).append("rect")
    .attr("x", -20)
    .attr("y", 30)
    .attr("width", 40)
    .attr("height", 5)
    .attr("fill", "#ccc")
    .attr("rx", 2);

  node.filter(d => !d.data.isAddButton).append("rect")
    .attr("x", -20)
    .attr("y", 30)
    .attr("width", d => {
      const level = d.data.pairing_count ?? 0;
      return Math.min((level / 5) * 40, 40);
    })
    .attr("height", 5)
    .attr("fill", "limegreen")
    .attr("rx", 2);
}


// Gradient defs
function appendGradients(svg) {
  const defs = svg.append("defs");

defs.append("linearGradient")
  .attr("id", "goldGradient")
  .attr("x1", "0%").attr("y1", "0%")
  .attr("x2", "100%").attr("y2", "100%")
  .selectAll("stop")
  .data([
    { offset: "0%", color: "#FFD700" },  // gold
    { offset: "100%", color: "#000000" } // black
  ])
  .enter()
  .append("stop")
  .attr("offset", d => d.offset)
  .attr("stop-color", d => d.color);

defs.append("linearGradient")
  .attr("id", "greenGradient")
  .attr("x1", "0%").attr("y1", "0%")
  .attr("x2", "100%").attr("y2", "100%")
  .selectAll("stop")
  .data([
    { offset: "0%", color: "#00ff00" },   // Hijau terang
    { offset: "100%", color: "#000000" }  // Hitam
  ])
  .enter()
  .append("stop")
  .attr("offset", d => d.offset)
  .attr("stop-color", d => d.color);


defs.append("linearGradient")
  .attr("id", "blueGradient")
  .attr("x1", "0%").attr("y1", "0%")
  .attr("x2", "100%").attr("y2", "100%")
  .selectAll("stop")
  .data([
    { offset: "0%", color: "#66ccff" },
    { offset: "100%", color: "#003366" }
  ])
  .enter()
  .append("stop")
  .attr("offset", d => d.offset)
  .attr("stop-color", d => d.color);
}



const zoomBehavior = d3.zoom().on("zoom", (event) => {
  currentZoomTransform = event.transform;
  g.attr("transform", currentZoomTransform);
});

function setupControls() {
  // 🔄 ROTASI POHON
  document.getElementById('rotateTreeBtn')?.addEventListener('click', () => {
    isVertical = !isVertical;
    loadTree();
    drawTree(lastLoadedData, true, currentZoomTransform);
  });

  // 🔍 ZOOM CONTROL
  window.zoomIn = () => zoomSmooth(1.2);
  window.zoomOut = () => zoomSmooth(0.8);
  window.resetZoom = () => {
    currentZoomTransform = d3.zoomIdentity;
    levelLimit = 3;
    loadTree();
  };

  // ⬆⬇ LEVEL CONTROL
  window.nextLevel = () => {
    levelLimit += 3;
    loadTree();
  };
  window.prevLevel = () => {
    levelLimit = Math.max(1, levelLimit - 3);
    loadTree();
  };

  // ⚙️ SLIDER SPACING
  const h = document.getElementById('hSpacing');
  const v = document.getElementById('vSpacing');
  const all = document.getElementById('nodeSpacing');

  const hVal = parseInt(localStorage.getItem('hSpacing') || 100);
  const vVal = parseInt(localStorage.getItem('vSpacing') || 100);
  horizontalSpacing = hVal;
  verticalSpacing = vVal;

  if (h) {
    h.value = hVal;
    h.addEventListener('input', (e) => {
      horizontalSpacing = parseInt(e.target.value);
      localStorage.setItem('hSpacing', horizontalSpacing);
      drawTree(lastLoadedData, true, currentZoomTransform);
    });
  }

  if (v) {
    v.value = vVal;
    v.addEventListener('input', (e) => {
      verticalSpacing = parseInt(e.target.value);
      localStorage.setItem('vSpacing', verticalSpacing);
      drawTree(lastLoadedData, true, currentZoomTransform);
    });
  }

  if (all) {
    all.value = Math.min(hVal, vVal);
    all.addEventListener('input', (e) => {
      const val = parseInt(e.target.value);
      horizontalSpacing = val;
      verticalSpacing = val;
      localStorage.setItem('hSpacing', val);
      localStorage.setItem('vSpacing', val);
      drawTree(lastLoadedData, true, currentZoomTransform);
    });
  }
}


function zoomSmooth(scaleFactor) {
  const newTransform = currentZoomTransform.scale(scaleFactor);
  svg.transition().duration(400).call(zoomBehavior.transform, newTransform);
  currentZoomTransform = newTransform;
}

function loadTree() {
  // Pastikan svg sudah ada
  const svgElement = document.querySelector("#tree-container svg");
  const currentTransform = svgElement ? d3.zoomTransform(svgElement) : null;

  fetch(`/tree/load/${currentRootId}?limit=${levelLimit}`)
    .then(res => res.json())
    .then(data => {
      lastLoadedData = data;
      drawTree(data, true, currentTransform);
    });
}



function drawTree(data, preserveZoom = false, zoomTransformOverride = null) {
  if (!data) return;
  d3.select("#tree-container").html("");

  const root = d3.hierarchy(data);
  const treeLayout = d3.tree().nodeSize(isVertical ? [horizontalSpacing, verticalSpacing] : [verticalSpacing, horizontalSpacing]);
  root.eachBefore(d => {
  if (d.children) {
    d.children.sort((a, b) => {
      if (a.data.position === 'left') return -1;
      if (a.data.position === 'right') return 1;
      return 0;
    });
  }
});
  treeLayout(root);

root.each((d) => {
  d.data.pairing_point = d.depth; // Menyisipkan level ke dalam tiap node
});
  svg = d3.create("svg")
    .attr("width", width)
    .attr("height", height);

  appendGradients(svg);
  g = svg.append("g");

  if (preserveZoom && zoomTransformOverride) {
    currentZoomTransform = zoomTransformOverride;
  } else {
    const centerX = width / 2;
    const centerY = height / 2;
    currentZoomTransform = isVertical
      ? d3.zoomIdentity.translate(centerX, 50)
      : d3.zoomIdentity.translate(50, centerY);
  }

  svg.call(zoomBehavior).call(zoomBehavior.transform, currentZoomTransform);

  g.append("g")
    .attr("fill", "none")
    .attr("stroke", "#ccc")
    .attr("stroke-opacity", 0.6)
    .attr("stroke-width", 1.5)
    .selectAll("path")
    .data(root.links())
    .join("path")
    .attr("d", isVertical
      ? d3.linkVertical().x(d => d.x).y(d => d.y)
      : d3.linkHorizontal().x(d => d.y).y(d => -d.x));

  const node = g.append("g")
    .selectAll("g")
    .data(root.descendants())
    .join("g")
    .attr("transform", d => isVertical ? `translate(${d.x},${d.y})` : `translate(${d.y},${-d.x})`)
    .on("mouseover", showTooltip)
    .on("mouseout", hideTooltip);

  node.append("rect")
    .attr("x", -30).attr("y", -40)
    .attr("width", 60).attr("height", 80)
    .attr("rx", 8)
    .attr("fill", d => getNodeColor(d.data));

  node.filter(d => !d.data.isAddButton).append("image")
    .attr("xlink:href", "/assets/img/profile.jpg")
    .attr("x", -16).attr("y", -35)
    .attr("width", 32).attr("height", 32)
    .attr("clip-path", "circle(16px at center)");
node.filter(d => !d.data.isAddButton).append("text")
  .attr("y", 10) // bawah nama
  .attr("text-anchor", "middle")
  .text(d => {
    const count = [
      d.data.is_active_bagan_1,
      d.data.is_active_bagan_2,
      d.data.is_active_bagan_3,
      d.data.is_active_bagan_4,
      d.data.is_active_bagan_5
    ].filter(v => v == 1).length;

    return '⭐️'.repeat(count);
  })
  .style("font-size", "10px")
  .attr("fill", "gold")
  .attr("class", "star-glow");
  node.filter(d => !d.data.isAddButton).append("text")
    .attr("y", 25).attr("text-anchor", "middle")
    .text(d => d.data.name || '')
    .attr("fill", "#fff").style("font-size", "12px");
// ⭐️ Tambahkan di sini


  // drawProgressBar(node);
// Filter hanya node yang merupakan tombol tambah
const addNodes = node.filter(d => d.data.isAddButton);

// Jadikan seluruh node klikable
addNodes
  .style("cursor", "pointer")
  .on("click", (e, d) => {
    hideTooltip();
    window.openAddModal(AUTH_USER_ID, d.data.position, d.data.parent_id);
  });

// Tambahkan teks "+ Tambah" di tengah node
addNodes.append("text")
  .attr("y", 0)
  .attr("text-anchor", "middle")
  .text("+ Tambah")
  .style("font-size", "10px")
  .attr("fill", "#fff");


  document.getElementById("tree-container").append(svg.node());
}

function showTooltip(event, d) {
  const tooltip = document.getElementById('tree-tooltip');
  if (!tooltip || d.data.isAddButton) return;
  tooltip.innerHTML = `
    <strong>${d.data.name}</strong><br>
    Status: ${d.data.status}<br>
    Posisi: ${d.data.position}<br>
    Pairing: ${d.data.pairing_count ?? '-'}<br>
    Anak Kiri: ${d.data.left_count ?? 0}<br>
    Anak Kanan: ${d.data.right_count ?? 0}
  `;

  const container = document.getElementById('tree-scroll');
  const rect = container.getBoundingClientRect();
  tooltip.style.left = `${event.clientX - rect.left + 10}px`;
  tooltip.style.top = `${event.clientY - rect.top + 10}px`;
  tooltip.classList.remove('hidden');
}


function hideTooltip() {
  document.getElementById('tree-tooltip')?.classList.add('hidden');
}

window.openAddModal = function (sponsorId, position, uplineId) {
  const modalEl = document.getElementById('addUserModal');
  const userList = document.getElementById('userList');
  if (!modalEl || !userList) return;

  userList.innerHTML = 'Memuat...';

  fetch(`/tree/available-users/${sponsorId}`)
    .then(res => {
      if (!res.ok) throw new Error('Gagal mengambil data.');
      return res.json();
    })
    .then(users => {
      userList.innerHTML = '';

      if (!Array.isArray(users) || users.length === 0) {
        userList.innerHTML = '<div class="text-center text-muted">Tidak ada user tersedia.</div>';
        return;
      }

      users.forEach(user => {
        const div = document.createElement('div');
        div.className = 'list-group-item d-flex justify-content-between align-items-center';
        div.innerHTML = `
          <div><strong>${user.username}</strong><br><small>${user.name}</small></div>
          <button class="btn btn-sm btn-primary">Pasang</button>
        `;
        div.querySelector('button').onclick = () => window.submitAddUser(user.id, position, uplineId);
        userList.appendChild(div);
      });
    })
    .catch(err => {
      console.error('❌ Gagal memuat data:', err);
      userList.innerHTML = '<div class="text-center text-danger">Gagal memuat data user.</div>';
    })
    .finally(() => {
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    });
}


window.submitAddUser = function(userId, position, uplineId) {
  fetch(`/tree/${userId}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ user_id: userId, position, upline_id: uplineId })
  })
  .then(res => res.json())
  .then(data => {
    updateNode(uplineId, position, data.id, data.name);
    bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
  })
  .catch(err => console.error('❌ Gagal memasang user:', err));
}
window.updateNode = function(parentId, position, id, name) {
  const stack = [lastLoadedData];
  while (stack.length > 0) {
    const node = stack.pop();
    if (node.id == parentId) {
      if (!node.children) node.children = [];

      // Hapus tombol "Tambah" sebelumnya
      node.children = node.children.filter(child => !(child.isAddButton && child.position === position));

      // Tambahkan node baru
      node.children.push({
        id,
        name,
        parent_id: parentId,
        position,
        isAddButton: false,
        level: (node.level ?? 0) + 1,
        children: []
      });
      break;
    }
    if (node.children) node.children.forEach(child => stack.push(child));
  }

  drawTree(lastLoadedData, true, currentZoomTransform);
 nextLevel();
  toastr.success(`User berhasil dipasang di posisi ${position}`);
}

</script>
@endpush
