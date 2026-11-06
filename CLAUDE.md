# CLAUDE.md — SIIGTALLER

Sistema de gestión para talleres mecánicos. Laravel 12, PHP 8.2, Blade, SQLite (dev) / MySQL (prod).

---

## Antes de tocar cualquier archivo

1. **Revisa los últimos cambios.** Ejecuta `git log --oneline -10` y `git diff HEAD~1` para entender qué se modificó recientemente. Aplica buenas prácticas de Laravel, reduce la complejidad innecesaria y asegúrate de que el código sea legible y limpio.

2. **Analiza el flujo completo.** Lee todos los archivos involucrados antes de escribir una sola línea: ruta → controlador → model → vista → test. No edites en medio del flujo sin entender los extremos.

3. **No adivines columnas de base de datos.** Antes de usar una columna, ábrela en la migración correspondiente (`database/migrations/`). Si no está en la migración, no existe aunque "tenga sentido" que exista.

---

## Reglas de trabajo

### Código simple por encima de todo
- Si hay dos formas de hacer algo, elige la más legible. No la más inteligente.
- No abstraigas antes de que haya repetición real. Tres líneas similares no ameritan una clase nueva.
- No agregues comentarios donde el código ya se explica solo. Solo donde la lógica no es obvia.
- No uses `//TODO`, `// hack`, ni parches con `@suppress` o `try/catch` vacíos.

### Dónde vive cada responsabilidad
```
Regla de negocio        → Model   (OrdenServicio, Inspeccion, …)
Autorización de ruta    → Gate / policy en AuthServiceProvider
Validación de entrada   → Form Request (app/Http/Requests/) o $request->validate()
Orquestación HTTP       → Controller  (recibe, delega al model, responde)
Presentación            → Blade (resources/views/)
```
Los controladores **no** deben contener lógica de negocio. Solo reciben la petición, llaman al modelo y devuelven una respuesta. Si un método supera ~50 líneas es una señal de que algo no está bien ubicado.

### Validaciones
- Toda petición que modifique datos pasa por `$request->validate()` o un `FormRequest`.
- Reglas nombradas (`'motivo_ingreso' => 'motivo de ingreso'`) para que los mensajes sean legibles.
- Validaciones de negocio (vehiculo ya tiene orden abierta, transición inválida de estado, etc.) van en el modelo y lanzan `ValidationException::withMessages([...])`.
- No confiar en el frontend: validar siempre en el servidor.

### Controladores
- Un método, una responsabilidad.
- No cargar relaciones que no se van a usar en la vista.
- Usar `Gate::authorize()` o `$this->authorize()` para recursos sensibles.
- Redirecciones con `->with('ok', '...')` en éxito y `->with('error', '...')` en fallo esperado.
- `abort(403)` / `abort(404)` solo para errores de acceso o recurso inexistente.

### Enums
- Los estados, roles, tipos y permisos están en `app/Enums/`. Usarlos siempre; nunca strings mágicos.
- Si se agrega un case a un enum, actualizar también los métodos `label()`, `siguientes()`, `rolesQuePuedenEntrar()`, etc.

### Migraciones
- Una migración = un cambio atómico y descriptivo.
- Nunca modificar una migración ya commiteada. Crear una nueva.
- Siempre incluir `down()` que deshaga el cambio.

---

## Tests — obligatorios en cada cambio

### Stack
- PHPUnit con `RefreshDatabase` en cada test de Feature.
- Base de datos: SQLite en memoria (configurada en `phpunit.xml`).
- Seeders de producción reales en los tests (ver `DatosDelTaller` trait).

### Dónde van los tests
```
tests/Feature/   → flujos HTTP completos (lo que hace un usuario en el navegador)
tests/Unit/      → lógica pura de modelos o clases sin HTTP
```

### Qué testear siempre
1. **El camino feliz**: la operación funciona con datos válidos.
2. **El camino con error esperado**: validación fallida, permiso denegado, transición inválida.
3. **Los límites del rol**: lo que un mecánico no puede hacer que no pueda hacerlo.

### Convención de nombres
```php
public function test_descripcion_en_espanol_del_escenario(): void
```

### Helpers disponibles en Feature tests
Usar el trait `Tests\Feature\Concerns\DatosDelTaller` que ya existe:
```php
$this->cargarCatalogos();          // seeders reales de catálogos
$this->usuario(Rol::Mecanico);     // crea usuario con rol
$this->vehiculo();                 // crea cliente + vehículo Versa
$this->servicio();                 // crea servicio 'Cambio de aceite'
$this->ordenConRecepcion($user);   // orden abierta + recepción completa
$this->ordenEnReparacion($r, $m);  // lleva la orden hasta 'en_reparacion'
```

### Ejecutar los tests
```bash
php artisan test
php artisan test --filter NombreDelTest
```

---

## Estructura del proyecto (referencia rápida)

```
app/
  Enums/           Estados, roles, tipos — nunca strings mágicos
  Http/
    Controllers/   Solo orquestación HTTP
    Requests/      FormRequests con reglas y mensajes
    Middleware/
  Models/          Reglas de negocio, relaciones, scopes, mutadores
  Providers/
    AppServiceProvider.php   Gates y políticas aquí

database/
  migrations/      Un cambio atómico por archivo
  seeders/         Seeders reales que también usan los tests

resources/views/   Blade; sin lógica de negocio

routes/web.php     Todas las rutas web; los permisos via middleware('can:...')

tests/
  Feature/         Tests HTTP
  Feature/Concerns/DatosDelTaller.php   Helpers compartidos
  Unit/            Tests de lógica pura
```

---

## Convenciones del proyecto

- **Español** para nombres de dominio: `motivo_ingreso`, `fecha_promesa`, `sucursal_id`.
- **Inglés** para infraestructura técnica: nombres de métodos Laravel, variables de utilidad.
- Modelos en singular PascalCase (`OrdenServicio`), tablas en plural snake_case (`ordenes_servicio`).
- Rutas con nombres `recurso.accion` (`ordenes.show`, `trabajos.store`).
- Flash messages: `'ok'` para éxito, `'error'` para fallo esperado.
- Folio de orden: lo genera `Sucursal::siguienteFolio()`, nunca a mano.
- Totales: siempre recalcular con `$orden->recalcularTotales()` después de cambiar trabajos o refacciones.

## Lo que NO hacer

- No saltarse `cambiarEstado()` del modelo para actualizar `estado` directamente con `update()`.
- No duplicar reglas de permisos: si existe un Gate, usarlo; no replicarlo en el controlador.
- No usar `dd()`, `dump()`, `var_dump()` ni `Log::info()` de debug en commits.
- No hardcodear IDs, strings de estado ni porcentajes de IVA: usar Enums y `PreferenciaOperacion`.
- No devolver JSON desde controladores web; estas rutas siempre responden con Blade o redirect.
