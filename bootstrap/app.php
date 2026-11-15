<?php

use App\Http\Middleware\ClinicalHeaders;
use App\Http\Middleware\EnsureClinicalAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'clinical.access' => EnsureClinicalAccess::class,
            'clinical.headers' => ClinicalHeaders::class,
        ]);
        $middleware->prependToPriorityList(SubstituteBindings::class, EnsureClinicalAccess::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
