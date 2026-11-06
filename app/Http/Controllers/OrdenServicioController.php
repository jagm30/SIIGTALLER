<?php

namespace App\Http\Controllers;

use App\Enums\EstadoOrden;
use App\Enums\Rol;
use App\Enums\TipoOrden;
use App\Models\OrdenServicio;
use App\Models\PreferenciaOperacion;
use App\Models\Refaccion;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Órdenes de servicio. Las reglas del negocio (ciclo de estados, recepción obligatoria,
 * entrega obligatoria, recordatorios) viven en el modelo OrdenServicio; aquí solo
 * recibimos la petición, llamamos al modelo y respondemos.
 */
class OrdenServicioController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $estado = EstadoOrden::tryFrom((string) $request->query('estado'));
        $buscar = trim((string) $request->query('buscar'));

        $ordenes = OrdenServicio::query()
            ->with(['vehiculo.marca', 'vehiculo.modelo', 'cliente', 'mecanicoResponsable'])
            ->when($user->esMecanico(), fn ($q) => $q->asignadasA($user))
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('folio', 'like', "%{$buscar}%")
                ->orWhereHas('vehiculo', fn ($v) => $v->where('placas_normalizadas', 'like', '%' . Vehiculo::normalizarPlacas($buscar) . '%'))))
            ->latest('fecha_ingreso')
            ->paginate(25)
            ->withQueryString();

        return view('ordenes.index', compact('ordenes', 'estado', 'buscar'));
    }

    /**
     * Paso 1 de la recepción: buscar el vehículo por placas o VIN.
     * Si viene ?vehiculo_id=, ya se muestra el formulario para abrir la orden.
     */
    public function create(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));
        $vehiculo = Vehiculo::with(['marca', 'modelo', 'cliente', 'ordenAbierta'])->find($request->integer('vehiculo_id'));

        $encontrados = $buscar === '' ? collect() : Vehiculo::query()
            ->with(['marca', 'modelo', 'cliente'])
            ->where(fn ($q) => $q->porPlacas($buscar)->orWhere('vin', strtoupper($buscar)))
            ->limit(10)
            ->get();

        // Para órdenes de garantía: las últimas órdenes entregadas de este vehículo.
        $anteriores = $vehiculo?->ordenes()->where('estado', EstadoOrden::Entregado)->limit(10)->get() ?? collect();

        return view('ordenes.create', compact('vehiculo', 'encontrados', 'buscar', 'anteriores'));
    }

    /** Abre la orden y manda directo a capturar la recepción. */
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'vehiculo_id' => ['required', 'exists:vehiculos,id'],
            'motivo_ingreso' => ['required', 'string', 'max:2000'],
            'tipo' => ['required', Rule::enum(TipoOrden::class)],
            'orden_garantia_de_id' => ['nullable', 'required_if:tipo,garantia', 'exists:ordenes_servicio,id'],
            'fecha_promesa' => ['nullable', 'date', 'after_or_equal:today'],
        ], [], ['motivo_ingreso' => 'motivo de ingreso', 'orden_garantia_de_id' => 'orden original']);

        $orden = OrdenServicio::abrir(
            vehiculo: Vehiculo::findOrFail($datos['vehiculo_id']),
            recepcionista: $request->user(),
            motivoIngreso: $datos['motivo_ingreso'],
            tipo: TipoOrden::from($datos['tipo']),
            garantiaDe: isset($datos['orden_garantia_de_id']) ? OrdenServicio::find($datos['orden_garantia_de_id']) : null,
            sucursalId: $request->user()->sucursal_id ?? $this->sucursalPorDefecto(),
        );

        if (! empty($datos['fecha_promesa'])) {
            $orden->update(['fecha_promesa' => $datos['fecha_promesa']]);
        }

        return redirect()->route('inspecciones.edit', $orden->recepcion)
            ->with('ok', "Orden {$orden->folio} abierta. Captura la recepción del vehículo.");
    }

    public function show(OrdenServicio $orden): View
    {
        Gate::authorize('ver-orden', $orden);

        $orden->load([
            'vehiculo.marca', 'vehiculo.modelo', 'cliente', 'recibidoPor', 'mecanicoResponsable', 'entregadoPor',
            'recepcion.realizadaPor', 'recepcion.fotos.anguloFoto', 'recepcion.danios',
            'entrega.realizadaPor', 'entrega.fotos.anguloFoto', 'entrega.danios',
            'trabajos.mecanicos', 'trabajos.servicio',
            'refacciones.trabajo',
            'bitacoraEstados.usuario',
        ]);

        return view('ordenes.show', [
            'orden' => $orden,
            'mecanicos' => User::activos()->conRol(Rol::Mecanico)->orderBy('name')->get(),
            // Con el conteo de sugeridas, la vista sabe si mostrar la casilla de "agregar refacciones".
            'servicios' => Servicio::activos()->withCount('refaccionesSugeridas')->get(),
            'refaccionesCatalogo' => Refaccion::activas()->get(),
            // Si está apagado en Preferencias, los mecánicos no capturan horas en sus trabajos.
            'registraHoras' => PreferenciaOperacion::bool('mecanicos_registran_horas', $orden->sucursal_id, true),
        ]);
    }

    /** Datos generales: mecánico responsable, diagnóstico, observaciones, fecha promesa y descuento. */
    public function update(Request $request, OrdenServicio $orden): RedirectResponse
    {
        Gate::authorize('ver-orden', $orden);
        $user = $request->user();

        if ($orden->estaCerrada()) {
            return back()->with('error', 'La orden está cerrada y ya no se puede modificar.');
        }

        $datos = $request->validate([
            'mecanico_responsable_id' => ['nullable', Rule::exists('users', 'id')->where('rol', Rol::Mecanico->value)],
            'diagnostico' => ['nullable', 'string', 'max:5000'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'fecha_promesa' => ['nullable', 'date'],
            // El descuento puede ser una cantidad en pesos o un porcentaje del subtotal.
            'descuento_tipo' => ['nullable', Rule::in(['monto', 'porcentaje'])],
            'descuento' => ['nullable', 'numeric', 'min:0', Rule::when($request->input('descuento_tipo') === 'porcentaje', ['max:100'])],
        ], ['descuento.max' => 'El descuento en porcentaje no puede ser mayor a 100.']);

        // Cada rol solo cambia lo que le corresponde.
        if ($user->esMecanico()) {
            $datos = array_intersect_key($datos, array_flip(['diagnostico', 'observaciones']));
        }
        if (! $user->can('precios.ver', $orden)) {
            unset($datos['descuento']);
        }
        // El diagnóstico solo se puede editar a partir de la fase de diagnóstico.
        if (! $orden->puedeEditarDiagnostico()) {
            unset($datos['diagnostico']);
        }

        $tipoDescuento = $datos['descuento_tipo'] ?? 'monto';
        unset($datos['descuento_tipo']);

        if (array_key_exists('descuento', $datos) && $tipoDescuento === 'porcentaje') {
            // Guardamos el porcentaje; recalcularTotales() saca la cantidad en pesos.
            $datos['descuento_porcentaje'] = $datos['descuento'];
            $datos['descuento'] = null;
        } elseif (array_key_exists('descuento', $datos)) {
            $datos['descuento_porcentaje'] = null;
        }

        $orden->update($datos);

        if (array_key_exists('descuento', $datos)) {
            $orden->recalcularTotales();
        }

        return back()->with('ok', 'Orden actualizada.');
    }

    /** Mueve la orden en su ciclo de vida. El modelo valida rol, transición y requisitos. */
    public function cambiarEstado(Request $request, OrdenServicio $orden): RedirectResponse
    {
        Gate::authorize('ver-orden', $orden);

        $datos = $request->validate([
            'estado' => ['required', Rule::enum(EstadoOrden::class)],
            'comentario' => ['nullable', 'string', 'max:255'],
        ]);

        $nuevo = EstadoOrden::from($datos['estado']);
        $orden->cambiarEstado($nuevo, $request->user(), $datos['comentario'] ?? null);

        return back()->with('ok', "La orden pasó a «{$nuevo->label()}».");
    }

    /** Crea (o retoma) la inspección de entrega y manda a capturarla. */
    public function iniciarEntrega(Request $request, OrdenServicio $orden): RedirectResponse
    {
        if ($orden->estado !== EstadoOrden::Terminado) {
            return back()->with('error', 'La inspección de entrega se hace cuando la orden está terminada.');
        }

        $entrega = $orden->iniciarEntrega($request->user());

        return redirect()->route('inspecciones.edit', $entrega);
    }

    /** Para el administrador, que no tiene sucursal fija: usa la primera sucursal activa. */
    private function sucursalPorDefecto(): ?int
    {
        return Sucursal::where('activo', true)->orderBy('id')->value('id');
    }
}
