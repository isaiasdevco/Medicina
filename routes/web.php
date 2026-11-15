<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\PacienteController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes();
Route::view('/', 'welcome')->name('welcome');
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth', 'clinical.access', 'clinical.headers'])->group(function () {
    Route::get('/lista', [PacienteController::class, 'index'])->name('paciente.index');
    Route::get('/form', [PacienteController::class, 'create'])->name('paciente.create');
    Route::post('/form', [PacienteController::class, 'store'])->name('paciente.store');
    Route::get('/form/{paciente}/edit', [PacienteController::class, 'edit'])->name('paciente.edit');
    Route::put('/form/{paciente}', [PacienteController::class, 'update'])->name('paciente.update');
    Route::delete('/form/{paciente}', [PacienteController::class, 'destroy'])->name('paciente.delete');
    Route::get('/paciente/{paciente}/foto', [PacienteController::class, 'photo'])->name('paciente.photo');
    Route::get('/paciente/pdf/{paciente}', [PacienteController::class, 'pdf'])->name('paciente.pdf');
    Route::get('/lista/qr/{paciente}', [PacienteController::class, 'qr'])->name('paciente.qr');

    Route::get('/listaMedico', [MedicoController::class, 'index'])->name('medico.index');
    Route::get('/formMedico', [MedicoController::class, 'create'])->name('medico.create');
    Route::post('/formMedico', [MedicoController::class, 'store'])->name('medico.store');
    Route::get('/formMedico/{medico}/edit', [MedicoController::class, 'edit'])->name('medico.edit');
    Route::put('/formMedico/{medico}', [MedicoController::class, 'update'])->name('medico.update');
    Route::delete('/formMedico/{medico}', [MedicoController::class, 'destroy'])->name('medico.delete');
});
