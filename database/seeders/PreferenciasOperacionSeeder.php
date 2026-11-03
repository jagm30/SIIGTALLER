<?php

namespace Database\Seeders;

use App\Models\PreferenciaOperacion;
use Illuminate\Database\Seeder;

/**
 * Preferencias generales (sucursal_id NULL) con los valores confirmados con el cliente:
 * IVA incluido sin desglose, documentos impresos sin precios, inspección de entrega obligatoria.
 *
 * Usa firstOrCreate: si el administrador ya cambió un valor, volver a correr el seeder no lo pisa.
 * Para un taller que no maneja precios: usar_precios = false (desde la pantalla de preferencias).
 */
class PreferenciasOperacionSeeder extends Seeder
{
    public const PREFERENCIAS = [
        // grupo, clave, tipo, valor, descripción
        ['precios', 'usar_precios', 'bool', '1', 'Activa o bloquea la captura y visualización de precios en servicios, refacciones y órdenes.'],
        ['precios', 'mostrar_precios_a_mecanicos', 'bool', '0', 'Si está desactivada, el mecánico registra trabajos y piezas sin ver importes.'],
        ['precios', 'imprimir_precios_en_documentos', 'bool', '0', 'Si está desactivada, la hoja de ingreso, la orden y los reportes se imprimen sin importes.'],
        ['precios', 'mostrar_precios_en_qr', 'bool', '0', 'Si la página pública del QR muestra importes.'],
        ['precios', 'precios_incluyen_iva', 'bool', '1', 'Los precios se capturan con IVA incluido y el IVA no se desglosa.'],
        ['precios', 'iva_porcentaje', 'decimal', '16.00', 'Solo para calcular el IVA contenido en reportes internos.'],
        ['precios', 'tarifa_hora_default', 'decimal', null, 'Tarifa sugerida al crear un servicio con cobro por hora.'],
        ['recepcion', 'recepcion_firma_obligatoria', 'bool', '1', 'Sin firma no se completa la recepción.'],
        ['recepcion', 'recepcion_fotos_obligatorias', 'bool', '1', 'Exige las fotos de los ángulos marcados como obligatorios.'],
        ['recepcion', 'recepcion_foto_por_danio', 'bool', '1', 'Cada daño registrado debe tener al menos una foto.'],
        ['recepcion', 'recepcion_permite_km_menor', 'bool', '1', 'Permite un kilometraje menor al anterior con justificación.'],
        ['recepcion', 'recepcion_terminos', 'text', 'El cliente declara que la información de esta hoja corresponde al estado en que entrega el vehículo, que retiró sus objetos de valor (salvo los indicados) y autoriza al taller a realizar el diagnóstico.', 'Leyenda que acepta el cliente al firmar. Ajústala con el taller.'],
        ['recepcion', 'recepcion_terminos_version', 'int', '1', 'Se incrementa cada vez que cambia el texto de los términos.'],
        ['entrega', 'entrega_inspeccion_obligatoria', 'bool', '1', 'Exige la inspección de entrega completada antes de marcar la orden como entregada.'],
        ['recordatorios', 'recordatorios_activos', 'bool', '1', 'Programa el próximo servicio al entregar el vehículo.'],
        ['recordatorios', 'recordatorio_meses_default', 'int', '6', 'Intervalo sugerido en meses.'],
        ['recordatorios', 'recordatorio_km_default', 'int', '10000', 'Intervalo sugerido en kilómetros.'],
    ];

    public function run(): void
    {
        foreach (self::PREFERENCIAS as [$grupo, $clave, $tipo, $valor, $descripcion]) {
            PreferenciaOperacion::firstOrCreate(
                ['sucursal_id' => null, 'clave' => $clave],
                ['grupo' => $grupo, 'tipo' => $tipo, 'valor' => $valor, 'descripcion' => $descripcion],
            );
        }
    }
}
