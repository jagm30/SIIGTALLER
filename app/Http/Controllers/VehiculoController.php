<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehiculoRequest;
use App\Models\Cliente;
use App\Models\Marca;
use App\Models\ModeloVehiculo;
use App\Models\Vehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VehiculoController extends Controller
{
    /** Búsqueda por placas (con o sin guiones), VIN o nombre del cliente. */
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));

        $vehiculos = Vehiculo::query()
            ->with(['marca', 'modelo', 'cliente'])
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(fn ($w) => $w
                    ->where('placas_normalizadas', 'like', '%' . Vehiculo::normalizarPlacas($buscar) . '%')
                    ->orWhere('vin', 'like', '%' . strtoupper($buscar) . '%')
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('vehiculos.index', compact('vehiculos', 'buscar'));
    }

    public function create(Request $request): View
    {
        $vehiculo = new Vehiculo(['cliente_id' => $request->integer('cliente_id') ?: null]);

        return view('vehiculos.create', ['vehiculo' => $vehiculo] + $this->listas());
    }

    public function store(VehiculoRequest $request): RedirectResponse
    {
        $vehiculo = DB::transaction(function () use ($request) {
            $datos = $this->datosDelVehiculo($request);

            $vehiculo = Vehiculo::create($datos);
            $vehiculo->propietarios()->create(['cliente_id' => $vehiculo->cliente_id, 'desde' => now()->toDateString()]);

            return $vehiculo;
        });

        return redirect()->route('vehiculos.show', $vehiculo)->with('ok', 'Vehículo registrado.');
    }

    /** Ficha del vehículo con su historial: lo que se le ha hecho y cuándo le toca volver. */
    public function show(Vehiculo $vehiculo): View
    {
        $vehiculo->load(['marca', 'modelo', 'cliente', 'recordatorios' => fn ($q) => $q->pendientes()]);

        $ordenes = $vehiculo->ordenes()
            ->with(['trabajos', 'refacciones'])
            ->paginate(10);

        return view('vehiculos.show', compact('vehiculo', 'ordenes'));
    }

    public function edit(Vehiculo $vehiculo): View
    {
        return view('vehiculos.edit', ['vehiculo' => $vehiculo] + $this->listas());
    }

    public function update(VehiculoRequest $request, Vehiculo $vehiculo): RedirectResponse
    {
        DB::transaction(function () use ($request, $vehiculo) {
            $datos = $this->datosDelVehiculo($request);
            $nuevoDueno = (int) $datos['cliente_id'] !== $vehiculo->cliente_id;

            // El kilometraje solo se actualiza en la recepción; aquí no se deja bajar por error.
            unset($datos['km_actual']);

            if ($nuevoDueno) {
                // Cambiar de dueño cierra el registro anterior; el historial sigue con el vehículo.
                $vehiculo->cambiarPropietario(Cliente::findOrFail($datos['cliente_id']));
            }

            $vehiculo->update($datos);
        });

        return redirect()->route('vehiculos.show', $vehiculo)->with('ok', 'Vehículo actualizado.');
    }

    public function destroy(Vehiculo $vehiculo): RedirectResponse
    {
        if ($vehiculo->ordenes()->exists()) {
            return back()->with('error', 'No se puede eliminar: el vehículo tiene historial de servicio.');
        }

        $vehiculo->delete();

        return redirect()->route('vehiculos.index')->with('ok', 'Vehículo eliminado.');
    }

    /** El QR impreso anterior deja de funcionar (por si se perdió o se filtró). */
    public function regenerarQr(Vehiculo $vehiculo): RedirectResponse
    {
        $vehiculo->regenerarQr();

        return back()->with('ok', 'Se generó un QR nuevo. Imprime otra etiqueta; la anterior ya no funciona.');
    }

    // ── Apoyo ────────────────────────────────────────────────────

    /** Listas para los selects del formulario. */
    private function listas(): array
    {
        return [
            'clientes' => Cliente::orderBy('nombre')->get(['id', 'nombre', 'apellidos', 'razon_social', 'tipo_persona']),
            'marcas' => Marca::activas()->with(['modelos' => fn ($q) => $q->activos()])->get(),
        ];
    }

    /**
     * Toma los datos validados y, si escribieron un modelo nuevo, lo da de alta
     * como "por verificar" para que el administrador lo revise después.
     */
    private function datosDelVehiculo(VehiculoRequest $request): array
    {
        $datos = $request->safe()->except([
            'nuevo_modelo', 'nueva_marca', 'tipo_carroceria',
            'nuevo_cliente_nombre', 'nuevo_cliente_apellidos', 'nuevo_cliente_telefono',
        ]);

        if ($request->filled('nuevo_cliente_nombre')) {
            abort_unless($request->user()->tienePermiso('clientes.gestionar'), 403);

            $cliente = Cliente::create([
                'nombre' => trim($request->input('nuevo_cliente_nombre')),
                'apellidos' => $request->filled('nuevo_cliente_apellidos') ? trim($request->input('nuevo_cliente_apellidos')) : null,
                'telefono' => trim($request->input('nuevo_cliente_telefono')),
            ]);

            $datos['cliente_id'] = $cliente->id;
        }

        if ($request->filled('nuevo_modelo')) {
            abort_unless($request->user()->tienePermiso('catalogos.alta_rapida'), 403);

            $marca = Marca::firstOrCreate(
                ['nombre' => trim($request->input('nueva_marca'))],
                ['verificado' => false],
            );
            $modelo = ModeloVehiculo::firstOrCreate(
                ['marca_id' => $marca->id, 'nombre' => trim($request->input('nuevo_modelo'))],
                ['tipo_carroceria' => $request->input('tipo_carroceria'), 'verificado' => false],
            );
            $datos['modelo_vehiculo_id'] = $modelo->id;
        }

        // La marca siempre sale del modelo, así nunca quedan desalineadas.
        $datos['marca_id'] = ModeloVehiculo::findOrFail($datos['modelo_vehiculo_id'])->marca_id;
        $datos['km_actual'] ??= 0;

        return $datos;
    }
}
