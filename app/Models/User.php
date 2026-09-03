<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'nombre', 'apellido', 'email', 'password', 'activo', 'bloqueado',
    'intentos_fallidos', 'ultimo_acceso', 'creado_por', 'actualizado_por',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'bloqueado' => 'boolean',
            'ultimo_acceso' => 'datetime',
        ];
    }

    public function getNameAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }

    public function auditoriaLogs()
    {
        return $this->hasMany(AuditoriaLog::class, 'usuario_id');
    }
}
