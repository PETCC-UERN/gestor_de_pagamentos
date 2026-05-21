<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // Quando um Model não for encontrado (findOrFail falhou),
        // retorna 404 com uma mensagem estruturada.
        $exceptions->render(function (
            \Illuminate\Database\Eloquent\ModelNotFoundException $e,
            \Illuminate\Http\Request $request
        ): ?JsonResponse {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Recurso não encontrado.',
                    'status'  => 404,
                ], 404);
            }
            return null;
        });

        // Quando a validação falha (FormRequest rejeitou a requisição),
        // retorna 422 com a lista de erros por campo.
        $exceptions->render(function (
            \Illuminate\Validation\ValidationException $e,
            \Illuminate\Http\Request $request
        ): ?JsonResponse {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Dados inválidos.',
                    'errors'  => $e->errors(),
                    'status'  => 422,
                ], 422);
            }
            return null;
        });

    })->create();
