<?php

namespace App\Services;

use App\Models\Fatura;
use Illuminate\Database\Eloquent\Collection;

class FaturaService
{
    public function listarTodas(): Collection
    {
        return Fatura::with('cliente')->get();
        // with('cliente') faz eager loading: carrega o cliente junto com cada fatura
        // em uma única query, evitando o problema N+1.
    }

    public function listarPorCliente(int $clienteId): Collection
    {
        return Fatura::where('cliente_id', $clienteId)->get();
    }

    public function buscarPorId(int $id): Fatura
    {
        return Fatura::with('cliente')->findOrFail($id);
    }

    public function criar(array $dados): Fatura
    {
        return Fatura::create($dados);
    }

    public function deletar(Fatura $fatura): void
    {
        $fatura->delete();
    }
}
