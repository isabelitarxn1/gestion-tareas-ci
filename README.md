# Proyecto Gestión de Tareas - Integración Continua

## Descripción

Este proyecto fue desarrollado para el módulo **Énfasis Profesional I – Integración Continua**.

La aplicación permite gestionar tareas académicas mediante una interfaz web desarrollada en PHP y una base de datos MySQL. El proyecto fue adaptado para ejecutarse en contenedores Docker, implementando prácticas de integración continua y trabajo colaborativo mediante Git y GitHub.

## Objetivos del proyecto

* Aplicar conceptos de integración continua.
* Utilizar control de versiones con Git y GitHub.
* Implementar una aplicación web funcional.
* Gestionar datos mediante MySQL.
* Desplegar la aplicación utilizando contenedores Docker.
* Permitir la comunicación entre múltiples contenedores.

## Tecnologías utilizadas

* PHP 8.2
* Apache
* MySQL 8.0
* Docker
* Docker Compose
* Git
* GitHub
* XAMPP (utilizado durante la fase inicial de desarrollo)

## Funcionalidades

* Registro de tareas.
* Almacenamiento de tareas en base de datos.
* Consulta y listado de tareas registradas.
* Comunicación entre aplicación web y base de datos mediante Docker.

## Arquitectura del proyecto

El proyecto utiliza dos contenedores Docker:

### Contenedor de aplicación

* Nombre: `gestion_tareas_app`
* Tecnologías: PHP 8.2 y Apache
* Función: ejecutar la aplicación web.

### Contenedor de base de datos

* Nombre: `gestion_tareas_db`
* Tecnología: MySQL 8.0
* Función: almacenar la información de las tareas.

Ambos contenedores se comunican a través de una red Docker creada automáticamente por Docker Compose.

## Ejecución del proyecto

### Clonar el repositorio

```bash
git clone https://github.com/isabelitarxn1/gestion-tareas-ci.git
```

### Ingresar al proyecto

```bash
cd gestion-tareas-ci
```

### Levantar los contenedores

```bash
docker compose up -d --build
```

### Verificar contenedores activos

```bash
docker ps
```

### Acceder a la aplicación

Abrir en el navegador:

```text
http://localhost:8081
```

## Estructura principal

```text
gestion_tareas_ci/
│
├── Dockerfile
├── docker-compose.yml
├── conexion.php
├── guardar.php
├── listar.php
├── index.php
└── README.md
```

## Integración Continua

Durante el desarrollo se utilizaron buenas prácticas de integración continua:

* Uso de repositorio GitHub.
* Trabajo mediante ramas.
* Control de versiones con Git.
* Construcción automatizada mediante Docker.
* Separación de servicios en contenedores independientes.
* Comunicación entre servicios mediante Docker Compose.

## Autor

Isabel Madrigal

Proyecto académico desarrollado para el módulo Énfasis Profesional I – Integración Continua.
