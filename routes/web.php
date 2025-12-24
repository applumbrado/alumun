<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Catalogos\GrupoController;
use App\Http\Controllers\Catalogos\PeriodosController;
use App\Http\Controllers\Catalogos\ServicioController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\CFE\CFEImportController;
use App\Http\Controllers\User\BulkPermissionsController;
use App\Http\Controllers\User\BulkUserRolesController;
use App\Http\Controllers\User\Reporting\UserReportingController;
use App\Http\Controllers\UserController;
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

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users.store', [UserController::class, 'store'])->name('users.store');
    Route::put('users.update/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users.delete/{user}', [UserController::class, 'destroy'])->name('users.delete');
    Route::get('users/download/data', [UserReportingController::class,'downloadUsersData'])->name('users.download');

    Route::get('/bulk-roles', [BulkUserRolesController::class, 'edit'])->name('bulk.roles.edit');
    Route::post('/bulk-roles/assign-partial', [BulkUserRolesController::class, 'assignPartial'])->name('bulk.roles.assignPartial');
    Route::post('/bulk-roles/remove-partial', [BulkUserRolesController::class, 'removePartial'])->name('bulk.roles.removePartial');

    Route::get('/bulk-permisos', [BulkPermissionsController::class, 'edit'])->name('bulk.permisos.edit');
    Route::post('/bulk-permisos/assign-partial', [BulkPermissionsController::class, 'assignPartial'])->name('bulk.permisos.assignPartial');
    Route::post('/bulk-permisos/remove-partial', [BulkPermissionsController::class, 'removePartial'])->name('bulk.permisos.removePartial');



});

//require __DIR__.'/auth.php';
