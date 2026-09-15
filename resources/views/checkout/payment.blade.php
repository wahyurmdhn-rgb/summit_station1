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

        <!-- Right Column: Select Payment Method -->
        <div class="payment-methods-card">
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

            <!-- Pay Now Button -->
            <button type="button" class="btn-pay-now" id="btn-pay" onclick="proceedPayment()">
                <span>Bayar Rp {{ number_format($order['total'], 0, ',', '.') }}</span> &nbsp;&rarr;
            </button>
        </div>

    </main>

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])

    <script>
        let selectedMethod = 'qris';

        function selectPayment(el) {
            document.querySelectorAll('.pm-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
            selectedMethod = el.getAttribute('data-id');
        }

        function proceedPayment() {
            window.location.href = "{{ route('payment.qris') }}?method=" + encodeURIComponent(selectedMethod);
        }
    </script>
    <script src="{{ asset('js/summit-navbar.js') }}"></script>
</body>
</html>
