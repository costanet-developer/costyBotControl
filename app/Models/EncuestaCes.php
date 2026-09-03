<?php

namespace App\Models;

use App\Casts\BotDatetime;
use Illuminate\Database\Eloquent\Model;

class EncuestaCes extends Model
{
    protected $table = 'encuestas_ces';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'programada_para' => BotDatetime::class,
            'enviada_en' => BotDatetime::class,
            'respondida_en' => BotDatetime::class,
            'vencida_en' => BotDatetime::class,
            'creado_en' => BotDatetime::class,
            'actualizado_en' => BotDatetime::class,
            'puntuacion' => 'integer',
            'intentos_envio' => 'integer',
        ];
    }
}
