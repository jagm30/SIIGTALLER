<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));

        $clientes = Cliente::query()
            ->withCount('vehiculos')
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(fn ($w) => $w
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%")
                    ->orWhere('razon_social', 'like', "%{$buscar}%")
                    ->orWhere('telefono', 'like', "%{$buscar}%")
                    ->orWhere('rfc', 'like', "%{$buscar}%"));
            })
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('clientes.index', compact('clientes', 'buscar'));
    }

    public function create(): View
    {
        return view('clientes.create', ['cliente' => new Cliente(['tipo_persona' => 'fisica'])]);
    }

    public function store(ClienteRequest $request): RedirectResponse
    {
        $cliente = Cliente::create($request->validated());

        // Lo normal al dar de alta un cliente es registrar enseguida su vehículo.
        return redirect()->route('vehiculos.create', ['cliente_id' => $cliente->id])
            ->with('ok', 'Cliente registrado. Ahora registra su vehículo.');
    }

    public function show(Cliente $cliente): View
    {
        $cliente->load(['vehiculos.marca', 'vehiculos.modelo']);
        $ordenes = $cliente->ordenes()->with('vehiculo.modelo')->latest('fecha_ingreso')->limit(20)->get();

        return view('clientes.show', compact('cliente', 'ordenes'));
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.show', $cliente)->with('ok', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        // Un cliente con vehículos u órdenes es historial: no se borra.
        if ($cliente->vehiculos()->exists() || $cliente->ordenes()->exists()) {
            return back()->with('error', 'No se puede eliminar: el cliente tiene vehículos u órdenes registradas.');
        }

        $cliente->delete();

        return redirect()->route('clientes.index')->with('ok', 'Cliente eliminado.');
    }
}
