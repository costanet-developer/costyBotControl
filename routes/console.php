<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('costy:detectar-casos-operativos')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

Schedule::command('costy:procesar-alertas-operativas')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

Schedule::command('costy:enviar-resumen-gerencial diario')
    ->dailyAt('07:30')
    ->timezone('America/Guayaquil')
    ->withoutOverlapping(30);

Schedule::command('costy:enviar-resumen-gerencial semanal')
    ->weeklyOn(1, '07:45')
    ->timezone('America/Guayaquil')
    ->withoutOverlapping(30);
