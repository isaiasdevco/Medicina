<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('app:authorize-staff {email} {--revoke}', function () {
    $user = User::where('email', $this->argument('email'))->first();
    if (! $user) {
        $this->error('No existe una cuenta con ese correo. Regístrela primero.');

        return 1;
    }
    $user->is_staff = ! $this->option('revoke');
    $user->save();
    $this->info($user->is_staff ? 'Acceso clínico autorizado.' : 'Acceso clínico revocado.');

    return 0;
})->purpose('Autorizar o revocar acceso clínico desde una consola de confianza');
