<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable // implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
    public const ROLE_PKM = 'pkm';
    public const ROLE_APPROVER = 'approver';
    public const ADMIN_ROLE_SUPER_ADMIN = 'super_admin';
    public const ADMIN_ROLE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'nomor_hp',
        'inisial',
        'role',
        'admin_role',
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
     * Get the allowed application roles.
     *
     * @return list<string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_USER,
            self::ROLE_PKM,
            self::ROLE_APPROVER,
        ];
    }

    /**
     * Get human readable labels for application roles.
     *
     * @return array<string, string>
     */
    public static function roleLabels(): array
    {
        return [
            self::ROLE_USER => 'Pembuat Order',
            self::ROLE_APPROVER => 'Approval',
            self::ROLE_PKM => 'Vendor',
            self::ROLE_ADMIN => 'Admin',
        ];
    }

    /**
     * Get the allowed admin subroles.
     *
     * @return array<string, string>
     */
    public static function adminRoleOptions(): array
    {
        return [
            self::ADMIN_ROLE_SUPER_ADMIN => 'Super Admin',
            self::ADMIN_ROLE_ADMIN => 'Admin',
        ];
    }

    /**
     * Get the dashboard route name for the user role.
     */
    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_PKM => 'pkm.dashboard',
            self::ROLE_APPROVER => 'approver.dashboard',
            default => 'user.dashboard',
        };
    }

    /**
     * Determine if the user has the given role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Resolve the admin subrole with a safe default for legacy records.
     */
    public function resolvedAdminRole(): ?string
    {
        if (! $this->hasRole(self::ROLE_ADMIN)) {
            return null;
        }

        return $this->admin_role ?: self::ADMIN_ROLE_SUPER_ADMIN;
    }

    /**
     * Determine if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Determine if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->resolvedAdminRole() === self::ADMIN_ROLE_SUPER_ADMIN;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        if (filled($this->inisial)) {
            return Str::of($this->inisial)->upper()->toString();
        }

        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /**
     * Get departments headed by the user.
     */
    public function headedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'general_manager_id');
    }

    /**
     * Get units managed by the user as senior manager.
     */
    public function seniorManagedUnits(): HasMany
    {
        return $this->hasMany(UnitWork::class, 'senior_manager_id');
    }

    /**
     * Get sections managed by the user.
     */
    public function managedSections(): HasMany
    {
        return $this->hasMany(UnitWorkSection::class, 'manager_id');
    }

    /**
     * Get admin menu access rows for the user.
     */
    public function adminMenuAccesses(): HasMany
    {
        return $this->hasMany(AdminMenuAccess::class);
    }

    /**
     * Determine if the admin user has access to a menu key.
     */
    public function hasAdminMenuAccess(string $menuKey): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->relationLoaded('adminMenuAccesses')) {
            return $this->adminMenuAccesses->contains('menu_key', $menuKey);
        }

        return $this->adminMenuAccesses()->where('menu_key', $menuKey)->exists();
    }
}
