# Directors Resale Platform

> Enterprise SaaS Platform for Real Estate Resale Networks

**Commercial Brand:** Directors Resale Hub

**Internal Project Name:** Directors Resale Platform

Current Version: **v0.1.0-foundation**

---

# Overview

Directors Resale Platform is an enterprise-grade SaaS platform built to manage real estate resale operations across franchise networks, partner agencies, and sales teams.

The platform combines CRM, Property Management, Matching Engine, Deal Management, Commission Management, AI, Business Intelligence, and Enterprise Workflow Automation into a single scalable system.

This is not a traditional CRM or listing website.

It is a complete operating platform for resale businesses.

---

# Core Objectives

* Build a scalable enterprise platform
* Support franchise and partner agency networks
* Manage complete resale lifecycle
* Automate business workflows
* Preserve full business history
* Deliver AI-powered recommendations
* Provide market intelligence
* Support future integrations and mobile applications

---

# Core Modules

## Core

* Organizations
* Organization Settings
* Users
* User Profiles
* Positions
* Permissions
* System Settings
* Event Engine

---

## CRM

* People
* Leads
* Requirements
* Requirement Groups
* Lead Assignment
* CRM History

---

## Property Engine

* Properties
* Ownership
* Listings
* Owner Approvals
* Property History
* Property Media

---

## Matching Engine

* AI Matching
* Manual Matching
* Match History
* Match Feedback
* Reverse Matching

---

## Deal Engine

* Deal Pipeline
* Negotiations
* Viewings
* Contracts
* Sold Approval
* Deal History

---

## Commission Engine

* Commission Plans
* Commission Rules
* Commission Sharing
* Commission Transactions
* Commission Snapshots

---

## Transfer Engine

* User Transfers
* Asset Transfers
* Listing Agreements
* Commission Protection

---

## Communication

* Notifications
* Emails
* WhatsApp
* SMS (Future)
* Communication Logs

---

## AI Engine

* Duplicate Detection
* OCR
* Identity Matching
* Listing Content Generation
* Smart Matching
* Opportunity Discovery
* Lead Scoring
* Fraud Detection
* Market Intelligence

---

## Analytics

* Dashboards
* KPIs
* Heat Maps
* Demand Index
* Reports
* Forecasting

---

## Integration Layer

* REST API
* Webhooks
* ERP Integrations
* CRM Integrations
* Power BI
* Future External Services

---

# Technology Stack

## Backend

Custom PHP API

Repository Pattern

Service Layer

REST API

MySQL

---

## Frontend

Next.js

TypeScript

Tailwind CSS

TanStack Query

React Hook Form + Zod

---

## Infrastructure

Cloudflare

Git

GitHub

Future Queue Workers

Future Object Storage

---

# Development Philosophy

The project follows an architecture-first approach.

Every feature must follow this lifecycle:

Business Rule

↓

Architecture

↓

Database

↓

API

↓

Backend

↓

Frontend

↓

Testing

No implementation starts before architecture approval.

---

# Project Structure

```text
app/
    Core/
        Contracts/
        Database/
        Repository/
    Exceptions/
    Http/
    Providers/
    Responses/
    Routing/

bootstrap/
config/
database/
resources/
storage/
scripts/
tests/
docs/
public/
routes/
frontend/

README.md
AI_CONTEXT.md
CHANGELOG.md
```

---

# Documentation

The `/docs` directory contains all project documentation.

Main sections include:

* Product
* Architecture
* Database
* API
* Development
* UI/UX
* AI
* Business Intelligence
* Project Management

---

# Architecture Principles

* History First
* Human Approval
* AI Suggests, Human Decides
* Event Driven Architecture
* No Hard Delete
* Enterprise First

---

# Current Project Status

Current Phase:

Listing Domain and Physical Data Model Architecture Approved

Current Sprint:

No implementation sprint selected

Current Milestone:

Milestone 2 - Core Business

Implementation Status:

Core and Admin implementation completed through AF003; Listing domain not implemented

Architecture Status:

Completed

Business Analysis:

Completed

Database Discovery:

Completed

Latest Verified Implementation:

BF001-BF012 and AF001-AF003 are implemented. AF003 browser QA passed and its branch was committed and pushed.

Next Development Milestone:

Not selected

The platform uses one authenticated Admin Application. User experience is driven by Organization,
Position, effective Permissions, and applicable resource scope; Position names are not authorization
rules. See `docs/architecture/ADMIN_EXPERIENCE_ARCHITECTURE.md`.

The approved product architecture distinguishes Global Marketplace Visibility from
Administrative Scope. Authenticated Users may browse marketplace-eligible available Listings across the
network, but that visibility does not grant authority over another Organization's Listings, Users,
reports, commissions, or operations. Listings, Requests, Reports, Commissions, Teams, and provisioning
workflows are planned, not implemented. The approved, not-yet-implemented Listing domain is defined by `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md`; its approved conceptual physical model is defined by `docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md`. No SQL schema or migration is approved. LF001 is not started, and no Listing implementation sprint or next implementation milestone is selected.

---

# Long-Term Vision

Directors Resale Platform is designed to become a regional enterprise platform capable of supporting:

* Large real estate networks
* Franchise organizations
* Independent partner agencies
* Multi-country deployments
* Multi-currency operations
* AI-assisted sales operations
* Enterprise reporting and market intelligence

---

# Contributors

## Product Owner

Defines business vision, priorities, workflows, and product direction.

## Solution Architect / Technical PM

Owns architecture, database design, API design, technical planning, implementation strategy, and development governance.

## Developers

Implement approved architecture following project standards.

## AI Assistants

Support implementation while respecting AI_CONTEXT.md and project architecture.

---

# License

Private Project

Copyright © Directors Resale Platform

All rights reserved.
