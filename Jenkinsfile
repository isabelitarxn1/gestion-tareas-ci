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
        // Token de ngrok, leído de una credencial "Secret text" configurada
        // en Jenkins (Manage Jenkins → Credentials) con el ID "ngrok-authtoken".
        // Esto evita tener el token hardcodeado en el Jenkinsfile.
        NGROK_AUTHTOKEN = credentials('ngrok-authtoken')
    }

    triggers {
        // Disparar el pipeline con webhook de GitHub/GitLab
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
                    # de la app/BD/ngrok — nunca tocamos el contenedor "jenkins".
                    docker rm -f gestion_tareas_app gestion_tareas_db ngrok_tunnel || true
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

        stage('Exponer con ngrok') {
            steps {
                echo 'Exponiendo la aplicación a internet con ngrok...'
                sh '''
                    # IMPORTANTE: ngrok corre como CONTENEDOR DOCKER independiente,
                    # no como proceso en background dentro del step de Jenkins.
                    # Un proceso lanzado con "&" dentro de un step "sh" puede ser
                    # eliminado por Jenkins cuando el step termina (el step mata a
                    # sus procesos hijos). Un contenedor Docker, en cambio, sigue
                    # vivo en el host independientemente del ciclo de vida del step.

                    # Quitar cualquier contenedor ngrok previo
                    docker rm -f ngrok_tunnel || true

                    # Levantar ngrok en un contenedor propio, en la misma red que
                    # la app, apuntando al contenedor "gestion_tareas_app" por
                    # nombre (no localhost, porque está en otro contenedor).
                    docker run -d \
                        --name ngrok_tunnel \
                        --network ${COMPOSE_PROJECT_NAME}_gestion_network \
                        -e NGROK_AUTHTOKEN="${NGROK_AUTHTOKEN}" \
                        ngrok/ngrok:latest \
                        http gestion_tareas_app:80

                    # Esperar a que la API local de ngrok (dentro de su propio
                    # contenedor) responda, con reintentos.
                    NGROK_URL=""
                    for i in $(seq 1 15); do
                        sleep 2
                        RESPONSE=$(docker exec ngrok_tunnel curl -s http://localhost:4040/api/tunnels || true)
                        if [ -n "$RESPONSE" ]; then
                            NGROK_URL=$(echo "$RESPONSE" | python3 -c "
import sys, json
try:
    data = json.load(sys.stdin)
    tunnels = data.get('tunnels', [])
    if tunnels:
        print(tunnels[0]['public_url'])
except Exception:
    pass
" 2>/dev/null)
                            if [ -n "$NGROK_URL" ]; then
                                break
                            fi
                        fi
                        echo "Esperando a que ngrok levante el túnel... intento $i/15"
                    done

                    if [ -z "$NGROK_URL" ]; then
                        echo "ERROR: ngrok no generó una URL pública a tiempo."
                        echo "--- Log del contenedor ngrok ---"
                        docker logs ngrok_tunnel || true
                        exit 1
                    fi

                    echo "========================================="
                    echo "APLICACION DISPONIBLE EN:"
                    echo "${NGROK_URL}"
                    echo "========================================="

                    # Guardar la URL en un archivo para referencia
                    echo "${NGROK_URL}" > /tmp/ngrok_url.txt
                '''
            }
        }
    }

    post {
        success {
            script {
                def ngrokUrl = sh(script: 'cat /tmp/ngrok_url.txt 2>/dev/null || echo "URL no disponible"', returnStdout: true).trim()
                echo """
                DESPLIEGUE EXITOSO
                ─────────────────────────────────────
                Rama:       gabriel
                URL pública: ${ngrokUrl}
                App local:  http://localhost:${APP_PORT}
                ─────────────────────────────────────
                """
            }
        }
        failure {
            echo '❌ El pipeline falló. Revisá los logs arriba para más detalles.'
            sh 'docker-compose -p ${COMPOSE_PROJECT_NAME} -f docker-compose.yml logs --tail=50 || true'
        }
        always {
            echo 'Pipeline finalizado.'
        }
    }
}