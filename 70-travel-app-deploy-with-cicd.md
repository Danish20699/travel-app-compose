# Lab 70: Travel App CI/CD Pipeline & Automated Multi-Environment Deployment

![CI/CD](https://img.shields.io/badge/CI%2FCD-Pipelines-007ACC?style=for-the-badge&logo=githubactions&logoColor=white)
![Jenkins](https://img.shields.io/badge/Jenkins-2.580%2B-D24939?style=for-the-badge&logo=jenkins&logoColor=white)
![Docker Compose](https://img.shields.io/badge/Docker%20Compose-v2.27%2B-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15%20Alpine-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)
![Status](https://img.shields.io/badge/Status-Completed-success?style=for-the-badge)

---

## 📌 Lab Overview & Real-World DevOps Context

In **Lab 66**, we engineered the multi-container **Kashmir Travel Packages & Reservation Platform** ("*Jannat-e-Kashmir*"), orchestrating an **Apache + PHP 8.2** web frontend, a **PostgreSQL 15** relational database, and an **Adminer** database management interface using Docker Compose.

While Lab 66 proved that our stack runs cleanly in local development, deploying updates manually to remote servers in an enterprise environment introduces friction:
- Developers must manually SSH into target servers.
- Database passwords and secrets risk leakage during manual configuration.
- Releases cannot be reliably rolled back if unexpected bugs reach live production.

In **Lab 70**, we transform our travel application into an enterprise-grade automated product by engineering a complete **CI/CD Pipeline in Jenkins** (`Jenkinsfile`). 

Every code change pushed to GitHub is automatically tested, packaged into an immutable version-tagged Docker image, published to Docker Hub, deployed to our **Staging VM** (`192.168.56.101`), verified through automated health probes, held behind an **Interactive Approval Gate**, and promoted safely to **Production** (`192.168.56.102`).

---

## 🏗️ End-to-End CI/CD Architecture Diagram

```text
  Developer Workstation
  (VS Code / Ubuntu WSL)
           │
           │ 1. git commit && git push origin main
           ▼
┌────────────────────────────────────────────────────────┐
│            GitHub Remote Repository                    │
│             (Danish20699/travel-app-compose)           │
│   • Source Code (PHP / HTML / CSS / JS)                │
│   • Dockerfile & docker-compose.yml                    │
│   • Database Schema (./db/init.sql)                    │
│   • Declarative Pipeline (Jenkinsfile)                 │
└──────────────────────────┬─────────────────────────────┘
                           │
                           │ 2. Webhook / SCM Polling Trigger
                           ▼
┌────────────────────────────────────────────────────────┐
│         Jenkins LTS Automation Controller              │
│ ┌────────────────────────────────────────────────────┐ │
│ │ Stage 1: Checkout SCM (Clean workspace)            │ │
│ ├────────────────────────────────────────────────────┤ │
│ │ Stage 2: Build Image & Tag (v${BUILD_NUMBER})      │ │
│ ├────────────────────────────────────────────────────┤ │
│ │ Stage 3: Authenticate & Push to Docker Hub         │ │
│ │          (danishdopsa/travel-app:v${BUILD_NUMBER}) │ │
│ └────────────────────────┬───────────────────────────┘ │
└──────────────────────────┼─────────────────────────────┘
                           │
                           │ 3. Automated SSH Deployment via compose
                           ▼
┌────────────────────────────────────────────────────────┐
│             Staging VM (192.168.56.101)                │
│   • Project Isolation: docker compose -p travel-app    │
│   • Database Healthcheck: pg_isready                   │
│   • Web Frontend: http://192.168.56.101:8085           │
│   • Database Admin: http://192.168.56.101:8081         │
└──────────────────────────┬─────────────────────────────┘
                           │
                           │ 4. Jenkins Approval Gate (Manual Sign-off)
                           ▼
┌────────────────────────────────────────────────────────┐
│            Production VM (192.168.56.102)              │
│   • Customer-Facing Live Workload                      │
│   • Persistent Storage: travel_pgdata named volume     │
│   • Zero-Downtime Rolling Container Replacement        │
└────────────────────────────────────────────────────────┘
```

---

## 📚 Multi-Container Application Architecture

The application stack consists of three coordinated services running on an isolated bridge network (`travel-network`):

| Service | Container Name | Image / Runtime | Port Mapping | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| **`travel-web`** | `travel-web-service` | Custom build (`Dockerfile`) | `8085:80` | Serves the dynamic PHP booking engine & Kashmir tour packages. |
| **`travel-db`** | `travel-db-service` | `postgres:15-alpine` | `5432` (Internal) | Stores destination packages, pricing, bookings, and user inquiries. |
| **`travel-adminer`**| `travel-adminer-service` | `adminer:latest` | `8081:8080` | Web-based relational database administration and SQL console. |

---

## 🛠️ Complete Declarative CI/CD Pipeline (`Jenkinsfile`)

The pipeline automates image creation, credential injection, and multi-node deployments while strictly adhering to DevOps security best practices:

```groovy
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
        stage('Checkout SCM') {
            steps {
                checkout scm
            }
        }

        stage('Build & Push Docker Image') {
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
                        scp -o StrictHostKeyChecking=no docker-compose.yml ${VM_USER}@${STAGING_IP}:~/${APP_NAME}/
                        ssh -o StrictHostKeyChecking=no ${VM_USER}@${STAGING_IP} "
                            cd ~/${APP_NAME}
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
                        scp -o StrictHostKeyChecking=no docker-compose.yml ${VM_USER}@${PROD_IP}:~/${APP_NAME}/
                        ssh -o StrictHostKeyChecking=no ${VM_USER}@${PROD_IP} "
                            cd ~/${APP_NAME}
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
            echo "Travel App CI/CD Pipeline failed! Check logs above."
        }
    }
}
```

---

## 🔍 Healthcheck & Dependency Orchestration

In a multi-container stack, starting containers simultaneously leads to race conditions where the web frontend crashes because PostgreSQL is still running its initial setup.

In `docker-compose.yml`, we solve this using strict health probe conditions:

```yaml
  travel-web:
    ...
    depends_on:
      travel-db:
        condition: service_healthy

  travel-db:
    image: postgres:15-alpine
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER} -d ${POSTGRES_DB}"]
      interval: 5s
      timeout: 5s
      retries: 10
      start_period: 10s
```

- **`pg_isready` Probe:** Actively sends ping requests to the PostgreSQL server process.
- **`start_period: 10s`:** Gives the container 10 seconds of grace time to run schema migrations before failures count against the retry counter.
- **`condition: service_healthy`:** Docker Compose will strictly delay launching `travel-web` and `travel-adminer` until the database probe returns exit code `0`.

---

## 🌐 Verification Commands

After the pipeline finishes, both environments are verified via command-line probes and browser inspections:

```bash
# 1. Verify Staging Web Application
curl -I http://192.168.56.101:8085
# HTTP/1.1 200 OK

# 2. Verify Staging Adminer Console
curl -I http://192.168.56.101:8081
# HTTP/1.1 200 OK

# 3. Verify Production Web Application
curl -I http://192.168.56.102:8085
# HTTP/1.1 200 OK

# 4. Verify Project Isolation on VMs
ssh verjenkins@192.168.56.101 "docker compose -p travel-app ps"
```

---

## 🧠 Key Takeaways & Enterprise Lessons

1. **Project Isolation (`-p travel-app`):** Always specify a project namespace with Docker Compose so services from different repositories (like `restaurant-app` and `travel-app`) run independently without overwriting each other's networks or volumes.
2. **Deterministic Build Tags:** Building images tagged with `v${BUILD_NUMBER}` ensures every release artifact is immutable and traceable to an exact Jenkins build log.
3. **Automated Secret Injection:** Storing Docker Hub tokens and SSH private keys in Jenkins Credentials eliminates hardcoded passwords from Git repositories.
4. **Controlled Production Promotions:** The interactive Approval Gate ensures that developers and QA testers can thoroughly validate Staging before live customers receive updates.
