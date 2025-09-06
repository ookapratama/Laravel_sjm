(function () {
    "use strict";

    const R = Object.assign(
        {
            pinsUnused: "/pins/unused",
            clonePreview: "/tree/clone/preview",
            cloneStore: "/tree/clone/store",
            treeIndex: "/tree",
            checkUsername: "/member/check-username",
            checkPin: "/member/check-pin",
            checkSponsor: "/member/check-sponsor",
            checkWhatsApp: "/member/check-whatsapp",
            availableUsers: "/tree/available-users",
        },
        window.ROUTES || {},
    );

    const qs = (sel, root = document) => root.querySelector(sel);
    const qsa = (sel, root = document) =>
        Array.from(root.querySelectorAll(sel));
    const toast = (type, msg) => {
        if (typeof toastr?.[type] === "function") toastr[type](msg);
    };

    // ===== LOAD UNUSED PINS
    async function loadUnusedPinsIntoSelect() {
        const sel = qs("#pinCodes");
        if (!sel) return;
        sel.innerHTML = "<option disabled>Memuat PIN...</option>";
        try {
            const r = await fetch(R.pinsUnused, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
            });
            if (!r.ok) throw new Error("HTTP " + r.status);
            const data = await r.json();
            const pins = data?.pins ?? [];
            sel.innerHTML = "";
            if (!Array.isArray(pins) || pins.length === 0) {
                sel.innerHTML =
                    "<option disabled>Tidak ada PIN tersedia</option>";
                return;
            }
            pins.forEach((item) => {
                const code = typeof item === "string" ? item : item?.code;
                if (!code) return;
                const opt = document.createElement("option");
                opt.value = code;
                opt.textContent = code;
                sel.appendChild(opt);
            });
        } catch (e) {
            console.error("loadUnusedPinsIntoSelect error:", e);
            sel.innerHTML = "<option disabled>Gagal memuat PIN</option>";
        }
    }
    document.addEventListener("DOMContentLoaded", loadUnusedPinsIntoSelect);

    // ===== PREVIEW CLONE
    async function previewCloneCandidates() {
        const parentId = qs("#cloneParentId")?.value;
        const useLogin = qs("#cloneUseLogin")?.value === "1";
        const count = Array.from(qs("#pinCodes")?.selectedOptions || []).length;
        if (!count) return toast("info", "Pilih PIN dulu");

        const params = new URLSearchParams({ count: String(count) });
        if (!useLogin && parentId) params.set("base_user_id", String(parentId));

        const res = await fetch(`${R.clonePreview}?${params.toString()}`, {
            headers: { Accept: "application/json" },
        });
        const data = await res.json();
        if (!res.ok) {
            toast("error", data?.message || "Gagal memuat preview");
            return;
        }

        const box = qs("#clonePreview");
        if (box) {
            box.innerHTML = (data.candidates || [])
                .map(
                    (c, i) =>
                        `${i + 1}. <code>${c.username}</code> / <code>${c.sponsor_code ?? c.referral_code ?? "-"}</code>`,
                )
                .join("<br>");
        }
    }

    // ===== SUBMIT CLONE
    async function submitCloneForm(e) {
        e.preventDefault();
        const form = qs("#cloneForm");
        if (!form) return;
        const fd = new FormData(form);
        const res = await fetch(R.cloneStore, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": qs('meta[name="csrf-token"]')?.content || "",
                Accept: "application/json",
            },
            body: fd,
        });
        if (!res.ok) {
            const t = await res.text();
            console.error("Clone failed:", t);
            toast("error", "Gagal clone: " + t);
            return;
        }
        const { ok, message } = await res.json();
        if (ok) {
            toast("success", message || "Berhasil clone & pasang");
            if (typeof window.loadTree === "function") window.loadTree();
            bootstrap.Modal.getInstance(qs("#addUserModal"))?.hide();
        }
    }

    // ===== BADGE PENDING
    async function refreshPendingBadge() {
        const badge = qs("#pendingCountBadge");
        if (!badge) return;
        try {
            const sponsorId = window.AUTH_USER_ID;
            const r = await fetch(`${R.availableUsers}/${sponsorId}/count`, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
            });
            const { count } = await r.json();
            if (Number(count) > 0) {
                badge.textContent = count;
                badge.classList.remove("d-none");
            } else {
                badge.textContent = "0";
                badge.classList.add("d-none");
            }
        } catch {
            badge.classList.add("d-none");
        }
    }

    // ===== OPEN MODAL UNIFIED
    function openAddModalUnified({
        parentId,
        position = "left",
        mode = "clone",
    }) {
        const modalEl = qs("#addUserModal");
        if (!modalEl) return;

        qs("#cloneParentId")?.setAttribute("value", parentId);
        qs("#clonePosition")?.setAttribute("value", position);
        const posLabel = position === "right" ? "Right" : "Left";
        const bagan = String(window.currentBagan || 1);

        if (qs("#clonePositionLabel"))
            qs("#clonePositionLabel").textContent = posLabel;
        if (qs("#cloneBagan")) qs("#cloneBagan").value = bagan;
        if (qs("#cloneUseLogin")) qs("#cloneUseLogin").value = "1";
        if (qs("#clonePreview")) qs("#clonePreview").innerHTML = "";

        if (qs("#addNewParentId")) qs("#addNewParentId").textContent = parentId;
        if (qs("#addNewPosition")) qs("#addNewPosition").textContent = posLabel;
        if (qs("#addNewBagan"))
            qs("#addNewBagan").textContent = `Bagan ${bagan}`;

        if (qs("#treeUplineId")) qs("#treeUplineId").value = parentId;
        if (qs("#treePosition"))
            qs("#treePosition").value = position.toLowerCase();

        loadUnusedPinsIntoSelect();

        const loadPendingList = () => {
            const sponsorId = window.AUTH_USER_ID;
            const list = qs("#userList");
            if (!list) return;
            list.innerHTML = "Memuat...";
            fetch(`${R.availableUsers}/${sponsorId}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
                credentials: "same-origin",
            })
                .then((r) => r.json())
                .then((users) => {
                    list.innerHTML = "";
                    if (!Array.isArray(users) || !users.length) {
                        list.innerHTML =
                            '<div class="text-center text-muted">Tidak ada user pending.</div>';
                        return;
                    }
                    users.forEach((u) => {
                        const row = document.createElement("div");
                        row.className =
                            "list-group-item d-flex justify-content-between align-items-center";
                        row.innerHTML = `<div><strong>${u.username}</strong><br><small>${u.name ?? ""}</small></div>
                             <button class="btn btn-sm btn-primary">Pasang</button>`;
                        row.querySelector("button").onclick = () =>
                            window.submitAddUser(u.id, position, parentId);
                        list.appendChild(row);
                    });
                })
                .catch(
                    () =>
                        (list.innerHTML =
                            '<div class="text-center text-danger">Gagal memuat data pending.</div>'),
                );
        };

        const tabCloneBtn = qs("#tabCloneBtn");
        const tabTambahBtn = qs("#tabTambahBtn");
        const tabAddNewBtn = qs("#tabAddNewBtn");

        tabTambahBtn?.addEventListener("shown.bs.tab", loadPendingList, {
            once: false,
        });

        const modal = new bootstrap.Modal(modalEl);
        modalEl.addEventListener(
            "shown.bs.modal",
            () => refreshPendingBadge(),
            { once: true },
        );
        modalEl.addEventListener(
            "shown.bs.modal",
            () => {
                if (mode === "tambah")
                    bootstrap.Tab.getOrCreateInstance(tabTambahBtn).show();
                else if (mode === "addnew")
                    bootstrap.Tab.getOrCreateInstance(tabAddNewBtn).show();
                else bootstrap.Tab.getOrCreateInstance(tabCloneBtn).show();
            },
            { once: true },
        );

        modal.show();
    }
    window.openAddModalUnified = openAddModalUnified;
    window.loadUnusedPinsIntoSelect = loadUnusedPinsIntoSelect; // optional: dipakai luar

    // ===== BIND BTN PREVIEW & CLONE SUBMIT
    document.addEventListener("click", (ev) => {
        if (ev.target?.id === "btnPreview") previewCloneCandidates();
    });
    document.addEventListener("submit", (ev) => {
        if (ev.target?.id === "cloneForm") submitCloneForm(ev);
    });

    // ===== WIZARD (VALIDASI STEP 1-4)
    (function wizard() {
        let currentStep = 1;
        const totalSteps = 4;
        const errorContainer = qs("#errorContainer");
        const errorList = qs("#errorList");

        window.pinValidationStatus = { isValid: false, lastChecked: "" };
        window.sponsorValidationStatus = { isValid: false, lastChecked: "" };

        // ==== RULES (SALIN 1:1 DARI KODEMU) ====
        const validationRules = {
            1: {
                pin_aktivasi: {
                    required: true,
                    minLength: 8,
                    pattern: /^[A-Z0-9]+$/,
                    customValidator: () =>
                        window.isPinValid && window.isPinValid(),
                    message: "PIN aktivasi harus diverifikasi dan valid",
                },
                sponsor_code: {
                    required: true,
                    minLength: 3,
                    pattern: /^[A-Za-z0-9]+$/,
                    customValidator: () =>
                        window.isSponsorValid && window.isSponsorValid(),
                    message: "Kode sponsor harus diverifikasi dan valid",
                },
            },
            2: {
                name: {
                    required: true,
                    minLength: 3,
                    pattern: /^[a-zA-Z\s.,']+$/,
                    message:
                        "Nama lengkap harus diisi minimal 3 karakter (hanya huruf dan tanda baca umum)",
                },
                no_telp: {
                    required: true,
                    pattern: /^(\+?62|0)[0-9]{8,13}$/,
                    message:
                        "Nomor HP harus valid format Indonesia (08xx atau +62xxx)",
                },
                email: {
                    required: true,
                    minLength: 6,
                    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                    message: "Format email tidak valid",
                },
                jenis_kelamin: {
                    required: true,
                    type: "radio",
                    message: "Pilih jenis kelamin",
                },
                no_ktp: {
                    required: false,
                    pattern: /^[0-9]{16}$/,
                    message: "No KTP harus 16 digit angka",
                },
                tempat_lahir: {
                    required: true,
                    minLength: 3,
                    pattern: /^[a-zA-Z\s.]+$/,
                    message: "Tempat lahir harus diisi minimal 3 karakter",
                },
                tanggal_lahir: {
                    required: true,
                    customValidator: (value) => {
                        const birthDate = new Date(value);
                        const today = new Date();
                        const age =
                            today.getFullYear() - birthDate.getFullYear();
                        return age >= 17 && age <= 100;
                    },
                    message:
                        "Tanggal lahir tidak valid (minimal usia 17 tahun)",
                },
                agama: {
                    required: true,
                    type: "radio",
                    message: "Pilih agama",
                },
                alamat: {
                    required: true,
                    minLength: 10,
                    message: "Alamat lengkap harus diisi minimal 10 karakter",
                },
                rt: {
                    required: false,
                    pattern: /^[0-9]{1,3}$/,
                    message: "RT harus berupa angka 1-3 digit",
                },
                rw: {
                    required: false,
                    pattern: /^[0-9]{1,3}$/,
                    message: "RW harus berupa angka 1-3 digit",
                },
                desa: {
                    required: true,
                    minLength: 3,
                    pattern: /^[a-zA-Z\s.]+$/,
                    message: "Desa/Kelurahan harus diisi minimal 3 karakter",
                },
                kecamatan: {
                    required: true,
                    minLength: 3,
                    pattern: /^[a-zA-Z\s.]+$/,
                    message: "Kecamatan harus diisi minimal 3 karakter",
                },
                kota: {
                    required: true,
                    minLength: 3,
                    pattern: /^[a-zA-Z\s.]+$/,
                    message: "Kota/Kabupaten harus diisi minimal 3 karakter",
                },
                kode_pos: {
                    required: false,
                    pattern: /^[0-9]{5}$/,
                    message: "Kode pos harus 5 digit angka",
                },
            },
            3: {
                username: {
                    required: true,
                    minLength: 4,
                    maxLength: 20,
                    pattern: /^[a-zA-Z0-9_]+$/,
                    message:
                        "Username 4-20 karakter, hanya huruf, angka, dan underscore",
                },
                password: {
                    required: true,
                    minLength: 6,
                    customValidator: (v) =>
                        /[a-zA-Z]/.test(v) && /[0-9]/.test(v),
                    message:
                        "Password minimal 6 karakter dengan kombinasi huruf dan angka",
                },
                password_confirmation: {
                    required: true,
                    customValidator: (v) => v === qs("#password")?.value,
                    message: "Konfirmasi password tidak cocok",
                },
            },
            4: {
                nama_rekening: {
                    required: true,
                    minLength: 3,
                    pattern: /^[a-zA-Z\s.,']+$/,
                    message: "Nama rekening harus diisi minimal 3 karakter",
                },
                nomor_rekening: {
                    required: true,
                    pattern: /^[0-9]{5,20}$/,
                    message: "Nomor rekening harus 5-20 digit angka",
                },
                nama_bank: {
                    required: true,
                    minLength: 3,
                    message: "Nama bank harus dipilih atau diisi",
                },
                nama_ahli_waris: {
                    required: false,
                    minLength: 3,
                    pattern: /^[a-zA-Z\s.,']*$/,
                    message: "Nama ahli waris minimal 3 karakter jika diisi",
                },
                hubungan_ahli_waris: {
                    required: false,
                    minLength: 3,
                    pattern: /^[a-zA-Z\s.,']*$/,
                    message:
                        "Hubungan ahli waris minimal 3 karakter jika diisi",
                },
                agree: {
                    required: true,
                    type: "checkbox",
                    message: "Anda harus menyetujui Syarat & Ketentuan",
                },
            },
        };

        function validateField(fieldName, value, rules) {
            const rule = rules[fieldName];
            if (!rule) return { isValid: true };
            if (rule.required && (!value || value.trim() === ""))
                return { isValid: false, message: rule.message };
            if (!rule.required && (!value || value.trim() === ""))
                return { isValid: true };
            if (rule.minLength && value.length < rule.minLength)
                return { isValid: false, message: rule.message };
            if (rule.maxLength && value.length > rule.maxLength)
                return { isValid: false, message: rule.message };
            if (rule.pattern && !rule.pattern.test(value))
                return { isValid: false, message: rule.message };
            if (rule.customValidator && !rule.customValidator(value))
                return { isValid: false, message: rule.message };
            return { isValid: true };
        }
        function validateSpecialField(fieldName, rules) {
            const rule = rules[fieldName];
            if (!rule) return { isValid: true };
            if (rule.type === "radio") {
                const radios = qsa(`input[name="${fieldName}"]`);
                const isChecked = radios.some((r) => r.checked);
                if (rule.required && !isChecked)
                    return { isValid: false, message: rule.message };
            }
            if (rule.type === "checkbox") {
                const cb = qs(`input[name="${fieldName}"]`);
                if (rule.required && (!cb || !cb.checked))
                    return { isValid: false, message: rule.message };
            }
            return { isValid: true };
        }
        function clearValidationFeedback() {
            qsa(".is-invalid").forEach((el) =>
                el.classList.remove("is-invalid"),
            );
            qsa(".invalid-feedback").forEach((el) => (el.textContent = ""));
            errorContainer?.classList.add("d-none");
            if (errorList) errorList.innerHTML = "";
        }
        function showFieldError(fieldName, message) {
            const inputs = qsa(`[name="${fieldName}"]`);
            inputs.forEach((input) => {
                input.classList.add("is-invalid");
                let feedback =
                    input.parentNode.querySelector(".invalid-feedback");
                if (!feedback) {
                    const container = input.closest(
                        ".col-md-6, .col-md-12, .col-12",
                    );
                    if (container)
                        feedback = container.querySelector(".invalid-feedback");
                }
                if (feedback) {
                    feedback.textContent = message;
                } else {
                    feedback = document.createElement("div");
                    feedback.className = "invalid-feedback";
                    feedback.textContent = message;
                    if (input.type === "radio" || input.type === "checkbox") {
                        const container = input.closest(
                            ".col-md-6, .col-md-12, .col-12",
                        );
                        if (container) container.appendChild(feedback);
                    } else {
                        input.parentNode.insertBefore(
                            feedback,
                            input.nextSibling,
                        );
                    }
                }
            });
        }

        function validateStep(step) {
            clearValidationFeedback();
            const stepRules = validationRules[step];
            if (!stepRules) return true;

            let isStepValid = true;
            const errors = [];

            if (step === 1) {
                const pinInput = qs("#pin_aktivasi");
                const sponsorInput = qs("#sponsor_code_display");

                const pinValue = pinInput?.value.trim() || "";
                const sponsorValue = sponsorInput?.value.trim() || "";

                let pinValid = true,
                    sponsorValid = true;

                if (!pinValue) {
                    showFieldError("pin_aktivasi", "PIN aktivasi harus diisi");
                    errors.push("PIN aktivasi harus diisi");
                    pinValid = false;
                } else if (pinValue.length < 8) {
                    showFieldError(
                        "pin_aktivasi",
                        "PIN harus minimal 8 karakter",
                    );
                    errors.push("PIN harus minimal 8 karakter");
                    pinValid = false;
                } else if (
                    !window.pinValidationStatus.isValid ||
                    window.pinValidationStatus.lastChecked !== pinValue
                ) {
                    showFieldError(
                        "pin_aktivasi",
                        "PIN aktivasi harus diverifikasi terlebih dahulu",
                    );
                    const pinFeedback = qs("#pinFeedback");
                    const pinStatus = qs("#pinStatus");
                    pinInput?.classList.add("is-invalid");
                    if (pinFeedback)
                        pinFeedback.textContent =
                            'Silakan klik tombol "Verifikasi" untuk memverifikasi PIN';
                    if (pinStatus) {
                        pinStatus.innerHTML =
                            '<i class="fas fa-exclamation-triangle"></i> PIN belum diverifikasi';
                        pinStatus.className =
                            "verification-status status-warning";
                    }
                    errors.push(
                        "PIN aktivasi harus diverifikasi terlebih dahulu",
                    );
                    pinValid = false;
                }

                if (!sponsorValue) {
                    showFieldError("sponsor_code", "Kode sponsor harus diisi");
                    errors.push("Kode sponsor harus diisi");
                    sponsorValid = false;
                } else if (sponsorValue.length < 3) {
                    showFieldError(
                        "sponsor_code",
                        "Kode sponsor harus minimal 3 karakter",
                    );
                    errors.push("Kode sponsor harus minimal 3 karakter");
                    sponsorValid = false;
                } else if (
                    !window.sponsorValidationStatus.isValid ||
                    window.sponsorValidationStatus.lastChecked !== sponsorValue
                ) {
                    showFieldError(
                        "sponsor_code",
                        "Kode sponsor harus diverifikasi terlebih dahulu",
                    );
                    const sponsorFeedback = qs("#sponsorFeedback");
                    const sponsorStatus = qs("#sponsorStatus");
                    sponsorInput?.classList.add("is-invalid");
                    if (sponsorFeedback)
                        sponsorFeedback.textContent =
                            'Silakan klik tombol "Verifikasi" untuk memverifikasi sponsor';
                    if (sponsorStatus) {
                        sponsorStatus.innerHTML =
                            '<i class="fas fa-exclamation-triangle"></i> Sponsor belum diverifikasi';
                        sponsorStatus.className =
                            "verification-status status-warning";
                    }
                    errors.push(
                        "Kode sponsor harus diverifikasi terlebih dahulu",
                    );
                    sponsorValid = false;
                }

                isStepValid = pinValid && sponsorValid;
                if (!isStepValid) {
                    if (!pinValid && !sponsorValid)
                        toast(
                            "error",
                            "PIN aktivasi dan kode sponsor harus diverifikasi terlebih dahulu",
                        );
                    else if (!pinValid)
                        toast(
                            "error",
                            "PIN aktivasi harus diverifikasi terlebih dahulu",
                        );
                    else
                        toast(
                            "error",
                            "Kode sponsor harus diverifikasi terlebih dahulu",
                        );
                }
            } else {
                Object.keys(stepRules).forEach((fieldName) => {
                    const rule = stepRules[fieldName];
                    if (rule.type === "radio" || rule.type === "checkbox") {
                        const v = validateSpecialField(fieldName, stepRules);
                        if (!v.isValid) {
                            showFieldError(fieldName, v.message);
                            errors.push(v.message);
                            isStepValid = false;
                        }
                    } else {
                        const input = qs(`[name="${fieldName}"]`);
                        if (input) {
                            const v = validateField(
                                fieldName,
                                input.value,
                                stepRules,
                            );
                            if (!v.isValid) {
                                showFieldError(fieldName, v.message);
                                errors.push(v.message);
                                isStepValid = false;
                            }
                        }
                    }
                });
            }

            if (errors.length > 0) {
                if (errorList)
                    errorList.innerHTML = errors
                        .map((e) => `<li>${e}</li>`)
                        .join("");
                errorContainer?.classList.remove("d-none");
                const firstInvalid = document.querySelector(".is-invalid");
                if (firstInvalid)
                    firstInvalid.scrollIntoView({
                        behavior: "smooth",
                        block: "center",
                    });
            }
            return isStepValid;
        }

        function setupRealTimeValidation() {
            Object.keys(validationRules).forEach((step) => {
                const stepRules = validationRules[step];
                Object.keys(stepRules).forEach((fieldName) => {
                    const inputs = qsa(`[name="${fieldName}"]`);
                    inputs.forEach((input) => {
                        if (
                            input.type === "radio" ||
                            input.type === "checkbox"
                        ) {
                            input.addEventListener("change", () => {
                                input.classList.remove("is-invalid");
                                const feedback = input
                                    .closest(".col-md-6, .col-md-12, .col-12")
                                    ?.querySelector(".invalid-feedback");
                                if (feedback) feedback.textContent = "";
                                const v = validateSpecialField(
                                    fieldName,
                                    stepRules,
                                );
                                if (!v.isValid)
                                    showFieldError(fieldName, v.message);
                            });
                        } else {
                            input.addEventListener("blur", () => {
                                input.classList.remove("is-invalid");
                                const fb =
                                    input.parentNode.querySelector(
                                        ".invalid-feedback",
                                    );
                                if (fb) fb.textContent = "";
                                const v = validateField(
                                    fieldName,
                                    input.value,
                                    stepRules,
                                );
                                if (!v.isValid)
                                    showFieldError(fieldName, v.message);
                            });
                            if (fieldName === "password_confirmation") {
                                input.addEventListener("input", () => {
                                    const v = validateField(
                                        fieldName,
                                        input.value,
                                        stepRules,
                                    );
                                    if (!v.isValid)
                                        showFieldError(fieldName, v.message);
                                    else {
                                        input.classList.remove("is-invalid");
                                        const fb =
                                            input.parentNode.querySelector(
                                                ".invalid-feedback",
                                            );
                                        if (fb) fb.textContent = "";
                                    }
                                });
                            }
                        }
                    });
                });
            });
        }

        function showStep(step) {
            qsa(".js-step").forEach((s) => s.classList.remove("active"));
            qs(`.js-step[data-step="${step}"]`)?.classList.add("active");
            qsa(".step-item").forEach((item, idx) => {
                const sn = idx + 1;
                item.classList.remove("active", "completed");
                if (sn < step) item.classList.add("completed");
                else if (sn === step) item.classList.add("active");
            });
            const prevBtn = qs("#prevBtn"),
                nextBtn = qs("#nextBtn"),
                submitBtn = qs("#submitBtn");
            if (prevBtn) prevBtn.style.display = step > 1 ? "block" : "none";
            if (nextBtn)
                nextBtn.style.display = step < totalSteps ? "block" : "none";
            if (submitBtn)
                submitBtn.style.display =
                    step === totalSteps ? "block" : "none";
        }

        function initWizard() {
            const prevBtn = qs("#prevBtn"),
                nextBtn = qs("#nextBtn");
            nextBtn?.addEventListener("click", () => {
                if (validateStep(currentStep)) {
                    currentStep++;
                    showStep(currentStep);
                }
            });
            prevBtn?.addEventListener("click", () => {
                currentStep--;
                showStep(currentStep);
            });
            showStep(currentStep);
            setupRealTimeValidation();
        }

        if (document.readyState === "loading")
            document.addEventListener("DOMContentLoaded", initWizard);
        else initWizard();

        window.validateStep = validateStep;
        window.clearValidationFeedback = clearValidationFeedback;
        window.totalSteps = totalSteps;

        // === Prefill sponsor dari ?ref ===
        (function () {
            const urlParams = new URLSearchParams(window.location.search);
            const ref = urlParams.get("ref") || "";
            const refInput = qs("#ref");
            const sponsorDisp = qs("#sponsor_code_display");
            const sponsorBanner = qs("#sponsorBanner");
            const sponsorText = qs("#sponsorText");

            if (ref) {
                if (refInput) refInput.value = ref;
                if (sponsorDisp) sponsorDisp.value = ref;
                sponsorBanner?.classList.remove("d-none");
                if (sponsorText)
                    sponsorText.textContent =
                        "Kode: " + ref + " (akan divalidasi saat submit)";
            }
            if (sponsorDisp && refInput) {
                sponsorDisp.addEventListener("input", function () {
                    refInput.value = this.value;
                });
            }
        })();

        // === Datalist bank ===
        (function () {
            const datalist = qs("#bankList");
            if (!datalist) return;
            const BUMN = [
                "Bank Mandiri",
                "Bank Rakyat Indonesia (BRI)",
                "Bank Negara Indonesia (BNI)",
                "Bank Tabungan Negara (BTN)",
                "Bank Syariah Indonesia (BSI)",
            ];
            const UMUM = [
                "Bank Central Asia (BCA)",
                "CIMB Niaga",
                "Bank Danamon",
                "OCBC NISP",
                "Permata Bank",
                "Panin Bank",
                "Maybank Indonesia",
                "KB Bukopin",
                "Bank BTPN",
                "Bank Mega",
                "Bank Sinarmas",
                "UOB Indonesia",
                "HSBC Indonesia",
                "Standard Chartered Indonesia",
                "Citibank N.A. Indonesia",
                "ICBC Indonesia",
                "Bank China Construction Bank Indonesia (CCB Indonesia)",
                "Bank Commonwealth",
                "QNB Indonesia",
                "Bank Woori Saudara",
                "Bank Shinhan Indonesia",
                "Bank JTrust Indonesia",
                "Bank MNC Internasional",
                "Bank Artha Graha Internasional",
                "Bank Capital Indonesia",
                "Bank Maspion Indonesia",
                "Bank Ina Perdana",
                "Bank Index Selindo",
                "Bank Victoria International",
                "Bank Mayora",
                "Bank Oke Indonesia",
                "Bank Sahabat Sampoerna",
                "Krom Bank Indonesia",
                "Bank Fama Internasional",
                "Bank Neo Commerce (BNC)",
                "Allo Bank Indonesia",
                "SeaBank Indonesia",
                "Bank Jago",
                "BCA Digital (blu)",
                "Bank Muamalat Indonesia",
                "BTPN Syariah",
                "Bank Mega Syariah",
            ];
            const BPD = [
                "Bank DKI",
                "Bank BJB (Jawa Barat & Banten)",
                "Bank Jateng",
                "Bank Jatim",
                "Bank DIY",
                "Bank BPD Bali",
                "Bank NTB Syariah",
                "Bank NTT",
                "Bank BPD Sumut",
                "Bank Sumsel Babel",
                "Bank Nagari (Sumbar)",
                "Bank Riau Kepri",
                "Bank Jambi",
                "Bank Bengkulu",
                "Bank Lampung",
                "Bank Kalbar",
                "Bank Kalteng",
                "Bank Kalsel",
                "Bank Kaltimtara",
                "Bank Kaltara",
                "Bank Sulselbar",
                "Bank Sultra",
                "Bank Sulteng",
                "Bank SulutGo",
                "Bank Maluku Malut",
                "Bank Papua",
            ];
            const banks = [
                ...BUMN,
                ...UMUM.sort((a, b) => a.localeCompare(b)),
                ...BPD.sort((a, b) => a.localeCompare(b)),
            ];
            datalist.innerHTML = banks
                .map((b) => `<option value="${b}"></option>`)
                .join("");
        })();

        // === Submit form utama via Fetch ===
        const form = qs("#ref-register-form");
        form?.addEventListener("submit", async function (e) {
            e.preventDefault();
            if (!validateStep(totalSteps)) {
                const firstInvalid = document.querySelector(".is-invalid");
                if (firstInvalid)
                    firstInvalid.scrollIntoView({
                        behavior: "smooth",
                        block: "center",
                    });
                toast(
                    "error",
                    "Harap lengkapi semua bidang yang wajib diisi pada step terakhir.",
                );
                return;
            }
            clearValidationFeedback();
            try {
                const formData = new FormData(this);
                const res = await fetch(this.action, {
                    method: "POST",
                    body: formData,
                    credentials: "include",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    redirect: "manual",
                });
                const ct = res.headers.get("content-type") || "";
                const data = ct.includes("application/json")
                    ? await res.json()
                    : {};
                if (res.ok) {
                    toast("success", data.message || "User berhasil disimpan");
                    const redirect =
                        data.redirect || window.ROUTES?.treeIndex || "/tree";
                    setTimeout(() => {
                        window.location.href = redirect;
                    }, 1000);
                    return;
                }
                if (res.status === 422 && data.errors) {
                    if (errorList) errorList.innerHTML = "";
                    let firstInvalidField = null;
                    Object.entries(data.errors).forEach(([field, msgs]) => {
                        const input = qs(`[name="${field}"]`);
                        const firstMsg = msgs[0];
                        const li = document.createElement("li");
                        li.textContent = firstMsg;
                        errorList?.appendChild(li);
                        if (input) {
                            input.classList.add("is-invalid");
                            let fb = input.nextElementSibling;
                            if (
                                !fb ||
                                !fb.classList.contains("invalid-feedback")
                            ) {
                                fb = document.createElement("div");
                                fb.className = "invalid-feedback";
                                input.parentNode.insertBefore(
                                    fb,
                                    input.nextSibling,
                                );
                            }
                            fb.textContent = firstMsg;
                            if (!firstInvalidField) firstInvalidField = input;
                            if (input.type === "radio")
                                qsa(`[name="${field}"]`).forEach((r) =>
                                    r.classList.add("is-invalid"),
                                );
                        } else {
                            console.warn(
                                `Validation error unknown field: ${field} - ${firstMsg}`,
                            );
                        }
                    });
                    errorContainer?.classList.remove("d-none");
                    toast(
                        "error",
                        "Terdapat kesalahan pada input Anda. Silakan periksa kembali.",
                    );
                    if (firstInvalidField) {
                        const parentStep =
                            firstInvalidField.closest(".js-step");
                        if (parentStep) {
                            const stepNumber = parseInt(
                                parentStep.dataset.step,
                                10,
                            );
                            if (stepNumber && stepNumber !== currentStep) {
                                currentStep = stepNumber;
                                showStep(currentStep);
                            }
                        }
                        setTimeout(
                            () =>
                                firstInvalidField.scrollIntoView({
                                    behavior: "smooth",
                                    block: "center",
                                }),
                            100,
                        );
                    } else {
                        errorContainer?.scrollIntoView({
                            behavior: "smooth",
                            block: "start",
                        });
                    }
                    return;
                }
                const errorMessage =
                    data.message ||
                    `Terjadi kesalahan ${res.status}: ${res.statusText}.`;
                if (errorList) errorList.innerHTML = `<li>${errorMessage}</li>`;
                errorContainer?.classList.remove("d-none");
                toast("error", errorMessage);
                console.error("Server error:", data);
            } catch (err) {
                console.error("Network/unexpected error:", err);
                if (errorList)
                    errorList.innerHTML = `<li>Terjadi kesalahan koneksi atau tidak terduga. Silakan coba lagi.</li>`;
                errorContainer?.classList.remove("d-none");
                toast("error", "Terjadi kesalahan koneksi atau tidak terduga.");
            }
        });
    })();

    // ===== USERNAME CHECK
    (function usernameCheck() {
        const usernameInput = qs("#username");
        const checkButton = qs("#checkUsername");
        const checkText = qs("#checkUsernameText");
        const checkSpinner = qs("#checkUsernameSpinner");
        const usernameFeedback = qs("#usernameFeedback");
        const usernameValidFeedback = qs("#usernameValidFeedback");
        const usernameStatus = qs("#usernameStatus");

        if (!usernameInput || !checkButton) return;

        let checkTimeout,
            lastCheckedUsername = "",
            _isUsernameAvailable = false;

        const validateUsernameFormat = (u) =>
            /^[a-zA-Z0-9_]+$/.test(u) && u.length >= 4 && u.length <= 20;
        const resetUsernameStatus = () => {
            usernameInput.classList.remove("is-valid", "is-invalid");
            if (usernameFeedback) usernameFeedback.textContent = "";
            usernameValidFeedback?.classList.add("d-none");
            if (usernameStatus) usernameStatus.textContent = "";
            _isUsernameAvailable = false;
        };
        const showChecking = () => {
            checkButton.classList.add("btn-checking");
            if (checkText) checkText.textContent = "Mengecek...";
            checkSpinner?.classList.remove("d-none");
            if (usernameStatus) {
                usernameStatus.innerHTML =
                    '<i class="fas fa-spinner fa-spin"></i> Mengecek ketersediaan...';
                usernameStatus.className = "username-status username-checking";
            }
        };
        const hideChecking = () => {
            checkButton.classList.remove("btn-checking");
            if (checkText) checkText.textContent = "Cek Ketersediaan";
            checkSpinner?.classList.add("d-none");
        };

        async function checkUsernameAvailability(username) {
            if (!validateUsernameFormat(username)) {
                usernameInput.classList.add("is-invalid");
                if (usernameFeedback)
                    usernameFeedback.textContent =
                        "Username harus 4-20 karakter, hanya huruf, angka, dan underscore";
                if (usernameStatus) {
                    usernameStatus.innerHTML =
                        '<i class="fas fa-times-circle"></i> Format tidak valid';
                    usernameStatus.className = "username-status username-taken";
                }
                return false;
            }
            showChecking();
            try {
                const resp = await fetch(R.checkUsername, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN":
                            qs('meta[name="csrf-token"]')?.content || "",
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: JSON.stringify({ username }),
                });
                const data = await resp.json();
                hideChecking();
                if (resp.ok) {
                    if (data.available) {
                        usernameInput.classList.remove("is-invalid");
                        usernameInput.classList.add("is-valid");
                        usernameValidFeedback?.classList.remove("d-none");
                        if (usernameStatus) {
                            usernameStatus.innerHTML =
                                '<i class="fas fa-check-circle"></i> Username tersedia!';
                            usernameStatus.className =
                                "username-status username-available";
                        }
                        _isUsernameAvailable = true;
                        lastCheckedUsername = username;
                    } else {
                        usernameInput.classList.remove("is-valid");
                        usernameInput.classList.add("is-invalid");
                        if (usernameFeedback)
                            usernameFeedback.textContent =
                                data.message || "Username sudah terpakai";
                        if (usernameStatus) {
                            usernameStatus.innerHTML =
                                '<i class="fas fa-times-circle"></i> Username sudah terpakai';
                            usernameStatus.className =
                                "username-status username-taken";
                        }
                        _isUsernameAvailable = false;
                    }
                } else {
                    throw new Error(
                        data.message ||
                            "Terjadi kesalahan saat mengecek username",
                    );
                }
            } catch (err) {
                hideChecking();
                console.error("Error checking username:", err);
                usernameInput.classList.add("is-invalid");
                if (usernameFeedback)
                    usernameFeedback.textContent =
                        "Terjadi kesalahan saat mengecek username. Silakan coba lagi.";
                if (usernameStatus) {
                    usernameStatus.innerHTML =
                        '<i class="fas fa-exclamation-triangle"></i> Error checking';
                    usernameStatus.className = "username-status username-taken";
                }
                _isUsernameAvailable = false;
            }
        }

        checkButton.addEventListener("click", (e) => {
            e.preventDefault();
            const u = usernameInput.value.trim();
            if (!u) {
                usernameInput.focus();
                return;
            }
            checkUsernameAvailability(u);
        });
        usernameInput.addEventListener("input", function () {
            clearTimeout(checkTimeout);
            resetUsernameStatus();
            const u = this.value.trim();
            if (u && !validateUsernameFormat(u)) {
                this.classList.add("is-invalid");
                if (usernameFeedback)
                    usernameFeedback.textContent =
                        "Username harus 4-20 karakter, hanya huruf, angka, dan underscore";
            } else if (u && u !== lastCheckedUsername) {
                checkTimeout = setTimeout(
                    () => checkUsernameAvailability(u),
                    1000,
                );
            }
        });
        usernameInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                checkButton.click();
            }
        });

        const form = qs("#ref-register-form");
        if (form) {
            form.addEventListener("submit", (e) => {
                const u = usernameInput.value.trim();
                if (!_isUsernameAvailable || u !== lastCheckedUsername) {
                    e.preventDefault();
                    usernameInput.classList.add("is-invalid");
                    if (usernameFeedback)
                        usernameFeedback.textContent =
                            "Silakan periksa ketersediaan username terlebih dahulu";
                    usernameInput.scrollIntoView({
                        behavior: "smooth",
                        block: "center",
                    });
                    toast(
                        "error",
                        "Silakan periksa ketersediaan username terlebih dahulu",
                    );
                }
            });
        }
        window.isUsernameAvailable = function () {
            const u = usernameInput.value.trim();
            return _isUsernameAvailable && u === lastCheckedUsername;
        };
    })();

    // ===== PIN/SPONSOR/WHATSAPP
    (function verification() {
        // --- PIN elements
        const pinInput = qs("#pin_aktivasi");
        const checkPinBtn = qs("#checkPin");
        const checkPinText = qs("#checkPinText");
        const checkPinSpinner = qs("#checkPinSpinner");
        const pinFeedback = qs("#pinFeedback");
        const pinValidFeedback = qs("#pinValidFeedback");
        const pinStatus = qs("#pinStatus");

        // --- Sponsor elements
        const sponsorInput = qs("#sponsor_code_display");
        const checkSponsorBtn = qs("#checkSponsor");
        const checkSponsorText = qs("#checkSponsorText");
        const checkSponsorSpinner = qs("#checkSponsorSpinner");
        const sponsorFeedback = qs("#sponsorFeedback");
        const sponsorValidFeedback = qs("#sponsorValidFeedback");
        const sponsorStatus = qs("#sponsorStatus");
        const sponsorInfoBanner = qs("#sponsorInfoBanner");
        const sponsorInfo = qs("#sponsorInfo");

        let isPinValid = false,
            isSponsorValid = false,
            lastCheckedPin = "",
            lastCheckedSponsor = "";

        // === PIN
        const validatePinFormat = (p) =>
            /^[A-Z0-9]+$/.test(p) && p.length >= 8 && p.length <= 16;
        const showPinChecking = () => {
            checkPinBtn?.classList.add("btn-checking");
            if (checkPinText) checkPinText.textContent = "Mengecek...";
            checkPinSpinner?.classList.remove("d-none");
            if (pinStatus) {
                pinStatus.innerHTML =
                    '<i class="fas fa-spinner fa-spin"></i> Memverifikasi PIN...';
                pinStatus.className = "verification-status status-checking";
            }
        };
        const hidePinChecking = () => {
            checkPinBtn?.classList.remove("btn-checking");
            if (checkPinText) checkPinText.textContent = "Verifikasi";
            checkPinSpinner?.classList.add("d-none");
        };
        const resetPinStatus = () => {
            pinInput?.classList.remove("is-valid", "is-invalid");
            if (pinFeedback) pinFeedback.textContent = "";
            pinValidFeedback?.classList.add("d-none");
            if (pinStatus) pinStatus.textContent = "";
            window.pinValidationStatus.isValid = false;
            window.pinValidationStatus.lastChecked = "";
        };

        async function checkPinActivation(pin) {
            if (!validatePinFormat(pin)) {
                pinInput?.classList.add("is-invalid");
                if (pinFeedback)
                    pinFeedback.textContent =
                        "PIN harus 8-16 karakter, hanya huruf kapital dan angka";
                if (pinStatus) {
                    pinStatus.innerHTML =
                        '<i class="fas fa-times-circle"></i> Format PIN tidak valid';
                    pinStatus.className = "verification-status status-invalid";
                }
                window.pinValidationStatus.isValid = false;
                return false;
            }
            showPinChecking();
            try {
                const fd = new FormData();
                fd.append("pin_aktivasi", pin);
                const csrf = qs(
                    '#ref-register-form input[name="_token"]',
                )?.value;
                if (csrf) fd.append("_token", csrf);
                const resp = await fetch(R.checkPin, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: fd,
                });
                const data = await resp.json();
                hidePinChecking();
                if (resp.ok) {
                    if (data.valid) {
                        pinInput?.classList.remove("is-invalid");
                        pinInput?.classList.add("is-valid");
                        pinValidFeedback?.classList.remove("d-none");
                        if (pinStatus) {
                            pinStatus.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message || "PIN valid dan tersedia!"}`;
                            pinStatus.className =
                                "verification-status status-valid";
                        }
                        window.pinValidationStatus.isValid = true;
                        window.pinValidationStatus.lastChecked = pin;
                        if (data.info && pinStatus)
                            pinStatus.innerHTML += `<br><small>${data.info}</small>`;
                    } else {
                        pinInput?.classList.remove("is-valid");
                        pinInput?.classList.add("is-invalid");
                        if (pinFeedback)
                            pinFeedback.textContent =
                                data.message ||
                                "PIN tidak valid atau sudah digunakan";
                        if (pinStatus) {
                            pinStatus.innerHTML =
                                '<i class="fas fa-times-circle"></i> PIN tidak valid';
                            pinStatus.className =
                                "verification-status status-invalid";
                        }
                        window.pinValidationStatus.isValid = false;
                        window.pinValidationStatus.lastChecked = "";
                    }
                } else {
                    throw new Error(
                        data.message ||
                            "Terjadi kesalahan saat memverifikasi PIN",
                    );
                }
            } catch (err) {
                hidePinChecking();
                console.error("Error checking PIN:", err);
                pinInput?.classList.add("is-invalid");
                if (pinFeedback)
                    pinFeedback.textContent =
                        "Terjadi kesalahan saat memverifikasi PIN. Silakan coba lagi.";
                if (pinStatus) {
                    pinStatus.innerHTML =
                        '<i class="fas fa-exclamation-triangle"></i> Error verifikasi';
                    pinStatus.className = "verification-status status-invalid";
                }
                window.pinValidationStatus.isValid = false;
                window.pinValidationStatus.lastChecked = "";
            }
        }

        // === SPONSOR
        const validateSponsorFormat = (s) =>
            /^[A-Za-z0-9]+$/.test(s) && s.length >= 3 && s.length <= 20;
        const showSponsorChecking = () => {
            checkSponsorBtn?.classList.add("btn-checking");
            if (checkSponsorText) checkSponsorText.textContent = "Mengecek...";
            checkSponsorSpinner?.classList.remove("d-none");
            if (sponsorStatus) {
                sponsorStatus.innerHTML =
                    '<i class="fas fa-spinner fa-spin"></i> Memverifikasi sponsor...';
                sponsorStatus.className = "verification-status status-checking";
            }
        };
        const hideSponsorChecking = () => {
            checkSponsorBtn?.classList.remove("btn-checking");
            if (checkSponsorText) checkSponsorText.textContent = "Verifikasi";
            checkSponsorSpinner?.classList.add("d-none");
        };
        const resetSponsorStatus = () => {
            sponsorInput?.classList.remove("is-valid", "is-invalid");
            if (sponsorFeedback) sponsorFeedback.textContent = "";
            sponsorValidFeedback?.classList.add("d-none");
            if (sponsorStatus) sponsorStatus.textContent = "";
            sponsorInfoBanner?.classList.add("d-none");
            window.sponsorValidationStatus.isValid = false;
            window.sponsorValidationStatus.lastChecked = "";
        };

        async function checkSponsorCode(sponsor) {
            if (!validateSponsorFormat(sponsor)) {
                sponsorInput?.classList.add("is-invalid");
                if (sponsorFeedback)
                    sponsorFeedback.textContent =
                        "Kode sponsor harus 3-20 karakter alfanumerik";
                if (sponsorStatus) {
                    sponsorStatus.innerHTML =
                        '<i class="fas fa-times-circle"></i> Format sponsor tidak valid';
                    sponsorStatus.className =
                        "verification-status status-invalid";
                }
                window.sponsorValidationStatus.isValid = false;
                return false;
            }
            showSponsorChecking();
            try {
                const fd = new FormData();
                fd.append("sponsor_code", sponsor);
                const csrf = qs(
                    '#ref-register-form input[name="_token"]',
                )?.value;
                if (csrf) fd.append("_token", csrf);
                const resp = await fetch(R.checkSponsor, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: fd,
                });
                const data = await resp.json();
                hideSponsorChecking();
                if (resp.ok) {
                    if (data.valid) {
                        sponsorInput?.classList.remove("is-invalid");
                        sponsorInput?.classList.add("is-valid");
                        sponsorValidFeedback?.classList.remove("d-none");
                        if (sponsorStatus) {
                            sponsorStatus.innerHTML =
                                '<i class="fas fa-check-circle"></i> Sponsor ditemukan!';
                            sponsorStatus.className =
                                "verification-status status-valid";
                        }
                        window.sponsorValidationStatus.isValid = true;
                        window.sponsorValidationStatus.lastChecked = sponsor;

                        if (data.sponsor_info && sponsorInfo) {
                            sponsorInfo.innerHTML = `<strong>${data.sponsor_info.name}</strong><br><small>ID: ${data.sponsor_info.member_id || sponsor} | Level: ${data.sponsor_info.level || "Member"}</small>`;
                            sponsorInfoBanner?.classList.remove("d-none");
                        }
                        const refInput = qs("#ref");
                        if (refInput) refInput.value = sponsor;
                    } else {
                        sponsorInput?.classList.remove("is-valid");
                        sponsorInput?.classList.add("is-invalid");
                        if (sponsorFeedback)
                            sponsorFeedback.textContent =
                                data.message || "Kode sponsor tidak ditemukan";
                        if (sponsorStatus) {
                            sponsorStatus.innerHTML =
                                '<i class="fas fa-times-circle"></i> Sponsor tidak ditemukan';
                            sponsorStatus.className =
                                "verification-status status-invalid";
                        }
                        window.sponsorValidationStatus.isValid = false;
                        window.sponsorValidationStatus.lastChecked = "";
                        sponsorInfoBanner?.classList.add("d-none");
                    }
                } else {
                    throw new Error(
                        data.message ||
                            "Terjadi kesalahan saat memverifikasi sponsor",
                    );
                }
            } catch (err) {
                hideSponsorChecking();
                console.error("Error checking sponsor:", err);
                sponsorInput?.classList.add("is-invalid");
                if (sponsorFeedback)
                    sponsorFeedback.textContent =
                        "Terjadi kesalahan saat memverifikasi sponsor. Silakan coba lagi.";
                if (sponsorStatus) {
                    sponsorStatus.innerHTML =
                        '<i class="fas fa-exclamation-triangle"></i> Error verifikasi';
                    sponsorStatus.className =
                        "verification-status status-invalid";
                }
                window.sponsorValidationStatus.isValid = false;
                window.sponsorValidationStatus.lastChecked = "";
                sponsorInfoBanner?.classList.add("d-none");
            }
        }

        checkPinBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            const pin = pinInput?.value.trim();
            if (!pin) {
                pinInput?.focus();
                return;
            }
            checkPinActivation(pin);
        });
        pinInput?.addEventListener("input", function () {
            resetPinStatus();
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
        });
        pinInput?.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                checkPinBtn?.click();
            }
        });

        checkSponsorBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            const s = sponsorInput?.value.trim();
            if (!s) {
                sponsorInput?.focus();
                return;
            }
            checkSponsorCode(s);
        });
        sponsorInput?.addEventListener("input", function () {
            resetSponsorStatus();
            this.value = this.value.replace(/[^A-Za-z0-9]/g, "");
            const refInput = qs("#ref");
            if (refInput) refInput.value = this.value;
        });
        sponsorInput?.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                checkSponsorBtn?.click();
            }
        });

        document.addEventListener("DOMContentLoaded", () => {
            const urlParams = new URLSearchParams(window.location.search);
            const ref = urlParams.get("ref");
            if (ref && sponsorInput) {
                sponsorInput.value = ref;
                const refInput = qs("#ref");
                if (refInput) refInput.value = ref;
                setTimeout(() => checkSponsorCode(ref), 500);
            }
            const sv = sponsorInput?.value.trim();
            if (sv) setTimeout(() => checkSponsorCode(sv), 500);
        });

        window.isPinValid = () => {
            const pin = pinInput?.value.trim();
            return isPinValid && pin === lastCheckedPin;
        };
        window.isSponsorValid = () => {
            const s = sponsorInput?.value.trim();
            return isSponsorValid && s === lastCheckedSponsor;
        };

        // === WhatsApp
        const phoneInput = qs("#no_telp");
        const checkWhatsAppBtn = qs("#checkWhatsApp");
        const checkWhatsAppText = qs("#checkWhatsAppText");
        const checkWhatsAppSpinner = qs("#checkWhatsAppSpinner");
        const phoneFeedback = qs("#phoneFeedback");
        const phoneValidFeedback = qs("#phoneValidFeedback");
        const whatsappStatus = qs("#whatsappStatus");

        let _isPhoneValid = false,
            lastCheckedPhone = "",
            waTimeout;

        const formatPhone = (v) => {
            let cleaned = v.replace(/\D/g, "");
            if (cleaned.startsWith("62")) return "+" + cleaned;
            if (cleaned.startsWith("0")) return "+62" + cleaned.substring(1);
            if (cleaned.startsWith("8")) return "+628" + cleaned.substring(1);
            return cleaned ? "+62" + cleaned : cleaned;
        };
        const validatePhoneFormat = (v) => {
            const cleaned = v.replace(/\D/g, "");
            if (cleaned.startsWith("62"))
                return cleaned.length >= 12 && cleaned.length <= 15;
            if (cleaned.startsWith("0"))
                return cleaned.length >= 10 && cleaned.length <= 13;
            if (cleaned.startsWith("8"))
                return cleaned.length >= 9 && cleaned.length <= 12;
            return false;
        };
        const showWACheck = () => {
            checkWhatsAppBtn?.classList.add("btn-checking");
            if (checkWhatsAppText)
                checkWhatsAppText.textContent = "Mengecek...";
            checkWhatsAppSpinner?.classList.remove("d-none");
            if (whatsappStatus) {
                whatsappStatus.innerHTML =
                    '<i class="fab fa-whatsapp fa-spin"></i> Memverifikasi WhatsApp...';
                whatsappStatus.className = "whatsapp-status whatsapp-checking";
            }
        };
        const hideWACheck = () => {
            checkWhatsAppBtn?.classList.remove("btn-checking");
            if (checkWhatsAppText) checkWhatsAppText.textContent = "Cek WA";
            checkWhatsAppSpinner?.classList.add("d-none");
        };
        const resetPhone = () => {
            phoneInput?.classList.remove("is-valid", "is-invalid");
            if (phoneFeedback) phoneFeedback.textContent = "";
            phoneValidFeedback?.classList.add("d-none");
            if (whatsappStatus) whatsappStatus.textContent = "";
            _isPhoneValid = false;
        };

        async function checkWhatsAppAvailability(no_telp) {
            const formatted = formatPhone(no_telp);
            if (!validatePhoneFormat(no_telp)) {
                phoneInput?.classList.add("is-invalid");
                if (phoneFeedback)
                    phoneFeedback.textContent =
                        "Format nomor HP tidak valid untuk Indonesia";
                if (whatsappStatus) {
                    whatsappStatus.innerHTML =
                        '<i class="fas fa-times-circle"></i> Format tidak valid';
                    whatsappStatus.className =
                        "whatsapp-status whatsapp-invalid";
                }
                return false;
            }
            showWACheck();
            try {
                const fd = new FormData();
                fd.append("no_telp", formatted);
                const csrf = qs(
                    '#ref-register-form input[name="_token"]',
                )?.value;
                if (csrf) fd.append("_token", csrf);
                const resp = await fetch(R.checkWhatsApp, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: fd,
                });
                const data = await resp.json();
                hideWACheck();
                if (resp.ok) {
                    if (data.valid) {
                        phoneInput?.classList.remove("is-invalid");
                        phoneInput?.classList.add("is-valid");
                        phoneValidFeedback?.classList.remove("d-none");
                        if (whatsappStatus) {
                            let txt =
                                '<i class="fab fa-whatsapp"></i> WhatsApp aktif!';
                            if (data.info)
                                txt += `<br><small>${data.info}</small>`;
                            whatsappStatus.innerHTML = txt;
                            whatsappStatus.className =
                                "whatsapp-status whatsapp-valid";
                        }
                        _isPhoneValid = true;
                        lastCheckedPhone = formatted;
                        if (phoneInput) phoneInput.value = formatted;
                    } else {
                        phoneInput?.classList.remove("is-valid");
                        phoneInput?.classList.add("is-invalid");
                        if (phoneFeedback)
                            phoneFeedback.textContent =
                                data.message ||
                                "Nomor WhatsApp tidak ditemukan atau tidak aktif";
                        if (whatsappStatus) {
                            const warn = data.reason === "not_whatsapp";
                            whatsappStatus.innerHTML = `${warn ? '<i class="fas fa-exclamation-triangle"></i>' : '<i class="fas fa-times-circle"></i>'} ${data.message || "WhatsApp tidak ditemukan"}`;
                            whatsappStatus.className = `whatsapp-status ${warn ? "whatsapp-warning" : "whatsapp-invalid"}`;
                        }
                        _isPhoneValid = false;
                    }
                } else {
                    throw new Error(
                        data.message ||
                            "Terjadi kesalahan saat memverifikasi WhatsApp",
                    );
                }
            } catch (err) {
                hideWACheck();
                console.error("Error checking WhatsApp:", err);
                phoneInput?.classList.add("is-invalid");
                if (phoneFeedback)
                    phoneFeedback.textContent =
                        "Terjadi kesalahan saat memverifikasi WhatsApp. Silakan coba lagi.";
                if (whatsappStatus) {
                    whatsappStatus.innerHTML =
                        '<i class="fas fa-exclamation-triangle"></i> Error verifikasi';
                    whatsappStatus.className =
                        "whatsapp-status whatsapp-invalid";
                }
                _isPhoneValid = false;
                toast(
                    "error",
                    "Gagal memverifikasi WhatsApp. Silakan coba lagi.",
                );
            }
        }

        checkWhatsAppBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            const v = phoneInput?.value.trim();
            if (!v) {
                phoneInput?.focus();
                return;
            }
            checkWhatsAppAvailability(v);
        });
        phoneInput?.addEventListener("input", function () {
            clearTimeout(waTimeout);
            resetPhone();
            let value = this.value;
            if (value.startsWith("+"))
                value = "+" + value.substring(1).replace(/\D/g, "");
            else value = value.replace(/\D/g, "");
            this.value = value;
            if (value && !validatePhoneFormat(value)) {
                this.classList.add("is-invalid");
                if (phoneFeedback)
                    phoneFeedback.textContent =
                        "Format: 08xxx atau +62xxx (10-13 digit)";
            }
            if (value && validatePhoneFormat(value)) {
                waTimeout = setTimeout(
                    () => checkWhatsAppAvailability(value),
                    2000,
                );
            }
        });
        phoneInput?.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                checkWhatsAppBtn?.click();
            }
            const char = String.fromCharCode(e.which || e.keyCode);
            if (
                !/[0-9+]/.test(char) &&
                !["Backspace", "Delete", "Tab", "Enter"].includes(e.key)
            )
                e.preventDefault();
        });
        phoneInput?.addEventListener("blur", function () {
            const v = this.value.trim();
            if (v && validatePhoneFormat(v)) this.value = formatPhone(v);
        });

        window.isPhoneValid = () => {
            const v = phoneInput?.value.trim() || "";
            const formatted = v ? formatPhone(v) : v;
            return _isPhoneValid && formatted === lastCheckedPhone;
        };

        // WA link helpers
        window.generateWhatsAppLink = function (no_telp, message = "") {
            const clean = no_telp.replace(/\D/g, "");
            const formatted = clean.startsWith("62")
                ? clean
                : "62" + clean.substring(1);
            const encoded = encodeURIComponent(message);
            return `https://wa.me/${formatted}${message ? "?text=" + encoded : ""}`;
        };
        window.testWhatsApp = function (no_telp) {
            const link = window.generateWhatsAppLink(
                no_telp,
                "Test pesan dari sistem registrasi",
            );
            window.open(link, "_blank");
        };
    })();

    // ===== Global binds (duplikasi aman)
    document.addEventListener("click", (e) => {
        if (e.target?.id === "btnPreview") previewCloneCandidates();
    });
    document.addEventListener("submit", (e) => {
        if (e.target?.id === "cloneForm") submitCloneForm(e);
    });

    // Expose bila dibutuhkan
    window.previewCloneCandidates = previewCloneCandidates;
    window.submitCloneForm = submitCloneForm;
})();
