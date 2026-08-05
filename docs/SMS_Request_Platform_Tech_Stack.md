# SMS Request & Billing Platform - Recommended Tech Stack

## Architecture

``` text
Client Website
      │
Vue 3 + Inertia.js
      │
Laravel 13
      │
PostgreSQL 16 ─ Redis ─ File Storage
      │
Queues / Notifications / Invoice PDFs
```

## Backend

-   Laravel 13
-   Laravel Breeze
-   Laravel Sanctum
-   Spatie Permission
-   Laravel Horizon
-   DomPDF

## Frontend

-   Vue 3 + Inertia.js
-   Tailwind CSS
-   shadcn-vue
-   Heroicons

## Database

Tables: - users - companies - sms_requests - sms_recipients - pricing -
invoices - payments - payment_transactions - notifications -
activity_logs - admin_notes

## Authentication

Roles: - Customer - Admin - Finance - Super Admin

## Storage

Laravel Storage (S3/R2-ready)

## Queue

Redis + Horizon

Jobs: - Generate Invoice - Send Email - Send SMS - Notify Admin -
Generate Reports

## Admin Dashboard

-   Pending Requests
-   Approved Requests
-   Paid Requests
-   Completed Campaigns
-   Revenue
-   SMS Statistics

## Notifications

Notify admins via: - Email - Telegram - Dashboard

## Payment Flow

``` text
Invoice
 ↓
Download PDF
 ↓
Pay via MoMo
 ↓
Verify Payment
 ↓
Mark Invoice Paid
```

## Invoice Contents

-   Invoice Number
-   Customer
-   SMS Quantity
-   Unit Price
-   VAT
-   Total
-   QR Code
-   Payment Link

## Email

-   Welcome
-   Invoice Ready
-   Payment Received
-   Campaign Completed

## SMS Workflow

``` text
Customer
 ↓
Create Campaign
 ↓
Admin Review
 ↓
Approve
 ↓
SMS Gateway
 ↓
Delivery Reports
```

## Customer Features

-   Account
-   Campaign History
-   Invoice Downloads
-   Payment History
-   Delivery Reports

## CSV Upload

Validate: - Invalid Numbers - Duplicates - Country Codes

## Pricing Calculator

Calculate: - Messages - Pages - Unit Cost - VAT - Total

## Campaign Scheduler

-   Immediate
-   Scheduled Date/Time

## Reports

-   Delivered
-   Failed
-   Expired
-   Pending

## Charts

Chart.js - Daily Revenue - SMS Sent - Delivery Rate - Monthly Growth

## Deployment

-   Ubuntu 24.04 LTS
-   Nginx
-   PHP 8.4
-   Laravel
-   Redis
-   PostgreSQL
-   Supervisor

## Recommended Stack Summary

  Layer            Technology
  ---------------- ---------------------------
  Backend          Laravel 13
  Frontend         Vue 3 + Inertia.js
  Styling          Tailwind CSS + shadcn-vue
  Database         PostgreSQL 16
  Cache            Redis
  Queues           Laravel Horizon
  Authentication   Fortify
  Roles            Spatie Permission
  PDFs             DomPDF
  Charts           Chart.js
  Notifications    Email + Telegram
  Payments         MTN MoMo / Gateway
  Storage          Laravel Storage
  Web Server       Nginx
  Containers       Docker Compose

## Recommended Business Flow

``` text
Register
 ↓
Create SMS Campaign
 ↓
Upload Contacts
 ↓
Automatic Cost Calculation
 ↓
Invoice Generation
 ↓
Customer Payment
 ↓
Payment Verification
 ↓
Admin Approval
 ↓
SMS Sent
 ↓
Delivery Reports
```
