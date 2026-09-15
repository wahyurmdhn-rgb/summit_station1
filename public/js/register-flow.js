document.addEventListener('DOMContentLoaded', () => {
    /* ---------- Toggle visibility password (login & register) ---------- */
    document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
        const input = document.getElementById(btn.dataset.passwordToggle);
        if (!input) return;
        btn.addEventListener('click', () => {
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            btn.classList.toggle('is-visible', !isVisible);
            btn.setAttribute('aria-pressed', String(!isVisible));
        });
    });

    const form = document.querySelector('[data-register-form]');
    const createButton = document.querySelector('[data-create-account]');
    const termsModal = document.querySelector('[data-terms-modal]');
    const ktpModal = document.querySelector('[data-ktp-modal]');
    const accept = document.querySelector('[data-accept-expedition]');
    const proceed = document.querySelector('[data-proceed-ktp]');
    const ktpFile = document.querySelector('[data-ktp-file]');
    const uploadFile = document.querySelector('[data-upload-file]');
    const complete = document.querySelector('[data-complete-register]');
    let confirmedFlow = false;

    if (!form || !createButton) return;

    /* ---------- Helper modal dengan animasi ---------- */
    const openModal = (modal) => {
        if (!modal) return;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => requestAnimationFrame(() => {
            modal.classList.add('is-open');
            if (modal === termsModal) {
                updateReadingProgress();
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

    /* ---------- Submit form: buka modal terms ---------- */
    form.addEventListener('submit', (event) => {
        if (confirmedFlow) return;
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

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

    // Klik area gelap di luar kartu menutup modal
    [termsModal, ktpModal].forEach((modal) => {
        modal?.addEventListener('pointerdown', (event) => {
            if (event.target === modal) closeModal(modal);
        });
    });

    // Tombol Escape menutup modal teratas yang terbuka
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (termsModal && !termsModal.hidden) closeModal(termsModal);
        else if (ktpModal && !ktpModal.hidden) closeModal(ktpModal);
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
        setTimeout(() => openModal(ktpModal), 200);
    });

    /* ---------- Upload KTP ---------- */
    const ktpPreview = document.querySelector('[data-ktp-preview]');
    const ktpPreviewImg = document.querySelector('[data-ktp-preview-img]');
    const ktpPreviewName = document.querySelector('[data-ktp-preview-name]');
    const ktpPreviewRemove = document.querySelector('[data-ktp-preview-remove]');
    const uploadBox = document.querySelector('.upload-box');
    let currentObjectURL = null;

    function revokeObjectURL() {
        if (currentObjectURL) {
            URL.revokeObjectURL(currentObjectURL);
            currentObjectURL = null;
        }
    }

    function showPreview(file) {
        if (!file || !ktpPreview || !ktpPreviewImg || !ktpPreviewName) return;

        revokeObjectURL();

        if (!file.type.startsWith('image/')) {
            uploadFile.textContent = 'Format file tidak didukung. Pilih file gambar (JPEG/PNG).';
            uploadFile.style.color = '#c0392b';
            ktpPreview.hidden = true;
            complete.disabled = true;
            return;
        }

        currentObjectURL = URL.createObjectURL(file);
        ktpPreviewImg.src = currentObjectURL;
        ktpPreviewName.textContent = file.name;
        ktpPreview.hidden = false;
        uploadFile.textContent = '';
        uploadBox.style.display = 'none';
        complete.disabled = false;
    }

    function clearPreview() {
        revokeObjectURL();
        if (ktpPreview) ktpPreview.hidden = true;
        if (ktpPreviewImg) ktpPreviewImg.src = '';
        if (ktpPreviewName) ktpPreviewName.textContent = '';
        if (uploadBox) uploadBox.style.display = '';
        uploadFile.textContent = '';
        uploadFile.style.color = '';
        complete.disabled = true;
    }

    ktpFile?.addEventListener('change', () => {
        const file = ktpFile.files[0];
        if (file) {
            showPreview(file);
        } else {
            clearPreview();
        }
    });

    ktpPreviewRemove?.addEventListener('click', () => {
        ktpFile.value = '';
        clearPreview();
    });

    complete?.addEventListener('click', () => {
        if (complete.disabled || complete.dataset.submitting === '1') return;
        complete.dataset.submitting = '1';
        complete.disabled = true;
        complete.classList.add('is-loading');
        confirmedFlow = true;

        // Input file KTP ada di modal (di luar <form>), pindahkan ke form
        // agar file ikut terkirim saat submit.
        if (ktpFile && !form.contains(ktpFile)) {
            ktpFile.hidden = true;
            form.appendChild(ktpFile);
        }

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
});
