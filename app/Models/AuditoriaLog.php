<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaLog extends Model
{
    protected $table = 'auditoria_logs';
    public $timestamps = false;

    protected $fillable = [
        'usuario_id', 'accion', 'modulo', 'entidad', 'entidad_id',
        'datos_anteriores', 'datos_nuevos', 'direccion_ip', 'user_agent',
        'resultado', 'descripcion',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'fecha_hora' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Los registros de auditoría son inmutables.'));
        static::deleting(fn () => throw new \LogicException('Los registros de auditoría son inmutables.'));
    }
}
