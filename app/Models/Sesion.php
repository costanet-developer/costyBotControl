<?php

namespace App\Models;

use App\Casts\BotDatetime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Sesion extends Model
{
    protected $table = 'sesiones';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'inicio' => BotDatetime::class,
            'fin' => BotDatetime::class,
            'menu_generado_en' => BotDatetime::class,
            'intentos_comprobante' => 'integer',
            'mensajes_procesados' => 'array',
            'es_multiples_servicios' => 'boolean',
            'servicios_disponibles' => 'array',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'numero_whatsapp', 'numero_whatsapp');
    }

    public function eventos()
    {
        return $this->hasMany(EventoInteraccion::class, 'sesion_id', 'sesion_id');
    }

    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class, 'sesion_id', 'sesion_id');
    }

    public function comprobantePrincipal()
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_id');
    }

    public function documentosIdentidad()
    {
        return $this->hasMany(DocumentoIdentidad::class, 'sesion_id', 'sesion_id');
    }

    public function validacionesIdentidad()
    {
        return $this->hasMany(ValidacionIdentidad::class, 'sesion_id', 'sesion_id');
    }

    public function ultimaValidacionIdentidad()
    {
        return $this->hasOne(ValidacionIdentidad::class, 'sesion_id', 'sesion_id')
            ->latestOfMany('actualizado_en');
    }

    public function otpVerificaciones()
    {
        return $this->hasMany(OtpVerificacion::class, 'sesion_id', 'sesion_id');
    }

    public function saldosFavor()
    {
        return $this->hasMany(SaldoFavor::class, 'sesion_id', 'sesion_id');
    }

    public function scopePagoProcesado(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('resultado', 'reactivado')
                ->orWhereHas('eventos', fn (Builder $eventos) => $eventos->where('paso', 'reactivacion_exitosa'))
                ->orWhereHas('comprobantes', fn (Builder $comprobantes) => $comprobantes->where('estado', 'reactivacion_exitosa'))
                ->orWhereHas('comprobantePrincipal', fn (Builder $comprobante) => $comprobante->where('estado', 'reactivacion_exitosa'));
        });
    }

    public function scopeConComprobanteRelacionado(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereNotNull('comprobante_id')->orWhereHas('comprobantes');
        });
    }

    public function scopeSinComprobanteRelacionado(Builder $query): Builder
    {
        return $query->whereNull('comprobante_id')->whereDoesntHave('comprobantes');
    }

    public function scopeRecibidoSinProcesar(Builder $query): Builder
    {
        return $query->conComprobanteRelacionado()
            ->where(function (Builder $query) {
                $query->whereNull('resultado')->orWhere('resultado', '<>', 'reactivado');
            })
            ->whereDoesntHave('eventos', fn (Builder $eventos) => $eventos->where('paso', 'reactivacion_exitosa'))
            ->whereDoesntHave('comprobantes', fn (Builder $comprobantes) => $comprobantes->where('estado', 'reactivacion_exitosa'))
            ->whereDoesntHave('comprobantePrincipal', fn (Builder $comprobante) => $comprobante->where('estado', 'reactivacion_exitosa'));
    }

    public function scopeSinPagoNiComprobante(Builder $query): Builder
    {
        return $query->sinComprobanteRelacionado()
            ->where(function (Builder $query) {
                $query->whereNull('resultado')->orWhere('resultado', '<>', 'reactivado');
            })
            ->whereDoesntHave('eventos', fn (Builder $eventos) => $eventos->where('paso', 'reactivacion_exitosa'));
    }
}
