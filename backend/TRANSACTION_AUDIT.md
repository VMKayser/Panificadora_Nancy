# Transaction audit — DB::transaction call-sites

Fecha: 2025-10-25

Objetivo
--------
Recolectar y priorizar los lugares del código donde se usan transacciones (p. ej. `DB::transaction`, `beginTransaction`) para planificar la aplicación del guard `SafeTransaction` o cambios de refactor que eviten desincronizaciones de niveles de transacción.

Hallazgos (priorizados)
-----------------------

Alta prioridad (afección directa a inventario / pedidos / producción)

- `app/Models/Produccion.php` — varias llamadas a `DB::transaction` alrededor del procesamiento de producción (líneas ~368-383). (Riesgo: operaciones compuestas que tocan inventarios y movimientos; alta prioridad de revisión)
- `app/Services/InventarioService.php` — `DB::transaction` en `descontarInventario` (línea ~111). (Riesgo alto: decremento de stock y consistencia)
- `app/Http/Controllers/Api/PedidoController.php` — uso de `DB::transaction` para creación de pedidos y flujo mostrador/normal (líneas ~87, 89, 300, 301). (Riesgo alto: creación de pedidos + descuentos de stock)
- `app/Http/Controllers/ProduccionController.php` — `DB::transaction` en creación/procesamiento de producciones (líneas ~106, 213). (Riesgo alto)

Media prioridad (CRUD sensibles y controladores administrativos)

- `app/Http/Controllers/Api/AdminProductoController.php` — `DB::transaction` en creación/actualización de productos (líneas ~96, 249). (Riesgo medio: cambios de catálogo que pueden afectar inventario)
- `app/Http/Controllers/RecetaController.php` / `app/Models/Receta.php` — `DB::transaction` en creación/actualización/duplicado de recetas (varias líneas: Receta::... y controller lines ~72,160,326). (Riesgo medio)
- `app/Http/Controllers/MateriaPrimaController.php` — transacciones en endpoints de materia prima (líneas ~196, 251). (Riesgo medio)
- `app/Http/Controllers/InventarioController.php` — `DB::transaction` en operaciones de inventario (línea ~222). (Riesgo medio)

Baja prioridad (seeders, comandos, observers, auth)

- `database/seeders/DemoDataSeeder.php` / `FixRolesSeeder.php` — `DB::transaction` en seeders (línea ~27, 24). (Bajo riesgo en producción, pero relevante en tests/seeders idempotencia)
- `app/Console/Commands/PoblarInventarioProductos.php` — `DB::transaction` en comando de consola (línea ~51). (Bajo)
- `app/Observers/UserObserver.php` — `DB::transaction` en observer (línea ~138). (Bajo / medio según uso)
- `app/Http/Controllers/Api/AuthController.php` — `DB::transaction` en creación de usuario (línea ~41). (Bajo)

Soporte / Helper existente
-------------------------

- `app/Support/SafeTransaction.php` — helper ya presente y usado para ejecutar callbacks sin abrir una nueva transacción si `transactionLevel()` > 0. Está registrado en el repo y genera logs `SafeTransaction: executing callback without opening new transaction` en el entorno de testing.

Recomendaciones inmediatas (plan de acción)
-------------------------------------------

1. Aplicar `SafeTransaction::run(...)` en call-sites de Alta prioridad:
   - `Produccion`, `InventarioService`, `PedidoController`, `ProduccionController`.
   - Razonamiento: evitar abrir transacciones anidadas que creen savepoints cuando ya exista una transacción activa en el contexto de testing (especialmente durante RefreshDatabase / seeding / teardown).

2. Añadir pruebas de regresión específicas (MySQL) que reproduzcan el teardown con objetos destructores (ya investigado): tests de integración que ejecuten creación de pedidos/producciones dentro de RefreshDatabase y validen que no ocurra `SAVEPOINT ... does not exist`.

3. Revisar observers / comandos / seeders y optar por `SafeTransaction` o por convertir operaciones en idempotentes/no transaccionales según corresponda. Seeders ya fueron ajustados para `upsert` en `metodos_pago`.

4. Instrumentación opcional: habilitar logs temporales (por ejemplo, `SafeTransaction` debug logs) en la CI para monitorear el nivel de transacción durante ejecuciones problemáticas. Mantener instrumentación fuera de la rama principal cuando no sea necesaria.

Pasos siguientes sugeridos (work items)
--------------------------------------

1. Implementar cambios en alta prioridad y ejecutar la suite PHPUnit bajo el contenedor MySQL; iterar hasta que no se reproduzca el fallo.
2. Hacer PRs pequeños por subsistema (Producción, Pedidos, Inventario) con cambios `DB::transaction -> SafeTransaction::run` y pruebas adjuntas.
3. Agendar una revisión de código para validar que el uso de transactions sigue un patrón coherente (abrir transacción solo en el borde de operación compuesta).

Registro de búsquedas realizadas
-------------------------------

- Búsqueda: `DB::transaction` — múltiples resultados en `app/Models/Produccion.php`, `app/Services/InventarioService.php`, `app/Http/Controllers/Api/PedidoController.php`, `app/Http/Controllers/ProduccionController.php`, `app/Http/Controllers/Api/AdminProductoController.php`, `database/seeders/DemoDataSeeder.php`, `app/Observers/UserObserver.php`, etc.
- Búsqueda: `transactionLevel` — ver `app/Support/SafeTransaction.php`.

Contacto
-------
Si quieres, puedo empezar a parchear los call-sites de alta prioridad ahora (aplicar `SafeTransaction::run` y ejecutar tests para cada cambio). Puedo abrir PRs separados por subsistema para facilitar revisión.
