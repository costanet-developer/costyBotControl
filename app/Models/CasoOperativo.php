<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasoOperativo extends Model
{
    protected $table = 'casos_operativos';

    protected $fillable = [
        'clave', 'tipo', 'prioridad', 'estado', 'sesion_id', 'comprobante_id',
        'saldo_favor_id', 'validacion_identidad_id', 'otp_verificacion_id',
        'titulo', 'detalle', 'detectado_en', 'ultima_deteccion_en', 'asignado_a',
        'asignado_en', 'resuelto_por', 'resuelto_en', 'resolucion',
    ];

    protected function casts(): array
    {
        return [
            'detalle' => 'array',
            'detectado_en' => 'datetime',
            'ultima_deteccion_en' => 'datetime',
            'asignado_en' => 'datetime',
            'resuelto_en' => 'datetime',
        ];
    }

    public function sesion()
    {
        return $this->belongsTo(Sesion::class, 'sesion_id', 'sesion_id');
    }

    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class);
    }

    public function saldoFavor()
    {
        return $this->belongsTo(SaldoFavor::class);
    }

    public function validacionIdentidad()
    {
        return $this->belongsTo(ValidacionIdentidad::class);
    }

    public function otpVerificacion()
    {
        return $this->belongsTo(OtpVerificacion::class);
    }

    public function asignadoA()
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    public function resueltoPor()
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }

    public function alertas()
    {
        return $this->hasMany(AlertaOperativa::class);
    }
}
