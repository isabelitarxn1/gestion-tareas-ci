pipeline {
    agent any

    stages {

        stage('Verificar archivos') {
            steps {
                sh 'pwd'
                sh 'ls -la'
            }
        }

        stage('Validar Docker Compose') {
            steps {
                sh 'docker compose config'
            }
        }

        stage('Build') {
            steps {
                sh 'docker compose build'
            }
        }

        stage('Deploy') {
            steps {
                sh 'docker compose down || true'
                sh 'docker compose up -d'
            }
        }

        stage('Comprobar contenedores') {
            steps {
                sh 'docker ps'
            }
        }
    }
}