<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Kolom yang boleh diisi secara mass-assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * Kolom yang disembunyikan saat data user dijadikan array/json.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting atribut user.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Helper untuk mengecek apakah user memiliki role tertentu.
     *
     * Contoh:
     * auth()->user()->hasRole('admin')
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Helper untuk mengecek apakah akun user aktif.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * User bisa mencatat banyak barang masuk.
     */
    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class);
    }

    /**
     * User bisa membuat banyak permintaan barang.
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }

    /**
     * User approver bisa menyetujui banyak permintaan.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(Request::class, 'approver_id');
    }
}