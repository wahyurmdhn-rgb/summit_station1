<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'ktp_path',
        'phone',
        'status',
        'role',
        'avatar_path',
        'password',
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
     * URL publik foto KTP milik User ini (dari path storage).
     * Mengembalikan null bila User belum mengupload KTP.
     */
    public function getKtpUrlAttribute(): ?string
    {
        if (! empty($this->attributes['ktp_path'])) {
            return asset('storage/' . $this->attributes['ktp_path']);
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
