pipeline {
    agent any

    environment {
        // Nombre de la imagen Docker de la app
        APP_IMAGE = "gestion-tareas-app"
        // Puerto donde corre la app
        APP_PORT = "8080"
        // Rama que dispara el pipeline
        BRANCH_NAME_TARGET = "main"
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

                    if (ramaActual != "desconocida" && !ramaActual.endsWith('main')) {
                        error("Este pipeline solo se ejecuta en la rama 'main'. Rama actual: ${ramaActual}")
                    } else if (ramaActual == "desconocida") {
                        echo "ADVERTENCIA: no se pudo determinar la rama automáticamente (probablemente un build manual). Continuando bajo el supuesto de que el job está configurado para la rama 'main'."
                    }
                }
            }
        }

        stage('Clonar repositorio') {
            steps {
                echo 'Obteniendo últimos cambios de la rama main...'
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
                    #
                    # IMPORTANTE: a propósito NO se usa "down -v" ni se borra
                    # el volumen "db_data". Esto es intencional:
                    #   - MySQL solo ejecuta init.sql la PRIMERA vez que el
                    #     volumen de datos está vacío.
                    #   - Si el volumen ya existe, MySQL lo conserva tal cual
                    #     está (con las tareas ya guardadas) y NO vuelve a
                    #     correr init.sql, incluso si el archivo cambia.
                    #   - Esto es lo que queremos: cada despliegue reconstruye
                    #     la app, pero la base de datos persiste entre
                    #     despliegues. Si en algún momento se necesita forzar
                    #     una reinicialización completa de la base de datos
                    #     (por ejemplo, tras modificar el esquema en init.sql),
                    #     hay que borrar el volumen manualmente:
                    #       docker-compose -p gestion_tareas_app_stack -f docker-compose.yml down
                    #       docker volume rm gestion_tareas_app_stack_db_data
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

                    # Verificar que la tabla "tareas" exista en la base de datos.
                    # Si esto falla, normalmente significa que el volumen de
                    # MySQL quedó inicializado vacío en algún momento anterior
                    # (sin haber corrido init.sql). Solución: borrar el volumen
                    # "gestion_tareas_app_stack_db_data" y volver a desplegar.
                    TABLA_EXISTE=$(docker exec gestion_tareas_db mysql -uroot -proot -N -e "USE gestion_tareas_ci; SHOW TABLES LIKE \\"tareas\\";" 2>/dev/null)
                    if [ -z "$TABLA_EXISTE" ]; then
                        echo "ERROR: la tabla 'tareas' no existe en la base de datos."
                        echo "Esto suele pasar cuando el volumen de MySQL ya existía vacío de una corrida anterior (init.sql solo corre una vez, en la primera inicialización del volumen)."
                        echo "Solución: docker-compose -p ${COMPOSE_PROJECT_NAME} -f docker-compose.yml down"
                        echo "          docker volume rm ${COMPOSE_PROJECT_NAME}_db_data"
                        echo "          y volver a correr el pipeline."
                        exit 1
                    fi
                    echo "OK: la tabla 'tareas' existe en la base de datos."
                '''
            }
        }

        stage('Métricas del pipeline') {
            steps {
                script {
                    def finPipeline = sh(script: 'date +%s', returnStdout: true).trim().toInteger()
                    def inicioPipeline = env.INICIO_PIPELINE.toInteger()

                    // Duraciones por stage (en segundos)
                    def durClonar   = env.FIN_CLONAR.toInteger()   - env.INICIO_CLONAR.toInteger()
                    def durDetener  = env.FIN_DETENER.toInteger()   - env.INICIO_DETENER.toInteger()
                    def durBuild    = env.FIN_BUILD.toInteger()     - env.INICIO_BUILD.toInteger()
                    def durDeploy   = env.FIN_DEPLOY.toInteger()    - env.INICIO_DEPLOY.toInteger()
                    def durVerif    = env.FIN_VERIFICAR.toInteger() - env.INICIO_VERIFICAR.toInteger()
                    def durTotal    = finPipeline - inicioPipeline

                    echo """
╔══════════════════════════════════════════════════════════════════╗
║           MÉTRICAS DE EJECUCIÓN DEL PIPELINE                    ║
╠══════════════════════════════════════════════════════════════════╣
║  INFORMACIÓN DEL COMMIT                                         ║
║  ─────────────────────────────────────────────────────────────  ║
║  Hash:          ${env.COMMIT_HASH.padRight(47)}║
║  Autor:         ${env.COMMIT_AUTOR.padRight(47)}║
║  Fecha:         ${env.COMMIT_FECHA.padRight(47)}║
║  Mensaje:       ${env.COMMIT_MSG.take(47).padRight(47)}║
║  Total commits: ${env.TOTAL_COMMITS.padRight(47)}║
╠══════════════════════════════════════════════════════════════════╣
║  MÉTRICAS DEL CÓDIGO FUENTE                                     ║
║  ─────────────────────────────────────────────────────────────  ║
║  Archivos PHP:  ${env.ARCHIVOS_PHP.padRight(47)}║
║  Líneas PHP:    ${env.LINEAS_PHP.padRight(47)}║
╠══════════════════════════════════════════════════════════════════╣
║  TIEMPOS DE EJECUCIÓN POR STAGE                                 ║
║  ─────────────────────────────────────────────────────────────  ║
║  Clonar repo:   ${(durClonar + " seg").padRight(47)}║
║  Detener cont:  ${(durDetener + " seg").padRight(47)}║
║  Build imagen:  ${(durBuild + " seg").padRight(47)}║
║  Deploy:        ${(durDeploy + " seg").padRight(47)}║
║  Verificar:     ${(durVerif + " seg").padRight(47)}║
║  ─────────────────────────────────────────────────────────────  ║
║  DURACIÓN TOTAL: ${(durTotal + " segundos").padRight(46)}║
╠══════════════════════════════════════════════════════════════════╣
║  MÉTRICAS DE INFRAESTRUCTURA                                    ║
║  ─────────────────────────────────────────────────────────────  ║
║  Imagen Docker:     ${env.IMAGE_ID ?: env.IMAGEN_ID}            ║
║  Tamaño imagen:     ${env.IMAGEN_SIZE.padRight(43)}║
║  App container:     ${env.APP_CONTAINER_STATUS.padRight(43)}║
║  DB  container:     ${env.DB_CONTAINER_STATUS.padRight(43)}║
║  Contenedores up:   ${env.TOTAL_CONTENEDORES.padRight(43)}║
╠══════════════════════════════════════════════════════════════════╣
║  MÉTRICAS DE BASE DE DATOS                                      ║
║  ─────────────────────────────────────────────────────────────  ║
║  Tareas en BD:      ${env.TOTAL_TAREAS_DB.padRight(43)}║
╠══════════════════════════════════════════════════════════════════╣
║  RESULTADO FINAL: EXITOSO                                        ║
║  App disponible en: http://localhost:${env.APP_PORT}            ║
╚══════════════════════════════════════════════════════════════════╝
"""
                }
            }
        }
    }

    post {
        success {
            echo """
            DESPLIEGUE EXITOSO
            ─────────────────────────────────────
            Rama:      main
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