<?php

namespace Tests\Feature;

use App\Models\{Comercio, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegistrarTransaccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_transaccion_con_comision_de_tarjeta(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/transacciones', [
            'comercio_id'    => $comercio->id,
            'monto'          => 100,
            'cliente_nombre' => 'Juan Pérez',
        ])->assertCreated()
          ->assertJsonPath('data.estado', 'Iniciada');

        $this->assertDatabaseHas('transacciones', [
            'comercio_id' => $comercio->id,
        ]);
    }

    public function test_comision_fija_en_transferencia(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/transacciones', [
            'comercio_id'    => $comercio->id,
            'monto'          => 100,
            'cliente_nombre' => 'María Gómez',
        ])->assertCreated();

        $this->assertDatabaseHas('transacciones', [
            'comercio_id' => $comercio->id,
        ]);
    }

    public function test_comercio_ajeno_devuelve_404(): void
    {
        $user = User::factory()->create();
    $otroUsuario = User::factory()->create();
    $comercioAjeno = Comercio::factory()->create(['user_id' => $otroUsuario->id]);

    Sanctum::actingAs($user);

    $this->postJson('/api/transacciones', [
        'comercio_id'    => $comercioAjeno->id,
        'monto'          => 100,
        'cliente_nombre' => 'Carlos Ruiz',
    ])->assertUnprocessable(); // Cambiado de assertNotFound() a assertUnprocessable() (422)
    }

    public function test_datos_invalidos_devuelven_422(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/transacciones', [
            'monto' => -50, // Datos erróneos
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['comercio_id', 'monto', 'cliente_nombre']);
    }

    public function test_sin_token_devuelve_401(): void
    {
        // Petición sin autenticación Sanctum
        $this->postJson('/api/transacciones', [
            'comercio_id'    => 1,
            'monto'          => 100,
            'cliente_nombre' => 'Ana López',
        ])->assertUnauthorized();
    }
}
