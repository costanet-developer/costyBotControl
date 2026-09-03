<?php

namespace App\Models;

use App\Casts\BotDatetime;
use Illuminate\Database\Eloquent\Model;

class ValidacionIdentidad extends Model
{
    protected $table = 'validaciones_identidad';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'cedula_ingresada_en' => BotDatetime::class,
            'anverso_recibido_en' => BotDatetime::class,
            'reverso_recibido_en' => BotDatetime::class,
            'otp_expira_en' => BotDatetime::class,
            'actualizado_en' => BotDatetime::class,
            'ocr_vs_sistema_detalle' => 'array',
            'codigo_dactilar_validado' => 'boolean',
            'correo_verificado' => 'boolean',
            'derivado_revision' => 'boolean',
            'otp_intentos' => 'integer',
            'intentos_fallidos_comparacion' => 'integer',
        ];
    }

    public function sesion()
    {
        return $this->belongsTo(Sesion::class, 'sesion_id', 'sesion_id');
    }
}
