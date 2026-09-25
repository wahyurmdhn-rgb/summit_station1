document.addEventListener('DOMContentLoaded', () => {
    /* ---------- Konstanta alur ---------- */
    const MIN_RENTAL_AGE = 17;
    const PARENT_ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'pdf'];
    const PARENT_MIME = ['image/jpeg', 'image/png', 'application/pdf'];
    const PARENT_MAX_BYTES = 5 * 1024 * 1024; // 5MB

    /* ---------- Toggle visibility password (login & register) ---------- */
    document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
        const input = document.getElementById(btn.dataset.passwordToggle);
        if (!input) return;
        btn.addEventListener('click', () => {
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            btn.classList.toggle('is-visible', !isVisible);
            btn.setAttribute('aria-pressed', String(!isVisible));
            btn.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
        });
    });

    const form = document.querySelector('[data-register-form]');
    const createButton = document.querySelector('[data-create-account]');
    const termsModal = document.querySelector('[data-terms-modal]');
    const parentModal = document.querySelector('[data-parent-modal]');
    const ktpModal = document.querySelector('[data-ktp-modal]');
    const accept = document.querySelector('[data-accept-expedition]');
    const proceed = document.querySelector('[data-proceed-ktp]');
    const complete = document.querySelector('[data-complete-register]');
    let confirmedFlow = false;
    let flowNeedsConsent = false;

    if (!form || !createButton) return;

    /* ---------- Helper modal dengan animasi ---------- */
    const allModals = [termsModal, parentModal, ktpModal];

    const openModal = (modal) => {
        if (!modal) return;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => requestAnimationFrame(() => {
            if (modal === ktpModal) {
                prepareKtpModal();
            }
            modal.classList.add('is-open');
            if (modal === termsModal) {
                updateReadingProgress();
            }
            const stepEl = modal.querySelector('[data-step-indicator]');
            if (stepEl) {
                if (modal === ktpModal) {
                    stepEl.textContent = flowNeedsConsent ? 'Langkah 3 dari 3' : 'Langkah 2 dari 2';
                } else if (modal === parentModal) {
                    stepEl.textContent = 'Langkah 2 dari 3';
                }
            }
        }));
    };

    const closeModal = (modal) => {
        if (!modal || modal.hidden) return;
        modal.classList.remove('is-open');
        setTimeout(() => {
            modal.hidden = true;
            if (!document.querySelector('.expedition-overlay:not([hidden])')) {
                document.body.style.overflow = '';
            }
        }, 240);
        if (modal === ktpModal) {
            revokeObjectURL();
        }
    };

    const anyOpen = () => allModals.some((m) => m && !m.hidden);

    /* ---------- Hitung umur dari tanggal lahir ---------- */
    const dobInput = document.getElementById('reg_dob');
    const ageHint = document.querySelector('[data-age-hint]');

    function computeAge(dobValue) {
        if (!dobValue) return null;
        const dob = new Date(dobValue + 'T00:00:00');
        if (Number.isNaN(dob.getTime())) return null;
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) age--;
        return age >= 0 ? age : null;
    }

    function updateAgeHint() {
        if (!dobInput || !ageHint) return;
        const age = computeAge(dobInput.value);
        if (age === null) {
            ageHint.textContent = 'Umur Anda dihitung otomatis dari tanggal lahir.';
            ageHint.className = 'age-hint';
            return;
        }
        if (age < MIN_RENTAL_AGE) {
            ageHint.textContent = `Umur Anda ${age} tahun. Karena di bawah ${MIN_RENTAL_AGE}, registrasi memerlukan persetujuan orang tua / wali.`;
            ageHint.className = 'age-hint is-minor';
        } else {
            ageHint.textContent = `Umur Anda ${age} tahun. Tidak memerlukan persetujuan orang tua.`;
            ageHint.className = 'age-hint is-ok';
        }
    }

    dobInput?.addEventListener('input', updateAgeHint);

    /* ---------- Submit form: buka modal terms ---------- */
    form.addEventListener('submit', (event) => {
        if (confirmedFlow) return;
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const age = dobInput ? computeAge(dobInput.value) : null;
        flowNeedsConsent = age !== null && age < MIN_RENTAL_AGE;

        openModal(termsModal);
    });

    /* ---------- Modal Terms: progress baca & interaksi ---------- */
    const termsScroll = document.querySelector('[data-terms-scroll]');
    const progressBar = document.querySelector('[data-terms-progress-bar]');
    const percentBadge = document.querySelector('[data-terms-percent]');
    const completionBanner = document.querySelector('[data-terms-completion-banner]');
    const hint = document.querySelector('[data-terms-hint]');
    const acceptRow = document.querySelector('[data-accept-row]');
    let hasReadTerms = false;

    const READ_THRESHOLD = 0.92;

    function updateReadingProgress() {
        if (!termsScroll || !progressBar) return;
        const maxScroll = termsScroll.scrollHeight - termsScroll.clientHeight;
        const ratio = maxScroll > 0 ? Math.min(Math.max(termsScroll.scrollTop / maxScroll, 0), 1) : 1;
        const percent = Math.round(ratio * 100);

        progressBar.style.width = percent + '%';
        if (percentBadge) {
            percentBadge.textContent = percent + '%';
            if (percent >= 92) {
                percentBadge.classList.add('is-complete');
            } else {
                percentBadge.classList.remove('is-complete');
            }
        }

        if (ratio >= READ_THRESHOLD) {
            completionBanner?.classList.add('is-visible');
        }

        if (!hasReadTerms && ratio >= READ_THRESHOLD) {
            hasReadTerms = true;
            hint?.classList.add('is-read');
            hint.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg> Selesai membaca! Centang persetujuan di bawah.';
            acceptRow?.classList.add('is-read');
            acceptRow?.classList.add('is-nudge');
            acceptRow?.addEventListener('animationend', () => acceptRow.classList.remove('is-nudge'), { once: true });
        }
    }

    termsScroll?.addEventListener('scroll', updateReadingProgress, { passive: true });

    document.querySelector('[data-open-terms]')?.addEventListener('click', (event) => {
        event.preventDefault();
        openModal(termsModal);
    });

    document.querySelector('[data-close-terms]')?.addEventListener('click', () => {
        closeModal(termsModal);
    });

    document.querySelector('[data-close-parent]')?.addEventListener('click', () => {
        closeModal(parentModal);
    });

    // Klik area gelap di luar kartu menutup modal
    allModals.forEach((modal) => {
        modal?.addEventListener('pointerdown', (event) => {
            if (event.target === modal) closeModal(modal);
        });
    });

    // Tombol Escape menutup modal teratas yang terbuka
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        for (const modal of allModals) {
            if (modal && !modal.hidden) {
                closeModal(modal);
                return;
            }
        }
    });

    /* ---------- Persetujuan & lanjut ke upload KTP ---------- */
    accept?.addEventListener('change', () => {
        proceed.disabled = !accept.checked;
        if (accept.checked) hint?.classList.add('is-hidden');
        else hint?.classList.remove('is-hidden');
    });

    // PROCEED diklik saat masih disabled: getarkan + soroti persetujuan
    proceed?.addEventListener('pointerdown', () => {
        if (!proceed.disabled) return;
        proceed.classList.remove('shake');
        void proceed.offsetWidth;
        proceed.classList.add('shake');
        acceptRow?.classList.remove('is-nudge');
        void acceptRow?.offsetWidth;
        acceptRow?.classList.add('is-nudge');
        acceptRow?.addEventListener('animationend', () => acceptRow.classList.remove('is-nudge'), { once: true });
    });

    proceed?.addEventListener('click', () => {
        if (proceed.disabled) return;
        closeModal(termsModal);
        const next = flowNeedsConsent ? parentModal : ktpModal;
        setTimeout(() => openModal(next), 200);
    });

    /* ---------- Upload KTP (17+) & Dokumen Pendukung (< 17) ---------- */
    const adultBlock = document.querySelector('[data-ktp-adult]');
    const minorBlock = document.querySelector('[data-ktp-minor]');
    const ktpFile = document.querySelector('[data-ktp-file]');
    const ktpPreview = adultBlock ? adultBlock.querySelector('[data-ktp-preview]') : null;
    const ktpPreviewImg = adultBlock ? adultBlock.querySelector('[data-ktp-preview-img]') : null;
    const ktpPreviewName = adultBlock ? adultBlock.querySelector('[data-ktp-preview-name]') : null;
    const ktpPreviewRemove = adultBlock ? adultBlock.querySelector('[data-ktp-preview-remove]') : null;
    const ktpUploadBox = adultBlock ? adultBlock.querySelector('.upload-box') : null;
    const uploadFile = adultBlock ? adultBlock.querySelector('[data-upload-file]') : null;
    let ktpFileReady = false;
    const DOC_ALLOWED_EXT = ['jpg', 'jpeg', 'png'];
    const KTP_MAX_BYTES = 10 * 1024 * 1024; // 10MB
    let currentObjectURL = null;

    function revokeObjectURL() {
        if (currentObjectURL) {
            URL.revokeObjectURL(currentObjectURL);
            currentObjectURL = null;
        }
    }

    function formatBytes(bytes) {
        if (!bytes) return '0 KB';
        const mb = bytes / (1024 * 1024);
        return mb >= 1 ? mb.toFixed(2) + ' MB' : Math.round(bytes / 1024) + ' KB';
    }

    function showKtpError(message) {
        if (ktpPreview) ktpPreview.hidden = true;
        if (ktpUploadBox) ktpUploadBox.style.display = '';
        if (uploadFile) {
            uploadFile.textContent = message;
            uploadFile.style.color = '#c0392b';
        }
        ktpFileReady = false;
        updateComplete();
    }

    function showKtpPreview(file) {
        if (!file || !ktpPreview || !ktpPreviewImg || !ktpPreviewName) return;

        revokeObjectURL();

        const ext = (file.name.split('.').pop() || '').toLowerCase();
        if (!DOC_ALLOWED_EXT.includes(ext) || !file.type.startsWith('image/')) {
            showKtpError('Format file tidak didukung. Pilih file gambar (JPEG/PNG).');
            return;
        }
        if (file.size > KTP_MAX_BYTES) {
            showKtpError('Ukuran file melebihi 10MB. Pilih file yang lebih kecil.');
            return;
        }

        currentObjectURL = URL.createObjectURL(file);
        ktpPreviewImg.src = currentObjectURL;
        ktpPreviewName.textContent = file.name;
        ktpPreview.hidden = false;
        if (uploadFile) {
            uploadFile.textContent = '';
            uploadFile.style.color = '';
        }
        if (ktpUploadBox) ktpUploadBox.style.display = 'none';
        ktpFileReady = true;
        updateComplete();
    }

    function clearKtp() {
        revokeObjectURL();
        if (ktpPreview) ktpPreview.hidden = true;
        if (ktpPreviewImg) ktpPreviewImg.src = '';
        if (ktpPreviewName) ktpPreviewName.textContent = '';
        if (ktpUploadBox) ktpUploadBox.style.display = '';
        if (uploadFile) {
            uploadFile.textContent = '';
            uploadFile.style.color = '';
        }
        ktpFileReady = false;
        updateComplete();
    }

    ktpFile?.addEventListener('change', () => {
        const file = ktpFile.files[0];
        if (file) {
            showKtpPreview(file);
        } else {
            clearKtp();
        }
    });

    ktpPreviewRemove?.addEventListener('click', () => {
        if (ktpFile) ktpFile.value = '';
        clearKtp();
    });

    // Drag & drop untuk upload KTP user (17+)
    if (ktpUploadBox) {
        ktpUploadBox.addEventListener('dragover', (e) => {
            e.preventDefault();
            ktpUploadBox.classList.add('is-dragover');
        });
        ['dragleave', 'drop'].forEach((evt) => {
            ktpUploadBox.addEventListener(evt, (e) => {
                e.preventDefault();
                ktpUploadBox.classList.remove('is-dragover');
            });
        });
        ktpUploadBox.addEventListener('drop', (e) => {
            const file = e.dataTransfer?.files?.[0];
            if (file && ktpFile) {
                const dt = new DataTransfer();
                dt.items.add(file);
                ktpFile.files = dt.files;
                showKtpPreview(file);
            }
        });
    }

    /* ---------- Dokumen pendukung user < 17 (KTP orang tua + kartu pelajar) ---------- */
    const docSections = minorBlock ? Array.from(minorBlock.querySelectorAll('[data-doc-section]')) : [];
    const docHandlers = {};

    function capitalizeLabel(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function wireDocSection(section) {
        const input = section.querySelector('[data-doc-file]');
        if (!input) return;
        const key = input.name;
        const refs = {
            preview: section.querySelector('[data-doc-preview]'),
            previewImg: section.querySelector('[data-doc-preview-img]'),
            nameEl: section.querySelector('[data-doc-name]'),
            sizeEl: section.querySelector('[data-doc-size]'),
            removeBtn: section.querySelector('[data-doc-remove]'),
            progress: section.querySelector('[data-doc-progress]'),
            progressBar: section.querySelector('[data-doc-progress-bar]'),
            statusEl: section.querySelector('[data-doc-status]'),
            okBadge: section.querySelector('[data-doc-ok]'),
            uploadBox: section.querySelector('.upload-box'),
        };
        const state = { file: null, url: null, timer: null };
        docHandlers[key] = { state, refs };

        const docLabel = key === 'ktp_orang_tua' ? 'KTP orang tua' : 'kartu pelajar';

        function setDocError(message) {
            state.file = null;
            if (refs.preview) refs.preview.hidden = true;
            if (refs.uploadBox) refs.uploadBox.style.display = '';
            if (refs.progress) {
                refs.progress.hidden = true;
                if (refs.progressBar) refs.progressBar.style.width = '0%';
            }
            if (refs.statusEl) {
                refs.statusEl.textContent = '✕ ' + message;
                refs.statusEl.style.color = '#c0392b';
            }
            if (refs.okBadge) refs.okBadge.hidden = true;
            updateComplete();
        }

        function clearDoc() {
            state.file = null;
            if (state.url) {
                URL.revokeObjectURL(state.url);
                state.url = null;
            }
            if (refs.preview) refs.preview.hidden = true;
            if (refs.previewImg) refs.previewImg.src = '';
            if (refs.nameEl) refs.nameEl.textContent = '';
            if (refs.sizeEl) refs.sizeEl.textContent = '';
            if (refs.uploadBox) refs.uploadBox.style.display = '';
            if (refs.progress) {
                refs.progress.hidden = true;
                if (refs.progressBar) {
                    refs.progressBar.style.width = '0%';
                    refs.progressBar.classList.remove('is-success');
                }
            }
            if (refs.statusEl) {
                refs.statusEl.textContent = '';
                refs.statusEl.style.color = '';
            }
            if (refs.okBadge) refs.okBadge.hidden = true;
            if (input) input.value = '';
            updateComplete();
        }

        function playDocProgress() {
            if (!refs.progress || !refs.progressBar) {
                if (refs.okBadge) refs.okBadge.hidden = false;
                updateComplete();
                return;
            }
            refs.progress.hidden = false;
            refs.progressBar.classList.remove('is-success');
            refs.progressBar.style.width = '0%';
            let value = 0;
            clearInterval(state.timer);
            state.timer = setInterval(() => {
                value = Math.min(value + Math.random() * 18 + 8, 100);
                refs.progressBar.style.width = value + '%';
                if (value >= 100) {
                    clearInterval(state.timer);
                    state.timer = null;
                    refs.progressBar.classList.add('is-success');
                    if (refs.statusEl) {
                        refs.statusEl.textContent = '✓ ' + capitalizeLabel(docLabel) + ' siap digunakan';
                        refs.statusEl.style.color = '#1e7a3a';
                    }
                    if (refs.okBadge) refs.okBadge.hidden = false;
                    updateComplete();
                }
            }, 90);
        }

        function acceptDocFile(file) {
            if (!file) return;
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (!DOC_ALLOWED_EXT.includes(ext) || !file.type.startsWith('image/')) {
                setDocError('Format tidak didukung. Gunakan file gambar JPG, JPEG, atau PNG.');
                return;
            }
            if (file.size > KTP_MAX_BYTES) {
                setDocError('Ukuran file melebihi 10MB. Gunakan file yang lebih kecil.');
                return;
            }

            if (state.url) {
                URL.revokeObjectURL(state.url);
                state.url = null;
            }
            state.url = URL.createObjectURL(file);
            state.file = file;

            if (refs.preview) refs.preview.hidden = false;
            if (refs.previewImg) refs.previewImg.src = state.url;
            if (refs.nameEl) refs.nameEl.textContent = file.name;
            if (refs.sizeEl) refs.sizeEl.textContent = formatBytes(file.size);
            if (refs.uploadBox) refs.uploadBox.style.display = 'none';
            if (refs.statusEl) {
                refs.statusEl.textContent = '';
                refs.statusEl.style.color = '';
            }
            playDocProgress();
        }

        input.addEventListener('change', () => {
            const file = input.files[0];
            if (file) acceptDocFile(file);
            else clearDoc();
        });

        refs.removeBtn?.addEventListener('click', () => {
            if (input) input.value = '';
            clearDoc();
        });

        // Drag & drop untuk tiap dokumen pendukung
        if (refs.uploadBox) {
            refs.uploadBox.addEventListener('dragover', (e) => {
                e.preventDefault();
                refs.uploadBox.classList.add('is-dragover');
            });
            ['dragleave', 'drop'].forEach((evt) => {
                refs.uploadBox.addEventListener(evt, (e) => {
                    e.preventDefault();
                    refs.uploadBox.classList.remove('is-dragover');
                });
            });
            refs.uploadBox.addEventListener('drop', (e) => {
                const file = e.dataTransfer?.files?.[0];
                if (file) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                    acceptDocFile(file);
                }
            });
        }
    }

    docSections.forEach(wireDocSection);

    /* ---------- Keaktifan tombol SELESAI (stabil, tanpa perubahan layout) ---------- */
    function updateComplete() {
        if (!complete) return;
        let ready = false;
        if (flowNeedsConsent) {
            ready = docSections.length > 0 && docSections.every((section) => {
                const input = section.querySelector('[data-doc-file]');
                return input && docHandlers[input.name] && docHandlers[input.name].state.file !== null;
            });
        } else {
            ready = ktpFileReady;
        }
        complete.disabled = !ready;
    }

    function resetKtpFlow() {
        revokeObjectURL();
        ktpFileReady = false;
        if (ktpFile) ktpFile.value = '';
        if (ktpPreview) ktpPreview.hidden = true;
        if (ktpPreviewImg) ktpPreviewImg.src = '';
        if (ktpPreviewName) ktpPreviewName.textContent = '';
        if (ktpUploadBox) ktpUploadBox.style.display = '';
        if (uploadFile) {
            uploadFile.textContent = '';
            uploadFile.style.color = '';
        }

        docSections.forEach((section) => {
            const input = section.querySelector('[data-doc-file]');
            if (input && docHandlers[input.name]) {
                const { state, refs } = docHandlers[input.name];
                state.file = null;
                if (state.url) {
                    URL.revokeObjectURL(state.url);
                    state.url = null;
                }
                if (state.timer) {
                    clearInterval(state.timer);
                    state.timer = null;
                }
                input.value = '';
                if (refs.preview) refs.preview.hidden = true;
                if (refs.previewImg) refs.previewImg.src = '';
                if (refs.nameEl) refs.nameEl.textContent = '';
                if (refs.sizeEl) refs.sizeEl.textContent = '';
                if (refs.uploadBox) refs.uploadBox.style.display = '';
                if (refs.progress) {
                    refs.progress.hidden = true;
                    if (refs.progressBar) {
                        refs.progressBar.style.width = '0%';
                        refs.progressBar.classList.remove('is-success');
                    }
                }
                if (refs.statusEl) {
                    refs.statusEl.textContent = '';
                    refs.statusEl.style.color = '';
                }
                if (refs.okBadge) refs.okBadge.hidden = true;
            }
        });

        if (complete) {
            complete.disabled = true;
            complete.classList.remove('is-loading');
            complete.dataset.submitting = '0';
        }
    }

    function prepareKtpModal() {
        if (!ktpModal) return;
        if (adultBlock) adultBlock.hidden = flowNeedsConsent;
        if (minorBlock) minorBlock.hidden = !flowNeedsConsent;
        resetKtpFlow();
    }

    /* ---------- Upload Bukti Persetujuan Orang Tua ---------- */
    const parentName = document.querySelector('[data-parent-field][name="parent_name"]');
    const parentRelation = document.querySelector('[data-parent-field][name="parent_relation"]');
    const parentPhone = document.querySelector('[data-parent-field][name="parent_phone"]');
    const parentRead = document.querySelector('[data-parent-read]');
    const parentConsentAccepted = document.querySelector('[data-parent-consent-accepted]');
    const parentReadRow = document.querySelector('[data-parent-read-row]');
    const parentConsentRow = document.querySelector('[data-parent-consent-row]');
    const parentProof = document.querySelector('[data-parent-proof]');
    const parentUploadBox = document.querySelector('[data-parent-upload-box]');
    const parentPreview = document.querySelector('[data-parent-proof-preview]');
    const parentThumb = document.querySelector('[data-parent-proof-thumb]');
    const parentProofName = document.querySelector('[data-parent-proof-name]');
    const parentProofSize = document.querySelector('[data-parent-proof-size]');
    const parentProgress = document.querySelector('[data-parent-proof-progress]');
    const parentProgressBar = document.querySelector('[data-parent-proof-bar]');
    const parentProofStatus = document.querySelector('[data-parent-proof-status]');
    const parentFileError = document.querySelector('[data-parent-file-error]');
    const parentProceed = document.querySelector('[data-parent-proceed]');
    let validParentFile = null;
    let parentThumbUrl = null;
    let parentProgressTimer = null;

    function revokeParentThumb() {
        if (parentThumbUrl) {
            URL.revokeObjectURL(parentThumbUrl);
            parentThumbUrl = null;
        }
    }

    function validateParentForm() {
        if (!parentProceed) return;
        const ready =
            parentName && parentName.value.trim() !== '' &&
            parentRelation && parentRelation.value !== '' &&
            parentPhone && parentPhone.value.trim() !== '' &&
            parentRead && parentRead.checked &&
            parentConsentAccepted && parentConsentAccepted.checked &&
            validParentFile !== null;
        parentProceed.disabled = !ready;
    }

    function setParentFileError(message) {
        validParentFile = null;
        if (parentPreview) parentPreview.hidden = true;
        if (parentProgress) parentProgress.style.display = '';
        if (parentProofStatus) {
            parentProofStatus.textContent = '✕ ' + message;
            parentProofStatus.className = 'parent-proof-status is-error';
        }
        validateParentForm();
    }

    function resetParentProofState() {
        revokeParentThumb();
        validParentFile = null;
        parentPreview.hidden = true;
        parentUploadBox.style.display = '';
        if (parentProofStatus) {
            parentProofStatus.textContent = '';
            parentProofStatus.className = 'parent-proof-status';
        }
        if (parentProgress) {
            parentProgress.style.display = '';
            if (parentProgressBar) parentProgressBar.style.width = '0%';
            parentProgressBar?.classList.remove('is-success');
        }
        if (parentFileError) parentFileError.hidden = true;
        validateParentForm();
    }

    function playParentProgress() {
        if (!parentProgress || !parentProgressBar) return;
        parentProgress.style.display = '';
        parentProgressBar.classList.remove('is-success');
        parentProgressBar.style.width = '0%';
        let value = 0;
        clearInterval(parentProgressTimer);
        parentProgressTimer = setInterval(() => {
            value = Math.min(value + Math.random() * 18 + 8, 100);
            parentProgressBar.style.width = value + '%';
            if (value >= 100) {
                clearInterval(parentProgressTimer);
                parentProgressBar.classList.add('is-success');
                if (parentProofStatus) {
                    parentProofStatus.textContent = '✓ Bukti siap diunggah';
                    parentProofStatus.className = 'parent-proof-status is-success';
                }
            }
        }, 90);
    }

    function showParentPreview(file) {
        revokeParentThumb();
        parentPreview.hidden = false;
        parentUploadBox.style.display = 'none';
        if (parentProofName) parentProofName.textContent = file.name;
        if (parentProofSize) parentProofSize.textContent = formatBytes(file.size);
        if (parentFileError) {
            parentFileError.hidden = true;
            parentFileError.textContent = '';
        }

        if (file.type === 'application/pdf') {
            if (parentThumb) {
                parentThumb.innerHTML = '<span class="pdf-badge-thumb">PDF</span>';
            }
        } else {
            parentThumbUrl = URL.createObjectURL(file);
            if (parentThumb) {
                parentThumb.innerHTML = '';
                const img = document.createElement('img');
                img.src = parentThumbUrl;
                img.alt = 'Pratinjau bukti';
                parentThumb.appendChild(img);
            }
        }

        playParentProgress();
    }

    parentProof?.addEventListener('change', () => {
        const file = parentProof.files[0];
        if (!file) {
            resetParentProofState();
            return;
        }

        const ext = (file.name.split('.').pop() || '').toLowerCase();
        if (!PARENT_ALLOWED_EXT.includes(ext) || !PARENT_MIME.includes(file.type)) {
            setParentFileError('Format tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF.');
            return;
        }
        if (file.size > PARENT_MAX_BYTES) {
            setParentFileError('Ukuran file melebihi 5MB. Gunakan file yang lebih kecil.');
            return;
        }

        validParentFile = file;
        showParentPreview(file);
        validateParentForm();
    });

    parentProof?.addEventListener('click', () => {
        parentFileError.hidden = true;
    });

    document.querySelector('[data-parent-proof-remove]')?.addEventListener('click', () => {
        if (parentProof) parentProof.value = '';
        resetParentProofState();
    });

    // Drag & drop untuk bukti persetujuan
    parentUploadBox?.addEventListener('dragover', (e) => {
        e.preventDefault();
        parentUploadBox.classList.add('is-dragover');
    });
    ['dragleave', 'drop'].forEach((evt) => {
        parentUploadBox?.addEventListener(evt, (e) => {
            e.preventDefault();
            parentUploadBox.classList.remove('is-dragover');
        });
    });
    parentUploadBox?.addEventListener('drop', (e) => {
        const file = e.dataTransfer?.files?.[0];
        if (file && parentProof) {
            const dt = new DataTransfer();
            dt.items.add(file);
            parentProof.files = dt.files;
            parentProof.dispatchEvent(new Event('change'));
        }
    });

    // Keaktifan tombol lanjut = semua data wali lengkap & valid
    [parentName, parentRelation, parentPhone].forEach((field) => {
        field?.addEventListener('input', validateParentForm);
    });
    [parentRead, parentConsentAccepted].forEach((box) => {
        box?.addEventListener('change', validateParentForm);
    });

    // PROCEED disabled diklik: getarkan row terkait
    parentProceed?.addEventListener('pointerdown', () => {
        if (!parentProceed.disabled) return;
        parentProceed.classList.remove('shake');
        void parentProceed.offsetWidth;
        parentProceed.classList.add('shake');
        const row = validParentFile === null ? parentUploadBox : (parentRead && !parentRead.checked ? parentReadRow : parentConsentRow);
        if (row) {
            row.classList.remove('is-nudge');
            void row.offsetWidth;
            row.classList.add('is-nudge');
            row.addEventListener('animationend', () => row.classList.remove('is-nudge'), { once: true });
        }
    });

    parentProceed?.addEventListener('click', () => {
        if (parentProceed.disabled || parentProceed.dataset.submitting === '1') return;
        parentProceed.dataset.submitting = '1';
        parentProceed.classList.add('is-loading');
        parentProceed.disabled = true;
        setTimeout(() => {
            parentProceed.dataset.submitting = '0';
            parentProceed.classList.remove('is-loading');
            closeModal(parentModal);
            setTimeout(() => openModal(ktpModal), 200);
        }, 350);
    });

    /* ---------- Selesai: pindahkan input & kirim form ---------- */
    complete?.addEventListener('click', () => {
        if (complete.disabled || complete.dataset.submitting === '1') return;
        complete.dataset.submitting = '1';
        complete.disabled = true;
        complete.classList.add('is-loading');

        // Data orang tua/wali (hanya jika di bawah umur)
        if (flowNeedsConsent) {
            const hidden = (name, value) => {
                const el = document.createElement('input');
                el.type = 'hidden';
                el.name = name;
                el.value = value;
                form.appendChild(el);
            };
            if (parentName) hidden('parent_name', parentName.value.trim());
            if (parentRelation) hidden('parent_relation', parentRelation.value);
            if (parentPhone) hidden('parent_phone', parentPhone.value.trim());
            if (parentConsentAccepted && parentConsentAccepted.checked) hidden('parent_consent_accepted', '1');

            // Input file bukti ada di modal (di luar <form>), pindahkan ke form
            if (parentProof && !form.contains(parentProof)) {
                parentProof.hidden = true;
                form.appendChild(parentProof);
            }

            // KTP orang tua + kartu pelajar (wajib untuk user < 17)
            docSections.forEach((section) => {
                const docInput = section.querySelector('[data-doc-file]');
                if (docInput && !form.contains(docInput)) {
                    docInput.hidden = true;
                    form.appendChild(docInput);
                }
            });
        } else if (ktpFile && !form.contains(ktpFile)) {
            // Input file KTP user sendiri ada di modal (di luar <form>), pindahkan ke form
            ktpFile.hidden = true;
            form.appendChild(ktpFile);
        }

        confirmedFlow = true;
        closeModal(ktpModal);
        setTimeout(() => form.submit(), 150);
    });

    /* ---------- Password Strength & Live Match Validation ---------- */
    const pwdInput = document.getElementById('password');
    const pwdConfirmInput = document.getElementById('password_confirmation');
    const pwdStrengthBar = document.querySelector('[data-pwd-strength-bar]');
    const pwdStrengthText = document.querySelector('[data-pwd-strength-text]');
    const pwdMatchHint = document.querySelector('[data-pwd-match-hint]');

    function checkPasswordStrength(val) {
        if (!val) return { score: 0, label: '', cls: '' };
        if (val.length < 8) {
            return { score: 1, label: 'Lemah (min. 8 karakter)', cls: 'is-weak' };
        }
        let score = 0;
        if (/[A-Z]/.test(val)) score++;
        if (/[a-z]/.test(val)) score++;
        if (/\d/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        if (score <= 2) {
            return { score: 1, label: 'Lemah', cls: 'is-weak' };
        } else if (score === 3) {
            return { score: 2, label: 'Sedang', cls: 'is-medium' };
        } else {
            return { score: 3, label: 'Kuat', cls: 'is-strong' };
        }
    }

    function checkPasswordMatch() {
        if (!pwdConfirmInput || !pwdMatchHint) return;
        const p1 = pwdInput ? pwdInput.value : '';
        const p2 = pwdConfirmInput.value;
        if (!p2) {
            pwdMatchHint.textContent = '';
            pwdMatchHint.className = 'pwd-match-hint';
            return;
        }
        if (p1 === p2) {
            pwdMatchHint.textContent = '✓ Password cocok';
            pwdMatchHint.className = 'pwd-match-hint is-match';
        } else {
            pwdMatchHint.textContent = '✕ Konfirmasi password tidak sama';
            pwdMatchHint.className = 'pwd-match-hint is-mismatch';
        }
    }

    if (pwdInput && pwdStrengthBar && pwdStrengthText) {
        pwdInput.addEventListener('input', () => {
            const val = pwdInput.value;
            const res = checkPasswordStrength(val);
            pwdStrengthBar.className = 'pwd-strength-bar ' + res.cls;
            pwdStrengthText.textContent = res.label ? `Kekuatan: ${res.label}` : '';
            checkPasswordMatch();
        });
    }

    if (pwdConfirmInput) {
        pwdConfirmInput.addEventListener('input', checkPasswordMatch);
    }

    /* ---------- Auto-sync Username to Name Hidden Field ---------- */
    const usernameInput = document.getElementById('reg_username') || form.querySelector('input[name="username"]');
    const nameInput = document.getElementById('reg_name');
    if (usernameInput && nameInput) {
        usernameInput.addEventListener('input', () => {
            nameInput.value = usernameInput.value.trim();
        });
        if (!nameInput.value && usernameInput.value) {
            nameInput.value = usernameInput.value.trim();
        }
    }

    /* ---------- Input Error Clearance on Typing ---------- */
    form.querySelectorAll('input').forEach((input) => {
        input.addEventListener('input', () => {
            const wrap = input.closest('.reg-input-wrap');
            if (wrap) wrap.classList.remove('has-error');
            const errEl = input.closest('.reg-form-group')?.querySelector('.reg-field-error');
            if (errEl) {
                errEl.style.opacity = '0';
                setTimeout(() => errEl.remove(), 200);
            }
        });
    });

    /* ---------- Kontrol tampilan PDF (zoom & fullscreen) ---------- */
    const pdfFrame = document.querySelector('[data-pdf-frame]');
    const pdfIframe = document.querySelector('[data-pdf-iframe]');
    const pdfZoomLabel = document.querySelector('[data-pdf-zoom-label]');
    let pdfZoom = 1;

    function applyPdfZoom() {
        if (!pdfIframe) return;
        const pct = Math.round(pdfZoom * 100);
        pdfIframe.style.transform = 'scale(' + pdfZoom + ')';
        pdfIframe.style.transformOrigin = 'top left';
        pdfIframe.style.width = (100 / pdfZoom) + '%';
        pdfIframe.style.height = (100 / pdfZoom) + '%';
        if (pdfZoomLabel) pdfZoomLabel.textContent = pct + '%';
    }

    document.querySelector('[data-pdf-zoom-in]')?.addEventListener('click', () => {
        pdfZoom = Math.min(pdfZoom + 0.1, 2.5);
        applyPdfZoom();
    });

    document.querySelector('[data-pdf-zoom-out]')?.addEventListener('click', () => {
        pdfZoom = Math.max(pdfZoom - 0.1, 0.5);
        applyPdfZoom();
    });

    document.querySelector('[data-pdf-fullscreen]')?.addEventListener('click', () => {
        const target = pdfFrame;
        if (!target) return;
        if (document.fullscreenElement) {
            document.exitFullscreen?.();
        } else {
            target.requestFullscreen?.();
        }
    });
});