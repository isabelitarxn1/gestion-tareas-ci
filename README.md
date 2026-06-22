# Proyecto Gestión de Tareas - Integración Continua

## Descripción

Este proyecto fue desarrollado para el módulo **Énfasis Profesional I – Integración Continua** del Politécnico Grancolombiano.

La aplicación permite registrar y consultar tareas académicas mediante una interfaz web desarrollada en PHP y una base de datos MySQL.

Además, el proyecto implementa conceptos de **Integración Continua (CI)** mediante GitHub, Docker, Docker Compose y Jenkins, permitiendo automatizar la construcción y el despliegue de la aplicación.

---

## Objetivos

### Objetivo General

Implementar integración continua en un producto mínimo viable de gestión de tareas mediante herramientas de control de versiones, contenedorización y automatización.

### Objetivos Específicos

1. Desarrollar un módulo de gestión de tareas que permita registrar y consultar información.
2. Incorporar mecanismos de control y seguimiento de cambios que favorezcan la colaboración entre los integrantes del proyecto y mejoren la administración del código fuente.
3. Implementar una arquitectura basada en contenedores para asegurar la comunicación entre servicios y la portabilidad del sistema.

---

## Tecnologías Utilizadas

| Tecnología     | Versión |
| -------------- | ------- |
| PHP            | 8.2     |
| Apache         | 2.4     |
| MySQL          | 8.0     |
| Docker         | Última  |
| Docker Compose | Última  |
| Jenkins        | LTS     |
| Git            | Última  |
| GitHub         | Cloud   |
| ngrok          | Última  |

---

## Funcionalidades

* Registro de tareas mediante un formulario web.
* Almacenamiento de tareas en una base de datos MySQL.
* Consulta de tareas registradas desde una vista independiente, accesible mediante un enlace desde el formulario principal.
* Comunicación entre contenedores Docker a través de una red interna.
* Automatización de la construcción y el despliegue mediante un pipeline de Jenkins.
* Disparo automático del pipeline mediante un webhook de GitHub.

---

## Arquitectura de la Solución

A diferencia de versiones anteriores del proyecto, **ngrok no expone la aplicación web**. Su única función es generar una URL pública temporal hacia la interfaz de Jenkins, para que GitHub pueda notificarle los cambios mediante un webhook. La aplicación queda disponible únicamente en la red local, en `http://localhost:8080`.

```text
Desarrollador
     │
     ▼
git push (rama main)
     │
     ▼
GitHub Repository
     │
     ▼
GitHub Webhook
     │
     ▼
ngrok (expone únicamente el puerto 8081 de Jenkins)
     │
     ▼
Jenkins (contenedor Docker, puerto 8081)
     │
     ▼
Pipeline CI (Jenkinsfile)
     │
     ▼
Docker Compose
     │
     ▼
 ┌─────────────┐
 │ PHP Apache  │  (contenedor gestion_tareas_app, puerto 8080)
 └──────┬──────┘
        │
        ▼
 ┌─────────────┐
 │ MySQL 8.0   │  (contenedor gestion_tareas_db, puerto 3306)
 └─────────────┘
```

---

## Contenedores Docker

### Aplicación Web

* Nombre: `gestion_tareas_app`
* Tecnología: PHP 8.2 + Apache
* Puerto: 8080

### Base de Datos

* Nombre: `gestion_tareas_db`
* Tecnología: MySQL 8.0
* Puerto: 3306
* Inicialización: la carpeta `db-init/` se monta en `/docker-entrypoint-initdb.d`, de modo que el script `db-init/init.sql` crea la base de datos `gestion_tareas_ci` y la tabla `tareas` la primera vez que se levanta el contenedor.

### Jenkins

* Nombre: `jenkins`
* Tecnología: Jenkins LTS
* Puerto: 8081

---

## Estructura del Proyecto

```text
gestion-tareas-ci/
│
├── Dockerfile
├── docker-compose.yml
├── docker-compose.jenkins.yml
├── Jenkinsfile
├── conexion.php
├── guardar.php
├── listar.php
├── index.php
├── db-init/
│   └── init.sql
└── README.md
```

---

# Instalación y Ejecución

## Clonar repositorio

```bash
git clone https://github.com/isabelitarxn1/gestion-tareas-ci.git
```

## Ingresar al proyecto

```bash
cd gestion-tareas-ci
```

## Ajustar la ruta del volumen de inicialización de la base de datos

En `docker-compose.yml`, el servicio `db` monta la carpeta `db-init/` mediante una ruta absoluta de Windows, en lugar de una ruta relativa. Esto es necesario porque Jenkins corre dentro de un contenedor Docker y, al ejecutar comandos `docker-compose`, estos terminan resolviéndose contra el directorio de trabajo del daemon de Docker en el equipo anfitrión, y no contra el workspace interno de Jenkins. Cada integrante del equipo debe ajustar esa ruta para que coincida con la ubicación real del proyecto en su propia máquina, por ejemplo:

```yaml
volumes:
  - db_data:/var/lib/mysql
  - C:\xampp\htdocs\gestion-tareas-ci\db-init:/docker-entrypoint-initdb.d
```

## Construir y levantar los servicios

```bash
docker compose build
docker compose up -d
```

## Verificar ejecución

```bash
docker ps
```

---

# Acceso al Sistema

Aplicación web (formulario de registro de tareas):

