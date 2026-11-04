{{-- Estilos sin librerías externas. Botones y campos grandes para usar con el dedo en tableta. --}}
<style>
    /* Lo que se oculta con el atributo hidden se oculta siempre, aunque su clase diga display: grid o flex. */
    [hidden] { display: none !important; }

    :root {
        --fondo: #f4f5f7; --tarjeta: #fff; --texto: #1f2933; --tenue: #66727f; --borde: #d9dde3;
        --primario: #1f5fbf; --primario-oscuro: #174a96; --exito: #1e7a46; --peligro: #b3261e; --alerta: #a86b00;
        --radio: 8px;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font: 16px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: var(--fondo); color: var(--texto); }
    a { color: var(--primario); }
    h1 { font-size: 1.5rem; margin: 0 0 .25rem; }
    h2 { font-size: 1.15rem; margin: 0 0 .75rem; }
    h3 { font-size: 1rem; margin: 1rem 0 .5rem; }

    .barra { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1.25rem; padding: .6rem 1rem; background: #1f2933; color: #fff; }
    .barra a { color: #d6dde6; text-decoration: none; padding: .35rem .6rem; border-radius: 6px; }
    .barra a.activo, .barra a:hover { background: #34414f; color: #fff; }
    .barra .marca { font-weight: 700; color: #fff; }
    .barra nav { display: flex; flex-wrap: wrap; gap: .25rem; flex: 1; }
    .barra .salir { display: flex; align-items: center; gap: .5rem; }
    .barra .tenue { color: #aab4bf; font-size: .875rem; }

    .contenido { max-width: 1180px; margin: 0 auto; padding: 1rem; }
    .encabezado { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: .75rem; margin-bottom: 1rem; }
    .tarjeta { background: var(--tarjeta); border: 1px solid var(--borde); border-radius: var(--radio); padding: 1rem; margin-bottom: 1rem; }
    .rejilla { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
    .rejilla-2 { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
    .tenue { color: var(--tenue); }
    .chico { font-size: .875rem; }
    .derecha { text-align: right; }
    .fila { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }

    .aviso { padding: .75rem 1rem; border-radius: var(--radio); margin-bottom: 1rem; border: 1px solid; }
    .aviso ul { margin: .25rem 0 0; padding-left: 1.25rem; }
    .aviso.exito { background: #e7f5ec; border-color: #b4dcc3; color: #155c34; }
    .aviso.error { background: #fdecea; border-color: #f2b8b3; color: #8c1d18; }
    .aviso.alerta { background: #fff6e0; border-color: #f0d48a; color: #6b4600; }

    label { display: block; font-weight: 600; font-size: .9rem; margin-bottom: .25rem; }
    .campo { margin-bottom: .9rem; }
    input[type=text], input[type=email], input[type=password], input[type=number], input[type=date], input[type=datetime-local], input[type=search], select, textarea {
        width: 100%; padding: .65rem .75rem; font: inherit; border: 1px solid var(--borde); border-radius: 6px; background: #fff; min-height: 44px;
    }
    textarea { min-height: 80px; }
    input:focus, select:focus, textarea:focus { outline: 2px solid var(--primario); outline-offset: 1px; }
    .casilla { display: flex; align-items: center; gap: .5rem; font-weight: 400; }
    .casilla input { flex-shrink: 0; }
    .casilla input { width: 22px; height: 22px; }
    .ayuda { font-size: .85rem; color: var(--tenue); margin-top: .2rem; }
    .error-campo { font-size: .85rem; color: var(--peligro); margin-top: .2rem; }

    .boton { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 44px; padding: .55rem 1.1rem;
        border: 1px solid var(--primario); border-radius: 6px; background: var(--primario); color: #fff; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
    .boton:hover { background: var(--primario-oscuro); }
    .boton.secundario { background: #fff; color: var(--primario); }
    .boton.peligro { background: var(--peligro); border-color: var(--peligro); }
    .boton.exito { background: var(--exito); border-color: var(--exito); }
    .boton.chico { min-height: 34px; padding: .3rem .7rem; font-size: .875rem; }
    .boton-link { background: none; border: 0; color: var(--peligro); cursor: pointer; font: inherit; padding: 0; text-decoration: underline; }

    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: .55rem .5rem; border-bottom: 1px solid var(--borde); vertical-align: top; }
    th { font-size: .8rem; text-transform: uppercase; letter-spacing: .02em; color: var(--tenue); }
    .tabla-scroll { overflow-x: auto; }

    .etiqueta { display: inline-block; padding: .1rem .55rem; border-radius: 999px; font-size: .8rem; font-weight: 600; background: #e8ecf1; color: #34414f; white-space: nowrap; }
    .etiqueta.recibido { background: #e8ecf1; }
    .etiqueta.diagnostico, .etiqueta.por_autorizar { background: #fff1d1; color: #7a4f00; }
    .etiqueta.en_reparacion { background: #dce9fb; color: #174a96; }
    .etiqueta.espera_refacciones { background: #fde3d6; color: #8a3a10; }
    .etiqueta.terminado { background: #dff3e7; color: #155c34; }
    .etiqueta.entregado { background: #1e7a46; color: #fff; }
    .etiqueta.cancelado { background: #f3d6d4; color: #8c1d18; }

    .contador { text-align: center; }
    .contador strong { display: block; font-size: 1.8rem; }

    /* Opciones como botones grandes (fotos, daños) */
    .opciones { display: flex; flex-wrap: wrap; gap: .35rem; }
    .opciones label { margin: 0; font-weight: 500; }
    .opciones input { position: absolute; opacity: 0; }
    .opciones span { display: inline-block; padding: .45rem .8rem; border: 1px solid var(--borde); border-radius: 6px; background: #fff; cursor: pointer; min-width: 52px; text-align: center; }
    .opciones input:checked + span { background: var(--primario); border-color: var(--primario); color: #fff; }
    .opciones input:focus-visible + span { outline: 2px solid var(--primario); outline-offset: 2px; }

    .fotos { display: grid; gap: .5rem; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }
    .fotos figure { margin: 0; background: #fafbfc; border: 1px solid var(--borde); border-radius: 6px; padding: .4rem; }
    .fotos img { width: 100%; height: 110px; object-fit: cover; border-radius: 4px; display: block; }
    .fotos figcaption { font-size: .8rem; margin-top: .25rem; }

    input[type=file] { font: inherit; font-size: .85rem; max-width: 100%; }
    input[type=file]::file-selector-button { min-height: 40px; padding: .4rem .8rem; margin-right: .5rem; border: 1px solid var(--primario);
        border-radius: 6px; background: #fff; color: var(--primario); font-weight: 600; cursor: pointer; }
    .firma { width: 100%; max-width: 560px; height: 200px; border: 2px dashed var(--borde); border-radius: 6px; background: #fff; touch-action: none; }
    .paginacion { margin-top: 1rem; }
    .paginacion .pagination { display: flex; flex-wrap: wrap; gap: .25rem; list-style: none; padding: 0; margin: 0; }
    .paginacion .pagination a, .paginacion .pagination span { display: inline-block; min-width: 40px; padding: .4rem .6rem; text-align: center; border: 1px solid var(--borde); border-radius: 6px; background: #fff; text-decoration: none; }
    .paginacion .pagination .active span { background: var(--primario); border-color: var(--primario); color: #fff; }
    .paginacion .pagination .disabled span { color: var(--tenue); }

    @media (max-width: 640px) {
        th.ocultar-movil, td.ocultar-movil { display: none; }
    }
    /* Checklist compacto (recepción y entrega): grupos en columnas, opciones como celdas para tocar */
    .progreso { font-size: .85rem; color: var(--tenue); white-space: nowrap; }
    .grupos-check { column-width: 340px; column-gap: 1rem; }
    .grupo-check { break-inside: avoid; border: 1px solid var(--borde); border-radius: var(--radio); padding: .5rem .6rem .4rem; margin-bottom: 1rem; }
    .grupo-check.completo { border-color: #b4dcc3; background: #f6fbf8; }
    .grupo-check header { display: flex; align-items: center; gap: .5rem; margin-bottom: .2rem; }
    .grupo-check h3 { margin: 0; font-size: 1rem; }
    .grupo-check header .progreso { margin-right: auto; }
    .grupo-check.completo [data-progreso] { color: var(--exito); font-weight: 600; }
    .matriz { width: 100%; border-collapse: collapse; }
    .matriz th, .matriz td { padding: 0; border: 0; vertical-align: middle; text-transform: none; letter-spacing: 0; color: inherit; }
    .matriz tr[data-item] { border-top: 1px solid #eef0f3; }
    .matriz tr[data-item] > th { font-weight: 400; text-align: left; font-size: .92rem; line-height: 1.2; padding: .2rem .4rem .2rem 0; }
    .matriz .requerido { color: var(--peligro); font-weight: 700; }
    .matriz tr.contestado .requerido { color: var(--tenue); font-weight: 400; }
    .matriz .cabecera th { padding-bottom: .1rem; }
    .matriz .cabecera button { width: 100%; min-height: 34px; padding: .2rem .25rem; border: 1px solid var(--borde); border-radius: 6px; background: #f4f5f7;
        font: inherit; font-size: .78rem; font-weight: 600; color: var(--texto); cursor: pointer; }
    .matriz .titulo-columna { display: block; text-align: center; font-size: .78rem; font-weight: 600; color: var(--tenue); }
    .matriz .cabecera button:active { background: var(--primario); color: #fff; }
    .matriz .celda { width: 48px; text-align: center; }
    .matriz .celda label { display: flex; align-items: center; justify-content: center; height: 42px; margin: 0; cursor: pointer; }
    .matriz .celda input { position: absolute; opacity: 0; }
    .matriz .marca { width: 24px; height: 24px; border: 2px solid #b8bfc8; border-radius: 50%; background: #fff; }
    .matriz .celda input:checked + .marca { border-color: var(--primario); background: var(--primario); box-shadow: inset 0 0 0 4px #fff; }
    .matriz .celda input:checked + .marca[data-valor="regular"] { border-color: var(--alerta); background: var(--alerta); }
    .matriz .celda input:checked + .marca[data-valor="malo"] { border-color: var(--peligro); background: var(--peligro); }
    .matriz .celda input:focus-visible + .marca { outline: 2px solid var(--primario); outline-offset: 2px; }
    .matriz input.valor { min-height: 36px; padding: .25rem .5rem; }
    .matriz .celda-nota { width: 36px; text-align: right; }
    .matriz .nota { width: 34px; height: 34px; border: 0; background: none; color: #b8bfc8; font-size: 1rem; cursor: pointer; border-radius: 6px; }
    .matriz .nota.tiene { color: var(--primario); background: #e8f0fb; }
    .matriz .renglon-nota input { min-height: 34px; padding: .25rem .5rem; margin: 0 0 .3rem; font-size: .9rem; }
</style>
