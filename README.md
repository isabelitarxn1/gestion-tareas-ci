# Proyecto Integración Continua

## Descripción
Proyecto desarrollado para el módulo Énfasis Profesional I (Integración Continua).

La aplicación está orientada a la gestión de tareas académicas utilizando PHP, MySQL y XAMPP.

## Tecnologías utilizadas

- PHP
- MySQL
- XAMPP
- Github

## Funcionalidades iniciales

- Registro de tareas
- Listado de tareas
- Conexión a base de datos MySQL
## Docker

El proyecto cuenta con un Dockerfile para ejecutar la aplicación PHP en un contenedor Docker.

### Construcción de la imagen

```bash
docker build -t gestion-tareas-ci .
```

### Ejecución del contenedor

```bash
docker run -d -p 8081:80 --name mi-app gestion-tareas-ci
```

### Acceso desde el navegador

```text
http://localhost:8081
```

Esta configuración permite ejecutar la aplicación sin depender directamente de la instalación local de Apache, facilitando la portabilidad y el despliegue del proyecto.


## Autor

Isabel Madrigal