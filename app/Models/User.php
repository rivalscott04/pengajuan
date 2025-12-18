<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'region_id',
        'name',
        'username',
        'email',
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

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function canImpersonate(): bool
    {
        return $this->hasRole('admin');
    }

    public function canBeImpersonated(): bool
    {
        $current = auth()->user();

        // Tidak boleh impersonate diri sendiri
        if ($current && $current->id === $this->id) {
            return false;
        }

        // Hanya user yang memang bisa akses panel yang boleh di-impersonate
        return $this->hasAnyRole([
            'admin',
            'operator_kabkota',
            'operator_kanwil',
        ]);
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // Admin bisa akses panel admin
        if ($panel->getId() === 'admin') {
            return $this->hasRole('admin');
        }
        
        // Operator bisa akses panel operator
        if ($panel->getId() === 'operator') {
            return $this->hasAnyRole(['operator_kabkota', 'operator_kanwil']);
        }
        
        return false;
    }
}
