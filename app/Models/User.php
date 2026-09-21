<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'domicile',
        'ktp_user_path',
        'ktp_orang_tua_path',
        'kartu_pelajar_path',
        'phone',
        'status',
        'role',
        'avatar_path',
        'password',
        'date_of_birth',
        'parent_consent_status',
        'parent_name',
        'parent_relation',
        'parent_phone',
        'parent_consent_path',
        'parent_consent_rejected_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
        ];
    }

    /**
     * Inisial untuk placeholder avatar
     */
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name));
        $initials = '';
        foreach ($words as $w) {
            if (!empty($w)) {
                $initials .= mb_strtoupper(mb_substr($w, 0, 1));
            }
            if (mb_strlen($initials) >= 2) {
                break;
            }
        }
        return $initials ?: 'U';
    }

    /**
     * URL publik foto KTP milik User sendiri (dipakai untuk user 17+).
     * Mengembalikan null bila User belum mengupload KTP.
     */
    public function getKtpUrlAttribute(): ?string
    {
        if (! empty($this->attributes['ktp_user_path'])) {
            return asset('storage/' . $this->attributes['ktp_user_path']);
        }

        return null;
    }

    /**
     * URL terkontrol untuk membuka KTP orang tua/wali (user di bawah 17 tahun).
     * Disimpan pada disk privat dan disajikan lewat route berizin
     * (admin atau user pemilik), bukan dari folder public.
     */
    public function getKtpOrangTuaUrlAttribute(): ?string
    {
        if (! empty($this->attributes['ktp_orang_tua_path'])) {
            return route('file.ktp-guardian', $this->getKey());
        }

        return null;
    }

    /**
     * URL terkontrol untuk membuka kartu pelajar (user di bawah 17 tahun).
     * Disimpan pada disk privat dan disajikan lewat route berizin.
     */
    public function getKartuPelajarUrlAttribute(): ?string
    {
        if (! empty($this->attributes['kartu_pelajar_path'])) {
            return route('file.student-card', $this->getKey());
        }

        return null;
    }

    /**
     * Status label display
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'AKTIF',
            'inactive' => 'NONAKTIF',
            'suspended' => 'TANGGUH',
            'pending', 'pending_verification' => 'MENUNGGU VERIFIKASI',
            default => strtoupper(str_replace('_', ' ', $this->status ?? 'AKTIF')),
        };
    }

    /**
     * Status badge CSS class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active' => 'user-badge-active',
            'inactive' => 'user-badge-inactive',
            'suspended' => 'user-badge-suspended',
            'pending', 'pending_verification' => 'user-badge-pending',
            default => 'user-badge-active',
        };
    }

    /**
     * Umur dihitung otomatis dari tanggal lahir.
     * Mengembalikan null bila tanggal lahir belum diisi.
     */
    public function getAgeAttribute(): ?int
    {
        $dob = $this->date_of_birth;
        if (empty($dob)) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($dob)->age;
    }

    /**
     * True bila user di bawah 17 tahun (wajib persetujuan orang tua).
     */
    public function getIsMinorAttribute(): bool
    {
        return $this->age !== null && $this->age < 17;
    }

    /**
     * Apakah persetujuan orang tua dibutuhkan (umur < 17).
     */
    public function getNeedsParentConsentAttribute(): bool
    {
        return $this->is_minor;
    }

    /**
     * Apakah user diperbolehkan menyewa alat di website.
     * User di bawah umur hanya boleh menyewa setelah persetujuan
     * orang tua/wali diverifikasi (disetujui) oleh admin.
     */
    public function canRent(): bool
    {
        if (! $this->needs_parent_consent) {
            return true;
        }

        return $this->parent_consent_status === 'verified';
    }

    /**
     * True bila user di bawah umur dan persetujuan orang tua/wali
     * belum disetujui admin, sehingga belum boleh menyewa.
     */
    public function getIsConsentPendingAttribute(): bool
    {
        return $this->needs_parent_consent && $this->parent_consent_status !== 'verified';
    }

    /**
     * Label status persetujuan orang tua untuk UI.
     */
    public function getParentConsentStatusLabelAttribute(): string
    {
        return match ($this->parent_consent_status) {
            'not_required' => 'TIDAK DIPERLUKAN',
            'pending' => 'MENUNGGU',
            'submitted' => 'MENUNGGU VERIFIKASI',
            'verified' => 'TERVERIFIKASI',
            'rejected' => 'DITOLAK',
            default => strtoupper(str_replace('_', ' ', $this->parent_consent_status ?? 'TIDAK DIPERLUKAN')),
        };
    }

    /**
     * Badge CSS class status persetujuan orang tua.
     */
    public function getParentConsentBadgeClassAttribute(): string
    {
        return match ($this->parent_consent_status) {
            'not_required' => 'user-badge-active',
            'pending', 'submitted' => 'user-badge-pending',
            'verified' => 'user-badge-success',
            'rejected' => 'user-badge-suspended',
            default => 'user-badge-active',
        };
    }

    /**
     * URL terkontrol untuk membuka bukti persetujuan orang tua.
     * File disimpan pada disk privat dan disajikan lewat route berizin
     * (admin atau user pemilik), bukan dari folder public.
     */
    public function getParentConsentUrlAttribute(): ?string
    {
        if (! empty($this->attributes['parent_consent_path'])) {
            return route('file.parent-consent', $this->getKey());
        }

        return null;
    }

    /**
     * Format tanggal lahir yang nyaman dibaca, mis. "12 Mei 2005".
     */
    public function getDateOfBirthFormattedAttribute(): ?string
    {
        if (empty($this->date_of_birth)) {
            return null;
        }

        return $this->date_of_birth->translatedFormat('d F Y');
    }

    /**
     * Relasi ke Orders
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relasi ke Reviews
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Relasi ke Refunds
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
