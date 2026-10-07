---
description: Workflow para verificar conexión SSH y desplegar backend y frontend a producción
---

# Verificar Conexión y Desplegar

Este workflow verifica la conectividad con el servidor de producción y ejecuta los scripts de despliegue.

## 1. Verificar Conexión SSH

Primero, verificamos que tengamos acceso al servidor remoto configurado en los scripts.

```bash
# Verificar acceso SSH (asume que el host 'panificadora' está configurado en ~/.ssh/config)
ssh -q -o BatchMode=yes -o ConnectTimeout=5 panificadora exit && echo "✅ Conexión SSH exitosa" || echo "❌ Error: No se pudo conectar a 'panificadora'. Verifica tu configuración SSH."
```

## 2. Desplegar Backend

Si la conexión es exitosa, procedemos a desplegar el backend.

```bash
# Ejecutar script de despliegue de backend
./deploy-backend.sh
```

## 3. Desplegar Frontend

Finalmente, desplegamos el frontend.

```bash
# Ejecutar script de despliegue de frontend
./deploy-frontend.sh
```
