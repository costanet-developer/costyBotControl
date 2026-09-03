<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventoInteraccion extends Model
{
    protected $table = 'eventos_interaccion';
    public $timestamps = false;

    public $casts = [
        'fecha_evento' => \App\Casts\BotDatetime::class,
        'datos_adicionales' => 'array',
        'monto_esperado' => 'decimal:2',
        'deuda_total' => 'decimal:2',
    ];

    public function sesion()
    {
        return $this->belongsTo(Sesion::class, 'sesion_id', 'sesion_id');
    }
}
