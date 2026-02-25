![Deploy Status](https://github.com/carlosbuitragosan/perfume-formulation-manager/actions/workflows/deploy.yml/badge.svg)
# Perfume Formulation Manager

A full-stack Laravel application for managing perfumery materials, inventory batches, and blend formulations.
The system models real-world ingredient data, enforces relational integrity between materials and bottles, and calculates blend compositions with structured validation logic.
Built using Test-Driven Development (Pest).

## Overview

- Material directory with structured domain data
- Bottle inventory linked to materials
- Blend creation with percentage-based formulation calculations

## Stack

Laravel · Livewire · MySQL · Pest · Tailwind

## Deployment

Deployed via GitHub Actions using a pull-based SSH deployment workflow. Each push to main triggers a controlled production update on the server.

## Next Steps

- Zero-downtime deployment strategy
- Docker-based containerization
