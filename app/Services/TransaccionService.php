<?php

namespace App\Services;

use App\Models\{Transaccion, User};
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Validation\ValidationException;

class TransaccionService
{
    public function __construct(private CalculadoraComision $calculadoraComision) {}

    public function registrar(User $usuario, array $datos): Transaccion
    {
        $comercio = $usuario->comercios()->findOrFail($datos['comercio_id']);

        $metodo = $datos['metodo'] ?? 'transferencia';
        $comision = $this->calculadoraComision->calcular($metodo, $datos['monto']);

        $transaccion = DB::transaction(function () use ($comercio, $datos, $comision) {
            $t = $comercio->transacciones()->create([
                'monto'          => $datos['monto'],
                'comision'       => $comision,
                'estado'         => 'Iniciada',
                'cliente_nombre' => $datos['cliente_nombre'] ?? null,
            ]);

            $t->eventos()->create([
                'tipo_evento' => 'creacion',
                'detalle'     => 'Transacción iniciada',
            ]);

            return $t;
        });

        Log::info("Transacción {$transaccion->id} registrada exitosamente");

        return $transaccion;
    }

    public function cambiarEstado(User $usuario, Transaccion $transaccion, string $nuevoEstado): Transaccion
    {
        if ($transaccion->comercio->user_id !== $usuario->id) {
            abort(404);
        }

        if ($transaccion->estado !== 'Iniciada') {
            throw ValidationException::withMessages([
                'estado' => ['La transacción ya fue cerrada o no está en estado Iniciada.'],
            ]);
        }

        $anterior = $transaccion->estado;

        DB::transaction(function () use ($transaccion, $nuevoEstado, $anterior) {
            $transaccion->update(['estado' => $nuevoEstado]);

            $transaccion->eventos()->create([
                'tipo_evento' => 'cambio_estado',
                'detalle'     => "{$anterior} -> {$nuevoEstado}",
            ]);
        });

        Log::info("Transacción {$transaccion->id}: {$anterior} -> {$nuevoEstado}");

        return $transaccion;
    }
}
