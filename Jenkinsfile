pipeline {
    agent any

    environment {
        DOCKER_USER     = "danishdopsa"
        VM_USER         = "verjenkins"
        STAGING_IP      = "192.168.56.101"
        PROD_IP         = "192.168.56.102"
        DOCKER_CREDS_ID = "dockerhub-creds"
        SSH_CREDS_ID    = "vm-ssh-key"
        TAG             = "v${env.BUILD_NUMBER}"
        APP_NAME        = "travel-app"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Build & Push Image') {
            steps {
                script {
                    withCredentials([usernamePassword(
                        credentialsId: "${DOCKER_CREDS_ID}",
                        usernameVariable: 'DOCKER_USER_VAR',
                        passwordVariable: 'DOCKER_PASS_VAR'
                    )]) {
                        sh """
                            echo "${DOCKER_PASS_VAR}" | docker login -u "${DOCKER_USER_VAR}" --password-stdin
                            docker build -t ${DOCKER_USER}/${APP_NAME}:${TAG} .
                            docker push ${DOCKER_USER}/${APP_NAME}:${TAG}
                        """
                    }
                }
            }
        }

        stage('Deploy to Staging') {
            steps {
                sshagent(credentials: ["${SSH_CREDS_ID}"]) {
                    sh """
                        ssh -o StrictHostKeyChecking=no ${VM_USER}@${STAGING_IP} "mkdir -p ~/${APP_NAME}"
                        scp -r -o StrictHostKeyChecking=no docker-compose.yml db ${VM_USER}@${STAGING_IP}:~/${APP_NAME}/
                        ssh -o StrictHostKeyChecking=no ${VM_USER}@${STAGING_IP} "
                            cd ~/${APP_NAME}
                            export DOCKER_USER=${DOCKER_USER}
                            export APP_TAG=${TAG}
                            docker compose -p ${APP_NAME} pull || true
                            docker compose -p ${APP_NAME} up -d --remove-orphans
                        "
                    """
                }
            }
        }

        stage('Approval Gate') {
            steps {
                input message: "Approve deployment to Production?", ok: "Deploy to Production!"
            }
        }

        stage('Deploy to Production') {
            steps {
                sshagent(credentials: ["${SSH_CREDS_ID}"]) {
                    sh """
                        ssh -o StrictHostKeyChecking=no ${VM_USER}@${PROD_IP} "mkdir -p ~/${APP_NAME}"
                        scp -r -o StrictHostKeyChecking=no docker-compose.yml db ${VM_USER}@${PROD_IP}:~/${APP_NAME}/
                        ssh -o StrictHostKeyChecking=no ${VM_USER}@${PROD_IP} "
                            cd ~/${APP_NAME}
                            export DOCKER_USER=${DOCKER_USER}
                            export APP_TAG=${TAG}
                            docker compose -p ${APP_NAME} pull || true
                            docker compose -p ${APP_NAME} up -d --remove-orphans
                        "
                    """
                }
            }
        }
    }

    post {
        always {
            sh "docker logout || true"
        }
        success {
            echo "Travel App CI/CD Pipeline completed successfully!"
        }
        failure {
            echo "Travel App CI/CD Pipeline failed! Check logs."
        }
    }
}
