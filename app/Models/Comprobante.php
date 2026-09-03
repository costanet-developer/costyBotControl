<?php

namespace App\Models;

use App\Casts\BotDatetime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comprobante extends Model
{
    protected $table = 'comprobantes';

    protected $primaryKey = 'id';

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'banco_valido' => 'boolean',
            'cuenta_valida' => 'boolean',
            'titular_valido' => 'boolean',
            'tiene_observaciones' => 'boolean',
            'fecha_hora' => BotDatetime::class,
            'created_at' => BotDatetime::class,
            'updated_at' => BotDatetime::class,
            'revisado_en' => BotDatetime::class,
            'aprobado_en' => BotDatetime::class,
            'rechazado_en' => BotDatetime::class,
            'alertas' => 'array',
            'alertas_ia_generativa' => 'array',
        ];
    }

    public function sesion()
    {
        return $this->belongsTo(Sesion::class, 'sesion_id', 'sesion_id');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function rechazadoPor()
    {
        return $this->belongsTo(User::class, 'rechazado_por');
    }

    public function observaciones()
    {
        return $this->hasMany(ObservacionInteraccion::class, 'comprobante_id');
    }

    public function revisiones()
    {
        return $this->hasMany(RevisionComprobante::class, 'comprobante_id');
    }

    public function saldosFavor()
    {
        return $this->hasMany(SaldoFavor::class, 'comprobante_id');
    }
}
