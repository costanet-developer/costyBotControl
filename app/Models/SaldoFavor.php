<?php

namespace App\Models;

use App\Casts\BotDatetime;
use Illuminate\Database\Eloquent\Model;

class SaldoFavor extends Model
{
    protected $table = 'saldos_a_favor';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'monto_pagado' => 'decimal:2',
            'monto_factura' => 'decimal:2',
            'excedente' => 'decimal:2',
            'fecha_registro' => BotDatetime::class,
        ];
    }

    public function sesion()
    {
        return $this->belongsTo(Sesion::class, 'sesion_id', 'sesion_id');
    }

    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_id');
    }
}
