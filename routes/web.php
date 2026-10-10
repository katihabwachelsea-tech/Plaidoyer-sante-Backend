<?php

use App\Http\Controllers\Web\AdminWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::get('/admin/login', [AdminWebController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [AdminWebController::class, 'login'])->name('admin.login.submit');

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', [AdminWebController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/logout', [AdminWebController::class, 'logout'])->name('admin.logout');
    Route::post('/medecins/{id}/validate', [AdminWebController::class, 'validateMedecin'])->name('admin.medecins.validate');
    Route::post('/services/{id}', [AdminWebController::class, 'updateService'])->name('admin.services.update');
});
