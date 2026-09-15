<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Keranjang Sewa - Pilihan Peralatan - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-cart.css') . '?v=' . filemtime(public_path('css/summit-cart.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-navbar.css') . '?v=' . filemtime(public_path('css/summit-navbar.css')) }}">
    <style>
        .cart-empty-container {
            text-align: center;
            padding: 80px 20px;
            background: #fff;
            border-radius: 16px;
            border: 1px dashed #d1d5db;
            margin-top: 20px;
        }
        .cart-empty-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #eef7f0;
            color: #185d31;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .cart-empty-title {
            font-size: 22px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 8px;
        }
        .cart-empty-desc {
            font-size: 14px;
            color: #6b7280;
            max-width: 420px;
            margin: 0 auto 24px;
        }
        .btn-view-catalog {
            display: inline-block;
            background: #185d31;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-view-catalog:hover {
            background: #114223;
        }
        .stock-alert-box {
            padding: 14px 18px;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .table-header-row {
            display: grid;
            grid-template-columns: 1fr auto auto; gap: 24px;
        }
        .cart-item-card {
            display: grid;
            grid-template-columns: 1fr auto auto; gap: 24px;
            align-items: center;
        }
        .item-steppers-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
        }
        .stepper-subgroup {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .item-stepper-box {
            background-color: #f6f5f0;
            border-radius: 8px;
            padding: 4px 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            font-size: 14px;
        }
        .item-stepper-btn {
            background: none;
            border: none;
            font-size: 16px;
            font-weight: 700;
            color: #333;
            cursor: pointer;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background-color 0.15s ease;
        }
        .item-stepper-btn:hover {
            background-color: #e5e3dc;
        }
        .item-stepper-btn:disabled {
            opacity: 0.35;
            color: #aaa;
            cursor: not-allowed;
            background: none;
        }
        .item-days-label {
            font-size: 10.5px;
            color: #777;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .item-date-box {
            display: flex;
            flex-direction: column;
            gap: 6px;
            background-color: #f6f5f0;
            border-radius: 8px;
            padding: 8px 10px;
        }
        .item-date-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .item-date-label {
            font-size: 10.5px;
            color: #888;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
            width: 44px;
        }
        .item-date-input {
            border: 1px solid #d5d3cb;
            border-radius: 6px;
            padding: 4px 6px;
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            color: #1f2937;
            background: #fff;
            width: 100%;
        }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    @include('layouts.navbar')

    <!-- Main Cart Container -->
    <main class="cart-main-container">
        <div class="cart-header-title">
<div class="cart-subtitle-tag">PERSIAPAN EKSPEDISI ANDA</div>
<h1 class="cart-page-heading">Pilihan Peralatan</h1>
        </div>

        @if (session('status'))
            <div style="margin-bottom: 20px; padding: 12px 16px; border-radius: 8px; background-color: #edf7ef; color: #175e30; font-size: 13px; font-weight: 700;">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="margin-bottom: 20px; padding: 12px 16px; border-radius: 8px; background-color: #fee2e2; color: #991b1b; font-size: 13px; font-weight: 700;">
                {{ $errors->first() }}
            </div>
        @endif

        @if (!empty($stockWarnings))
            <div class="stock-alert-box">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>Peringatan Ketersediaan Stok</span>
                </div>
                <ul style="margin: 4px 0 0 22px; font-weight: 500;">
                    @foreach ($stockWarnings as $warn)
                        <li>{{ $warn }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (empty($cartItems))
            <!-- Empty State -->
            <div class="cart-empty-container">
                <div class="cart-empty-icon">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </div>
                <h2 class="cart-empty-title">Keranjang masih kosong</h2>
                <p class="cart-empty-desc">
                    Belum ada perlengkapan mendaki yang dipilih. Jelajahi katalog kami dan temukan gear terbaik untuk petualangan Anda.
                </p>
                <a href="{{ route('catalog') }}" class="btn-view-catalog">
                        Lihat Katalog &rarr;
                </a>
            </div>
        @else
            <div class="cart-layout-grid">
                <!-- Left Column: Cart Items -->
                <div class="cart-items-column">
                    <div class="table-header-row">
                        <span>PRODUCT DETAILS</span>
                        <span style="text-align: center;">DURASI &amp; JUMLAH</span>
                        <span style="text-align: right;">SUBTOTAL</span>
                    </div>

                    <div class="cart-select-all-row">
                        <label class="summit-checkbox">
                            <input type="checkbox" id="select-all" checked onclick="toggleSelectAll(this.checked)">
                            <span class="checkbox-box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            <span class="checkbox-label">Pilih Semua Barang</span>
                        </label>
                        <span class="select-all-hint" id="select-all-hint">{{ $selectedCount ?? count($cartItems) }} dari {{ count($cartItems) }} barang dipilih</span>
                    </div>

                    @foreach ($cartItems as $item)
                        @php
                            $itemToday = now()->format('Y-m-d');
                            $itemStartStr = $item['rent_start'] ?? $itemToday;
                            $itemDaysNum = max(1, (int) ($item['days'] ?? 1));
                            $itemEndStr = $item['rent_end'] ?? date('Y-m-d', strtotime($itemStartStr . ' +' . ($itemDaysNum - 1) . ' days'));
                        @endphp
                        <div class="cart-item-card {{ (!array_key_exists('selected', $item) || !empty($item['selected'])) ? 'is-selected' : '' }}" id="cart-card-{{ $item['id'] }}" data-price-per-day="{{ $item['price_per_day'] }}" data-stock="{{ $item['stock_available'] ?? 1 }}" data-days="{{ $item['days'] }}" data-start="{{ $itemStartStr }}" data-end="{{ $itemEndStr }}" data-qty="{{ $item['quantity'] }}" data-selected="{{ (!array_key_exists('selected', $item) || !empty($item['selected'])) ? '1' : '0' }}">
                            <div class="item-product-info">
                                <label class="summit-checkbox item-select-checkbox">
                                    <input type="checkbox" class="item-select" data-item-id="{{ $item['id'] }}" data-item-name="{{ $item['name'] }}" {{ (!array_key_exists('selected', $item) || !empty($item['selected'])) ? 'checked' : '' }} onchange="toggleItemSelect(this)">
                                    <span class="checkbox-box">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </span>
                                </label>
                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="item-thumb">
                                <div class="item-details">
                                    <span class="item-category-tag">{{ $item['category'] }}</span>
                                    <h3 class="item-name">{{ $item['name'] }}</h3>
                                    <span class="item-subtitle">Rp {{ number_format($item['price_per_day'], 0, ',', '.') }} / hari &bull; <span id="display-meta-qty-{{ $item['id'] }}">{{ $item['quantity'] }}</span> unit</span>
                                </div>
                            </div>

                            <div class="item-steppers-container">
                                <!-- Rentang Tanggal Sewa (durasi dihitung otomatis) -->
                                <div class="stepper-subgroup" style="min-width: 216px;">
                                    <div class="item-date-box">
                                        <div class="item-date-row">
                                            <span class="item-date-label">Mulai</span>
                                            <input type="date" class="item-date-input" id="item-start-{{ $item['id'] }}" data-item-id="{{ $item['id'] }}" value="{{ $itemStartStr }}" min="{{ $itemToday }}">
                                        </div>
                                        <div class="item-date-row">
                                            <span class="item-date-label">Kembali</span>
                                            <input type="date" class="item-date-input" id="item-end-{{ $item['id'] }}" data-item-id="{{ $item['id'] }}" value="{{ $itemEndStr }}" min="{{ $itemToday }}">
                                        </div>
                                    </div>
                                    <span class="item-days-label">Durasi: <span id="display-days-{{ $item['id'] }}">{{ $item['days'] }}</span> Hari</span>
                                </div>

                                <!-- Stepper Jumlah Barang (Qty) -->
                                <div class="stepper-subgroup">
                                    <div class="item-stepper-box">
                                        <button type="button" class="item-stepper-btn" id="btn-qty-minus-{{ $item['id'] }}" data-max-qty="{{ $item['stock_available'] ?? 1 }}" onclick="updateCartItem('{{ $item['id'] }}', 'quantity', -1)" title="Kurangi 1 unit">&minus;</button>
                                        <span id="display-qty-{{ $item['id'] }}">{{ $item['quantity'] }}</span>
                                        <button type="button" class="item-stepper-btn" id="btn-qty-plus-{{ $item['id'] }}" data-max-qty="{{ $item['stock_available'] ?? 1 }}" onclick="updateCartItem('{{ $item['id'] }}', 'quantity', 1)" title="Tambah 1 unit">&plus;</button>
                                    </div>
                                    <span class="item-days-label">Jumlah Unit (MAKS: {{ $item['stock_available'] ?? 1 }})</span>
                                </div>
                            </div>

                            <div class="item-subtotal-cell">
                                <span class="item-subtotal-price" id="display-subtotal-{{ $item['id'] }}">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                <form method="post" action="{{ route('cart.remove', $item['id']) }}">
                                    @csrf
                                    <button type="submit" class="btn-remove-item" title="Hapus Item">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach

                    <!-- Continue Browsing & Clear Cart Row -->
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <a href="{{ route('catalog') }}" class="continue-browsing-card" style="flex: 1;">
                            <div class="icon-plus-circle">&plus;</div>
                            <span>Tambah alat lain dari katalog</span>
                        </a>

                        <form method="POST" action="{{ route('cart.clear') }}" onsubmit="return confirm('Kosongkan semua item di keranjang?');">
                            @csrf
                            <button type="submit" style="background: #fff; border: 1px solid #e5e7eb; color: #ef4444; font-weight: 700; font-size: 12px; padding: 14px 18px; border-radius: 12px; cursor: pointer;">
                                Kosongkan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="order-summary-card">
                    <h2 class="summary-card-title">Ringkasan Pesanan</h2>

                    <div class="summary-row">
                        <span>Subtotal Sewa</span>
                        <span class="amount" id="summary-subtotal">Rp {{ number_format($subtotalRentals, 0, ',', '.') }}</span>
                    </div>

                    <div class="summary-row">
                        <span>Service Fee</span>
                        <span class="amount" id="summary-service-fee">Rp {{ number_format($serviceFee, 0, ',', '.') }}</span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="total-payable-block">
                        <span class="total-label">TOTAL PAYABLE</span>
                        <div class="total-amount" id="summary-total">
                            Rp{{ number_format($totalPayable, 0, ',', '.') }}
                        </div>
                    </div>

                    @if ((($selectedCount ?? count($cartItems)) ?? 0) === 0)
                        <button type="button" class="btn-ajukan-peminjaman" id="btn-ajukan" disabled style="background: #9ca3af; cursor: not-allowed;">
                            <span>Pilih minimal satu barang untuk melanjutkan</span>
                        </button>
                    @elseif ($hasInsufficientStock)
                        <button type="button" class="btn-ajukan-peminjaman" id="btn-ajukan" disabled style="background: #9ca3af; cursor: not-allowed;">
                            <span>Stok alat tidak mencukupi</span>
                        </button>
                    @else
                        <a href="{{ route('payment') }}" class="btn-ajukan-peminjaman" id="btn-ajukan">
                            <span>Ajukan Peminjaman</span>
                            <span>&rarr;</span>
                        </a>
                    @endif

                    <div class="summary-trust-row">
                        <div class="trust-item-col">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            <span>PEMBAYARAN AMAN</span>
                        </div>
                        <div class="trust-item-col">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>INSPECTED GEAR</span>
                        </div>
                        <div class="trust-item-col">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span>24/7 SUPPORT</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </main>

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])

    <script>
        // ── Konfigurasi ──
        var SERVICE_FEE = 25000;
        var insufficientStock = {{ isset($hasInsufficientStock) && $hasInsufficientStock ? 'true' : 'false' }};

        // ── SUMBER KEBENARAN LOKAL (frontend) ──
        // Semua Ringkasan Pemesanan dihitung instan dari state ini.
        var cartItems = {};
        var savedState = {};   // {days, qty} terakhir yang valid tersimpan di server
        var saveTimers = {};   // timer persist per item (trailing save)

        function initCartState() {
            cartItems = {};
            document.querySelectorAll('.cart-item-card').forEach(function (card) {
                var id = card.id.replace('cart-card-', '');
                cartItems[id] = {
                    price: parseInt(card.dataset.pricePerDay) || 0,
                    stock: parseInt(card.dataset.stock) || 1,
                    days: parseInt(card.dataset.days) || 1,
                    qty: parseInt(card.dataset.qty) || 1,
                    start: card.dataset.start || '',
                    end: card.dataset.end || '',
                    selected: card.dataset.selected === '1'
                };
                savedState[id] = { days: cartItems[id].days, qty: cartItems[id].qty, start: cartItems[id].start, end: cartItems[id].end };
            });
        }

        // ── Formatter (format sama persis dengan server) ──
        function formatRp(amount) {
            return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        function formatRpNoSpace(amount) {
            return 'Rp' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function getMaxQty(itemId) {
            var plusBtn = document.getElementById('btn-qty-plus-' + itemId);
            if (plusBtn && plusBtn.dataset.maxQty) {
                return Math.max(1, parseInt(plusBtn.dataset.maxQty) || 1);
            }
            return 1;
        }

        function syncQtyButtons(itemId) {
            var qtyEl = document.getElementById('display-qty-' + itemId);
            var minusBtn = document.getElementById('btn-qty-minus-' + itemId);
            var plusBtn = document.getElementById('btn-qty-plus-' + itemId);
            if (!qtyEl) return;
            var qty = parseInt(qtyEl.textContent) || 0;
            var max = getMaxQty(itemId);
            if (minusBtn) minusBtn.disabled = qty <= 1;
            if (plusBtn) plusBtn.disabled = qty >= max;
        }

        // ── RINGKASAN PEMESANAN: dihitung LOKAL & INSTAN ──
        // Dipanggil sinkronus setiap kali ada perubahan (checkbox / qty / durasi).
        // TIDAK menunggu response server.
        function recomputeSummary() {
            var subtotalRentals = 0;
            var selectedCount = 0;

            for (var id in cartItems) {
                var it = cartItems[id];
                var subtotal = it.price * it.days * it.qty;

                var subEl = document.getElementById('display-subtotal-' + id);
                if (subEl) subEl.textContent = formatRp(subtotal);

                if (it.selected) {
                    subtotalRentals += subtotal;
                    selectedCount++;
                }
            }

            var serviceFee = subtotalRentals > 0 ? SERVICE_FEE : 0;
            var totalPayable = subtotalRentals + serviceFee;

            var sumSubtotal = document.getElementById('summary-subtotal');
            if (sumSubtotal) sumSubtotal.textContent = formatRp(subtotalRentals);

            var sumFee = document.getElementById('summary-service-fee');
            if (sumFee) sumFee.textContent = formatRp(serviceFee);

            var sumTotal = document.getElementById('summary-total');
            if (sumTotal) sumTotal.textContent = formatRpNoSpace(totalPayable);

            updateSelectSummary(selectedCount, Object.keys(cartItems).length);
        }

        // Perbarui hint "X dari Y barang dipilih", checkbox "Pilih Semua", dan tombol.
        function updateSelectSummary(selected, total) {
            var hint = document.getElementById('select-all-hint');
            if (hint) hint.textContent = selected + ' dari ' + total + ' barang dipilih';

            var selectAll = document.getElementById('select-all');
            if (selectAll && total > 0) {
                selectAll.checked = (selected === total);
                selectAll.indeterminate = selected > 0 && selected < total;
            }

            renderSubmitButton(selected, total);
        }

        // Ganti tombol Ajukan Peminjaman antara link aktif / tombol disabled.
        function renderSubmitButton(selected, total) {
            var current = document.getElementById('btn-ajukan');
            if (!current) return;

            var enabled = !insufficientStock && selected > 0;
            var reason = insufficientStock ? 'stock' : (selected === 0 ? 'empty' : '');

            var isEnabledEl = current.tagName === 'A';
            if (enabled === isEnabledEl) return; // sudah benar

            var container = current.parentNode;
            var newEl;

            if (enabled) {
                newEl = document.createElement('a');
                newEl.className = 'btn-ajukan-peminjaman';
                newEl.id = 'btn-ajukan';
                newEl.href = '{{ route('payment') }}';
                newEl.innerHTML = '<span>Ajukan Peminjaman</span><span>&rarr;</span>';
            } else {
                newEl = document.createElement('button');
                newEl.type = 'button';
                newEl.className = 'btn-ajukan-peminjaman btn-disabled';
                newEl.id = 'btn-ajukan';
                newEl.setAttribute('disabled', '');
                newEl.style.background = '#9ca3af';
                newEl.style.cursor = 'not-allowed';
                newEl.textContent = reason === 'stock'
                    ? 'Stok alat tidak mencukupi'
                    : 'Pilih minimal satu barang untuk melanjutkan';
            }

            container.replaceChild(newEl, current);
        }

        // ── PEMILIHAN BARANG (CHECKBOX) ──
        // Update UI instan, lalu simpan ke server di background.
        function sendSelect(body) {
            fetch('{{ route('cart.select') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(body)
            }).catch(function (err) {
                console.error('Error updating selection:', err);
            });
        }

        function toggleItemSelect(checkbox) {
            var id = checkbox.dataset.itemId;
            var checked = checkbox.checked;
            var it = cartItems[id];
            if (!it) return;

            it.selected = checked;
            var card = document.getElementById('cart-card-' + id);
            if (card) card.classList.toggle('is-selected', checked);

            recomputeSummary(); // INSTAN — tanpa menunggu server

            sendSelect({ id: id, checked: checked, all: false });
        }

        function toggleSelectAll(checked) {
            for (var id in cartItems) {
                cartItems[id].selected = checked;
                var cb = document.querySelector('.item-select[data-item-id="' + id + '"]');
                if (cb) cb.checked = checked;
                var card = document.getElementById('cart-card-' + id);
                if (card) card.classList.toggle('is-selected', checked);
            }

            recomputeSummary(); // INSTAN

            sendSelect({ all: true, checked: checked });
        }

        // ── JUMLAH UNIT & DURASI ──
        // Durasi (hari) datang dari pasangan Tanggal Mulai <-> Tanggal Pengembalian.
        // Perubahan qty di bawah bersifat lokal & instan, lalu disimpan (trailing save).
        function updateCartItem(itemId, field, delta) {
            var it = cartItems[itemId];
            if (!it) return;

            if (field === 'quantity') {
                var nq = Math.max(1, Math.min(getMaxQty(itemId), it.qty + delta));
                if (nq === it.qty) return;
                it.qty = nq;
                var qtyEl = document.getElementById('display-qty-' + itemId);
                if (qtyEl) qtyEl.textContent = nq;
                var metaQtyEl = document.getElementById('display-meta-qty-' + itemId);
                if (metaQtyEl) metaQtyEl.textContent = nq;
                syncQtyButtons(itemId);
            } else {
                return;
            }

            recomputeSummary(); // INSTAN — sebelum request backend selesai
            scheduleSave(itemId);
        }

        // Trailing save: selalu kirim snapshot TERAKHIR, hindari race condition
        // dan tidak mengirim banyak request per klik cepat.
        function scheduleSave(itemId) {
            if (saveTimers[itemId]) clearTimeout(saveTimers[itemId]);
            saveTimers[itemId] = setTimeout(function () {
                persistItem(itemId);
            }, 250);
        }

        function persistItem(itemId) {
            var it = cartItems[itemId];
            if (!it) return;

            fetch('/cart/update/' + itemId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    days: it.days,
                    quantity: it.qty,
                    rent_start: it.start,
                    rent_end: it.end
                })
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.success) {
                        savedState[itemId] = { days: it.days, qty: it.qty, start: it.start, end: it.end };
                    } else {
                        if (data && data.message) alert(data.message);
                        revertItem(itemId);
                    }
                })
                .catch(function (err) {
                    console.error('Error updating cart:', err);
                    revertItem(itemId);
                });
        }

        // ── PERUBAHAN TANGGAL SEWA (per item) ──
        // Tanggal mulai & pengembalian -> durasi (inklusif). Dipakai di server juga.
        function updateCartDatesByInputs(itemId) {
            var it = cartItems[itemId];
            if (!it) return;

            var startEl = document.getElementById('item-start-' + itemId);
            var endEl = document.getElementById('item-end-' + itemId);
            if (!startEl || !endEl) return;

            var s = startEl.value;
            var e = endEl.value;
            if (!s || !e) return;

            // Tanggal kembali tidak boleh sebelum tanggal mulai.
            if (e < s) { endEl.value = s; e = s; }
            endEl.min = s;

            var days = Math.round((Date.parse(e + 'T00:00:00Z') - Date.parse(s + 'T00:00:00Z')) / 86400000) + 1;
            days = Math.max(1, Math.min(30, days));

            it.days = days;
            it.start = s;
            it.end = e;

            var daysEl = document.getElementById('display-days-' + itemId);
            if (daysEl) daysEl.textContent = days;

            recomputeSummary(); // INSTAN
            scheduleSave(itemId);
        }

        // Kembalikan ke nilai terakhir yang valid bila backend menolak.
        function revertItem(itemId) {
            var st = savedState[itemId] || { days: 1, qty: 1, start: '', end: '' };
            var it = cartItems[itemId];
            if (!it) return;

            it.days = st.days;
            it.qty = st.qty;
            it.start = st.start;
            it.end = st.end;

            var daysEl = document.getElementById('display-days-' + itemId);
            if (daysEl) daysEl.textContent = st.days;
            var qtyEl = document.getElementById('display-qty-' + itemId);
            if (qtyEl) qtyEl.textContent = st.qty;
            var metaEl = document.getElementById('display-meta-qty-' + itemId);
            if (metaEl) metaEl.textContent = st.qty;
            var sEl = document.getElementById('item-start-' + itemId);
            if (sEl && st.start) sEl.value = st.start;
            var eEl = document.getElementById('item-end-' + itemId);
            if (eEl && st.end) eEl.value = st.end;
            syncQtyButtons(itemId);
            recomputeSummary();
        }

        // ── INISIALISASI ──
        initCartState();

        document.querySelectorAll('.item-date-input').forEach(function (el) {
            el.addEventListener('change', function () {
                var id = el.getAttribute('data-item-id');
                var sEl = document.getElementById('item-start-' + id);
                var eEl = document.getElementById('item-end-' + id);
                if (sEl && eEl && sEl.value && eEl.value && eEl.value < sEl.value) eEl.value = sEl.value;
                updateCartDatesByInputs(id);
            });
        });

        document.querySelectorAll('.item-stepper-box .item-stepper-btn[id^="btn-qty-"]').forEach(function (btn) {
            var m = btn.id.match(/^btn-qty-(minus|plus)-(.+)$/);
            if (m) syncQtyButtons(m[2]);
        });

        recomputeSummary();
    </script>
    <script src="{{ asset('js/summit-navbar.js') }}"></script>
</body>
</html>
