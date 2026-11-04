<?php

use App\Http\Controllers\AdjuntoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InspeccionController;
use App\Http\Controllers\InspeccionDanioController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\OrdenServicioController;
use App\Http\Controllers\PreferenciaController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\RefaccionController;
use App\Http\Controllers\RefaccionUsadaController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\TrabajoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VehiculoController;
use Illuminate\Support\Facades\Route;

/*
| Página pública del QR: la ve el cliente desde su celular, sin iniciar sesión.
*/
Route::get('/v/{token}', [QrController::class, 'show'])->name('qr.show');

/*
| Inicio de sesión
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

/*
| Todo lo demás requiere sesión. Los permisos de cada rol están en App\Enums\Rol.
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    // Clientes y vehículos
    Route::middleware('can:clientes.gestionar')->group(function () {
        Route::resource('clientes', ClienteController::class)->parameters(['clientes' => 'cliente']);
        Route::resource('vehiculos', VehiculoController::class)->parameters(['vehiculos' => 'vehiculo']);
    });
    Route::post('/vehiculos/{vehiculo}/qr', [VehiculoController::class, 'regenerarQr'])
        ->middleware('can:qr.regenerar')->name('vehiculos.qr');

    // Órdenes de servicio
    Route::get('/ordenes', [OrdenServicioController::class, 'index'])->name('ordenes.index');
    Route::get('/ordenes/nueva', [OrdenServicioController::class, 'create'])->middleware('can:recepcion.realizar')->name('ordenes.create');
    Route::post('/ordenes', [OrdenServicioController::class, 'store'])->middleware('can:recepcion.realizar')->name('ordenes.store');
    Route::get('/ordenes/{orden}', [OrdenServicioController::class, 'show'])->name('ordenes.show');
    Route::patch('/ordenes/{orden}', [OrdenServicioController::class, 'update'])->name('ordenes.update');
    Route::post('/ordenes/{orden}/estado', [OrdenServicioController::class, 'cambiarEstado'])->name('ordenes.estado');
    Route::post('/ordenes/{orden}/entrega', [OrdenServicioController::class, 'iniciarEntrega'])->middleware('can:entrega.realizar')->name('ordenes.entrega');

    // Trabajos y refacciones de la orden (mecánico y administrador)
    Route::middleware('can:ordenes.trabajar')->group(function () {
        Route::post('/ordenes/{orden}/trabajos', [TrabajoController::class, 'store'])->name('trabajos.store');
        Route::patch('/trabajos/{trabajo}', [TrabajoController::class, 'update'])->name('trabajos.update');
        Route::delete('/trabajos/{trabajo}', [TrabajoController::class, 'destroy'])->name('trabajos.destroy');
        Route::post('/ordenes/{orden}/refacciones', [RefaccionUsadaController::class, 'store'])->name('refacciones-usadas.store');
        Route::delete('/refacciones-usadas/{refaccionUsada}', [RefaccionUsadaController::class, 'destroy'])->name('refacciones-usadas.destroy');
    });

    // Recepción y entrega del vehículo (el permiso se revisa en el controlador según el tipo)
    Route::get('/inspecciones/{inspeccion}', [InspeccionController::class, 'edit'])->name('inspecciones.edit');
    Route::put('/inspecciones/{inspeccion}', [InspeccionController::class, 'update'])->name('inspecciones.update');
    Route::post('/inspecciones/{inspeccion}/completar', [InspeccionController::class, 'completar'])->name('inspecciones.completar');
    Route::post('/inspecciones/{inspeccion}/fotos', [AdjuntoController::class, 'storeFotos'])->name('inspecciones.fotos');
    Route::post('/inspecciones/{inspeccion}/firma', [AdjuntoController::class, 'storeFirma'])->name('inspecciones.firma');
    Route::post('/inspecciones/{inspeccion}/danios', [InspeccionDanioController::class, 'store'])->name('danios.store');
    Route::delete('/danios/{danio}', [InspeccionDanioController::class, 'destroy'])->name('danios.destroy');

    // Ver y borrar archivos (fotos y firmas se guardan en disco privado)
    Route::get('/adjuntos/{adjunto}', [AdjuntoController::class, 'show'])->name('adjuntos.show');
    Route::delete('/adjuntos/{adjunto}', [AdjuntoController::class, 'destroy'])->name('adjuntos.destroy');

    // Catálogos (solo administrador)
    Route::middleware('can:catalogos.gestionar')->group(function () {
        Route::resource('servicios', ServicioController::class)->except('show')->parameters(['servicios' => 'servicio']);
        // Refacciones que lleva cada servicio (se sugieren al agregarlo a una orden)
        Route::post('/servicios/{servicio}/refacciones', [ServicioController::class, 'agregarRefaccion'])->name('servicios.refacciones.store');
        Route::delete('/servicios/{servicio}/refacciones/{refaccion}', [ServicioController::class, 'quitarRefaccion'])->name('servicios.refacciones.destroy');
        Route::resource('refacciones', RefaccionController::class)->except('show')->parameters(['refacciones' => 'refaccion']);

        Route::get('/marcas', [MarcaController::class, 'index'])->name('marcas.index');
        Route::post('/marcas', [MarcaController::class, 'store'])->name('marcas.store');
        Route::post('/marcas/{marca}/modelos', [MarcaController::class, 'storeModelo'])->name('modelos.store');
        Route::patch('/modelos/{modelo}', [MarcaController::class, 'updateModelo'])->name('modelos.update');
        Route::post('/modelos/{modelo}/fusionar', [MarcaController::class, 'fusionarModelo'])->name('modelos.fusionar');
    });

    // Usuarios y preferencias (solo administrador)
    Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy'])
        ->parameters(['usuarios' => 'usuario'])->middleware('can:usuarios.gestionar');
    Route::get('/preferencias', [PreferenciaController::class, 'edit'])->middleware('can:preferencias.editar')->name('preferencias.edit');
    Route::put('/preferencias', [PreferenciaController::class, 'update'])->middleware('can:preferencias.editar')->name('preferencias.update');
});
