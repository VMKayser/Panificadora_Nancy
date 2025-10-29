# CHANGELOG

## 2025-10-24 — Corrección: desincronización de transacciones en tests, seeders idempotentes y limpieza

Resumen
-------
Se solucionó un fallo crítico reproducible únicamente en MySQL durante la ejecución de la suite de pruebas: errores de SAVEPOINT / "There is no active transaction" provocados por la destrucción tardía de objetos `PendingCommand` que invocaban el kernel/artisan durante el teardown de tests. También se corrigieron errores secundarios por seeders no idempotentes que provocaban entradas duplicadas en `metodos_pago`. Finalmente, se eliminó la instrumentación temporal utilizada para el diagnóstico y se verificó que la suite de tests queda completamente verde.

Cambios clave
-----------
- Evitar efectos secundarios de `PendingCommand` durante el bootstrap de tests: las invocaciones a `artisan()` usadas en la fase de bootstrap se ejecutan de forma que no devuelvan objetos `PendingCommand` que puedan ser destruidos mientras hay transacciones activas (previene desconexiones PDO y desincronizaciones de niveles de transacción).
- Seeders idempotentes: `database/seeders/MetodoPagoSeeder.php` y `database/seeders/DemoDataSeeder.php` cambiados para usar `DB::table(...)->upsert(...)`.
- Tests y observers adaptados para idempotencia: reemplazo de `MetodoPago::create(...)` por `MetodoPago::firstOrCreate(...)` o aislamiento explícito de datos en tests (`MetodoPago::query()->delete()` donde correspondía).
- Limpieza de diagnóstico temporal: eliminación de listeners, kernel proxy y logs (por ejemplo `storage/logs/test-transactions.log`, `storage/logs/pending-command-creation.log`) y reversión de `tests/TestCase.php` a una versión minimalista sin instrumentación.

Validación
----------
- Ejecución completa de la suite PHPUnit dentro del contenedor Docker de pruebas: OK (33 tests, 154 assertions).
- Reproducción del fallo original antes de los cambios; tras aplicar correcciones y adaptar tests, la suite pasó completamente.

Notas y próximos pasos
---------------------
- Recomendado: auditoría global de llamadas a transacciones (`DB::transaction`, `beginTransaction`, etc.) y aplicación de un guard (`SafeTransaction`) en call-sites críticos (Producción, Inventario, Pedidos) para prevenir regressiones.
- Añadir tests de regresión específicos que reproduzcan escenarios de desincronización de transacciones bajo MySQL.

Firmado: Equipo de desarrollo — Corrección investigada y aplicada el 2025-10-24.
