<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class BootstrapDemoAccount extends Command
{
    protected $signature = 'app:bootstrap-demo';

    protected $description = 'Create the first demo staff account from private deployment variables';

    public function handle(): int
    {
        if (! config('demo.enabled')) {
            $this->error('La creación de cuentas de demostración no está habilitada.');

            return self::FAILURE;
        }

        $credentials = [
            'email' => config('demo.email'),
            'password' => config('demo.password'),
        ];
        if (Validator::make($credentials, [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'max:255'],
        ])->fails()) {
            $this->error('Configure DEMO_EMAIL y DEMO_PASSWORD (al menos 12 caracteres) en las variables privadas del servicio.');

            return self::FAILURE;
        }

        $existing = User::where('email', $credentials['email'])->first();
        if ($existing) {
            if (! $existing->is_staff) {
                $this->error('La cuenta indicada ya existe sin permiso clínico. No se modificó.');

                return self::FAILURE;
            }
            $this->info('La cuenta de demostración ya está preparada. No se modificó su contraseña.');

            return self::SUCCESS;
        }

        $user = new User($credentials + ['name' => 'Responsable de la demostración']);
        $user->is_staff = true;
        $user->save();
        $this->info('Cuenta de demostración creada.');

        return self::SUCCESS;
    }
}
