<?php

namespace App\Console\Commands;

use App\Services\CasoOperativoDetector;
use Illuminate\Console\Command;

class DetectarCasosOperativos extends Command
{
    protected $signature = 'costy:detectar-casos-operativos';

    protected $description = 'Detecta y actualiza casos operativos sin modificar las tablas administradas por n8n';

    public function handle(CasoOperativoDetector $detector): int
    {
        $resultado = $detector->detectar();

        $this->info("Casos nuevos: {$resultado['nuevos']}; actualizados: {$resultado['actualizados']}");
        $this->table(
            ['Tipo', 'Detectados'],
            collect($resultado['por_tipo'])->map(fn ($cantidad, $tipo) => [$tipo, $cantidad])->values()->all()
        );

        return self::SUCCESS;
    }
}
