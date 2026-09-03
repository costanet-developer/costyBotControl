<?php

namespace App\Models;

use App\Casts\BotDatetime;
use Illuminate\Database\Eloquent\Model;

class OtpVerificacion extends Model
{
    protected $table = 'otp_verificaciones';

    public $timestamps = false;

    protected $hidden = [
        'codigo_enviado',
        'codigo_ingresado',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => BotDatetime::class,
            'expira_en' => BotDatetime::class,
            'intentos' => 'integer',
            'max_intentos' => 'integer',
        ];
    }

    public function sesion()
    {
        return $this->belongsTo(Sesion::class, 'sesion_id', 'sesion_id');
    }
}
