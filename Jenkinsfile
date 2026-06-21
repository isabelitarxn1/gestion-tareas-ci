pipeline {
    agent any

    environment {
        // Nombre de la imagen Docker de la app
        APP_IMAGE = "gestion-tareas-app"
        // Puerto donde corre la app
        APP_PORT = "8080"
        // Rama que dispara el pipeline
        BRANCH_NAME_TARGET = "gabriel"
        // Nombre de proyecto FIJO y único para docker-compose.
        // Esto evita que "docker-compose down" use el nombre de la carpeta
        // del workspace (que puede coincidir/confundirse con otros stacks,
        // incluido el propio Jenkins) y termine apagando contenedores que
        // no le pertenecen, como el propio Jenkins.
        COMPOSE_PROJECT_NAME = "gestion_tareas_app_stack"
    }

    triggers {
        // Disparar el pipeline con webhook de GitHub.
        // NOTA: ngrok ya NO se usa para exponer esta app. ngrok se usa
        // únicamente, de forma manual y aparte de este pipeline, para
        // exponer Jenkins (puerto 8081) y así poder configurar la Payload
        // URL del webhook de GitHub. Ver sección 11 de la documentación.
        githubPush()
    }

    stages {

        stage('Verificar rama') {
            steps {
                script {
                    // env.GIT_BRANCH solo viene poblado cuando el trigger es un webhook.
                    // En un build manual (Build Now) puede venir null o vacío, así que
                    // usamos BRANCH_NAME (si existe) y si no, confiamos en la rama
                    // configurada en el propio job de Jenkins.
                    def ramaActual = env.GIT_BRANCH ?: env.BRANCH_NAME ?: "desconocida"
                    echo "Rama actual: ${ramaActual}"

                    if (ramaActual != "desconocida" && !ramaActual.endsWith('gabriel')) {
                        error("Este pipeline solo se ejecuta en la rama 'gabriel'. Rama actual: ${ramaActual}")
                    } else if (ramaActual == "desconocida") {
                        echo "ADVERTENCIA: no se pudo determinar la rama automáticamente (probablemente un build manual). Continuando bajo el supuesto de que el job está configurado para la rama 'gabriel'."
                    }
                }
            }
        }

        stage('Clonar repositorio') {
            steps {
                echo 'Obteniendo últimos cambios de la rama gabriel...'
                checkout scm
            }
        }

        stage('Detener contenedores anteriores') {
            steps {
                echo 'Deteniendo y eliminando contenedores previos de la app (sin tocar Jenkins)...'
                sh '''
                    # -p fija el nombre de proyecto explícitamente, así Compose
                    # solo gestiona los contenedores de ESTE stack y nunca toca
                    # nada fuera de él (como el contenedor jenkins).
                    docker-compose -p ${COMPOSE_PROJECT_NAME} -f docker-compose.yml down --remove-orphans || true

                    # Eliminación puntual por nombre, solo de los contenedores
                    # de la app/BD — nunca tocamos el contenedor "jenkins".
                    docker rm -f gestion_tareas_app gestion_tareas_db || true
                '''
            }
        }

        stage('Construir imagen Docker') {
            steps {
                echo 'Construyendo imagen de la aplicación...'
                sh 'docker build -t ${APP_IMAGE}:latest .'
            }
        }

        stage('Desplegar aplicación con base de datos') {
            steps {
                echo 'Levantando app y base de datos con docker-compose...'
                sh '''
                    docker-compose -p ${COMPOSE_PROJECT_NAME} -f docker-compose.yml up -d --build
                '''
            }
        }

        stage('Verificar despliegue') {
            steps {
                echo 'Esperando que los servicios estén listos...'
                sh '''
                    sleep 15
                    docker-compose -p ${COMPOSE_PROJECT_NAME} -f docker-compose.yml ps
                    # Verificar que el contenedor de la app esté corriendo
                    docker ps | grep gestion_tareas_app || (echo "ERROR: El contenedor no está corriendo" && exit 1)
                '''
            }
        }
    }

    post {
        success {
            echo """
            ✅ DESPLIEGUE EXITOSO
            ─────────────────────────────────────
            Rama:      gabriel
            App local: http://localhost:${APP_PORT}
            ─────────────────────────────────────
            """
        }
        failure {
            echo 'El pipeline falló. Revisá los logs arriba para más detalles.'
            sh 'docker-compose -p ${COMPOSE_PROJECT_NAME} -f docker-compose.yml logs --tail=50 || true'
        }
        always {
            echo 'Pipeline finalizado.'
        }
    }
}