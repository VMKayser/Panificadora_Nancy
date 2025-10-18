# Database schema - Panificadora Nancy

Este documento resume las tablas principales creadas por las migraciones en `database/migrations`. Es útil como referencia rápida antes/después de despliegues.

> Nota: los tipos y constraints se tomaron de las migraciones. Revisa las migraciones si necesitas detalles adicionales.

---

## users
- id (PK)
- name (string)
- email (string, unique)
- email_verified_at (timestamp, nullable)
- password (string)
- remember_token (string, nullable)
- created_at, updated_at (timestamps)


## password_reset_tokens
- email (PK)
- token (string)
- created_at (timestamp)


## sessions
- id (PK string)
- user_id (FK users, nullable)
- ip_address (string, 45, nullable)
- user_agent (text, nullable)
- payload (longText)
- last_activity (integer)


## categorias
- id (PK)
- nombre (string(100))
- url (string(100), unique)
- descripcion (text, nullable)
- imagen (string, nullable)
- esta_activo (boolean, default true)
- order (integer, default 0)
- created_at, updated_at


## productos
- id (PK)
- categorias_id (FK categorias -> id)
- nombre (string(150))
- url (string(150), unique)
- descripcion (text, nullable)
- descripcion_corta (text, nullable)
- precio_minorista (decimal 10,2)
- precio_mayorista (decimal 10,2, nullable)
- cantidad_minima_mayoreo (integer, default 10)
- es_de_temporada (boolean, default false)
- esta_activo (boolean, default true)
- requiere_tiempo_anticipacion (boolean)
- tiempo_anticipacion (integer, nullable)
- unidad_tiempo (enum: horas,dias,semanas)
- limite_produccion (boolean)
- created_at, updated_at, deleted_at (softDeletes)


## pedidos
- id (PK)
- numero_pedido (string, unique)
- user_id (FK users, nullable)
- cliente_nombre (string)
- cliente_apellido (string)
- cliente_email (string)
- cliente_telefono (string)
- tipo_entrega (enum delivery|recoger)
- direccion_entrega (text, nullable)
- indicaciones_especiales (text, nullable)
- subtotal (decimal 10,2)
- descuento (decimal 10,2, default 0)
- total (decimal 10,2)
- metodos_pago_id (FK metodos_pago)
- codigo_promocional (string, nullable)
- estado (enum)
- estado_pago (enum)
- qr_pago (text, nullable)
- referencia_pago (string, nullable)
- fecha_entrega (timestamp, nullable)
- fecha_pago (timestamp, nullable)
- vendedor_id (FK vendedores, nullable)
- descuento_bs (decimal 10,2, default 0)
- motivo_descuento (text, nullable)
- created_at, updated_at, deleted_at (softDeletes)

Note: Se añadió recientemente la columna `stock_descargado` (tinyint(1), default 0) usada para marcar si
el stock ya fue descontado para este pedido y evitar descuentos duplicados al confirmar/entregar pedidos.


## detalle_pedidos
- id (PK)
- pedidos_id (FK pedidos)
- productos_id (FK productos)
- nombre_producto (string)
- precio_unitario (decimal 10,2)
- cantidad (integer)
- subtotal (decimal 10,2)
- requiere_anticipacion (boolean)
- tiempo_anticipacion (integer, nullable)
- unidad_tiempo (enum horas,dias,semanas)
- created_at, updated_at


## vendedores
- id (PK)
- user_id (FK users)
- codigo_vendedor (string, unique, nullable)
- comision_porcentaje (decimal 5,2)
- descuento_maximo_bs (decimal 10,2)
- puede_dar_descuentos (boolean)
- puede_cancelar_ventas (boolean)
- turno (string, nullable)
- fecha_ingreso (date, nullable)
- estado (enum activo|inactivo|suspendido)
- observaciones (text, nullable)
- ventas_realizadas (integer)
- total_vendido (decimal 12,2)
- descuentos_otorgados (decimal 10,2)
- created_at, updated_at, deleted_at


## panaderos
- id (PK)
- nombre, apellido, email(unique), telefono, ci(unique)
- direccion (text, nullable)
- fecha_ingreso (date)
- turno (enum)
- especialidad (enum)
- salario_base (decimal)
- total_kilos_producidos (integer)
- total_unidades_producidas (integer)
- ultima_produccion (date, nullable)
- activo (boolean)
- observaciones (text, nullable)
- created_at, updated_at, deleted_at


## clientes
- id (PK)
- nombre, apellido, email(unique), telefono
- direccion (text, nullable)
- ci (string, nullable)
- tipo_cliente (enum regular|mayorista|vip)
- total_pedidos (integer)
- total_gastado (decimal 10,2)
- fecha_ultimo_pedido (date, nullable)
- activo (boolean)
- notas (text, nullable)
- created_at, updated_at, deleted_at


## metodos_pago
- id (PK)
- nombre (string)
- codigo (string, unique)
- descripcion (text, nullable)
- icono (string, nullable)
- esta_activo (boolean)
- comision_porcentaje (decimal 5,2)
- orden (integer)
- created_at, updated_at


## materias_primas
- id
- nombre (string)
- codigo_interno (string, unique, nullable)
- unidad_medida (enum kg,g,L,ml,unidades)
- stock_actual (decimal 10,3)
- stock_minimo (decimal 10,3)
- costo_unitario (decimal 10,2)
- proveedor (string, nullable)
- ultima_compra (date, nullable)
- activo (boolean)
- timestamps, softDeletes


## recetas
- id
- producto_id (FK productos)
- nombre_receta (string)
- descripcion (text, nullable)
- rendimiento (decimal)
- unidad_rendimiento (enum unidades,kg,docenas)
- costo_total_calculado, costo_unitario_calculado
- activa (boolean)
- version (integer)
- timestamps, softDeletes


## ingredientes_receta
- id
- receta_id (FK recetas)
- materia_prima_id (FK materias_primas)
- cantidad (decimal)
- unidad (enum)
- costo_calculado (decimal)
- orden (integer)
- timestamps


## producciones
- id
- producto_id, receta_id, user_id (FK)
- fecha_produccion (date), hora_inicio, hora_fin
- cantidad_producida (decimal)
- unidad (enum)
- harina_real_usada, harina_teorica, diferencia_harina
- tipo_diferencia (enum)
- costo_produccion, costo_unitario
- estado (enum)
- observaciones, timestamps, softDeletes


## movimientos_materia_prima
- id
- materia_prima_id (FK)
- tipo_movimiento (enum)
- cantidad, costo_unitario, stock_anterior, stock_nuevo
- produccion_id (FK nullable)
- user_id (FK nullable)
- observaciones, numero_factura
- timestamps


## inventario_productos_finales
- id
- producto_id (FK unique)
- stock_actual, stock_minimo, fecha_elaboracion, dias_vida_util, fecha_vencimiento, costo_promedio
- timestamps


## movimientos_productos_finales
- id
- producto_id (FK)
- tipo_movimiento (enum)
- cantidad, stock_anterior, stock_nuevo
- produccion_id (FK nullable), pedido_id (FK nullable), user_id (FK)
- observaciones, timestamps


---

Si quieres, puedo:
- Añadir a este documento los índices definidos en cada migración (ya aparecieron algunos).
- Generar un diagrama ER básico (archivo DOT o Mermaid) para visualizar relaciones.
- Exportar una versión en formato SQL (CREATE TABLE) basada en estas migraciones.

Dime qué prefieres y lo hago.