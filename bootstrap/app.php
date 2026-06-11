<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            $apiRoutes = __DIR__.'/../routes/api.php';
            if (is_file($apiRoutes)) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group($apiRoutes);
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'parent.bearer' => \App\Http\Middleware\ParentApiPreferBearerToken::class,
            'parent.sanctum' => \App\Http\Middleware\EnsureParentSanctumUser::class,
        ]);
        $middleware->web(prepend: [
            \App\Http\Middleware\TenantDatabaseMiddleware::class,
        ]);
        $middleware->api(prepend: [
            \App\Http\Middleware\TenantDatabaseMiddleware::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle API authentication exceptions
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'token' => null,
                ], 200);
            }
        });
        
        // Handle API method not allowed exceptions
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Method not allowed. Please use POST method for this endpoint.',
                    'token' => null,
                ], 200);
            }
        });
    })->create();
