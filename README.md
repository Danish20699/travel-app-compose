# 🏔️ Jannat-e-Kashmir: Multi-Tier Travel Application (Docker Compose)

[![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![Docker Compose](https://img.shields.io/badge/Docker_Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docs.docker.com/compose/)
[![PHP](https://img.shields.io/badge/PHP_8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL_15-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Adminer](https://img.shields.io/badge/Adminer-Database_GUI-4B7CFE?style=for-the-badge)](https://www.adminer.org/)
[![Apache](https://img.shields.io/badge/Apache-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org/)
[![GitHub Actions](https://img.shields.io/badge/CI/CD-GitHub_Actions-2088FF?style=for-the-badge&logo=github-actions&logoColor=white)](https://github.com/features/actions)

A production-grade, multi-tier travel packages listing and reservation application architected with **Apache + PHP 8.2**, **PostgreSQL 15**, and **Adminer Database Management GUI**, orchestrated via **Docker Compose**. Engineered following the **Twelve-Factor App** methodology with decoupled environment configuration, automated database initialization, dependency-aware healthchecks, and live source bind mounting.

---

## 🏗️ System Architecture

```text
                           [ Client Browser / User ]
                                 │            │
                    Port 8085 : 80            Port 8081 : 8080
                                 │            │
                                 ▼            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│  Docker Compose Project: travel-app-compose                                            │
│                                                                                        │
│  ┌────────────────────────────────────────┐  depends_on (healthy)  ┌─────────────────┐ │
│  │ Service: travel-web                    │ ─────────────────────> │ Service: travel-db│
│  │ Container: travel-web-service          │                        │ Container: db-… │ │
│  │ Image: custom built via Dockerfile     │                        │ Image: pg:15-…  │ │
│  │ Ports: 8085:80                         │                        │ Ports: 5432     │ │
│  │ Volumes: ./src -> /var/www/html (Bind) │                        │ Health: isready │ │
│  └───────────────────┬────────────────────┘                        └────────┬────────┘ │
│                      │                                                      ▲          │
│                      │                       ┌──────────────────────────────┘          │
│                      │                       │ depends_on (healthy)                    │
│                      │     ┌─────────────────┴───────────────────────┐                 │
│                      │     │ Service: travel-adminer                 │                 │
│                      │     │ Container: travel-adminer-service       │                 │
│                      │     │ Image: adminer:latest                   │                 │
│                      │     │ Ports: 8081:8080                        │                 │
│                      │     └─────────────────┬───────────────────────┘                 │
│                      │                       │                                         │
│                      └───────────────────────┼───────────────────────┐                 │
│                                              ▼                       │                 │
│                      ┌───────────────────────────────────────────────┴─────┐           │
│                      │ Network: travel-network (Isolated Bridge)           │           │
│                      │ Internal DNS: resolve 'travel-db'                   │           │
│                      └───────────────────────┬─────────────────────────────┘           │
│                                              │                                         │
│                                              ▼                                         │
│                                   ┌─────────────────────┐                              │
│                                   │ Volume: travel_pg…  │                              │
│                                   │ Path: /var/lib/pg…  │                              │
│                                   └─────────────────────┘                              │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 🎨 Application Showcase

### 1. 🏔️ Kashmir Travel Luxury Experience (`http://localhost:8085`)
![Jannat-e-Kashmir Travel Platform](assets/app-preview.png)
*Live multi-container Kashmir Travel experience running on Apache/PHP 8.2 with custom golden vector crest and dynamic PDO queries.*

### 2. 🗄️ Adminer Database Management Console (`http://localhost:8081`)
![Adminer PostgreSQL Database Console](assets/adminer-dashboard.png)
*Adminer 6.1.0 visual database management interface showing live `destinations` table in PostgreSQL 15.*

---

## ✨ Key Features & Engineering Highlights

- **Dynamic Destination Catalog**: Queries curated Kashmir luxury destinations (Gulmarg Gondola & Skiing, Dal Lake Royal Houseboat, Pahalgam Betaab Valley, Sonamarg Glacier, Doodhpathri, Gurez Valley, and KGL Alpine Trek) live from PostgreSQL using PDO prepared statements.
- **Interactive Reservation Engine**: High-conversion booking modal with real-time client & server validation, automated unique reference generator (`KMR-XXXXXX`), and atomic inserts into the relational `bookings` table.
- **Adminer Web Database Management GUI**: Built-in visual database interface accessible via browser on port `8081` to manage tables, run SQL queries, and inspect records.
- **Twelve-Factor App Compliance**: Strict separation of configuration and code via `.env` (ignored by git) and a documented `.env.example` template.
- **Automated Database Seeding**: Declarative schema and seed migration through `./db/init.sql` mounted into `/docker-entrypoint-initdb.d/init.sql`.
- **Healthcheck-Driven Boot Sequencing**: Uses PostgreSQL's `pg_isready` probe with `start_period: 10s`, guaranteeing the web application and Adminer never encounter cold-start connection drops.
- **Developer Productivity**: Live source synchronization via `./src:/var/www/html` bind mounts for instant hot-reloading without image rebuilds.

---

## 🗂️ Project Directory Structure

```text
travel-app-compose/
├── .github/
│   ├── ISSUE_TEMPLATE/
│   │   ├── bug_report.md
│   │   └── feature_request.md
│   └── workflows/
│       └── ci.yml                       # CI pipeline validating docker-compose syntax
├── assets/
│   └── app-preview.png                  # Application UI showcase asset
├── db/
│   └── init.sql                         # Schema (destinations, bookings) & seed data
├── src/
│   └── index.php                        # Full-stack responsive web application
├── .env                                 # Local configuration & credentials (git-ignored)
├── .env.example                         # Safe environment template
├── .gitignore                           # Excludes secrets, logs, and persistent data
├── Dockerfile                           # Custom PHP 8.2 Apache + PostgreSQL PDO image
├── docker-compose.yml                   # Declarative multi-container orchestration
├── 66-travel-packages-listing-app.md    # Lab 66 documentation & verification log
├── CONTRIBUTING.md                      # Community contribution guidelines
├── CODE_OF_CONDUCT.md                  # Contributor Covenant Code of Conduct
├── LICENSE                              # MIT License
└── README.md                            # Project overview & documentation
```

---

## 🚀 Quick Start Guide

### Prerequisites
- Docker Engine 20.10+
- Docker Compose v2.0+

### 1. Clone the Repository
```bash
git clone https://github.com/Danish20699/travel-app-compose.git
cd travel-app-compose
```

### 2. Configure Environment
```bash
cp .env.example .env
```

### 3. Build & Launch the Multi-Container Stack
```bash
docker compose up -d --build
```

### 4. Verify Stack Status
```bash
docker compose ps
```

Expected output:
```text
NAME                     IMAGE                           COMMAND                  SERVICE          STATUS                 PORTS
travel-db-service        postgres:15-alpine              "docker-entrypoint.s…"   travel-db        Up (healthy)           5432/tcp
travel-web-service       travel-app-compose-travel-web   "docker-php-entrypoi…"   travel-web       Up                     0.0.0.0:8085->80/tcp
travel-adminer-service   adminer:latest                  "entrypoint.sh docke…"   travel-adminer   Up                     0.0.0.0:8081->8080/tcp
```

### 5. Access the Applications
- **Travel Web App**: [`http://localhost:8085`](http://localhost:8085)
- **Adminer DB GUI**: [`http://localhost:8081`](http://localhost:8081)

#### Adminer Login Details:
| Field | Value |
| :--- | :--- |
| **System** | `PostgreSQL` |
| **Server** | `travel-db` *(Docker internal DNS hostname)* |
| **Username** | `danish` *(from `.env`)* |
| **Password** | `danish_secure_pass_123` *(from `.env`)* |
| **Database** | `travel_db` *(from `.env`)* |

---

## 🧪 Testing & Verification

### Database Health Check
```bash
docker compose exec travel-db pg_isready -U danish -d travel_db
```

### Query Destinations Table
```bash
docker compose exec travel-db psql -U danish -d travel_db -c "SELECT id, title, price, duration FROM destinations;"
```

### Query Bookings Table
```bash
docker compose exec travel-db psql -U danish -d travel_db -c "SELECT * FROM bookings ORDER BY created_at DESC LIMIT 5;"
```

---

## 📚 Curriculum & Lab Documentation

| Lab | Documentation File | Key Concepts Practiced | Status |
| :---: | :--- | :--- | :---: |
| **Lab 66** | [66-travel-packages-listing-app.md](66-travel-packages-listing-app.md) | Multi-tier orchestration, Adminer DB GUI, Twelve-Factor `.env`, automated DB seeding, healthchecks | ✅ Completed |

---

## 👤 Author

**Danish Nazir**  
DevOps Engineer & Full-Stack Developer  
- GitHub: [@Danish20699](https://github.com/Danish20699)  
- Email: danishnazir20699@gmail.com  

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