```text
http://localhost:8080
```

Lista de tareas registradas (accesible también desde un enlace en la página principal):

```text
http://localhost:8080/listar.php
```

Jenkins:

```text
http://localhost:8081
```

---

# Integración Continua con Jenkins

La automatización del proyecto fue implementada mediante Jenkins, ejecutándose en un contenedor Docker independiente (`docker-compose.jenkins.yml`), con acceso al socket de Docker del equipo anfitrión para poder construir imágenes y levantar contenedores.

## Objetivo

Automatizar la construcción y el despliegue de la aplicación después de cada cambio realizado en la rama `main` del repositorio.

---

# Jenkinsfile

El pipeline implementado contiene las siguientes etapas:

## Verificar rama

Confirma que el pipeline se está ejecutando sobre la rama `main`.

## Clonar repositorio

Obtiene la última versión del código desde GitHub.

## Detener contenedores anteriores

Detiene y elimina los contenedores previos de la aplicación y la base de datos, sin afectar al contenedor de Jenkins. El volumen de datos de MySQL se conserva intencionalmente entre despliegues, de modo que las tareas guardadas no se pierden en cada ejecución del pipeline.

## Construir imagen Docker

```bash
docker build -t gestion-tareas-app:latest .
```

## Desplegar aplicación con base de datos

```bash
docker-compose -p gestion_tareas_app_stack -f docker-compose.yml up -d --build
```

## Verificar despliegue

Confirma que el contenedor de la aplicación esté en ejecución y que la tabla `tareas` exista en la base de datos, como mecanismo de detección temprana de fallos en la inicialización de MySQL.

---

# Configuración del Pipeline

En Jenkins se creó un Pipeline denominado:

```text
gestion-tareas-main
```

Configurado mediante:

```text
Pipeline Script from SCM
```

Conectado directamente al repositorio de GitHub, apuntando a la rama `main`.

---

# Integración mediante ngrok

ngrok se utiliza exclusivamente para exponer la interfaz de Jenkins (puerto 8081) y permitir que GitHub pueda enviarle notificaciones mediante un webhook. La aplicación web **no** se expone con ngrok; permanece disponible solo en la red local.

Comando:

```bash
ngrok http 8081
```

Esto genera una URL pública temporal que permite recibir las solicitudes de GitHub. Dado que esta URL cambia cada vez que se reinicia el túnel, la actualización de la Payload URL del webhook se realiza de forma manual.

---

# Webhook GitHub

Configuración:

```text
Settings
↓
Webhooks
↓
Add Webhook
```

Parámetros:

```text
Payload URL:
https://URL-NGROK/github-webhook/

Content Type:
application/json

Eventos:
Just the push event
```

Cada vez que se realiza un:

```bash
git push origin main
```

GitHub notifica automáticamente a Jenkins, y este dispara la ejecución del pipeline.

---

# Flujo Completo de Integración Continua

```text
Desarrollador realiza cambios
            │
            ▼
   git push (rama main)
            │
            ▼
          GitHub
            │
            ▼
         Webhook
            │
            ▼
    ngrok (puerto 8081)
            │
            ▼
          Jenkins
            │
            ▼
        Pipeline
            │
            ▼
 Docker Compose Build
            │
            ▼
 Docker Compose Deploy
            │
            ▼
 Aplicación Actualizada
 (http://localhost:8080)
```

---

## Conceptos Aplicados

* Integración Continua (CI).
* Control de versiones con Git.
* Trabajo colaborativo mediante GitHub.
* Contenedorización con Docker.
* Orquestación con Docker Compose.
* Automatización con Jenkins.
* Exposición de servicios locales mediante túneles (ngrok).
* DevOps básico.

---

## Beneficios Obtenidos

* Automatización del despliegue de la aplicación.
* Reducción de errores manuales.
* Mayor trazabilidad de cambios mediante Git.
* Portabilidad gracias al uso de Docker.
* Mejor colaboración entre los integrantes del equipo.
* Base para futuras implementaciones DevOps y CI/CD.

---

## Equipo de Trabajo

* Laura Monsalve Corpus
* Arianna Mora Villarreal
* main Salazar Lozada
* Jhobardelson Zuluaga García
* Isabel Cristina Madrigal Jaramillo

---

## Repositorio

[https://github.com/isabelitarxn1/gestion-tareas-ci](https://github.com/isabelitarxn1/gestion-tareas-ci)

---

## Referencias

Docker Inc. (2024). *Docker Compose overview*. Docker Documentation. https://docs.docker.com/compose/

Docker Inc. (2024). *Use bind mounts*. Docker Documentation. https://docs.docker.com/storage/bind-mounts/

GitHub Inc. (2024). *Webhooks documentation*. GitHub Docs. https://docs.github.com/en/webhooks

Jenkins Project. (2024). *Pipeline syntax*. Jenkins Documentation. https://www.jenkins.io/doc/book/pipeline/syntax/

MySQL. (2024). *Docker official image initialization scripts*. MySQL Documentation / Docker Hub. https://hub.docker.com/_/mysql

ngrok Inc. (2024). *ngrok documentation*. https://ngrok.com/docs

The PHP Group. (2024). *PHP: mysqli — Manual*. PHP Documentation. https://www.php.net/manual/es/book.mysqli.php

---

Proyecto académico desarrollado con fines educativos para el módulo Énfasis Profesional I – Integración Continua.