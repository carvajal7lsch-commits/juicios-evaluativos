# Juicios Evaluativos

Sistema web para la gestión de juicios evaluativos (SENA).

**Stack:** PHP 8.3 · Apache · MariaDB · sin dependencias de Composer.

## Estructura del Proyecto

- `api/`: Endpoints de la API del sistema.
- `assets/`: Recursos estáticos (CSS, JS, imágenes).
- `config/`: Archivos de configuración, incluida la conexión a la base de datos.
- `includes/`: Componentes y utilidades compartidas.
- `pages/`: Vistas y páginas de la aplicación.
- `sql/`: Scripts de base de datos.
- `docker/`: Configuración de Apache y PHP para el contenedor.

Compose: `docker-compose.yml` es el de **producción** (el que despliega Dokploy)
y `docker-compose.dev.yml` el de **desarrollo local**.

## Opción A — Docker (recomendado)

```bash
cp .env.example .env      # ajusta las claves
docker compose -f docker-compose.dev.yml up -d --build
```

- App: http://localhost:8080
- Adminer: http://localhost:8081

La base de datos se crea e inicializa sola con los scripts de `sql/`.

## Opción B — Servidor local (XAMPP / Laragon)

1. Clonar el repositorio dentro de `htdocs`.
2. Copiar `config/config.env.example.php` a `config/config.env.php`.
3. Configurar los parámetros de conexión en `config/config.env.php`.
4. Importar `sql/schema.sql` y luego las migraciones de `sql/`.
5. Abrir el proyecto en el navegador.

## Despliegue en producción

Ver **[DEPLOY.md](DEPLOY.md)** — despliegue en VPS con Dokploy, backups y
gestión de la base de datos.
