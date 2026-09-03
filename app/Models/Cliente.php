<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'numero_whatsapp';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'primera_interaccion' => \App\Casts\BotDatetime::class,
            'ultima_interaccion' => \App\Casts\BotDatetime::class,
        ];
    }

    public function sesiones()
    {
        return $this->hasMany(Sesion::class, 'numero_whatsapp', 'numero_whatsapp');
    }
}
