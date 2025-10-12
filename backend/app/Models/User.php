<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Role;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at', // Ocultar fecha de verificación por seguridad
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Relación con roles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole($roleName)
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Verificar si el usuario tiene cualquiera de los roles dados
     */
    public function hasAnyRole($roles)
    {
        return $this->roles()->whereIn('name', (array) $roles)->exists();
    }

    /**
     * Relación con cliente (si el usuario es un cliente)
     */
    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'email', 'email');
    }

    /**
     * Relación con panadero (si el usuario es panadero)
     */
    public function panadero()
    {
        return $this->hasOne(Panadero::class);
    }

    /**
     * Relación con vendedor (si el usuario es vendedor)
     */
    public function vendedor()
    {
        return $this->hasOne(Vendedor::class);
    }

    /**
     * Verificar si el usuario es cliente
     */
    public function esCliente()
    {
        return $this->hasRole('cliente');
    }

    /**
     * Verificar si el usuario es panadero
     */
    public function esPanadero()
    {
        return $this->hasRole('panadero');
    }

    /**
     * Verificar si el usuario es vendedor
     */
    public function esVendedor()
    {
        return $this->hasRole('vendedor');
    }
}
