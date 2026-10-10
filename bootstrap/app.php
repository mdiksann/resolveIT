<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [HandleInertiaRequests::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();
            if (! $request->expectsJson() && in_array($status, [403, 404, 419, 500, 503]) && (! config('app.debug') || in_array($status, [403, 404, 419, 503]))) {
                $incidentId = $status === 500 ? (string) Str::uuid() : null;
                if ($incidentId) {
                    Log::error("Server Error [Incident: {$incidentId}]: ".$exception->getMessage(), [
                        'incident_id' => $incidentId,
                        'exception' => $exception,
                    ]);
                }

                $props = [
                    'status' => $status,
                ];

                if ($incidentId !== null) {
                    $props['incidentId'] = $incidentId;
                }

                return Inertia::render("Errors/{$status}", $props)
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
