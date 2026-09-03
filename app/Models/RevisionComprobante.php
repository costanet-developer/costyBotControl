<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevisionComprobante extends Model
{
    protected $table = 'revisiones_comprobante';
    public $timestamps = false;

    protected $fillable = [
        'comprobante_id', 'usuario_id', 'estado_anterior',
        'estado_nuevo', 'observacion',
    ];

    protected $casts = [
        'fecha_revision' => \App\Casts\BotDatetime::class,
    ];

    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
