<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarTransaccionRequest;
use App\Http\Requests\UpdateTransaccionRequest;
use App\Http\Resources\TransaccionResource;
use App\Models\Transaccion;
use App\Services\TransaccionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransaccionApiController extends Controller
{
    // Inyectamos el servicio en el constructor para mantener el controlador delgado
    public function __construct(private TransaccionService $servicio) {}

    public function index(Request $request)
    {
        $datos = $request->validate([
            'estado' => ['nullable', Rule::in(['Iniciada', 'Completada', 'Fallida'])],
        ]);

        $estado = $datos['estado'] ?? null;

        $transacciones = Transaccion::whereHas(
            'comercio',
            fn($q) =>
            $q->where('user_id', $request->user()->id)
        )
            ->when($estado, fn($q, $e) => $q->where('estado', $e))
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

        $q = Transaccion::whereHas(
            'comercio',
            fn($q) =>
            $q->where('user_id', $request->user()->id)
        );

        $q->when($datos['estado'] ?? null, fn($q, $e) => $q->where('estado', $e))
            ->when($datos['desde'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($datos['hasta'] ?? null, fn($q, $h) => $q->whereDate('created_at', '<=', $h))
            ->when($datos['monto_min'] ?? null, fn($q, $m) => $q->where('monto', '>=', $m))
            ->when($datos['monto_max'] ?? null, fn($q, $m) => $q->where('monto', '<=', $m));

        return TransaccionResource::collection($q->latest()->get());
    }

    private function autorizar(Transaccion $transaccion): void
    {
        abort_unless($transaccion->comercio->user_id === request()->user()->id, 404);
    }

    public function store(GuardarTransaccionRequest $request)
    {
        // El controlador ya no maneja lógica de negocio ni transacciones de BD:
        // delega toda la responsabilidad al servicio de manera limpia.
        $transaccion = $this->servicio->registrar($request->user(), $request->validated());

        return (new TransaccionResource($transaccion))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Transaccion $transaccion)
    {
        $this->autorizar($transaccion);

        return new TransaccionResource($transaccion);
    }

    public function update(UpdateTransaccionRequest $request, Transaccion $transaccion)
    {
        $transaccionActualizada = $this->servicio->cambiarEstado(
            $request->user(),
            $transaccion,
            $request->validated('estado')
        );

        return new TransaccionResource($transaccionActualizada);
    }

    public function destroy(Transaccion $transaccion)
    {
        $this->autorizar($transaccion);

        $transaccion->delete();

        return response()->noContent(); // 204
    }
}
