<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Services\ClienteService;
use Illuminate\Http\JsonResponse;

class ClienteController extends Controller
{
    // Injeção de dependência via construtor.
    // O Laravel resolve automaticamente a instância do ClienteService.
    public function __construct(private ClienteService $service)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->service->listarTodos());
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        // $request->validated() retorna apenas os campos que passaram pela validação,
        // descartando qualquer campo extra que tenha vindo na requisição.
        $cliente = $this->service->criar($request->validated());
        return response()->json($cliente, 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json($cliente);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $atualizado = $this->service->atualizar($cliente, $request->validated());
        return response()->json($atualizado);
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $this->service->deletar($cliente);
        return response()->json(null, 204);
    }
}
