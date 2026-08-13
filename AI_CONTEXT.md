# AI_CONTEXT.md

# Directors Resale Platform

Version: 1.0

---

# Project Overview

Directors Resale Platform is an enterprise SaaS platform designed to manage real estate resale operations across a network of franchise organizations and partner agencies.

The platform is not a traditional CRM or property portal.

It is a complete business operating platform for resale transactions, commission management, approvals, AI-powered matching, analytics, and market intelligence.

Commercial Brand Name:

Directors Resale Hub

Internal Project Name:

Directors Resale Platform

---

# Project Philosophy

The platform follows five core principles.

## 1. History First

Business history must never be lost.

Every important change should be preserved.

Never overwrite historical business data.

---

## 2. Human Approval

AI can recommend.

Users can request.

Only authorized humans approve business-critical actions.

Examples:

* Property Approval
* Sold Approval
* Transfer Approval
* Commission Override

---

## 3. AI Suggests — Human Decides

Artificial Intelligence never makes final business decisions.

AI responsibilities:

* Matching
* Recommendations
* Duplicate Detection
* OCR
* Market Intelligence
* Content Generation

Humans approve the final action.

---

## 4. Event Driven Architecture

Everything important is treated as a business event.

Examples:

Property Listed

Owner Approved

Lead Assigned

Deal Created

Deal Sold

Transfer Completed

Commission Generated

Notifications and automations react to events instead of direct module-to-module calls.

---

## 5. No Hard Delete

Business records are never permanently removed.

Use:

* Archive
* Suspend
* History
* Audit

instead of deleting records.

---

# Product Vision

Build the leading enterprise resale platform for franchise-based real estate companies.

The system must support:

* Franchise Networks
* Partner Agencies
* Sales Teams
* Commission Management
* Property Marketplace
* AI Matching
* Market Intelligence
* Enterprise Audit
* Multi Currency
* Future Mobile Applications
* Third-party Integrations

---

# Development Principles

Always design for scalability.

Never design only for today's requirements.

Future expansion should require adding modules instead of rewriting existing ones.

---

# Backend Architecture

Architecture Pattern:

Repository Pattern

↓

Service Layer

↓

Controller Layer

↓

REST API

↓

Frontend

Business logic must remain inside Services.

Repositories are responsible only for database access.

Controllers contain no business logic.

---

# Database Principles

Normalize business entities.

Avoid duplicated business data.

Avoid JSON except for:

* AI payloads
* Integration payloads
* Dynamic settings
* Logs

Business fields must be stored as proper database columns.
All *_by columns reference the internal users.id primary key.
Public ULIDs must never be stored in audit relationships.

---

# Naming Conventions

Tables:

snake_case

plural

Example:

properties

property_listings

commission_transactions

Columns:

snake_case

Foreign Keys:

table_singular_id

Example:

organization_id

property_id

deal_id

---

# Status Strategy

Statuses must be standardized across the platform.

Reuse common values whenever possible.

Examples:

draft

pending

approved

rejected

active

inactive

hold

cancelled

completed

archived

---

# Security Principles

Every API request must be authenticated.

Every business action must be authorized.

Never expose owner phone numbers outside approved workflows.

Always log sensitive operations.

---

# Audit Strategy

Every critical action should be traceable.

Examples:

Who created it?

Who modified it?

Who approved it?

Who transferred it?

When did it happen?

---

# AI Strategy

AI is a business assistant.

AI is not a business decision maker.

AI modules include:

* OCR
* Identity Matching
* Duplicate Detection
* Smart Matching
* Opportunity Discovery
* Lead Scoring
* Market Intelligence
* Listing Content Generation
* Fraud Detection

---

# Core Modules

Core

CRM

Property

Deals

Commissions

Transfers

Notifications

Communications

AI

Analytics

Integrations

Each module must remain independent.

Communication between modules should happen through business events whenever possible.

---

# Development Workflow

Every feature follows the same lifecycle.

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

No implementation starts before architecture and database design are complete.

---

# Current Implementation State

The current project milestone is Milestone 2 - Core Business.

- BF001-BF005: Foundation completed
- BF006: Organization Management completed
- BF007: Franchise Management completed
- BF008: Partner Agency Management selected and planned, but not implemented

The approved hierarchy is Organization -> Franchise -> Partner Agency, followed by Users. BF008's
implementation specification has not yet been approved. Users, authentication, authorization, CRM,
Property, Listings, Deals, Commissions, Transfers, Notifications, AI, Analytics, Integrations, and the
Frontend remain future and not implemented.

The BF006-BF007 database is intentionally staged. Organization and Franchise records use the
`organizations` table; Franchise records use `organization_type = franchise` and
`parent_organization_id`. No `franchises` table exists. Global ULID, audit, security, and no-hard-delete
standards remain target architecture rather than a claim about the complete current implementation.

---

# Project Roles

Product Owner

Defines business vision and priorities.

Technical PM / Solution Architect

Owns architecture, database design, API design, implementation planning, technical decisions, and development governance.

Developer

Implements approved architecture.

AI Assistant

Supports implementation while respecting this document.

---

# Golden Rules

Never break architecture for speed.

Never duplicate business logic.

Never bypass approval workflows.

Never expose confidential business information.

Always preserve business history.

Always think enterprise-first.

Always design for future expansion.

This document is the primary technical reference for all AI assistants, developers, and contributors working on Directors Resale Platform.
