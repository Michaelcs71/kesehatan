<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, HasRoles {
        // Alias method dari trait HasRoles supaya bisa dipanggil dengan nama berbeda
        HasRoles::hasPermissionTo as protected parentHasPermissionTo;
    }

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'whatsapp_number',
        'avatar_path',
        'is_active',
        'permission_overridden',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'is_active'             => 'boolean',
            'permission_overridden' => 'boolean',
            'role'                  => UserRole::class,
        ];
    }

    public function pasienProfile(): HasOne
    {
        return $this->hasOne(PasienProfile::class);
    }

    public function pmoProfile(): HasOne
    {
        return $this->hasOne(PmoProfile::class);
    }

    public function biodata(): HasOne
    {
        return $this->hasOne(UserBiodata::class);
    }

    public function pasienBinaan(): HasMany
    {
        return $this->hasMany(PasienProfile::class, 'pmo_id');
    }

    /**
     * Mappings di mana user ini sebagai PASIEN (mencari PMO-nya)
     */
    public function pasienPmoAsPasien(): HasMany
    {
        return $this->hasMany(PasienPmo::class, 'id_user');
    }

    /**
     * Mappings di mana user ini sebagai PMO (mencari pasien yang dia damping)
     */
    public function pasienPmoAsPmo(): HasMany
    {
        return $this->hasMany(PasienPmo::class, 'pmo_user_id');
    }

    public function isRole(UserRole|string $role): bool
    {
        $value = $role instanceof UserRole ? $role->value : $role;
        return $this->role?->value === $value;
    }

    public function isAdmin(): bool
    {
        return $this->role?->isAdmin() ?? false;
    }

    public function isSuperadmin(): bool
    {
        return $this->role?->isSuperadmin() ?? false;
    }

    public function isPasien(): bool
    {
        return $this->isRole(UserRole::PASIEN);
    }

    public function isPmo(): bool
    {
        return $this->isRole(UserRole::PMO);
    }

    /**
     * Apakah role user ini perlu biodata lengkap?
     * (Hanya pasien & PMO yang perlu)
     */
    public function requiresBiodata(): bool
    {
        return $this->isPasien() || $this->isPmo();
    }

    public function getInitials(): string
    {
        $words = explode(' ', trim($this->name ?? 'U'));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= strtoupper($w[0] ?? '');
        }
        return $initials ?: 'U';
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            UserRole::SUPERADMIN                  => 'superadmin.dashboard',
            UserRole::ADMIN                       => 'admin.dashboard',
            UserRole::PMO                         => 'pmo.dashboard',
            UserRole::PASIEN                      => 'pasien.dashboard',
            default                               => 'dashboard',
        };
    }

    // ============================================================
    // PERMISSION OVERRIDE LOGIC
    // ============================================================

    /**
     * Override Spatie's hasPermissionTo:
     * - Superadmin: always YES
     * - permission_overridden = true: hanya direct permissions
     * - default: pakai Spatie behavior (role + direct via trait)
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        if ($this->permission_overridden) {
            $permissionName = is_string($permission) ? $permission : $permission->name;
            return $this->getDirectPermissions()->contains('name', $permissionName);
        }

        return $this->parentHasPermissionTo($permission, $guardName);
    }

    /**
     * Custom getAllPermissions:
     * - Override mode: hanya direct
     * - Default: union role + direct (Spatie default)
     */
    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        if ($this->permission_overridden) {
            return $this->getDirectPermissions();
        }

        $rolePerms = $this->getPermissionsViaRoles();
        $direct    = $this->getDirectPermissions();

        return $direct->merge($rolePerms)->unique('id');
    }

    /**
     * Aktifkan mode override + sync direct permissions.
     * Default: snapshot dari role permissions saat ini.
     */
    public function enablePermissionOverride(?array $permissions = null): void
    {
        if ($permissions === null) {
            $permissions = $this->roles()->first()?->permissions->pluck('name')->toArray() ?? [];
        }

        $this->update(['permission_overridden' => true]);
        $this->syncPermissions($permissions);
    }

    /**
     * Matikan mode override (kembali ke role default)
     */
    public function disablePermissionOverride(): void
    {
        $this->update(['permission_overridden' => false]);
        $this->syncPermissions([]);
    }
}
