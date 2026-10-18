<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleName;
use App\Models\Cart\Cart;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Address;
use App\Models\Coupon;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property mixed $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Coupon> $coupons
 * @property-read int|null $coupons_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * 
 *  @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @property-read Collection<int, Coupon> $coupons
 * @property-read int|null $coupons_count
 *
 * @property-read Collection<int, Address> $addresses
 * @property-read int|null $addresses_count
 * @property-read SellerStripeAccount|null $stripeAccount
 * 
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
    ];

    protected $with = ['roles'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function booted(): void
    {
        static::created(function (User $user) {
            $user->assignRole(RoleName::USER);
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(RoleName|string $role): bool
    {
        return $this->hasAnyRole([$role]);
    }

    /**
     * @param array<int, RoleName|string> $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $roleNames = collect($roles)
            ->map(fn(RoleName|string $role) => $role instanceof RoleName ? $role->value : strtolower($role));

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn(Role $role) => $roleNames->contains($role->name));
        }

        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    public function assignRole(RoleName|string ...$roles): self
    {
        $roleNames = collect($roles)
            ->map(fn(RoleName|string $role) => $role instanceof RoleName ? $role->value : strtolower($role));
        $roleIds = Role::query()->whereIn('name', $roleNames)->pluck('id');

        $this->roles()->syncWithoutDetaching($roleIds);
        $this->unsetRelation('roles');

        return $this;
    }

    public function syncRoles(RoleName|string ...$roles): self
    {
        $roleNames = collect($roles)
            ->map(fn(RoleName|string $role) => $role instanceof RoleName ? $role->value : strtolower($role));
        $roleIds = Role::query()->whereIn('name', $roleNames)->pluck('id');

        $this->roles()->sync($roleIds);
        $this->unsetRelation('roles');

        return $this;
    }


    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function coupons()
    {
        $query = $this->hasMany(Coupon::class, 'seller_id');
        if (!$this->hasRole(RoleName::SELLER)) {
            $query->whereRaw('1 = 0');
        }
        return $query;
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, "user_id");
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function stripeAccount(): HasOne
    {
        return $this->hasOne(SellerStripeAccount::class, 'seller_id');
    }

}
