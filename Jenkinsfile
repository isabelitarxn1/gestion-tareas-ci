pipeline {
    agent any

    environment {
        // Nombre de la imagen Docker de la app
        APP_IMAGE = "gestion-tareas-app"
        // Puerto donde corre la app
        APP_PORT = "8080"
        // Rama que dispara el pipeline
        BRANCH_NAME_TARGET = "gabriel"
    }

    triggers {
        // Disparar el pipeline con webhook de GitHub/GitLab
        githubPush()
    }

    stages {

        stage('Verificar rama') {
            steps {
                script {
                    echo "Rama actual: ${env.GIT_BRANCH}"
                    if (!env.GIT_BRANCH?.endsWith('gabriel')) {
                        error("Este pipeline solo se ejecuta en la rama 'gabriel'. Rama actual: ${env.GIT_BRANCH}")
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
                echo 'Deteniendo y eliminando contenedores previos...'
                sh '''
                    docker-compose down --remove-orphans || true
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
                    docker-compose up -d --build
                '''
            }
        }

        stage('Verificar despliegue') {
            steps {
                echo 'Esperando que los servicios estén listos...'
                sh '''
                    sleep 15
                    docker-compose ps
                    # Verificar que el contenedor de la app esté corriendo
                    docker ps | grep gestion_tareas_app || (echo "ERROR: El contenedor no está corriendo" && exit 1)
                '''
            }
        }

        stage('Exponer con ngrok') {
            steps {
                echo 'Exponiendo la aplicación a internet con ngrok...'
                sh '''
                    # Detener ngrok si ya está corriendo
                    pkill ngrok || true
                    sleep 2

                    # Iniciar ngrok en segundo plano apuntando al puerto de la app
                    nohup ngrok http ${APP_PORT} --log=stdout > /tmp/ngrok.log 2>&1 &

                    # Esperar a que ngrok inicie
                    sleep 8

                    # Obtener la URL pública de ngrok via su API local
                    NGROK_URL=$(curl -s http://localhost:4040/api/tunnels | python3 -c "
import sys, json
data = json.load(sys.stdin)
tunnels = data.get('tunnels', [])
if tunnels:
    print(tunnels[0]['public_url'])
else:
    print('No se encontró túnel ngrok')
")

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
                ✅ DESPLIEGUE EXITOSO
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
            sh 'docker-compose logs --tail=50 || true'
        }
        always {
            echo 'Pipeline finalizado.'
        }
    }
}