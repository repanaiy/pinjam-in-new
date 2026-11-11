<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role', 'nis', 'employee_code',
        'kelas', 'phone', 'avatar_path', 'is_active', 'late_count', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'late_count' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isSiswa(): bool
    {
        return $this->role === Role::Siswa;
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function scopeSiswa($q)
    {
        return $q->where('role', Role::Siswa->value);
    }

    public function scopeAdmins($q)
    {
        return $q->where('role', Role::Admin->value);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }
}
