<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFaturaRequest;
use App\Models\Fatura;
use App\Services\FaturaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaturaController extends Controller
{
    public function __construct(private FaturaService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        // Se vier o parâmetro ?cliente_id=1 na URL, filtra por cliente.
        // Caso contrário, retorna todas as faturas.
        if ($request->has('cliente_id')) {
            return response()->json(
                $this->service->listarPorCliente($request->integer('cliente_id'))
            );
        }

        return response()->json($this->service->listarTodas());
    }

    public function store(StoreFaturaRequest $request): JsonResponse
    {
        $fatura = $this->service->criar($request->validated());
        return response()->json($fatura->load('cliente'), 201);
        // load('cliente') carrega o relacionamento na resposta,
        // para que o JSON retorne os dados do cliente junto com a fatura criada.
    }

    public function show(Fatura $fatura): JsonResponse
    {
        return response()->json($fatura->load('cliente'));
    }

    public function destroy(Fatura $fatura): JsonResponse
    {
        $this->service->deletar($fatura);
        return response()->json(null, 204);
    }
}
