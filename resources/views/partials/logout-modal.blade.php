{{-- Modal Konfirmasi Keluar (shared: customer navbar & admin sidebar).
     Buka: klik elemen ber-attr [data-logout-open]; Tutup: [data-close-logout].
     Yah: tombol #confirm-logout submit form #logout-form di halaman yang sama. --}}
<div class="logout-modal" id="logout-modal" hidden>
    <div class="logout-backdrop" data-close-logout></div>
    <section class="logout-dialog" role="dialog" aria-modal="true" aria-labelledby="logout-title">
        <div class="logout-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </div>
        <h2 id="logout-title">Konfirmasi Keluar</h2>
        <p>Yakin ingin mengakhiri sesi Anda? Anda perlu masuk kembali untuk mengelola ekspedisi aktif Anda.</p>
        <button type="button" class="confirm-logout" id="confirm-logout">Ya, Keluar</button>
        <button type="button" class="cancel-logout" data-close-logout>Tetap Masuk</button>
    </section>
</div>