<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Catalogos\GrupoController;
use App\Http\Controllers\Catalogos\PeriodosController;
use App\Http\Controllers\Catalogos\ServicioController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\CFE\CFEImportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('welcome');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login.show');
    Route::post('/login', [LoginController::class, 'login'])->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::prefix('catalogos')->group(function () {

        Route::get('/grupos', [GrupoController::class, 'index'])->name('grupos.index');
        Route::post('/grupos', [GrupoController::class, 'store'])->name('grupos.store');
        Route::put('/grupos/{grupo}', [GrupoController::class, 'update'])->name('grupos.update');
        Route::delete('/grupos/{grupo}', [GrupoController::class, 'destroy'])->name('grupos.destroy');

        Route::get('/servicios', [ServicioController::class, 'index'])->name('servicios.index');
        Route::post('/servicios', [ServicioController::class, 'store'])->name('servicios.store');
        Route::put('/servicios/{servicio}', [ServicioController::class, 'update'])->name('servicios.update');
        Route::delete('/servicios/{servicio}', [ServicioController::class, 'destroy'])->name('servicios.destroy');


    });

    Route::prefix('cfe')->group(function () {

        Route::get('/importar/index', [CFEImportController::class, 'index'])->name('cfe.importar.index');
        Route::post('/importar', [CFEImportController::class, 'importar'])->name('cfe.importar');


    });

    Route::get('/periodos', [PeriodosController::class, 'index'])->name('periodos.index');
    Route::post('/periodos', [PeriodosController::class, 'store'])->name('periodos.store');
    Route::put('/periodos/{periodo}', [PeriodosController::class, 'update'])->name('periodos.update');
    Route::delete('/periodos/{periodo}', [PeriodosController::class, 'destroy'])->name('periodos.destroy');

    Route::post('/periodos/{periodo}/predeterminar', [PeriodosController::class, 'setPredeterminado'])
        ->name('periodos.predeterminar');





});

//require __DIR__.'/auth.php';
