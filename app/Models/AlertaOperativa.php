<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaOperativa extends Model
{
    protected $table = 'alertas_operativas';

    protected $fillable = [
        'clave', 'caso_operativo_id', 'tipo', 'nivel', 'estado', 'destinatarios',
        'notificada_en', 'estado_email', 'email_enviado_en', 'ultimo_error',
    ];

    protected function casts(): array
    {
        return [
            'destinatarios' => 'array',
            'notificada_en' => 'datetime',
            'email_enviado_en' => 'datetime',
        ];
    }

    public function caso()
    {
        return $this->belongsTo(CasoOperativo::class, 'caso_operativo_id');
    }
}
