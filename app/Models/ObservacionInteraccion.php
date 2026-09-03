<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObservacionInteraccion extends Model
{
    protected $table = 'observaciones_interaccion';

    use SoftDeletes;

    protected $fillable = ['sesion_id', 'comprobante_id', 'usuario_id', 'observacion'];

    protected function casts(): array
    {
        return [
            'created_at' => \App\Casts\BotDatetime::class,
            'updated_at' => \App\Casts\BotDatetime::class,
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
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
