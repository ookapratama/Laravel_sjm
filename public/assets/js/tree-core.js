(function () {
    "use strict";

    // ===== Styles: security & hint
    const securityStyles = `
<style>
.tree-nav.restricted{opacity:.5;pointer-events:none}
.node-restricted{opacity:.6;filter:grayscale(.5)}
.node-restricted .node-name{font-style:italic;color:#6c757d!important}
.access-denied-overlay{position:absolute;inset:0;background:rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;font-size:12px;color:#fff;pointer-events:none}
.member-navigation-hint{position:fixed;bottom:10px;right:10px;background:rgba(255,193,7,.9);color:#000;padding:8px 12px;border-radius:6px;font-size:12px;max-width:250px;z-index:50;box-shadow:0 2px 8px rgba(0,0,0,.2)}
.member-navigation-hint.hidden{display:none}
.member-navigation-hint .close-hint{float:right;margin-left:8px;cursor:pointer;font-weight:700}
</style>`;
    document.head.insertAdjacentHTML("beforeend", securityStyles);

    // ===== STATE (di-inject dari Blade -> window.*)
    const AUTH_USER_ID = window.AUTH_USER_ID;
    const AUTH_USER_ROLE = window.AUTH_USER_ROLE || "member";
    const AUTH_USER_UPLINE_ID = window.AUTH_USER_UPLINE_ID ?? null;

    // elevated roles: akses penuh
    const ELEVATED_ROLES = new Set(["super-admin", "admin", "finance"]);
    const isElevated = () => ELEVATED_ROLES.has(String(AUTH_USER_ROLE));

    window.currentRootId = Number.isFinite(+window.CURRENT_ROOT_ID)
        ? +window.CURRENT_ROOT_ID
        : AUTH_USER_ID;
    window.currentBagan = Number(localStorage.getItem("selectedBagan") || 1);

    let lastLoadedData = null;
    let svgSel = null,
        g = null;
    let currentZoomTransform = d3.zoomIdentity;

    let isLoading = false;
    let pendingController = null;

    const upStack = []; // riwayat turun untuk tombol UP (member & elevated)
    const parentCache = new Map();

    // ===== UTIL
    const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
    const toNum = (v) => {
        const n = Number(String(v ?? "").trim());
        return Number.isFinite(n) ? n : null;
    };

    function canAccessNode(nodeId) {
        if (isElevated()) return true; // admin/super-admin/finance: bebas
        const currentUserId = AUTH_USER_ID;
        if (AUTH_USER_ROLE === "member") {
            if (nodeId === currentUserId) return true; // diri sendiri
            return true; // FE longgar; backend tetap final validator
        }
        return false;
    }

    function activeBagansFrom(d) {
        if (!d || typeof d !== "object") return [];
        if (Array.isArray(d.active_bagans)) return d.active_bagans.map(Number);
        return Object.keys(d)
            .filter((k) => k.startsWith("is_active_bagan_") && d[k] == 1)
            .map((k) => parseInt(k.replace("is_active_bagan_", ""), 10));
    }
    function isActiveOnSelected(d) {
        return activeBagansFrom(d).includes(Number(window.currentBagan));
    }
    function topSafeOffset(nodeH) {
        const upBtn = document.querySelector(".tree-nav.up button");
        const upH = upBtn ? upBtn.getBoundingClientRect().height : 36;
        return upH + 18 + nodeH / 2;
    }
    function shortName(s, max) {
        if (!s) return "";
        return s.length > max ? s.slice(0, max) + "…" : s;
    }

    function normalizeIds(node) {
        if (!node || typeof node !== "object") return node;
        node.id = toNum(node.id);
        node.parent_id = toNum(node.parent_id ?? node.upline_id);

        if (node.data && typeof node.data === "object") {
            node.data.id = toNum(node.data.id);
            node.data.parent_id = toNum(
                node.data.parent_id ?? node.data.upline_id,
            );
        }
        if (Array.isArray(node.children)) {
            node.children.forEach((c) => {
                if (!c || typeof c !== "object") return;
                c.id = toNum(c.id);
                c.parent_id = toNum(c.parent_id ?? c.upline_id);
                if (c.data && typeof c.data === "object") {
                    c.data.id = toNum(c.data.id);
                    c.data.parent_id = toNum(
                        c.data.parent_id ?? c.data.upline_id,
                    );
                }
                if (Array.isArray(c.children)) {
                    c.children.forEach((g) => {
                        if (!g || typeof g !== "object") return;
                        g.id = toNum(g.id);
                        g.parent_id = toNum(g.parent_id ?? g.upline_id);
                        if (g.data && typeof g.data === "object") {
                            g.data.id = toNum(g.data.id);
                            g.data.parent_id = toNum(
                                g.data.parent_id ?? g.data.upline_id,
                            );
                        }
                    });
                }
            });
        }
        return node;
    }

    async function fetchJSON(url, opts = {}) {
        try {
            if (pendingController) pendingController.abort();
            pendingController = new AbortController();
            const res = await fetch(url, {
                ...opts,
                signal: pendingController.signal,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    ...(opts.headers || {}),
                },
            });
            if (!res.ok)
                throw Object.assign(new Error("HTTP " + res.status), {
                    status: res.status,
                });
            return res.json();
        } catch (error) {
            if (error.status === 403) {
                handleAccessDenied(error);
                throw error;
            }
            throw error;
        }
    }
    async function fetchTEXT(url) {
        const res = await fetch(url, {
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        const text = await res.text();
        return { ok: res.ok, text, status: res.status };
    }

    function tryJSON(any) {
        if (any == null) return null;
        if (typeof any === "object") return any;
        try {
            return JSON.parse(any);
        } catch {
            return null;
        }
    }
    function pickParentId(payload) {
        const d = tryJSON(payload) ?? {};
        const arr = [
            d.parent_id,
            d.parentId,
            d.pid,
            d.parent,
            d?.data?.parent_id,
            d?.data?.parentId,
            d?.user?.upline_id,
            d?.user?.parent_id,
            d.upline_id,
            d.id,
        ];
        for (const c of arr) {
            const n = toNum(c);
            if (n && n > 0) return n;
        }
        const nested = toNum(d?.parent?.id ?? d?.parent?.parent_id);
        return nested && nested > 0 ? nested : null;
    }

    async function resolveParentId(currentId) {
        const cur = toNum(currentId);
        if (!cur || cur <= 0) return null;
        if (parentCache.has(cur)) return parentCache.get(cur);

        const local = [
            lastLoadedData?.parent_id,
            lastLoadedData?.upline_id,
            lastLoadedData?.data?.parent_id,
            lastLoadedData?.data?.upline_id,
        ]
            .map(toNum)
            .find((n) => n && n > 0);
        if (local) {
            parentCache.set(cur, local);
            return local;
        }

        try {
            const r = await fetchTEXT(`/tree/parent/${cur}`);
            if (r.ok) {
                const result = tryJSON(r.text);
                if (result && !result.error) {
                    const pid = pickParentId(r.text);
                    if (pid && pid > 0) {
                        parentCache.set(cur, pid);
                        return pid;
                    }
                } else if (result && result.error) {
                    throw { status: 403, message: result.message };
                }
            }
        } catch (error) {
            if (error.status === 403) throw error;
        }

        try {
            const r2 = await fetchTEXT(`/users/ajax/${cur}`);
            if (r2.ok) {
                const pid = pickParentId(r2.text);
                if (pid && pid > 0) {
                    parentCache.set(cur, pid);
                    return pid;
                }
            }
        } catch {}
        return null;
    }

    // ===== ZOOM
    const zoomBehavior = d3.zoom().on("zoom", (e) => {
        currentZoomTransform = e.transform;
        if (g) g.attr("transform", currentZoomTransform);
    });
    function bindZoomIfNeeded() {
        if (!svgSel || !svgSel.node()) return false;
        if (!svgSel.node().__zoom) svgSel.call(zoomBehavior);
        return true;
    }
    window.zoomIn = () => {
        if (!bindZoomIfNeeded()) return;
        const t = currentZoomTransform.scale(1.2);
        svgSel.transition().duration(300).call(zoomBehavior.transform, t);
        currentZoomTransform = t;
    };
    window.zoomOut = () => {
        if (!bindZoomIfNeeded()) return;
        const t = currentZoomTransform.scale(0.83);
        svgSel.transition().duration(300).call(zoomBehavior.transform, t);
        currentZoomTransform = t;
    };
    window.resetZoom = () => {
        currentZoomTransform = d3.zoomIdentity;
        loadTree();
    };

    // ===== LOAD TREE
    async function loadTree() {
        if (isLoading) return;
        isLoading = true;

        const prev = document.querySelector("#tree-container svg");
        const keepT = prev ? d3.zoomTransform(prev) : null;

        try {
            const data = await fetchJSON(
                `/tree/load/${window.currentRootId}?limit=3&user_role=${AUTH_USER_ROLE}`,
            );
            if (data.error || data.access_denied)
                throw new Error(data.message || "Akses ditolak");

            if (data && data.parent_id == null && data.upline_id != null)
                data.parent_id = data.upline_id;
            lastLoadedData = normalizeIds(data);

            const cur = toNum(window.currentRootId);
            const pid = toNum(
                lastLoadedData?.parent_id ?? lastLoadedData?.upline_id,
            );
            if (cur && pid && pid > 0) parentCache.set(cur, pid);

            drawTree(lastLoadedData, true, keepT && keepT.k ? keepT : null);
        } catch (e) {
            if (e.name !== "AbortError") {
                console.log("Load tree error:", e);
                if (e.status === 403) {
                    handleAccessDenied(e, "navigation");
                } else {
                    window.toastr?.error?.("Gagal memuat tree: " + e.message);
                    if (window.currentRootId !== AUTH_USER_ID) {
                        window.currentRootId = AUTH_USER_ID;
                        setTimeout(() => loadTree(), 1000);
                    }
                }
            }
        } finally {
            isLoading = false;
        }
    }
    window.loadTree = loadTree;

    // ===== SKIN
    function appendGradients(sel) {
        const defs = sel.append("defs");
        const grad = (id, from, to) => {
            const g = defs
                .append("linearGradient")
                .attr("id", id)
                .attr("x1", "0%")
                .attr("y1", "0%")
                .attr("x2", "100%")
                .attr("y2", "100%");
            g.append("stop").attr("offset", "0%").attr("stop-color", from);
            g.append("stop").attr("offset", "100%").attr("stop-color", to);
        };
        grad("goldGradient", "#FFD700", "#000");
        grad("greenGradient", "#00c853", "#003300");
        grad("blueGradient", "#66ccff", "#003366");
        grad("grayGradient", "#9aa5b1", "#3c4a57");
    }
    function getNodeColor(d) {
        if (d.isAddButton) return "url(#blueGradient)";
        return isActiveOnSelected(d)
            ? "url(#greenGradient)"
            : "url(#grayGradient)";
    }

    // ===== DRAW
    function drawTree(data, preserveZoom = false, zoomOverride = null) {
        if (!data) return;

        const board = document.getElementById("tree-scroll");
        const W = board?.clientWidth || window.innerWidth || 1200;
        const H = board?.clientHeight || window.innerHeight || 750;

        const maxCols = Math.pow(2, 3 - 1);
        const hGap = clamp(Math.floor(W / (maxCols + 4)), 16, 48);
        const vGap = clamp(Math.floor(H / (3 + 3)), 60, 110);
        const NODE_W = clamp(
            Math.floor((W - (maxCols + 1) * hGap) / maxCols),
            72,
            110,
        );
        const NODE_H = clamp(Math.floor(NODE_W * 0.9), 60, 100);
        const RADIUS = clamp(Math.floor(NODE_W * 0.16), 8, 14);
        const AVA = Math.floor(NODE_W * 0.38);

        const container = document.getElementById("tree-container");
        if (!container) return;
        container.innerHTML = "";
        const svgEl = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "svg",
        );
        svgEl.setAttribute("width", W);
        svgEl.setAttribute("height", H);
        container.appendChild(svgEl);

        svgSel = d3.select(svgEl);
        appendGradients(svgSel);
        g = svgSel.append("g");

        const centerX = W / 2,
            centerY = topSafeOffset(NODE_H);
        if (preserveZoom && zoomOverride) {
            currentZoomTransform = zoomOverride;
            if (currentZoomTransform.y < centerY)
                currentZoomTransform = d3.zoomIdentity
                    .translate(centerX, centerY)
                    .scale(zoomOverride.k);
        } else {
            currentZoomTransform = d3.zoomIdentity.translate(centerX, centerY);
        }
        svgSel
            .call(zoomBehavior)
            .call(zoomBehavior.transform, currentZoomTransform);

        const root = d3.hierarchy(data);
        root.eachBefore((d) => {
            if (d.children) {
                d.children.sort((a, b) => {
                    if (a.data.position === "left") return -1;
                    if (a.data.position === "right") return 1;
                    return 0;
                });
            }
            if (d.depth >= 2) d.children = null; // tampil 3 level
        });

        const MAX_STARS = 7;
        const getStarCount = () =>
            Math.max(
                0,
                Math.min(MAX_STARS, (Number(window.currentBagan) || 0) - 1),
            );

        const layout = d3.tree().nodeSize([hGap + NODE_W, vGap + NODE_H]);
        layout(root);

        // edges elbow
        g.append("g")
            .attr("fill", "none")
            .attr("stroke", "#cbd5e1")
            .attr("stroke-opacity", 0.65)
            .attr("stroke-width", 1.2)
            .selectAll("path")
            .data(root.links())
            .join("path")
            .attr(
                "d",
                (d) =>
                    `M${d.source.x},${d.source.y} V${(d.source.y + d.target.y) / 2} H${d.target.x} V${d.target.y}`,
            );

        const node = g
            .append("g")
            .selectAll("g")
            .data(root.descendants())
            .join("g")
            .attr("transform", (d) => `translate(${d.x},${d.y})`)
            .attr("class", (d) => (d.data.restricted ? "node-restricted" : ""))
            .on("mouseover", showTooltip)
            .on("mouseout", hideTooltip);

        node.append("rect")
            .attr("x", -NODE_W / 2)
            .attr("y", -NODE_H / 2)
            .attr("width", NODE_W)
            .attr("height", NODE_H)
            .attr("rx", RADIUS)
            .attr("fill", (d) =>
                d.data.restricted ? "url(#grayGradient)" : getNodeColor(d.data),
            );

        // Avatar
        node.filter((d) => !d.data.isAddButton && !d.data.restricted)
            .append("image")
            .attr("xlink:href", (d) =>
                d.data.photo ? `/${d.data.photo}` : `/assets/img/profile.webp`,
            )
            .attr("x", -AVA / 2)
            .attr("y", -NODE_H / 2 + 6)
            .attr("width", AVA)
            .attr("height", AVA)
            .attr(
                "clip-path",
                `circle(${AVA / 2}px at ${AVA / 2}px ${AVA / 2}px)`,
            );

        // Lock icon
        node.filter((d) => d.data.restricted)
            .append("text")
            .attr("y", -5)
            .attr("text-anchor", "middle")
            .text("🔒")
            .style("font-size", Math.max(14, Math.floor(NODE_W * 0.2)) + "px");

        // Stars
        node.filter((d) => !d.data.isAddButton && !d.data.restricted)
            .append("text")
            .attr("y", 10)
            .attr("text-anchor", "middle")
            .text(() => "⭐️".repeat(getStarCount()))
            .style("font-size", Math.max(9, Math.floor(NODE_W * 0.11)) + "px")
            .attr("fill", "gold");

        // Name
        node.filter((d) => !d.data.isAddButton && !d.data.restricted)
            .append("text")
            .attr("y", NODE_H / 2 - 8)
            .attr("text-anchor", "middle")
            .attr("class", "node-name")
            .text((d) =>
                shortName(
                    d.data.name || d.data.username || "",
                    NODE_W <= 80 ? 7 : 9,
                ),
            )
            .attr("fill", (d) =>
                isActiveOnSelected(d.data) ? "#fff" : "#cbd5e1",
            )
            .style("font-size", Math.max(10, Math.floor(NODE_W * 0.12)) + "px");

        // Restricted label
        node.filter((d) => d.data.restricted)
            .append("text")
            .attr("y", NODE_H / 2 - 8)
            .attr("text-anchor", "middle")
            .attr("class", "node-name")
            .text("Akses Terbatas")
            .attr("fill", "#6c757d")
            .style("font-size", Math.max(9, Math.floor(NODE_W * 0.1)) + "px")
            .style("font-style", "italic");

        // + Tambah
        const addNodes = node.filter(
            (d) => d.data.isAddButton && !d.data.restricted,
        );
        addNodes.style("cursor", "pointer").on("click", (e, d) => {
            e.stopPropagation();
            const pos = d.data.position || d.parent?.data?.position || "left";
            const up = d.data.parent_id ?? d.parent?.data?.id ?? null;
            if (!up) {
                window.toastr?.warning?.("Upline tidak terdeteksi.");
                return;
            }
            if (!canAccessNode(up)) {
                window.toastr?.error?.(
                    "Anda tidak memiliki akses untuk menambah member di posisi ini.",
                );
                return;
            }
            window.openAddModalUnified?.({
                parentId: up,
                position: pos,
                mode: "clone",
            });
        });
        addNodes
            .append("text")
            .attr("y", 2)
            .attr("text-anchor", "middle")
            .text("+ Tambah")
            .style("font-size", Math.max(10, Math.floor(NODE_W * 0.12)) + "px")
            .attr("fill", "#fff");

        updateNavigationUI();
        showMemberNavigationHint();
    }

    function drawTreeWithSecurity(
        data,
        preserveZoom = false,
        zoomOverride = null,
    ) {
        drawTree(data, preserveZoom, zoomOverride);
        updateNavigationUI();
    }
    window.drawTree = drawTreeWithSecurity;

    // ===== TOOLTIP
    function showTooltip(event, d) {
        const el = document.getElementById("tree-tooltip");
        if (!el || d.data.isAddButton) return;

        let content = "";
        if (d.data.restricted) {
            content = `
        <strong>Akses Terbatas</strong><br>
        <small>Anda tidak memiliki izin<br>untuk melihat detail member ini</small>`;
        } else {
            const aktif = isActiveOnSelected(d.data) ? "Ya" : "Tidak";
            content = `
        <strong>${d.data.name}</strong><br>
        Bagan P${window.currentBagan}: <b>${aktif}</b><br>
        Status: ${d.data.status}<br>
        Pairing: ${d.data.pairing_count ?? "-"}<br>
        Kiri: ${d.data.left_count ?? 0} • Kanan: ${d.data.right_count ?? 0}`;
            if (AUTH_USER_ROLE === "member") {
                if (d.data.id === AUTH_USER_ID) {
                    content +=
                        '<br><small><i class="fas fa-user"></i> Ini adalah Anda</small>';
                } else if (d.data.id === AUTH_USER_UPLINE_ID) {
                    content +=
                        '<br><small><i class="fas fa-level-up-alt"></i> Upline Anda</small>';
                } else {
                    content +=
                        '<br><small><i class="fas fa-level-down-alt"></i> Downline Anda</small>';
                }
            }
        }

        el.innerHTML = content;
        const box = document
            .getElementById("tree-scroll")
            ?.getBoundingClientRect() || { left: 0, top: 0 };
        el.style.left = `${event.clientX - box.left + 10}px`;
        el.style.top = `${event.clientY - box.top + 10}px`;
        el.classList.remove("hidden");
    }
    function hideTooltip() {
        document.getElementById("tree-tooltip")?.classList.add("hidden");
    }

    function handleAccessDenied(response, context = "") {
        if (response.status === 403) {
            let message = "Akses ditolak.";
            if (isElevated()) {
                message = "Akses ditolak oleh server untuk area ini.";
            } else {
                message =
                    context === "navigation"
                        ? "Anda telah mencapai batas area yang dapat diakses. Area di atas mungkin milik manajemen perusahaan."
                        : "Anda hanya dapat melihat data dalam jaringan MLM Anda (upline member dan downline).";
            }
            window.toastr?.error?.(message);

            // untuk member, fallback ke node aman (diri sendiri)
            if (!isElevated() && window.currentRootId !== AUTH_USER_ID) {
                setTimeout(() => {
                    window.currentRootId = AUTH_USER_ID;
                    upStack.length = 0; // reset jejak agar tidak membingungkan
                    loadTree();
                }, 1000);
            }
            return true;
        }
        return false;
    }

    // ===== NAV
    function realChild(side) {
        const kids = (lastLoadedData?.children || []).filter(
            (c) =>
                c?.position === side && !c.isAddButton && Number.isFinite(c.id),
        );
        return kids.length ? kids[0] : null;
    }

    function goDownSecure(toId) {
        const to = toNum(toId);
        if (!to || to <= 0) return;
        if (!canAccessNode(to)) {
            window.toastr?.error?.(
                "Anda tidak memiliki akses untuk melihat member tersebut.",
            );
            return;
        }
        if (
            Number.isFinite(window.currentRootId) &&
            window.currentRootId !== to
        ) {
            upStack.push(window.currentRootId); // simpan jejak supaya bisa Up kembali
        }
        window.currentRootId = to;
        loadTree();
    }

    function updateNavigationUI() {
        const upButton = document.querySelector(".tree-nav.up button");
        if (!upButton) return;

        if (isElevated()) {
            upButton.parentElement.classList.remove("restricted");
            upButton.setAttribute("title", "Naik ke upline");
        } else {
            // Member: Up aktif jika ada jejak, atau jika bukan di root (boleh balik ke root)
            if (upStack.length > 0 || window.currentRootId !== AUTH_USER_ID) {
                upButton.parentElement.classList.remove("restricted");
                upButton.setAttribute(
                    "title",
                    upStack.length > 0
                        ? "Kembali ke node sebelumnya"
                        : "Kembali ke root Anda",
                );
            } else {
                upButton.parentElement.classList.add("restricted");
                upButton.setAttribute(
                    "title",
                    "Anda sudah di root jaringan Anda",
                );
            }
        }
    }

    function showMemberNavigationHint() {
        if (
            AUTH_USER_ROLE === "member" &&
            !localStorage.getItem("member-hint-dismissed")
        ) {
            if (!document.querySelector(".member-navigation-hint")) {
                const hint = document.createElement("div");
                hint.className = "member-navigation-hint";
                hint.innerHTML = `
          <span class="close-hint" onclick="dismissMemberHint()">&times;</span>
          <strong>Info Navigasi Member:</strong><br>
          Anda dapat melihat:<br>
          • Data diri Anda<br>
          • Upline member dalam jaringan<br>
          • Semua downline Anda<br>`;
                document.body.appendChild(hint);
                setTimeout(() => {
                    if (hint.parentElement) hint.classList.add("hidden");
                }, 12000);
            }
        }
    }
    window.dismissMemberHint = function () {
        const hint = document.querySelector(".member-navigation-hint");
        if (hint) {
            hint.classList.add("hidden");
            localStorage.setItem("member-hint-dismissed", "true");
        }
    };

    // ==== NAV UP: member boleh naik via jejak (kembali), & maksimal kembali ke root (AUTH_USER_ID).
    // Elevated boleh bebas (gunakan resolveParentId jika tak ada jejak).
    window.navUp = async function () {
        if (!isElevated()) {
            // MEMBER: gunakan jejak jika ada
            if (upStack.length > 0) {
                window.currentRootId = upStack.pop();
                await loadTree();
                return;
            }
            // jika sedang bukan di root (misal masuk via link), kembalikan ke root miliknya
            if (window.currentRootId !== AUTH_USER_ID) {
                window.currentRootId = AUTH_USER_ID;
                await loadTree();
                return;
            }
            window.toastr?.info?.("Anda sudah di root jaringan Anda.");
            return;
        }

        // ELEVATED:
        if (upStack.length) {
            const targetId = upStack[upStack.length - 1];
            window.currentRootId = upStack.pop();
            try {
                await loadTree();
            } catch (error) {
                if (error.status === 403) {
                    window.toastr?.error?.("Akses ditolak untuk level ini.");
                    upStack.push(targetId);
                    window.currentRootId = targetId;
                }
            }
            return;
        }

        const cur = toNum(window.currentRootId);
        if (!cur || cur <= 0) {
            window.toastr?.info?.("Root tidak valid.");
            return;
        }
        try {
            const pid = await resolveParentId(cur);
            if (!pid || pid <= 0) {
                window.toastr?.info?.("Sudah di level teratas.");
                return;
            }
            if (pid === cur) {
                window.toastr?.info?.("Sudah di upline yang sama.");
                return;
            }
            window.currentRootId = pid;
            await loadTree();
        } catch (error) {
            if (error.status === 403) {
                window.toastr?.error?.("Akses ditolak untuk level ini.");
            } else {
                window.toastr?.error?.(
                    "Gagal navigasi ke atas: " +
                        (error.message || "Kesalahan sistem"),
                );
            }
        }
    };

    window.navLeft = () => {
        const L = realChild("left");
        if (!L) {
            window.toastr?.info?.("Tidak ada anak kiri.");
            return;
        }
        goDownSecure(L.id);
    };
    window.navRight = () => {
        const R = realChild("right");
        if (!R) {
            window.toastr?.info?.("Tidak ada anak kanan.");
            return;
        }
        goDownSecure(R.id);
    };
    window.navDown = () => {
        const L = realChild("left"),
            R = realChild("right");
        const kids = [
            ...(L?.children || []).filter(
                (n) => !n.isAddButton && Number.isFinite(n.id),
            ),
            ...(R?.children || []).filter(
                (n) => !n.isAddButton && Number.isFinite(n.id),
            ),
        ];
        if (!kids.length) {
            window.toastr?.info?.("Tidak ada cucu.");
            return;
        }
        const mid = kids[Math.floor(kids.length / 2)] || kids[0];
        goDownSecure(mid.id);
    };

    // ===== MENU BAGAN
    function bindBaganMenu() {
        const items = document.querySelectorAll(".menu-bagan[data-bagan]");
        items.forEach((a) => {
            a.addEventListener("click", (e) => {
                e.preventDefault();
                const n = parseInt(a.dataset.bagan);
                if (!Number.isFinite(n)) return;
                window.currentBagan = n;
                localStorage.setItem("selectedBagan", String(n));
                items.forEach((x) => x.classList.toggle("active", x === a));
                if (lastLoadedData)
                    drawTree(lastLoadedData, true, currentZoomTransform);
            });
            a.classList.toggle(
                "active",
                Number(a.dataset.bagan) === window.currentBagan,
            );
        });
    }
    window.bindBaganMenu = bindBaganMenu;

    // ===== PASANG USER PENDING (dipakai file 2 juga)
    window.submitAddUser = function (userId, position, uplineId) {
        fetch(`/tree/${userId}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
            },
            body: JSON.stringify({
                user_id: userId,
                position,
                upline_id: uplineId,
            }),
        })
            .then((r) => r.json())
            .then(() => {
                window.toastr?.success?.("User berhasil dipasang");
                loadTree();
                bootstrap.Modal.getInstance(
                    document.getElementById("addUserModal"),
                )?.hide();
            })
            .catch(() => window.toastr?.error?.("Gagal memasang user"));
    };

    // ===== BOOT
    document.addEventListener("DOMContentLoaded", () => {
        bindBaganMenu();

        // paksa member start di dirinya sendiri
        if (
            AUTH_USER_ROLE === "member" &&
            window.currentRootId !== AUTH_USER_ID
        ) {
            window.currentRootId = AUTH_USER_ID;
        }

        loadTree();

        if (AUTH_USER_ROLE === "member") {
            setTimeout(() => {
                if (!localStorage.getItem("member-security-notice-shown")) {
                    window.toastr?.info?.(
                        "Mode Member: Anda hanya dapat melihat data diri, upline langsung, dan downline Anda.",
                    );
                    localStorage.setItem(
                        "member-security-notice-shown",
                        "true",
                    );
                }
            }, 2000);
        }
    });
})();
