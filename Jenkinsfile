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
                    // ── Duración total: Jenkins lo sabe con precisión ──────────
                    def durTotalSeg  = (System.currentTimeMillis() - currentBuild.startTimeInMillis) / 1000
                    def durTotalFmt  = "${durTotalSeg.toInteger()} seg"

                    // ── Información del commit ────────────────────────────────
                    def commitHash   = sh(script: 'git rev-parse --short HEAD 2>/dev/null || echo "N/A"',          returnStdout: true).trim()
                    def commitAutor  = sh(script: 'git log -1 --pretty=format:"%an" 2>/dev/null || echo "N/A"',   returnStdout: true).trim()
                    def commitFecha  = sh(script: 'git log -1 --pretty=format:"%ci" 2>/dev/null || echo "N/A"',   returnStdout: true).trim()
                    def commitMsg    = sh(script: 'git log -1 --pretty=format:"%s" 2>/dev/null || echo "N/A"',    returnStdout: true).trim()
                    def totalCommits = sh(script: 'git rev-list --count HEAD 2>/dev/null || echo "0"',            returnStdout: true).trim()

                    // ── Métricas del código fuente ────────────────────────────
                    def archivosPhp  = sh(script: 'find . -name "*.php" | grep -v vendor | wc -l',               returnStdout: true).trim()
                    def lineasPhp    = sh(script: 'find . -name "*.php" | grep -v vendor | xargs wc -l 2>/dev/null | tail -1 | awk \'{print $1}\'', returnStdout: true).trim() ?: "0"

                    // ── Métricas de la imagen Docker ──────────────────────────
                    def imagenSize   = sh(script: 'docker image inspect gestion-tareas-app:latest --format "{{.Size}}" 2>/dev/null | awk \'{printf "%.1f MB", $1/1024/1024}\'', returnStdout: true).trim() ?: "N/A"
                    def imagenId     = sh(script: 'docker image inspect gestion-tareas-app:latest --format "{{.Id}}" 2>/dev/null | cut -c8-19',                                 returnStdout: true).trim() ?: "N/A"

                    // ── Estado de los contenedores ────────────────────────────
                    def appStatus    = sh(script: 'docker ps --filter "name=gestion_tareas_app" --format "{{.Status}}" 2>/dev/null || echo "N/A"', returnStdout: true).trim()
                    def dbStatus     = sh(script: 'docker ps --filter "name=gestion_tareas_db"  --format "{{.Status}}" 2>/dev/null || echo "N/A"', returnStdout: true).trim()
                    def contActivos  = sh(script: 'docker ps | grep -c "gestion_tareas" 2>/dev/null || echo "0"', returnStdout: true).trim()

                    // ── Métricas de base de datos ─────────────────────────────
                    def totalTareas  = sh(script: 'docker exec gestion_tareas_db mysql -uroot -proot -N -e "SELECT COUNT(*) FROM gestion_tareas_ci.tareas;" 2>/dev/null || echo "0"', returnStdout: true).trim()

                    // ── Número de build y trigger ─────────────────────────────
                    def buildNum     = currentBuild.number.toString()
                    def buildCausa   = currentBuild.getBuildCauses()[0]?.shortDescription ?: "Manual"

                    echo """
╔══════════════════════════════════════════════════════════════════╗
║         MÉTRICAS DE EJECUCIÓN DEL PIPELINE                      ║
╠══════════════════════════════════════════════════════════════════╣
║  BUILD #${buildNum.padRight(56)}║
║  Disparado por: ${buildCausa.take(47).padRight(47)}║
╠══════════════════════════════════════════════════════════════════╣
║  INFORMACIÓN DEL COMMIT                                         ║
║  ─────────────────────────────────────────────────────────────  ║
║  Hash:          ${commitHash.padRight(47)}║
║  Autor:         ${commitAutor.padRight(47)}║
║  Fecha:         ${commitFecha.padRight(47)}║
║  Mensaje:       ${commitMsg.take(47).padRight(47)}║
║  Total commits: ${totalCommits.padRight(47)}║
╠══════════════════════════════════════════════════════════════════╣
║  MÉTRICAS DEL CÓDIGO FUENTE                                     ║
║  ─────────────────────────────────────────────────────────────  ║
║  Archivos PHP:  ${archivosPhp.padRight(47)}║
║  Líneas PHP:    ${lineasPhp.padRight(47)}║
╠══════════════════════════════════════════════════════════════════╣
║  DURACIÓN TOTAL DEL PIPELINE                                    ║
║  ─────────────────────────────────────────────────────────────  ║
║  Tiempo total:  ${durTotalFmt.padRight(47)}║
╠══════════════════════════════════════════════════════════════════╣
║  MÉTRICAS DE INFRAESTRUCTURA                                    ║
║  ─────────────────────────────────────────────────────────────  ║
║  ID imagen:     ${imagenId.padRight(47)}║
║  Tamaño imagen: ${imagenSize.padRight(47)}║
║  App container: ${appStatus.padRight(47)}║
║  DB  container: ${dbStatus.padRight(47)}║
║  Contenedores:  ${contActivos.padRight(47)}║
╠══════════════════════════════════════════════════════════════════╣
║  MÉTRICAS DE BASE DE DATOS                                      ║
║  ─────────────────────────────────────────────────────────────  ║
║  Tareas en BD:  ${totalTareas.padRight(47)}║
╠══════════════════════════════════════════════════════════════════╣
║  RESULTADO: EXITOSO                                             ║
║  App: http://localhost:${APP_PORT}                              ║
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