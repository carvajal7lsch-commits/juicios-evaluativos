# Juicios Evaluativos

Sistema web para la gestión de juicios evaluativos.

## Estructura del Proyecto

- `api/`: Endpoints de la API del sistema.
- `assets/`: Recursos estáticos (CSS, JS, imágenes).
- `config/`: Archivos de configuración, incluida la conexión a la base de datos.
- `includes/`: Componentes y utilidades compartidas.
- `pages/`: Vistas y páginas de la aplicación.
- `sql/`: Scripts de base de datos.

## Instalación y Configuración

1. Clonar el repositorio.
2. Copiar el archivo `config/config.env.example.php` a `config/config.env.php`.
3. Configurar los parámetros de conexión a la base de datos en `config/config.env.php`.
4. Importar los scripts de la base de datos ubicados en la carpeta `sql/`.
5. Desplegar en un servidor web compatible con PHP (Apache/Nginx).
