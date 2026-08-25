<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'phone',
        'password',
        'profile_photo_path',
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

    public function setRoleAttribute($value): void
    {
        $this->attributes['role'] = strtolower(trim((string) $value));
    }

    public static function supportsPhoneColumn(): bool
    {
        return Schema::hasColumn((new static)->getTable(), 'phone');
    }

    public static function normalizePhone(mixed $phone): ?string
    {
        return filled($phone) ? trim((string) $phone) : null;
    }

    public static function mergePhoneAttribute(array $attributes, mixed $phone): array
    {
        if (!self::supportsPhoneColumn()) {
            return $attributes;
        }

        $attributes['phone'] = self::normalizePhone($phone);

        return $attributes;
    }

    public function getDirectoryPhoneAttribute(): ?string
    {
        $phone = self::normalizePhone($this->getAttribute('phone'));
        if ($phone !== null) {
            return $phone;
        }

        if (!$this->isOwner()) {
            return null;
        }

        $businessPhone = self::normalizePhone($this->business?->phone);
        if ($businessPhone !== null) {
            return $businessPhone;
        }

        return self::normalizePhone($this->branch?->phone);
    }

    public static function assignableRoles(): array
    {
        return [
            self::ROLE_STAFF => 'Staff',
            self::ROLE_MANAGER => 'Manager',
        ];
    }

    private function normalizedRole(): string
    {
        return strtolower(trim((string) $this->role));
    }

    public function isOwner(): bool
    {
        return $this->normalizedRole() === self::ROLE_OWNER;
    }

    public function isManager(): bool
    {
        return $this->normalizedRole() === self::ROLE_MANAGER;
    }

    public function isStaff(): bool
    {
        return $this->normalizedRole() === self::ROLE_STAFF;
    }

    public function isManagerLevel(): bool
    {
        return $this->isManager() || $this->isStaff();
    }

    public function canViewFinancials(): bool
    {
        return $this->is_super_admin || $this->isOwner() || $this->isManager();
    }

    public function canViewProfit(): bool
    {
        return $this->canViewFinancials();
    }

    public function canViewAllSales(): bool
    {
        return $this->is_super_admin || $this->isOwner() || $this->isManager();
    }

    public function canAccessSaleRecord(Sale $sale): bool
    {
        return $this->canViewAllSales() || (int) $sale->user_id === (int) $this->id;
    }

    public function canAccessAbility(string $ability): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $canManageInventory = $this->isOwner() || $this->isManagerLevel();
        $canEditSales = $this->isOwner() || $this->isManagerLevel();

        return match ($ability) {
            'manage_staff', 'manage_settings', 'manage_branches' => $this->isOwner(),
            'manage_profile' => $this->isOwner() || $this->isManager(),
            'delete_products' => $this->isOwner() || $this->isManager(),
            'manage_catalog', 'view_stock', 'add_stock', 'adjust_stock' => $canManageInventory,
            'edit_sales' => $canEditSales,
            'view_profit' => $this->canViewProfit(),
            default => false,
        };
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        $path = trim((string) $this->profile_photo_path);

        if ($path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function getDisplayNameAttribute(): string
    {
        $name = trim((string) $this->name);

        if ($name !== '' && !filter_var($name, FILTER_VALIDATE_EMAIL)) {
            return $name;
        }

        $emailSource = $name !== '' ? $name : trim((string) $this->email);
        if ($emailSource !== '' && filter_var($emailSource, FILTER_VALIDATE_EMAIL)) {
            $localPart = Str::before($emailSource, '@');
            $normalized = preg_replace('/[._-]+/', ' ', $localPart) ?? $localPart;
            $display = Str::of($normalized)->trim()->title()->toString();

            return $display !== '' ? $display : $emailSource;
        }

        if ($name !== '') {
            return $name;
        }

        $email = trim((string) $this->email);

        return $email !== '' ? $email : 'Unknown User';
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
