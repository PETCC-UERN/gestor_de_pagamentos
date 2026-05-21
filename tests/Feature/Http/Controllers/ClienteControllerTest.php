<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ClienteController
 */
final class ClienteControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $clientes = Cliente::factory()->count(3)->create();

        $response = $this->get(route('clientes.index'));

        $response->assertOk();
        $response->assertJson($clientes);
    }


    #[Test]
    public function show_responds_with(): void
    {
        $cliente = Cliente::factory()->create();

        $response = $this->get(route('clientes.show', $cliente));

        $response->assertOk();
        $response->assertJson($cliente);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ClienteController::class,
            'store',
            \App\Http\Requests\ClienteStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $nome = fake()->word();
        $email = fake()->safeEmail();
        $telefone = fake()->word();

        $response = $this->post(route('clientes.store'), [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
        ]);

        $clientes = Cliente::query()
            ->where('nome', $nome)
            ->where('email', $email)
            ->where('telefone', $telefone)
            ->get();
        $this->assertCount(1, $clientes);
        $cliente = $clientes->first();

        $response->assertOk();
        $response->assertJson($cliente 201);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ClienteController::class,
            'update',
            \App\Http\Requests\ClienteUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $cliente = Cliente::factory()->create();
        $nome = fake()->word();
        $email = fake()->safeEmail();
        $telefone = fake()->word();

        $response = $this->put(route('clientes.update', $cliente), [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
        ]);

        $cliente->refresh();

        $response->assertOk();
        $response->assertJson($cliente);

        $this->assertEquals($nome, $cliente->nome);
        $this->assertEquals($email, $cliente->email);
        $this->assertEquals($telefone, $cliente->telefone);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $cliente = Cliente::factory()->create();

        $response = $this->delete(route('clientes.destroy', $cliente));

        $response->assertNoContent();

        $this->assertModelMissing($cliente);
    }
}
