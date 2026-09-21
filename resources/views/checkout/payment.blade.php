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
        .btn-pay-now {
            cursor: pointer;
            border: none;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
        }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    @include('layouts.navbar')

    <!-- Main Payment Layout -->
    <main class="payment-main">

        <!-- Left Column: Transaction Details -->
        <div class="txn-column">
            <div>
                <div class="txn-tag">DETAIL TRANSAKSI</div>
                <h1 class="txn-heading">Konfirmasi &amp; Bayar</h1>
            </div>

            <!-- Product Mini Card -->
            <div class="txn-product-card">
                @if (!empty($order['image']))
                    <img src="{{ $order['image'] }}" alt="{{ $order['product_name'] }}" class="txn-product-thumb">
                @endif
                <div class="txn-product-info">
                    <div class="txn-product-name">{{ $order['product_name'] }}</div>
                    <div class="txn-product-meta">{{ $order['product_subtitle'] }}</div>
                </div>
            </div>

            <!-- Fee Breakdown -->
            <div class="txn-breakdown">
                <div class="txn-row">
                    <span>Periode Sewa</span>
                    <span class="fee-amount" style="font-size: 12px;">
                        @if (!empty($order['rent_start']) && !empty($order['rent_end']))
                            {{ date('d M Y', strtotime($order['rent_start'])) }} - {{ date('d M Y', strtotime($order['rent_end'])) }}
                        @else
                            {{ $order['days'] ?? 0 }} hari
                        @endif
                    </span>
                </div>
                <div class="txn-row">
                    <span>Biaya Sewa Dasar</span>
                    <span class="fee-amount">Rp {{ number_format($order['base_rental'], 0, ',', '.') }}</span>
                </div>
                @if (!empty($order['insurance']) && $order['insurance'] > 0)
                    <div class="txn-row">
                        <span>Asuransi Peralatan</span>
                        <span class="fee-amount">Rp {{ number_format($order['insurance'], 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="txn-row">
                    <span>Biaya Layanan</span>
                    <span class="fee-amount">Rp {{ number_format($order['service_fee'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Total -->
            <div class="txn-total-row">
                <div class="txn-total-label">Total<br>Pembayaran</div>
                <div class="txn-total-amount">Rp {{ number_format($order['total'], 0, ',', '.') }}</div>
            </div>

            <!-- Security Notice -->
            <div class="txn-security-notice">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <p class="txn-security-text">
                    Transaksi Anda diamankan dengan enkripsi SSL 256-bit. Seluruh peralatan sewa disanitasi dan diperiksa sebelum dikirim.
                </p>
            </div>
        </div>

        <!-- Right Column: Select Payment Method + Metode Pengambilan -->
        <form method="POST" action="{{ route('payment.qris') }}" class="payment-methods-card" id="payment-form">
            @csrf
            <input type="hidden" name="payment_method" id="payment-method-input" value="qris">

            <div>
                <h2 class="pm-title">Pilih Metode Pembayaran</h2>
                <p class="pm-subtitle">Pilih dompet digital favorit Anda atau pindai QRIS.</p>
            </div>

            <div class="pm-grid" id="pm-grid">
                @foreach ($payment_methods as $pm)
                    <div class="pm-option {{ $loop->first ? 'selected' : '' }}"
                         onclick="selectPayment(this)"
                         data-id="{{ $pm['id'] }}"
                         style="cursor: pointer;">
                        <div class="pm-icon-box pm-icon-{{ $pm['id'] }}">
                            @if ($pm['id'] === 'qris')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                                    <rect x="5" y="5" width="3" height="3" fill="currentColor" stroke="none"></rect>
                                    <rect x="16" y="5" width="3" height="3" fill="currentColor" stroke="none"></rect>
                                    <rect x="5" y="16" width="3" height="3" fill="currentColor" stroke="none"></rect>
                                    <path d="M14 14h3v3h-3zM17 17h3v3h-3zM14 17v3"></path>
                                </svg>
                            @elseif ($pm['id'] === 'gopay')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm4 9H8v-1.5l2-2V10h4v1.5l2 2V15z"/>
                                </svg>
                            @elseif ($pm['id'] === 'dana')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                    <line x1="2" y1="10" x2="22" y2="10"></line>
                                </svg>
                            @elseif ($pm['id'] === 'ovo')
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                                    <circle cx="12" cy="12" r="10"/>
                                    <text x="12" y="16" text-anchor="middle" font-size="8" font-weight="bold" fill="white">OVO</text>
                                </svg>
                            @else
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 7h-3a4 4 0 0 0-8 0H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM12 5a2 2 0 0 1 2 2h-4a2 2 0 0 1 2-2z"/>
                                </svg>
                            @endif
                        </div>
                        <div class="pm-info">
                            <div class="pm-name">{{ $pm['name'] }}</div>
                            <div class="pm-desc">{{ $pm['desc'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- ─── Metode Pengambilan ─── -->
            @php
                $selectedDelivery = $delivery['delivery_method'] ?? '';
                $dName = trim((string) ($delivery['recipient_name'] ?? ''));
                $dPhone = trim((string) ($delivery['recipient_phone'] ?? ''));
                $dAddress = trim((string) ($delivery['delivery_address'] ?? ''));
                $dNote = trim((string) ($delivery['delivery_note'] ?? ''));
            @endphp

            <div class="delivery-section">
                <div class="delivery-head">
                    <h3 class="dm-title">Metode Pengambilan</h3>
                    <p class="dm-subtitle">Bagaimana Anda ingin menerima perlengkapan rental?</p>
                </div>

                <input type="hidden" name="delivery_method" id="delivery-method-input" value="{{ $selectedDelivery }}">

                <div class="delivery-grid">
                    <!-- A. Ambil di Tempat -->
                    <div class="delivery-option {{ $selectedDelivery === 'pickup' ? 'selected' : '' }}"
                         data-delivery="pickup"
                         onclick="selectDelivery(this)"
                         role="radio"
                         aria-checked="{{ $selectedDelivery === 'pickup' ? 'true' : 'false' }}"
                         style="cursor: pointer;">
                        <div class="delivery-icon delivery-icon-store">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 10h16l-1.5 11h-13L4 10z"></path>
                                <path d="M2 7l2-4h16l2 4H2z"></path>
                                <path d="M4 10v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9"></path>
                                <path d="M12 10v11"></path>
                            </svg>
                        </div>
                        <div class="delivery-info">
                            <div class="delivery-name">Ambil di Tempat</div>
                            <div class="delivery-desc">Ambil langsung di Summit Station</div>
                        </div>
                        <small class="delivery-note">Anda dapat mengambil perlengkapan langsung di lokasi kami.</small>
                    </div>

                    <!-- B. Dikirim ke Lokasi -->
                    <div class="delivery-option {{ $selectedDelivery === 'delivery' ? 'selected' : '' }}"
                         data-delivery="delivery"
                         onclick="selectDelivery(this)"
                         role="radio"
                         aria-checked="{{ $selectedDelivery === 'delivery' ? 'true' : 'false' }}"
                         style="cursor: pointer;">
                        <div class="delivery-icon delivery-icon-car">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 3h15v13H1z"></path>
                                <path d="M16 8h4l3 3v5h-7V8z"></path>
                                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                <circle cx="18.5" cy="18.5" r="2.5"></circle>
                            </svg>
                        </div>
                        <div class="delivery-info">
                            <div class="delivery-name">Dikirim ke Lokasi</div>
                            <div class="delivery-desc">Perlengkapan diantar menggunakan mobil Summit Station</div>
                        </div>
                        <small class="delivery-note">Perlengkapan akan dikirim ke alamat yang Anda tentukan.</small>
                    </div>
                </div>

                <p class="delivery-error" id="delivery-error" style="display: none;"></p>

                <!-- Pengambilan di Tempat -->
                <div class="delivery-pickup-info" id="delivery-pickup-info" style="display: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        <path d="M9 12l2 2 4-4"></path>
                    </svg>
                    <div>
                        <strong>Pengambilan di Summit Station</strong>
                        <p>Silakan ambil perlengkapan sesuai jadwal rental yang telah dipilih.</p>
                    </div>
                </div>

                <!-- Form Alamat Pengiriman (muncul saat Dikirim dipilih) -->
                <div class="delivery-form" id="delivery-form">
                    <div class="delivery-form-inner">
                        <h4>Alamat Pengiriman</h4>

                        <div class="delivery-field">
                            <label for="recipient_name">Nama Penerima</label>
                            <input type="text" id="recipient_name" name="recipient_name" value="{{ $dName }}"
                                   placeholder="Nama lengkap penerima barang" autocomplete="name">
                        </div>

                        <div class="delivery-field">
                            <label for="recipient_phone">Nomor WhatsApp</label>
                            <input type="tel" id="recipient_phone" name="recipient_phone" value="{{ $dPhone }}"
                                   placeholder="Contoh: 08123456789" autocomplete="tel">
                        </div>

                        <div class="delivery-field">
                            <label for="delivery_address">Alamat Lengkap</label>
                            <textarea id="delivery_address" name="delivery_address" rows="3"
                                      placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota, provinsi" autocomplete="street-address">{{ $dAddress }}</textarea>
                        </div>

                        <div class="delivery-field">
                            <label for="delivery_note">Catatan / Patokan <span class="delivery-optional">(opsional)</span></label>
                            <input type="text" id="delivery_note" name="delivery_note" value="{{ $dNote }}"
                                   placeholder="Patokan: dekat minimarket, gerbang kompleks, dll.">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pay Now Button -->
            <button type="submit" class="btn-pay-now" id="btn-pay">
                <span>Bayar Rp {{ number_format($order['total'], 0, ',', '.') }}</span> &nbsp;&rarr;
            </button>
        </form>

    </main>

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])

    <script>
        let selectedMethod = 'qris';
        let selectedDelivery = '';

        function selectPayment(el) {
            document.querySelectorAll('.pm-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
            selectedMethod = el.getAttribute('data-id');
            document.getElementById('payment-method-input').value = selectedMethod;
        }

        function selectDelivery(el) {
            document.querySelectorAll('.delivery-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
            selectedDelivery = el.getAttribute('data-delivery');
            document.getElementById('delivery-method-input').value = selectedDelivery;
            document.querySelectorAll('.delivery-option').forEach(o => {
                o.setAttribute('aria-checked', o === el ? 'true' : 'false');
            });
            clearDeliveryErrors();
            updateDeliveryUI();
        }

        function updateDeliveryUI() {
            var pickupInfo = document.getElementById('delivery-pickup-info');
            var deliveryForm = document.getElementById('delivery-form');

            if (pickupInfo) {
                pickupInfo.style.display = selectedDelivery === 'pickup' ? 'flex' : 'none';
            }
            if (deliveryForm) {
                deliveryForm.classList.toggle('open', selectedDelivery === 'delivery');
            }
        }

        function showDeliveryError(message) {
            var box = document.getElementById('delivery-error');
            box.textContent = message;
            box.style.display = 'block';
        }

        function clearDeliveryErrors() {
            var box = document.getElementById('delivery-error');
            if (box) {
                box.style.display = 'none';
                box.textContent = '';
            }
            document.querySelectorAll('.delivery-field.invalid').forEach(f => f.classList.remove('invalid'));
        }

        document.addEventListener('DOMContentLoaded', function () {
            var hidden = document.getElementById('delivery-method-input');
            selectedDelivery = hidden ? hidden.value : '';

            if (selectedDelivery) {
                document.querySelectorAll('.delivery-option').forEach(o => {
                    var isSel = o.getAttribute('data-delivery') === selectedDelivery;
                    o.classList.toggle('selected', isSel);
                    o.setAttribute('aria-checked', isSel ? 'true' : 'false');
                });
            }

            updateDeliveryUI();
        });

        document.getElementById('payment-form').addEventListener('submit', function (e) {
            clearDeliveryErrors();

            if (!selectedMethod) {
                showDeliveryError('Silakan pilih metode pembayaran terlebih dahulu.');
                e.preventDefault();
                return;
            }

            if (!selectedDelivery) {
                showDeliveryError('Silakan pilih metode pengambilan terlebih dahulu.');
                e.preventDefault();
                return;
            }

            if (selectedDelivery === 'delivery') {
                var valid = true;

                var name = document.getElementById('recipient_name').value.trim();
                var phone = document.getElementById('recipient_phone').value.trim();
                var address = document.getElementById('delivery_address').value.trim();

                if (!name) {
                    document.getElementById('recipient_name').closest('.delivery-field').classList.add('invalid');
                    valid = false;
                }
                if (!address) {
                    document.getElementById('delivery_address').closest('.delivery-field').classList.add('invalid');
                    valid = false;
                }

                var digits = phone.replace(/[^0-9]/g, '');
                if (digits.indexOf('62') === 0) {
                    digits = '0' + digits.slice(2);
                }
                if (!/^08\d{8,13}$/.test(digits)) {
                    document.getElementById('recipient_phone').closest('.delivery-field').classList.add('invalid');
                    valid = false;
                }

                if (!valid) {
                    showDeliveryError('Lengkapi alamat pengiriman terlebih dahulu.');
                    e.preventDefault();
                    return;
                }
            }
        });
    </script>
    <script src="{{ asset('js/summit-navbar.js') }}"></script>
</body>
</html>