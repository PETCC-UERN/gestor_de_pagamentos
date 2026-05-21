<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Cliente;
use App\Models\Fatura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\FaturaController
 */
final class FaturaControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $faturas = Fatura::factory()->count(3)->create();

        $response = $this->get(route('faturas.index'));

        $response->assertOk();
        $response->assertJson($faturas);
    }


    #[Test]
    public function show_responds_with(): void
    {
        $fatura = Fatura::factory()->create();

        $response = $this->get(route('faturas.show', $fatura));

        $response->assertOk();
        $response->assertJson($fatura);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\FaturaController::class,
            'store',
            \App\Http\Requests\FaturaStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $cliente = Cliente::factory()->create();
        $valor = fake()->randomFloat(/** decimal_attributes **/);
        $vencimento = Carbon::parse(fake()->date());
        $status = fake()->word();

        $response = $this->post(route('faturas.store'), [
            'cliente_id' => $cliente->id,
            'valor' => $valor,
            'vencimento' => $vencimento,
            'status' => $status,
        ]);

        $faturas = Fatura::query()
            ->where('cliente_id', $cliente->id)
            ->where('valor', $valor)
            ->where('vencimento', $vencimento)
            ->where('status', $status)
            ->get();
        $this->assertCount(1, $faturas);
        $fatura = $faturas->first();

        $response->assertOk();
        $response->assertJson($fatura 201);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $fatura = Fatura::factory()->create();

        $response = $this->delete(route('faturas.destroy', $fatura));

        $response->assertNoContent();

        $this->assertModelMissing($fatura);
    }
}
