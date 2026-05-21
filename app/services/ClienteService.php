<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Collection;

class ClienteService
{
    public function listarTodos(): Collection
    {
        return Cliente::all();
    }

    public function buscarPorId(int $id): Cliente
    {
        // findOrFail lança ModelNotFoundException se não encontrar,
        // que o Laravel converte automaticamente em resposta 404.
        return Cliente::findOrFail($id);
    }

    public function criar(array $dados): Cliente
    {
        return Cliente::create($dados);
    }

    public function atualizar(Cliente $cliente, array $dados): Cliente
    {
        $cliente->update($dados);
        return $cliente->fresh(); // recarrega o modelo do banco após o update
    }

    public function deletar(Cliente $cliente): void
    {
        $cliente->delete();
    }
}
