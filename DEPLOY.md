# Despliegue — Juicios Evaluativos

Stack: **PHP 8.3 + Apache + MariaDB 11.4**. Sin Composer ni build de assets: la
imagen simplemente copia el código y sirve con Apache.

---

## 1. Probar en local antes de subir

```bash
cp .env.example .env        # ajusta las claves
docker compose -f docker-compose.dev.yml up -d --build
```

| Servicio | URL                     |
|----------|-------------------------|
| App      | http://localhost:8080   |
| Adminer  | http://localhost:8081   |
| MariaDB  | localhost:3307          |

Comprobaciones rápidas:

```bash
curl http://localhost:8080/health.php     # -> {"status":"ok","db":"up"}
docker compose -f docker-compose.dev.yml logs -f app
docker compose -f docker-compose.dev.yml down    # -v también borra la BD
```

---

## 2. Despliegue en el VPS con Dokploy

1. **Subir el repo** a GitHub/GitLab (o usar el Git privado de Dokploy).
2. En Dokploy: **Create Application → Compose**.
3. Configurar:
   - *Repository*: tu repositorio, rama `main`
   - *Compose Path*: dejar vacío — usa `./docker-compose.yml`, que es el de
     producción. **No apuntes a `docker-compose.dev.yml`**: ese publica los
     puertos 8080/8081/3307 en el host, choca con Traefik y dejaría Adminer y
     MariaDB abiertos a internet.
4. Pestaña **Environment**, pegar (con claves reales):

   ```
   DB_NAME=juicios_evaluativos
   DB_USER=juicios
   DB_PASS=<clave-fuerte>
   DB_ROOT_PASS=<otra-clave-fuerte>
   ```

5. Pestaña **Domains** → *Add Domain*:
   - Host: `juicios.tudominio.com`
   - **Service Name**: `app`
   - **Port**: `80`
   - HTTPS: activado, certificado *Let's Encrypt*
6. **Deploy**.

> El DNS del dominio debe apuntar (registro `A`) a la IP del VPS **antes** de
> pedir el certificado, o Let's Encrypt fallará.

### Por qué `dokploy-network`

El servicio `app` se une a `dokploy-network` (red externa que Dokploy ya creó en
el VPS) para que Traefik pueda enrutarlo. `db` vive solo en la red `internal` y
**no expone ningún puerto** al exterior: únicamente `app` la alcanza.

---

## 3. Base de datos

Los scripts de `sql/` se montan en `/docker-entrypoint-initdb.d/` y se ejecutan
**una sola vez**, cuando el volumen `db_data` está vacío:

1. `01-schema.sql` → crea la BD y las tablas
2. `02-migracion.sql` → tabla `programa_resultado`

En despliegues posteriores se ignoran y **los datos se conservan** (el volumen
persiste). Ojo: `schema.sql` contiene `DROP TABLE IF EXISTS`, así que no lo
ejecutes a mano contra una base con datos reales.

> `DB_NAME` debe seguir siendo `juicios_evaluativos`: los scripts SQL llevan
> `USE juicios_evaluativos` escrito dentro.

### Cambios de esquema posteriores

```bash
# Dentro del VPS
docker exec -i <contenedor_db> mariadb -u root -p<DB_ROOT_PASS> juicios_evaluativos < nueva_migracion.sql
```

### Backup

```bash
docker exec <contenedor_db> mariadb-dump -u root -p<DB_ROOT_PASS> \
  --single-transaction juicios_evaluativos > backup_$(date +%F).sql
```

Recomendado: programar ese comando en un cron del VPS, o usar el módulo de
**Backups** de Dokploy apuntando al volumen `db_data`.

---

## 4. Configuración

| Origen | Cuándo se usa | Prioridad |
|--------|---------------|-----------|
| `config/config.env.php` | Desarrollo local (XAMPP) | 1ª |
| Variables de entorno    | Docker / Dokploy         | 2ª (rellena lo que falte) |

`config/config.env.php` está en `.gitignore` **y** en `.dockerignore`, así que
tus credenciales locales nunca entran en la imagen.

---

## 5. Notas de seguridad

- Apache bloquea por HTTP las rutas `/config`, `/sql`, `/scratch`, `/docker` y `/.git`.
- `display_errors = Off`; los errores van a los logs del contenedor.
- El error de conexión a BD ya no filtra el mensaje de PDO al cliente.
- **La autenticación está desactivada**: `index.php` redirige directo al
  dashboard y `requireLogin()` no se invoca en ninguna página. Si el dominio es
  público, cualquiera entra. Antes de exponerlo conviene reactivar el login o
  poner un *Basic Auth* delante desde Dokploy.
