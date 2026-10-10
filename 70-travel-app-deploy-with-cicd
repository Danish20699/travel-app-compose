# Lab 70 — Travel App CI/CD Deployment with Jenkins

## 1. Overview & Objectives

In this lab, we automate the end-to-end continuous integration and continuous deployment (**CI/CD**) lifecycle for the multi-container **Travel Packages Listing Application** using **Jenkins LTS**, **Docker**, **Docker Compose**, and remote Linux Virtual Machines (**Staging** and **Production**).

---

## 2. CI/CD Architecture Workflow

```text
+-------------------+       +--------------------+       +---------------------+
|  Developer Workstation | ----> | GitHub Repository  | ----> | Jenkins Automation  |
|  (Git Commit & Push)   |       | (travel-app-compose)|       | Controller (v2.580) |
+-------------------+       +--------------------+       +----------+----------+
                                                                    |
                +---------------------------------------------------+
                |
                v
       +-----------------------------------------------------------+
       | Stage 1: Checkout SCM (Fetch latest source code & branch) |
       +-----------------------------------------------------------+
                |
                v
       +-----------------------------------------------------------+
       | Stage 2: Build & Push Docker Image to Docker Hub Registry |
       |          Image: danishdopsa/travel-app:v${BUILD_NUMBER}   |
       +-----------------------------------------------------------+
                |
                v
       +-----------------------------------------------------------+
       | Stage 3: Automated Deployment to Staging Environment      |
       |          Target: verjenkins@192.168.56.101:8081           |
       |          Tool: docker compose -p travel-app up -d         |
       +-----------------------------------------------------------+
                |
                v
       +-----------------------------------------------------------+
       | Stage 4: Manual Approval Gate                             |
       |          "Approve deployment to Production?"              |
       +-----------------------------------------------------------+
                | [Approved]
                v
       +-----------------------------------------------------------+
       | Stage 5: Zero-Downtime Deployment to Production VM        |
       |          Target: verjenkins@192.168.56.102:8081           |
       +-----------------------------------------------------------+
```

---

## 3. Application Components

The travel application consists of two coordinated services orchestrated through `docker-compose.yml`:
1. **Frontend / Web Service (`travel-web`):**
   - Apache + PHP 8.2 runtime
   - PDO PostgreSQL extension
   - Connects to database service via Docker bridge network
2. **Backend / Database Service (`travel-db`):**
   - PostgreSQL 15 Alpine
   - Persistent named volume (`db_data`) for data retention
   - Healthcheck script ensuring database readiness before web server traffic

---

## 4. Pipeline Configuration (`Jenkinsfile`)

The declarative pipeline manages environment secrets and credentials securely:

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
    ...
}
```

---

## 5. Deployment Verification

After deployment, the services are verified on both target nodes:

- **Staging Verification:**
  ```bash
  curl -I http://192.168.56.101:8081
  # Expected: HTTP/1.1 200 OK
  ```

- **Production Verification:**
  ```bash
  curl -I http://192.168.56.102:8081
  # Expected: HTTP/1.1 200 OK
  ```

- **Container Status on VMs:**
  ```bash
  docker compose -p travel-app ps
  ```

---

## 6. Key Takeaways
- **Infrastructure as Code & Pipeline as Code:** The complete deployment definition is versioned alongside application source code in `Jenkinsfile` and `docker-compose.yml`.
- **Project Isolation:** By assigning unique Compose project namespaces (`-p travel-app`), services run completely isolated from other applications running on the same host.
- **Controlled Release Cycle:** The staging deployment and approval gate guarantee that breaking changes never reach production without verification.
