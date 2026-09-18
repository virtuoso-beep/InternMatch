# InternMatch: An Intelligent Student Internship Placement and Monitoring System for the University of Mindanao Using Competency-Based Semantic Matching, Machine Learning, and Geospatial Accessibility Analysis

InternMatch is an intelligent **student internship placement and monitoring system** developed for the **University of Mindanao**. The system integrates **Natural Language Processing (NLP), semantic similarity, machine learning, and geospatial accessibility analysis** to support competency-based internship recommendations, centralized internship management, and data-driven decision-making in student internship placement.

## Project Members

- **Odruña, Twinkle Pril**
- **Pancho, Angeli Sophia**
- **Talamillo, Trisha**

## Academic Project

This system is developed as a **Capstone Project** for the **University of Mindanao Tagum College, Department of Computing Education** for **Academic Year 2026–2027**.

> **InternMatch** aims to support competency-based, accessible, and data-driven student internship placement through the integration of semantic matching, machine learning, and geospatial accessibility analysis.

## Run with Docker

Docker provides a consistent local environment for the team: MySQL, the Laravel API, and the Vite frontend run together without installing PHP, MySQL, or the PHP MySQL driver on the host machine.

Requirements:

- Docker Desktop with Compose

Start the complete application from the repository root:

```bash
docker compose up --build
```

Open `http://localhost:8080` in a browser. The API is available at `http://localhost:8000`. The backend container waits for MySQL, runs migrations, and seeds the demo accounts automatically on startup.

Demo login accounts all use the password `password`:

- `admin@example.com`
- `student@example.com`
- `coordinator@example.com`
- `supervisor@example.com`
- `dean@example.com`

Stop the containers with `Ctrl+C`. To remove the containers and the local Docker database volume, run:

```bash
docker compose down -v
```
