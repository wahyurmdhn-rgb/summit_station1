<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Konfirmasi &amp; Bayar - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-payment.css') . '?v=' . filemtime(public_path('css/summit-payment.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-navbar.css') . '?v=' . filemtime(public_path('css/summit-navbar.css')) }}">
    <style>
        .btn-upload-proof {
            cursor: pointer;
            border: none;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
        }
    </style>
</head>
<body>
    @include('layouts.navbar')

    <main class="qris-main">
        <div class="payment-breadcrumb">Checkout &gt; <strong>Pembayaran</strong></div>
        <h1 class="qris-heading">Konfirmasi &amp; Bayar</h1>

        <section class="qris-layout">
            <!-- Left Column: Order Summary -->
            <div class="summary-stack">
                <aside class="order-summary-card">
                    <h2>Ringkasan Pesanan</h2>
                    <div class="summary-date-row">
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <path d="M16 2v4M8 2v4M3 10h18"></path>
                            </svg>
                            @if (!empty($order['rent_start']) && !empty($order['rent_end']))
                                {{ date('M d', strtotime($order['rent_start'])) }} - {{ date('M d, Y', strtotime($order['rent_end'])) }}
                            @else
                                {{ now()->format('M d') }} - {{ now()->addDays(max(0, ($order['days'] ?? 3) - 1))->format('M d, Y') }}
                            @endif
                        </span>
                        <small>{{ $order['days'] ?? 3 }} HARI</small>
                    </div>

                    <div class="summary-lines">
                        @if (!empty($cartItems))
                            @foreach ($cartItems as $item)
                                <div>
                                    <span>{{ $item['name'] }} &times;{{ $item['quantity'] }}</span>
                                    <strong>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</strong>
                                </div>
                            @endforeach
                        @else
                            <div>
                                <span>{{ $order['product_name'] }}</span>
                                <strong>Rp {{ number_format($order['base_rental'], 0, ',', '.') }}</strong>
                            </div>
                        @endif

                        @if (!empty($order['service_fee']) && $order['service_fee'] > 0)
                            <div style="color: #64748b; font-size: 12px;">
                                <span>Biaya Layanan</span>
                                <span>Rp {{ number_format($order['service_fee'], 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="summary-total">
                        <span>Total Pembayaran</span>
                        <strong>Rp {{ number_format($order['total'], 0, ',', '.') }}</strong>
                    </div>
                </aside>

                <div class="secure-note">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    Pembayaran Terenkripsi 256-bit
                </div>
            </div>

            <!-- Right Column: QRIS Scan & Upload Proof Form -->
            <form method="POST" action="{{ route('payment.process') }}" enctype="multipart/form-data" class="qris-card" id="qris-form">
                @csrf
                <input type="hidden" name="payment_method" value="{{ $payment_method ?? 'qris' }}">

                @if (($payment_method ?? 'qris') === 'qris')
                    <div class="scan-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                            <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                            <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                            <path d="M14 14h3v3h-3zM18 18h3v3h-3zM14 19h2"></path>
                        </svg>
                    </div>
                    <h2>Pindai &amp; Bayar QRIS</h2>
                    <p>Buka aplikasi bank atau e-wallet favorit Anda dan pindai kode QR di bawah ini untuk menyelesaikan pembayaran.</p>

                    <div class="qr-frame" aria-label="QRIS code">
                        <img src="{{ asset('images/qris.jpg') }}" alt="QRIS Code" style="width:100%;height:100%;object-fit:contain;border-radius:10px;">
                    </div>
                @else
                    <div class="scan-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                            <path d="M2 10h20"></path>
                        </svg>
                    </div>
                    <h2>Bayar via {{ strtoupper($payment_method) }}</h2>
                    <p>Lakukan pembayaran melalui {{ strtoupper($payment_method) }} Anda, lalu unggah bukti pembayaran di bawah ini untuk verifikasi.</p>
                @endif

                <label class="upload-proof" for="proof-upload" style="cursor: pointer;" id="proof-dropzone">
                    <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" id="proof-upload" style="display: none;">
                    <span class="upload-icon" id="proof-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 16l-4-4-4 4"></path>
                            <path d="M12 12v9"></path>
                            <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                            <path d="M16 16h1a4 4 0 0 0 0-8"></path>
                        </svg>
                    </span>
                    <strong>Upload Bukti Pembayaran</strong>
                    <span id="proof-label">Klik untuk memilih file atau tarik dan lepas file di sini</span>
                    <small>FORMAT YANG DIDUKUNG: JPG, JPEG, PNG, PDF | MAKS. 5MB</small>
                </label>

                <div class="proof-image-preview-wrap" id="proof-image-preview-wrap" style="display: none;">
                    <img id="proof-image-preview" src="" alt="Preview bukti pembayaran">
                </div>

                <div class="proof-preview" id="proof-preview" style="display: none;">
                    <span class="proof-preview-icon" id="proof-preview-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <path d="M22 4L12 14.01l-3-3"></path>
                        </svg>
                    </span>
                    <span class="proof-preview-meta">
                        <strong id="proof-file-name"></strong>
                        <span id="proof-file-size"></span>
                    </span>
                    <button type="button" class="proof-remove" id="proof-remove" aria-label="Hapus file bukti">Hapus</button>
                </div>

                <p class="proof-error" id="proof-error" style="display: none;"></p>

                <div class="qris-timer" id="qris-timer">
                    <span class="qris-timer-icon" id="qris-timer-icon">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="13" r="8"></circle>
                            <path d="M12 9v5l3 2M9 2h6"></path>
                        </svg>
                    </span>
                    <span id="qris-countdown">05:00</span>
                </div>

                <div class="expired-state" id="expired-state" style="display: none;">
                    <div class="expired-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 8v4M12 16h.01"></path>
                        </svg>
                    </div>
                    <h3>Waktu Pembayaran Habis</h3>
                    <p>Batas waktu pembayaran telah berakhir. Silakan ulangi proses pembayaran.</p>
                    <a class="btn-retry-payment" href="{{ route('payment') }}">
                        <span>Ulangi Pembayaran</span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 4v6h6M23 20v-6h-6"></path>
                            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10M3.51 15A9 9 0 0 0 18.36 18.36L23 14"></path>
                        </svg>
                    </a>
                </div>

                <button type="submit" class="btn-upload-proof" id="btn-upload-proof" disabled>
                    <span>Upload &amp; Konfirmasi</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M13 5l7 7-7 7"></path>
                    </svg>
                </button>

                <p class="proof-expired-hint" id="proof-expired-hint" style="display: none;">Pembayaran sudah melewati batas waktu.</p>

                <a class="change-method" href="{{ route('payment') }}">Ganti Metode Pembayaran</a>
            </form>
        </section>

    </main>

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])

    <script>
        (function () {
            var MAX_BYTES = 5 * 1024 * 1024;
            var ALLOWED = ['jpg', 'jpeg', 'png', 'pdf'];
            var deadline = {{ (int) ($payment_deadline ?? 0) }};
            var serverNow = {{ (int) now()->timestamp }};
            var clockOffset = (serverNow * 1000) - Date.now();

            var form = document.getElementById('qris-form');
            var fileInput = document.getElementById('proof-upload');
            var dropzone = document.getElementById('proof-dropzone');
            var proofLabel = document.getElementById('proof-label');
            var proofError = document.getElementById('proof-error');
            var proofPreview = document.getElementById('proof-preview');
            var proofFileName = document.getElementById('proof-file-name');
            var proofFileSize = document.getElementById('proof-file-size');
            var proofRemove = document.getElementById('proof-remove');
            var btnUpload = document.getElementById('btn-upload-proof');
            var countdownEl = document.getElementById('qris-countdown');
            var timerWrap = document.getElementById('qris-timer');
            var timerIcon = document.getElementById('qris-timer-icon');
            var expiredState = document.getElementById('expired-state');
            var expiredHint = document.getElementById('proof-expired-hint');
            var imagePreviewWrap = document.getElementById('proof-image-preview-wrap');
            var imagePreview = document.getElementById('proof-image-preview');
            var currentObjectURL = null;
            var IMAGE_EXTS = ['jpg', 'jpeg', 'png'];
            var expired = false;
            var countdownTimer = null;

            function remainingMs() {
                return (deadline * 1000) - (Date.now() + clockOffset);
            }

            function formatBytes(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
            }

            function isFileValid(file) {
                if (!file) return false;
                var ext = (file.name.split('.').pop() || '').toLowerCase();
                return ALLOWED.indexOf(ext) !== -1 && file.size <= MAX_BYTES;
            }

            function showError(message) {
                proofError.textContent = message;
                proofError.style.display = 'block';
            }

            function hideError() {
                proofError.style.display = 'none';
                proofError.textContent = '';
            }

            function clearSelection() {
                fileInput.value = '';
                proofLabel.textContent = 'Klik untuk memilih file atau tarik dan lepas file di sini';
                proofPreview.style.display = 'none';
                dropzone.classList.remove('has-file');
                imagePreviewWrap.style.display = 'none';
                imagePreviewWrap.classList.remove('show');
                imagePreview.removeAttribute('src');
                if (currentObjectURL) {
                    URL.revokeObjectURL(currentObjectURL);
                    currentObjectURL = null;
                }
                hideError();
                refreshButton();
            }

            function refreshButton() {
                btnUpload.disabled = !isFileValid(fileInput.files[0]) || remainingMs() <= 0;
            }

            var WARNING_ICON = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<circle cx="12" cy="12" r="10"></circle>' +
                '<path d="M12 8v4M12 16h.01"></path>' +
                '</svg>';

            function lockExpired() {
                if (expired) return;
                expired = true;

                if (countdownTimer) {
                    clearInterval(countdownTimer);
                    countdownTimer = null;
                }

                countdownEl.textContent = '00:00';
                if (timerIcon) timerIcon.innerHTML = WARNING_ICON;
                timerWrap.classList.add('expired');

                fileInput.disabled = true;
                dropzone.classList.add('is-locked');
                dropzone.style.cursor = 'not-allowed';

                btnUpload.disabled = true;

                expiredState.style.display = 'flex';
                expiredHint.style.display = 'block';

                requestAnimationFrame(function () {
                    expiredState.classList.add('show');
                });

                hideError();
            }

            fileInput.addEventListener('change', function () {
                if (expired) {
                    this.value = '';
                    return;
                }

                var file = this.files[0];
                if (!file) {
                    clearSelection();
                    return;
                }

                var ext = (file.name.split('.').pop() || '').toLowerCase();
                if (ALLOWED.indexOf(ext) === -1) {
                    showError('Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF.');
                    this.value = '';
                    proofLabel.textContent = 'Klik untuk memilih file atau tarik dan lepas file di sini';
                    proofPreview.style.display = 'none';
                    dropzone.classList.remove('has-file');
                    refreshButton();
                    return;
                }

                if (file.size > MAX_BYTES) {
                    showError('Ukuran file maksimal 5MB.');
                    this.value = '';
                    proofLabel.textContent = 'Klik untuk memilih file atau tarik dan lepas file di sini';
                    proofPreview.style.display = 'none';
                    dropzone.classList.remove('has-file');
                    refreshButton();
                    return;
                }

                hideError();
                proofLabel.textContent = 'File siap diunggah';
                dropzone.classList.add('has-file');
                proofFileName.textContent = file.name;
                proofFileSize.textContent = formatBytes(file.size);
                proofPreview.style.display = 'flex';

                var isImage = IMAGE_EXTS.indexOf(ext) !== -1;
                if (isImage) {
                    if (currentObjectURL) {
                        URL.revokeObjectURL(currentObjectURL);
                    }
                    currentObjectURL = URL.createObjectURL(file);
                    imagePreview.onload = function () {
                        imagePreviewWrap.style.display = 'flex';
                        requestAnimationFrame(function () {
                            imagePreviewWrap.classList.add('show');
                        });
                    };
                    imagePreview.src = currentObjectURL;
                } else {
                    imagePreviewWrap.style.display = 'none';
                    imagePreviewWrap.classList.remove('show');
                    imagePreview.removeAttribute('src');
                    if (currentObjectURL) {
                        URL.revokeObjectURL(currentObjectURL);
                        currentObjectURL = null;
                    }
                }

                refreshButton();
            });

            proofRemove.addEventListener('click', function (e) {
                e.preventDefault();
                clearSelection();
            });

            form.addEventListener('submit', function (e) {
                if (expired) {
                    e.preventDefault();
                    showError('Waktu pembayaran telah habis. Silakan ulangi proses pembayaran.');
                    return;
                }

                if (remainingMs() <= 0) {
                    e.preventDefault();
                    lockExpired();
                    showError('Waktu pembayaran telah habis. Silakan ulangi proses pembayaran.');
                    return;
                }

                var file = fileInput.files[0];
                if (!file) {
                    e.preventDefault();
                    showError('Silakan upload bukti pembayaran terlebih dahulu.');
                    return;
                }

                var ext = (file.name.split('.').pop() || '').toLowerCase();
                if (ALLOWED.indexOf(ext) === -1) {
                    e.preventDefault();
                    showError('Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF.');
                    btnUpload.disabled = true;
                    return;
                }

                if (file.size > MAX_BYTES) {
                    e.preventDefault();
                    showError('Ukuran file maksimal 5MB.');
                    btnUpload.disabled = true;
                    return;
                }
            });

            function tick() {
                var remain = remainingMs();

                if (remain <= 0) {
                    lockExpired();
                    return;
                }

                var total = Math.ceil(remain / 1000);
                var minutes = String(Math.floor(total / 60)).padStart(2, '0');
                var rest = String(total % 60).padStart(2, '0');
                countdownEl.textContent = minutes + ':' + rest;
                refreshButton();
            }

            tick();
            if (!expired) {
                countdownTimer = setInterval(tick, 1000);
            }
        })();
    </script>
    <script src="{{ asset('js/summit-navbar.js') }}"></script>
</body>
</html>
