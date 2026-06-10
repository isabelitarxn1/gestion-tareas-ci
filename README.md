# Proyecto Gestión de Tareas - Integración Continua

## Descripción

Este proyecto fue desarrollado para el módulo **Énfasis Profesional I – Integración Continua** del Politécnico Grancolombiano.

La aplicación permite registrar y consultar tareas académicas mediante una interfaz web desarrollada en PHP y una base de datos MySQL.

Además, el proyecto implementa conceptos de **Integración Continua (CI)** mediante GitHub, Docker, Docker Compose y Jenkins, permitiendo automatizar la construcción y despliegue de la aplicación.

---

## Objetivos

### Objetivo General

Implementar integración continua en un producto mínimo viable de gestión de tareas mediante herramientas de control de versiones, contenedorización y automatización.

### Objetivos Específicos

1.

Desarrollar un módulo de gestión de tareas que permita registrar y consultar información.

2.

Incorporar mecanismos de control y seguimiento de cambios que favorezcan la colaboración entre los integrantes del proyecto y mejoren la administración del código fuente.

3.

Implementar una arquitectura basada en contenedores para asegurar la comunicación entre servicios y la portabilidad del sistema.

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

---

## Funcionalidades

* Registro de tareas.
* Almacenamiento en base de datos MySQL.
* Consulta de tareas registradas.
* Comunicación entre contenedores Docker.
* Automatización de despliegue mediante Jenkins.
* Ejecución automática del pipeline mediante Webhooks.

---

## Arquitectura de la Solución

```text
Desarrollador
     │
     ▼
 GitHub Repository
     │
     ▼
 GitHub Webhook
     │
     ▼
 ngrok
     │
     ▼
 Jenkins
     │
     ▼
 Pipeline CI
     │
     ▼
 Docker Compose
     │
     ▼
 ┌─────────────┐
 │ PHP Apache  │
 └──────┬──────┘
        │
        ▼
 ┌─────────────┐
 │ MySQL 8.0   │
 └─────────────┘
```

---

## Contenedores Docker

### Aplicación Web

* Nombre: `gestion_tareas_app`
* Tecnología: PHP 8.2 + Apache
* Puerto: 8081

### Base de Datos

* Nombre: `gestion_tareas_db`
* Tecnología: MySQL 8.0
* Puerto: 3306

### Jenkins

* Nombre: `jenkins`
* Tecnología: Jenkins LTS
* Puerto: 8080

---

## Estructura del Proyecto

```text
gestion_tareas_ci/
│
├── Dockerfile
├── docker-compose.yml
├── Jenkinsfile
├── conexion.php
├── guardar.php
├── listar.php
├── index.php
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

## Construir contenedores

```bash
docker compose build
```

## Levantar servicios

```bash
docker compose up -d
```

## Verificar ejecución

```bash
docker ps
```

---

# Acceso al Sistema

Aplicación web:

```text
http://localhost:8081
```

Jenkins:

```text
http://localhost:8080
```

---

# Integración Continua con Jenkins

La automatización del proyecto fue implementada mediante Jenkins.

## Objetivo

Automatizar validaciones y despliegues después de cada cambio realizado en GitHub.

---

# Jenkinsfile

El pipeline implementado contiene las siguientes etapas:

## Verificar archivos

```bash
pwd
ls -la
```

## Validar Docker Compose

```bash
docker compose config
```

## Build

```bash
docker compose build
```

## Deploy

```bash
docker compose down
docker compose up -d
```

## Comprobar Contenedores

```bash
docker ps
```

---

# Configuración del Pipeline

En Jenkins se creó un Pipeline denominado:

```text
gestion-tareas-ci
```

Configurado mediante:

```text
Pipeline Script from SCM
```

Conectado directamente al repositorio GitHub.

---

# Integración mediante Ngrok

Para permitir la comunicación entre GitHub y Jenkins se utilizó Ngrok.

Comando:

```bash
ngrok http 8080
```

Esto genera una URL pública que permite recibir solicitudes externas.

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
git push
```

GitHub notifica automáticamente a Jenkins.

---
# Flujo Completo de Integración Continua

```text
Desarrollador realiza cambios
            │
            ▼
         git push
            │
            ▼
          GitHub
            │
            ▼
         Webhook
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
```
---

## Conceptos Aplicados

* Integración Continua (CI).
* Control de versiones con Git.
* Trabajo colaborativo mediante GitHub.
* Contenedorización con Docker.
* Orquestación con Docker Compose.
* Automatización con Jenkins.
* DevOps básico.

---
## Beneficios Obtenidos

- Automatización del despliegue de la aplicación.
- Reducción de errores manuales.
- Mayor trazabilidad de cambios mediante Git.
- Portabilidad gracias al uso de Docker.
- Mejor colaboración entre los integrantes del equipo.
- Base para futuras implementaciones DevOps y CI/CD.

## Equipo de Trabajo

* Laura Monsalve Corpus
* Arianna Mora Villarreal
* Gabriel Salazar Lozada
* Jhobardelson Zuluaga García
* Isabel Cristina Madrigal Jaramillo

---

## Repositorio

https://github.com/isabelitarxn1/gestion-tareas-ci

---


Proyecto académico desarrollado con fines educativos para el módulo Énfasis Profesional I – Integración Continua.


