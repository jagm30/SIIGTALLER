<?php

namespace App\Http\Controllers;

use App\Enums\TipoCarroceria;
use App\Models\Marca;
use App\Models\ModeloVehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Catálogo de marcas y modelos de vehículos.
 * También aquí el administrador revisa los modelos que recepción dio de alta rápida.
 */
class MarcaController extends Controller
{
    public function index(): View
    {
        return view('marcas.index', [
            'marcas' => Marca::orderBy('nombre')->with(['modelos' => fn ($q) => $q->orderBy('nombre')])->get(),
            'pendientes' => ModeloVehiculo::pendientesDeVerificar()->with('marca')->withCount('vehiculos')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60', 'unique:marcas,nombre'],
        ], ['nombre.unique' => 'Esa marca ya existe.']);

        Marca::create($datos + ['verificado' => true]);

        return back()->with('ok', 'Marca agregada.');
    }

    public function storeModelo(Request $request, Marca $marca): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80', Rule::unique('modelos_vehiculo')->where('marca_id', $marca->id)],
            'tipo_carroceria' => ['required', Rule::enum(TipoCarroceria::class)],
            'anio_inicio' => ['nullable', 'integer', 'between:1950,2100'],
            'anio_fin' => ['nullable', 'integer', 'between:1950,2100', 'gte:anio_inicio'],
        ], ['nombre.unique' => 'Esa marca ya tiene un modelo con ese nombre.']);

        $marca->modelos()->create($datos + ['verificado' => true]);

        return back()->with('ok', "Modelo agregado a {$marca->nombre}.");
    }

    /** Corrige un modelo (por ejemplo, uno de alta rápida) y lo marca como verificado. */
    public function updateModelo(Request $request, ModeloVehiculo $modelo): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80',
                Rule::unique('modelos_vehiculo')->where('marca_id', $modelo->marca_id)->ignore($modelo)],
            'tipo_carroceria' => ['required', Rule::enum(TipoCarroceria::class)],
            'activo' => ['boolean'],
        ]);

        $modelo->update($datos + ['verificado' => true, 'activo' => $request->boolean('activo', true)]);
        $modelo->marca()->update(['verificado' => true]);

        return back()->with('ok', 'Modelo verificado.');
    }

    /** Une un modelo duplicado o mal escrito con el correcto: sus vehículos pasan al correcto. */
    public function fusionarModelo(Request $request, ModeloVehiculo $modelo): RedirectResponse
    {
        $datos = $request->validate([
            'destino_id' => ['required', Rule::exists('modelos_vehiculo', 'id')->whereNot('id', $modelo->id)],
        ], ['destino_id.exists' => 'Elige un modelo distinto al que vas a fusionar.'], ['destino_id' => 'modelo correcto']);

        $destino = ModeloVehiculo::findOrFail($datos['destino_id']);
        $modelo->fusionarEn($destino);

        return back()->with('ok', "Se fusionó «{$modelo->nombre}» con «{$destino->nombre}».");
    }
}
