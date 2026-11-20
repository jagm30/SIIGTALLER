<?php

namespace App\Http\Controllers;

use App\Models\PreferenciaOperacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Nombre y logo del taller, que se muestran en la barra, el inicio de sesión, el QR y los PDF. */
class IdentidadTallerController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2 MB
            'quitar_logo' => ['boolean'],
        ], [], ['nombre' => 'nombre del taller', 'logo' => 'logo']);

        PreferenciaOperacion::actualizarIdentidadTaller(
            $datos['nombre'],
            $request->file('logo'),
            $request->boolean('quitar_logo'),
            $request->user(),
        );

        return back()->with('ok', 'Datos del taller guardados.');
    }

    /** Público: el logo aparece también en el inicio de sesión y en la página del QR. */
    public function logo(): StreamedResponse
    {
        return PreferenciaOperacion::respuestaLogoTaller() ?? abort(404);
    }
}
