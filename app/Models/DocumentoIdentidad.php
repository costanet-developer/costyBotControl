<?php

namespace App\Models;

use App\Casts\BotDatetime;
use Illuminate\Database\Eloquent\Model;

class DocumentoIdentidad extends Model
{
    protected $table = 'documentos_identidad';

    public $timestamps = false;

    public $casts = [
        'fecha_hora' => BotDatetime::class,
        'coincide' => 'boolean',
        'ocr_valido' => 'boolean',
        'ocr_json' => 'array',
        'ocr_confianza' => 'decimal:2',
    ];

    public function sesion()
    {
        return $this->belongsTo(Sesion::class, 'sesion_id', 'sesion_id');
    }
}
