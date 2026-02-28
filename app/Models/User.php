<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_OWNER = 'owner';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_STAFF = 'staff';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'business_id',
        'branch_id',
        'role',
        'is_active',
        'is_super_admin',
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
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    public static function assignableRoles(): array
    {
        return [
            self::ROLE_STAFF => 'Staff',
            self::ROLE_MANAGER => 'Manager',
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function canViewProfit(): bool
    {
        return $this->is_super_admin || $this->isOwner() || $this->isManager();
    }

    public function canAccessAbility(string $ability): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return match ($ability) {
            'manage_staff', 'manage_settings', 'manage_branches' => $this->isOwner(),
            'manage_catalog' => $this->isOwner() || $this->isManager() || $this->role === self::ROLE_STAFF,
            'adjust_stock', 'edit_sales' => $this->isOwner() || $this->isManager(),
            'view_profit' => $this->canViewProfit(),
            default => false,
        };
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
