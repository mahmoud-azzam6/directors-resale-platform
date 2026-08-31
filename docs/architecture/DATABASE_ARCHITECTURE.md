# DATABASE_ARCHITECTURE.md

# Directors Resale Platform

Version: 1.0

Status: Approved

---

# Purpose

This document defines the database architecture of Directors Resale Platform.

It describes:

* Database modules
* Module responsibilities
* Relationships
* Ownership
* Business boundaries
* Event flow
* Approval flow
* AI interaction
* Integration strategy

This document intentionally does not describe table structures in detail.

Detailed schemas are documented separately in DATABASE_SCHEMA.md.

---

# Architecture Philosophy

The database is designed using Domain-Driven Design (DDD) principles.

Business domains are isolated into independent modules.

Each module owns its own data.

Cross-module communication should happen through business events whenever possible.

---

# High Level Architecture

```text
                    Core Platform
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
      Identity          CRM            Property
        │                 │                 │
        └──────────────┬──┴──────────────┐
                       │                 │
                    Matching         Deal Engine
                       │                 │
                       └──────────┬──────┘
                                  │
                           Commission Engine
                                  │
              ┌───────────────────┼────────────────────┐
              │                   │                    │
      Notifications          AI Engine          Analytics
                                  │
                           Integration Layer
```

---

# Module 1

## Core

Purpose

Provides the platform foundation.

Responsibilities

* Organizations
* Users
* Positions
* Permissions
* Settings
* Audit
* Events

Owns

* organizations
* organization_settings
* users
* user_profiles
* positions
* permissions
* system_events
* activity_logs

No business module should duplicate identity information.

---

# Module 2

## CRM

Purpose

Represents people and customer relationships.

Responsibilities

* People
* Leads
* Requirements
* Customer Journey

Owns

* people
* people_history
* leads
* lead_assignment_history
* requirement_groups
* requirements

Business Rule

One Person may have multiple Leads.

One Lead may have multiple Requirements.

---

# Module 3

## Property Engine

The approved conceptual Listing boundaries and behavior are defined by `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md`. No physical Listing schema is approved by that document.

Purpose

Represents physical real estate assets.

Responsibilities

* Properties
* Ownership
* Listings
* Media
* Approval

Owns

* properties
* property_ownerships
* property_listings
* property_media
* owner_approvals
* property_listing_history

Business Rule

Property is permanent.

Ownership changes.

Listings are temporary.

Never mix those concepts.

---

# Module 4

## Matching Engine

Purpose

Creates relationships between requirements and listings.

Responsibilities

* AI Matching
* Manual Matching
* Reverse Matching
* Match Feedback

Owns

* matches
* match_feedback

Business Rule

Matches are stored.

Matches are never calculated only at runtime.

Historical matches are preserved.

---

# Module 5

## Deal Engine

Purpose

Represents negotiations and completed transactions.

Responsibilities

* Negotiations
* Offers
* Contracts
* Sold Workflow

Owns

* deals
* deal_participants
* deal_history

Business Rule

Multiple deals may exist for the same listing.

Only one deal becomes Sold.

---

# Module 6

## Commission Engine

Purpose

Calculates financial commissions.

Responsibilities

* Commission Plans
* Overrides
* Sharing
* Transactions

Owns

* commission_plans
* commission_plan_rules
* commission_transactions

Business Rule

Commission values are stored as snapshots.

Changing commission plans never changes historical transactions.

---

# Module 7

## Approval Engine

Purpose

Provides a generic approval workflow.

Responsibilities

* Owner Approval
* Sold Approval
* Transfer Approval
* Commission Approval

Owns

* approval_requests

Business Rule

Approval engine is generic.

No module implements custom approval logic.

---

# Module 8

## Transfer Engine

Purpose

Handles movement of users and business assets.

Responsibilities

* User Transfers
* Asset Transfers
* Listing Agreements

Owns

* user_transfers
* user_asset_transfers
* listing_transfer_agreements

Business Rule

Transfers preserve complete history.

Nothing is overwritten.

---

# Module 9

## Notification Engine

Purpose

Handles user notifications.

Responsibilities

* In-App Notifications
* Email
* WhatsApp
* Future SMS

Owns

* notifications
* notification_preferences
* notification_templates
* notification_batches

Notifications are reactions to business events.

---

# Module 10

## Communication Engine

Purpose

Tracks external communications.

Responsibilities

* Email Logs
* WhatsApp Logs
* Future Communication Providers

Owns

* communication_logs
* communication_templates

---

# Module 11

## AI Engine

Purpose

Provides intelligent assistance.

Responsibilities

* OCR
* Duplicate Detection
* Identity Matching
* Smart Matching
* Content Generation
* Opportunity Discovery
* Fraud Detection
* Market Intelligence

Owns

* ai_events
* ai_profiles
* ai_insights
* ai_recommendations

Business Rule

AI never performs business actions.

AI only generates recommendations.

---

# Module 12

## Analytics

Purpose

Provides reporting and business intelligence.

Responsibilities

* KPIs
* Heat Maps
* Dashboards
* Demand Index
* Forecasting

Analytics primarily consumes data.

It should not own transactional business data.

---

# Module 13

## Integration Layer

Purpose

Connects external systems.

Responsibilities

* Webhooks
* API Keys
* Sync Jobs
* External Events

Future integrations include

* ERP
* CRM
* Power BI
* Accounting Systems
* Digital Signature Platforms

---

# Cross Module Rules

CRM never owns property data.

Property never owns people.

Deals never own properties.

Deals reference Listings.

Listings reference Ownership.

Ownership references People.

---

# Event Flow

Business Events

↓

Automation

↓

Notifications

↓

AI Learning

↓

Analytics

Every important business action generates a system event.

---

# Approval Flow

Business Action

↓

Pending Approval

↓

Approve / Reject

↓

Business Event

↓

Automation

Approval always happens before financial impact.

---

# AI Flow

Business Data

↓

AI Analysis

↓

Recommendation

↓

Human Decision

↓

Business Action

AI never bypasses approvals.

---

# Security Model

Authentication

↓

Authorization

↓

Business Validation

↓

Approval

↓

Execution

↓

Audit

Every business action must be auditable.

---

# Future Expansion

The architecture is designed to support future modules without changing existing domains.

Potential future modules include:

* Mobile Applications
* Public API
* Customer Portal
* Digital Contracts
* E-Signature
* Payment Gateway
* AI Agents
* Marketplace Integrations

---

# Final Principle

The database models business facts, not UI screens.

Every table exists because of a business concept.

Never create database tables only to satisfy a page or interface.
