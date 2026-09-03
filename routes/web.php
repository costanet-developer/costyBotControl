<?php

use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\CasoOperativoController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\ComprobanteImagenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoIdentidadImagenController;
use App\Http\Controllers\InteraccionController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PendienteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResumenGerencialController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified', 'user.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/interacciones', [InteraccionController::class, 'index'])->name('interacciones.index');
    Route::get('/interacciones/{sesion}', [InteraccionController::class, 'show'])->name('interacciones.show');
    Route::get('/interacciones/{sesion}/detalle', [InteraccionController::class, 'detalle'])->name('interacciones.detalle');
    Route::get('/pendientes', [PendienteController::class, 'index'])->name('pendientes.index');
    Route::patch('/casos-operativos/{caso}/tomar', [CasoOperativoController::class, 'tomar'])->name('casos-operativos.tomar');
    Route::patch('/casos-operativos/{caso}/cerrar', [CasoOperativoController::class, 'cerrar'])->name('casos-operativos.cerrar');
    Route::patch('/casos-operativos/{caso}/reabrir', [CasoOperativoController::class, 'reabrir'])->name('casos-operativos.reabrir');
    Route::patch('/comprobantes/{comprobante}/estado', [ComprobanteController::class, 'cambiarEstado'])->name('comprobantes.estado');

    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::patch('/usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update');
    Route::post('/usuarios/{user}/toggle-activo', [UserController::class, 'toggleActivo'])->name('usuarios.toggle-activo');
    Route::post('/usuarios/{user}/toggle-bloqueo', [UserController::class, 'toggleBloqueo'])->name('usuarios.toggle-bloqueo');
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');

    Route::get('/reportes', [ReportController::class, 'index'])->name('reportes.index');
    Route::post('/reportes/export', [ReportController::class, 'export'])->name('reportes.export');
    Route::get('/resumen-gerencial', [ResumenGerencialController::class, 'index'])->name('resumen-gerencial.index');
    Route::post('/resumen-gerencial/export', [ResumenGerencialController::class, 'export'])->name('resumen-gerencial.export');
    Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    Route::post('/auditoria/export', [AuditoriaController::class, 'export'])->name('auditoria.export');
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::patch('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodas'])->name('notificaciones.leer-todas');
    Route::get('/notificaciones/{notificacion}/abrir', [NotificacionController::class, 'abrir'])->name('notificaciones.abrir');

    Route::get('/comprobantes/{comprobante}/imagen', [ComprobanteImagenController::class, 'show'])->name('comprobantes.imagen');
    Route::get('/comprobantes/{comprobante}/descargar', [ComprobanteImagenController::class, 'descargar'])->name('comprobantes.descargar');
    Route::get('/documentos-identidad/{documento}/imagen', [DocumentoIdentidadImagenController::class, 'show'])->name('documentos-identidad.imagen');
    Route::get('/documentos-identidad/{documento}/descargar', [DocumentoIdentidadImagenController::class, 'descargar'])->name('documentos-identidad.descargar');
});

Route::middleware(['auth', 'user.active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
