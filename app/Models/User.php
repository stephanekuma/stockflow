<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
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

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn(string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /**
     * Check if the user can access the given panel.
     *
     * @param \Filament\Panel $panel
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@inventory.test'); // && $this->hasVerifiedEmail();
    }

    /**
     * The stores that the user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Store>
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_user', 'user_id', 'store_id');
    }

    /**
     * Retrieve the collection of stores associated with the user.
     *
     * @param \Filament\Panel $panel
     * @return \Illuminate\Support\Collection
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->stores;
    }

    /**
     * Check if the user can access the given tenant.
     *
     * @param Model $tenant
     * @return bool
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->stores()->whereKey($tenant)->exists();
    }
}
