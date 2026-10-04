{{-- ============================================================
    ISI DROPDOWN LONCENG NOTIFIKASI ADMIN.
    Dipakai oleh dua tempat (satu sumber markup, tidak ada duplikasi):
      1. Render awal di admin.partials.header
      2. Response endpoint polling (/admin/notifications/poll)
         agar notifikasi baru bisa muncul tanpa reload halaman.
    Data: $adminNotifications, $adminUnreadCount
    ============================================================ --}}
<div class="admin-notif-dropdown-header">
    <strong>Notifikasi Aktivitas</strong>
    <span class="admin-notif-count">{{ $adminUnreadCount ?? 0 }} belum dibaca</span>
</div>
<div class="admin-notif-list">
    @forelse (($adminNotifications ?? collect()) as $notif)
        @php
            $rawData = $notif->data;
            $data = is_array($rawData)
                ? $rawData
                : (array) json_decode((string) $rawData, true);
            $title = $data['title'] ?? 'Notifikasi';
            $body = $data['body'] ?? '';
            $icon = $data['icon'] ?? '🔔';
            $link = route('admin.notifications.open', $notif->id);
        @endphp
        <a href="{{ $link }}" class="admin-notif-item {{ $notif->read_at ? '' : 'unread' }}" data-notif-id="{{ $notif->id }}" data-notif-unread="{{ $notif->read_at ? '0' : '1' }}">
            <div class="admin-notif-item-icon">{{ $icon }}</div>
            <div class="admin-notif-item-content">
                <div class="admin-notif-item-title">
                    @if (! $notif->read_at)<span class="admin-notif-dot"></span>@endif
                    {{ $title }}
                </div>
                <div class="admin-notif-item-body">{{ $body }}</div>
                <div class="admin-notif-item-time">{{ $notif->created_at ? $notif->created_at->diffForHumans() : '' }}</div>
            </div>
        </a>
    @empty
        <div class="admin-notif-empty">
            <div class="admin-notif-empty-icon">🔔</div>
            <p>Belum ada notifikasi aktivitas.</p>
        </div>
    @endforelse
</div>
<div class="admin-notif-dropdown-footer">
    <a href="{{ route('admin.notifications.index') }}" class="admin-notif-view-all">Lihat semua notifikasi</a>
    @if (($adminUnreadCount ?? 0) > 0)
        <form method="POST" action="{{ route('admin.notifications.readAll') }}">
            @csrf
            <button type="submit" class="admin-notif-mark-all">Tandai semua dibaca</button>
        </form>
    @endif
</div>