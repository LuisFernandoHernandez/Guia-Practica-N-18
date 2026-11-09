<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarTransaccionRequest;
use App\Http\Resources\TransaccionResource;
use App\Models\Transaccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class TransaccionApiController extends Controller
{
    public function index(Request $request)
{
    $datos = $request->validate([
        'estado' => ['nullable', Rule::in(['Iniciada', 'Completada', 'Fallida'])],
    ]);

    $estado = $datos['estado'] ?? null;

    $transacciones = Transaccion::whereHas('comercio', fn ($q) =>
        $q->where('user_id', $request->user()->id)
    )
    ->when($estado, fn ($q, $e) => $q->where('estado', $e))
    ->latest()
    ->get();

    return TransaccionResource::collection($transacciones);
}

    public function filtrar(Request $request)
    {
        $datos = $request->validate([
            'estado'    => ['nullable', Rule::in(['Iniciada', 'Completada', 'Fallida'])],
            'desde'     => ['nullable', 'date'],
            'hasta'     => ['nullable', 'date', 'after_or_equal:desde'],
            'monto_min' => ['nullable', 'numeric', 'min:0'],
            'monto_max' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when($request->filled('monto_min'), ['gte:monto_min'])
            ],
        ]);

        $q = Transaccion::whereHas('comercio', fn ($q) =>
            $q->where('user_id', $request->user()->id)
        );

        $q->when($datos['estado'] ?? null, fn ($q, $e) => $q->where('estado', $e))
          ->when($datos['desde'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
          ->when($datos['hasta'] ?? null, fn ($q, $h) => $q->whereDate('created_at', '<=', $h))
          ->when($datos['monto_min'] ?? null, fn ($q, $m) => $q->where('monto', '>=', $m))
          ->when($datos['monto_max'] ?? null, fn ($q, $m) => $q->where('monto', '<=', $m));

        return TransaccionResource::collection($q->latest()->get());
    }

    private function autorizar(Transaccion $transaccion): void
    {
        abort_unless($transaccion->comercio->user_id === request()->user()->id, 404);
    }

    public function store(GuardarTransaccionRequest $request)
    {
        // creación y el evento en una transacción de base de datos
        $transaccion = DB::transaction(function () use ($request) {
            // 1. Crear la transacción con los datos validados
            $t = Transaccion::create($request->validated());

            // 2. Registrar el evento inicial de creación
            $t->eventos()->create([
                'tipo_evento' => 'creacion',
                'detalle' => 'Transacción iniciada',
            ]);

            return $t;
        });

        // 3. Devolver la respuesta en formato JSON con código 201
        return (new TransaccionResource($transaccion))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Transaccion $transaccion)
    {
        $this->autorizar($transaccion);

        return new TransaccionResource($transaccion);
    }

    public function update(Request $request, Transaccion $transaccion)
    {
        $this->autorizar($transaccion);
        //
    }

    public function destroy(Transaccion $transaccion)
    {
        $this->autorizar($transaccion);

        $transaccion->delete();

        return response()->noContent(); // 204
    }
}
