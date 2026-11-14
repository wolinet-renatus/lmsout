# THE OPEN UNIVERSITY OF TANZANIA (OUT)

## THE INSTITUTE OF EDUCATIONAL AND MANAGEMENT TECHNOLOGIES (IEMT)

### SOFTWARE REQUIREMENTS SPECIFICATION (SRS) & SYSTEM ARCHITECTURE REFERENCE

## UNIFIED OUT LMS & SHORT COURSES MANAGEMENT INFORMATION SYSTEM (`LMSOUT`)

| Document Attribute | Details |
| :--- | :--- |
| **Project Title** | Unified Learning Management & Short Courses Management Information System (`LMSOUT` / Edux) |
| **Organization** | The Open University of Tanzania (OUT) — IEMT |
| **Baseline Reference** | `SCMIS SRS v1.0` (1st February, 2023 — Beatus Winifrid Mbunda, Evodius Albart, Mahfoudh Katakweba) merged with `OUT LMS v3.0.0` (Maintained by Wolinet) |
| **System Version** | `3.0.0` (`VersionUpdateSeeder::NEW_VERSION`) |
| **Runtime Stack** | PHP `^8.3 \| ^8.4 \| ^8.5`, Laravel Framework `^13.0`, MySQL `8+`, Vite `^7.0`, Tailwind CSS `^4.0`, Bootstrap 4/5 |
| **Updated Date** | 14th November, 2026 |

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Overall System Description](#2-overall-system-description)
3. [External Interface Requirements](#3-external-interface-requirements)
4. [Functional Requirements (SCMIS SRS + Edux Unified Implementation)](#4-functional-requirements)
5. [Role-Based Access Control (RBAC) & Regional Branch Isolation](#5-role-based-access-control-rbac--regional-branch-isolation)
6. [Role-Specific Dashboards, Sidebars, Filters & Pagination](#6-role-specific-dashboards-sidebars-filters--pagination)
7. [Edux Core LMS, Payment Gateways & AI Features](#7-edux-core-lms-payment-gateways--ai-features)
8. [Non-Functional & Security Requirements](#8-non-functional--security-requirements)
9. [Database Schema & Domain Models](#9-database-schema--domain-models)
10. [REST API Specification](#10-rest-api-specification)
11. [Operations, Backups & Automated Test Verification](#11-operations-backups--automated-test-verification)

---

## 1. Introduction

### 1.1 Purpose
This document provides the complete technical and functional specification of **LMSOUT** — the unified **OUT Learning Management System (Edux)** and **Short Courses Management Information System (SCMIS)** operated for **The Open University of Tanzania (OUT)**. It preserves all stakeholder requirements originally established in `SCMIS SRS v1.0` (IEMT) while documenting the full production architecture, online LMS workflows, GePG/installment billing, RBAC matrix, regional branch isolation, Sanctum REST API, and role-adaptive UI implemented in version `3.0.0`.

### 1.2 Intended Audience
- **Institutional Leadership (Director, Deputy Director, Branch Managers)**: Oversight across 17 regional centers, academic delivery, fee collection, and certificate clearance governance.
- **Development, Security & DevOps Engineers**: Reference for Laravel routes, controllers, services, policies, database schema, security middleware, and automated feature tests.
- **Training Coordinators, Tutors, Accountants, Marketing & Center Staff**: Operational map of daily workflows within strictly scoped regional portals.

### 1.3 Product Scope
**LMSOUT** unifies two previously separate operational domains into a single modular codebase:
1. **Short Courses Management Information System (SCMIS / Campus Operations)**: Regional branch governance, classroom/venue capacity management, cohort timetables, Face-to-Face & Hybrid applicant registration, fee waivers (including 50% staff discount on Basic Computer courses), invoice & GePG-style `99104*` control number generation, `Full`/`Half`/`Quarter` installment schedules, overdue reminders, QR/manual attendance tracking, certificate clearance queues, and executive PDF/Excel/CSV center reports.
2. **Online Digital Learning Platform (Edux LMS)**: Public storefront, course & bundle catalog, visual curriculum builder (video, document, text, YouTube playlists, Gemini AI studio), interactive course player with drip scheduling, timed quizzes & question banks, assignment originality checks, live video classes, community forum, student–instructor real-time messaging, Gemini AI study assistant, drag-and-drop QR certificate builder, multi-gateway checkout, and mobile/client REST API.

---

## 2. Overall System Description

### 2.1 Product Perspective
In line with the original SCMIS SRS goals while scaling across digital and physical campuses, **LMSOUT** delivers:
- Centralized digital records replacing manual center paperwork and reducing operational duplication across all regional centers.
- Rapid creation and publishing of online courses, blended cohorts, applicants, invoices, and certificates.
- Instant filtering, searching, sorting, and pagination across courses, learners, applicants, invoices, timetables, and audit trails.
- Real-time visibility of regional center schedules, venue utilization, attendance percentages, payment statuses (`paid`, `partial`, `unpaid`), and certificate clearance readiness (`pending`, `ready`, `issued`).
- Mobile-ready and browser-responsive UI alongside token-authenticated JSON endpoints for mobile applications.

### 2.2 Product Modules
| Module | Scope & Primary Responsibilities |
| :--- | :--- |
| **Course & Bundle Management** | Online, Face-to-Face (`face_to_face`), and Hybrid (`hybrid`) courses; topics/lessons, drip content, YouTube import, AI builder, categories, languages, course bundles, attendance thresholds |
| **Center & Venue Management** | Regional centers (`branches`), physical rooms/labs (`venues`), seat capacity checks, timetable slot conflict detection (`course_assignments`), and global country/nationality lookups |
| **Applicant & Student Lifecycle** | Online checkout & public/staff F2F/Hybrid application forms (`/courses/{course}/apply`), automatic learner account creation, cohort enrollment, unenrollment, and academic progression |
| **Billing, GePG & Payments** | Course/application invoices (`invoices`), `99104*` control numbers, installment plans (`payment_schedules`), 9 online gateways + offline bank slip verification, receipt generation, tax & co-instructor revenue splits (`RevenueShareService`) |
| **Instructor & Cohort Delivery** | Visual course builder, question bank + CSV import + merit lists, assignment grading + originality checks, live classes, campus Paid/Unpaid student rosters, QR & manual attendance marking, payout withdrawals |
| **Assessment & Certification** | Attendance threshold enforcement (`attendance_threshold`), certificate clearance queue, automated tutor alert emails when certificates are ready, custom drag-and-drop templates (`DomPDF` / `FPDF`), and public QR/serial verification (`/verify-certificate`) |
| **Communication, AI & Helpdesk** | Support tickets (`support_tickets`), peer Q&A (`community_*`), course discussions, direct student–instructor messaging (`ChatService`), Gemini AI Study Assistant (`AiChatController`), announcement marquee, push notifications, and newsletter campaigns |
| **Governance, RBAC & Maintenance** | Spatie RBAC (`roles`, `permissions`), multi-branch vs. center-isolated scoping (`BranchScoped`), activity/security audit logs (`activity_log`), system backups (`SystemBackupService`), deployment inspector (`UpdaterController`), and CMS (`FrontendSettingController`) |

### 2.3 Key System Users (12-Role Unified Model)
Expanding the original 6 SCMIS roles (`ST`, `TC`, `SA`, `Instructor`, `MO`, `DR`) to cover full institutional governance and branch finance/front-desk separation:

| Index | Role Name | Spatie Role Key | Operational Scope |
| :--- | :--- | :--- | :--- |
| 1 | **Super Administrator** | `super-admin` | Unrestricted system governance (`manage-all`), all centers, backups, updater |
| 2 | **Administrator** | `admin` | Full cross-branch academy governance, courses, finance, staff, CMS |
| 3 | **System Administrator** | `system-administrator` | Full platform IT governance, staff security blocks/resets, backups, audit logs |
| 4 | **Director** | `director` | Cross-branch executive oversight, reports, approvals, clearances, audit logs |
| 5 | **Deputy Director** | `deputy-director` | Multi-branch academic oversight, reports, instructor approvals, audit logs |
| 6 | **Marketing Officer** | `marketing-officer` | Cross-branch growth: applicants, courses, bundles, coupons, CMS, campaigns |
| 7 | **Branch Manager / Registrar** | `branch-registrar` | Branch-isolated HQ/center registration, venues, timetables, invoices, clearances |
| 8 | **Training Coordinator** | `coordinator` | Branch-isolated academic logistics: courses, venues, schedules, attendance, clearances |
| 9 | **Accountant** | `accountant` | Branch-isolated finance: GePG invoices, payments, installments, withdrawals, financial reports |
| 10 | **Center Staff** | `staff` | Branch-isolated front desk: applicant intake, attendance, clearances, support tickets |
| 11 | **Instructor / Tutor** | `instructor` | Portal-isolated (`/instructor/*`): courses, builders, assessments, campus cohorts, rosters, earnings |
| 12 | **Student** | `student` | Portal-isolated (`/student/*`): enrolled courses, quizzes, live classes, timetable, invoices, certificates |

### 2.4 Operating Environment
- **Server Runtime**: Linux / macOS / Apache or Nginx running **PHP `^8.3` / `8.4` / `8.5`** with `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`/`imagick`, `mbstring`, `openssl`, `pdo_mysql`, `xml`, and `zip` extensions.
- **Database**: MySQL `8.0+` / MariaDB `10.6+` (or SQLite for lightweight local evaluation).
- **Client Browsers**: Modern Chromium (Chrome, Edge), Mozilla Firefox, Apple Safari 9+ & iOS Safari (with `-webkit-backdrop-filter` glassmorphism compatibility).
- **Build & Asset Pipeline**: Composer `2.x`, Node.js `18+`, Vite `^7.0.7`, Tailwind CSS `^4.0.0`, Axios `^1.11`.

### 2.5 User Documentation & Online Help
Built-in interactive role-aware **Online Help** portals are served across `/admin/help`, `/instructor/help`, and `/student/help` via `HelpController`, complemented by the guided web installer (`resources/views/setup/`) and `README.md`.

---

## 3. External Interface Requirements

### 3.1 Hardware Interfaces
- Server hardware sized for institutional concurrency (baseline Gen 9+ server specification, 16GB+ RAM, expandable storage for course media, uploaded payment slips, and compressed ZIP backups).
- Responsive client support across desktops, tablets, mobile smartphones (including camera/QR scan links for campus check-ins), and touch screens.

### 3.2 Software Interfaces
- **Payment Gateways (`PaymentGatewayService`)**: Stripe (`stripe/stripe-php`), Paystack (`yabacon/paystack-php`), PayPal, Razorpay, Flutterwave, Mollie, SSLCommerz, bKash, XPay, plus institutional Offline Bank Transfer & GePG (`99104*`) control number workflow.
- **AI Integration (`GeminiService`)**: Google Gemini API powering Student AI Study Assistant and Instructor/Admin AI Course Builder (`aiCourseBuilder`, `aiStudio`).
- **Document & Export Engines**: `barryvdh/laravel-dompdf`, `codedge/laravel-fpdf` (PDF certificates, invoices, payment receipts, reports), `maatwebsite/excel` & `phpoffice/phpspreadsheet` (Excel/CSV reporting and question bank imports), `intervention/image` / GD (auto-resized profile avatars & media), `QrCodeService` (QR verification and classroom check-ins).
- **Mail & Security Services**: Configurable SMTP (`SmtpConfigurator`), Google reCAPTCHA (`RecaptchaService`), OTP two-step verification (`OtpService`), Spatie Activitylog (`spatie/laravel-activitylog`), and Opcodes Log Viewer (`opcodesio/log-viewer`).

---

## 4. Functional Requirements

Every requirement from `SCMIS SRS v1.0` (`R1`–`R6`) is implemented and extended in `LMSOUT v3.0.0` (`R1`–`R10`):

| Ref | Actor / Domain | Requirements & Implemented Behavior in `LMSOUT` (`edux`) |
| :--- | :--- | :--- |
| **R1** | **Student (`ST`)** | Authenticate (`/login`, OTP/2FA, password reset), view/update profile & avatar (`/student/settings`), change password, enroll in online courses/bundles or apply for F2F/Hybrid short courses (`/courses/{course}/apply`), unenroll from eligible enrollments (`DELETE /student/campus/enrollments/{enrollment}`), generate invoices & `99104*` control numbers, make online/installment payments, upload offline payment receipts (`POST /student/campus/invoices/{invoice}/receipt`), receive automated email/database overdue installment alerts (`InstallmentReminderService`), view official payment receipts & printable invoices, view enrolled courses, timetables & start/end times (`/student/campus/timetable`), check in via QR code (`/student/campus/checkin/{token}`), complete lessons/quizzes/assignments, download QR-verified PDF certificates (`/student/campus/certificates/{enrollment}/pdf`), and submit/track support tickets. |
| **R2** | **Training Coordinator / Branch Manager (`TC` / `BM`)** | Register and manage instructors and applicants, manage regional branches (`/admin/campus/branches` for multi-branch) and venues (`/admin/campus/venues`), create & assign courses with delivery mode (`online`, `face_to_face`, `hybrid`), link instructors + venues + branches with time-slot conflict & capacity checks (`admin.campus.timetable.*`), mark manual attendance or open timed QR check-in sessions (`admin.campus.attendance.*`), send automated tutor/learner alert emails when certificates reach `ready` status (`CertificateClearanceController`, `NotificationTemplate`), and generate Weekly/Monthly/Quarterly regional reports broken down by center, course, learner count, and payment status (`Full`/`Half`/`Quarter`/`Paid`/`Partial`/`Unpaid`). |
| **R3** | **Systems Administrator / Super Admin (`SA`)** | Full authentication security controls, forced password reset (`force_password_reset`), view/update profile photo with server-side auto-resize (`Admin\ProfileController`), create/edit/delete staff & system users (`StaffController`, `UserController`), create custom roles & assign granular RBAC permissions (`RoleSeeder::permissionGroups()`), block/unblock users and trigger password resets (`UserSecurityController`), search/filter users globally (`/admin/campus/search`), manage languages, CMS frontend, backups, deployment updater, and audit logs. |
| **R4** | **Marketing Officer (`MO`)** | Register & manage short courses, bundles (`admin.bundles.*`), categories, and languages across all 17 regional centers; monitor intake conversion funnels, applicant demand by center/course, discount waivers, coupons (`admin.coupons.*`), homepage/CMS banners, broadcast notifications, and export applicant/center course reports (without access to destructive system backups, staff CRUD, or internal payout tables). |
| **R5** | **Director & Deputy Director (`DR`)** | Cross-branch executive login & role dashboard (`/admin/dashboard`), inspect all active regional cohorts & venue utilization (`Active Scheduled Classes`), monitor regional branch fee collections & outstanding balances (`Regional Center Performance`), inspect Ready/Issued campus certificates (`admin.campus.certificates.*`), review/approve pending instructor applications, view executive reports (`ReportsController`), and audit security logs (`AuditLogController`). |
| **R6** | **Instructor / Tutor** | Portal login (`/instructor/dashboard`), profile & password management, author courses & curriculum topics/lessons/quizzes/assignments, view assigned campus cohorts & Paid/Unpaid student rosters (`/instructor/campus/{assignment}/roster`), view class timetables, mark attendance or launch QR attendance sessions (`/instructor/campus/{assignment}/qr`), receive automated certificate-ready email alerts, reply to discussions/reviews, and manage earnings & payout withdrawals. |
| **R7** | **Accountant** | Branch-isolated financial governance inside `/admin/*`: manage center invoices & regenerate GePG `99104*` control numbers (`InvoiceController`), verify bank slips / offline payments (`payments.offline-pending`, `invoices.payments.verify`), configure & track `Full`/`Half`/`Quarter` payment schedules (`PaymentScheduleController`), trigger installment reminders, manage withdrawals & coupons, and export center financial reports. |
| **R8** | **Center Staff** | Branch-isolated front-desk operations inside `/admin/*`: register walk-in Face-to-Face/Hybrid applicants (`applicants.create`), mark attendance, assist with certificate clearances, resolve learner/instructor support tickets (`admin.tickets.*`), and view branch timetables without exposing executive or sensitive accounting payout totals. |
| **R9** | **Unified Storefront & Checkout** | Enforces delivery-mode rules: `online` courses allow direct gateway/cart checkout and hide offline/campus-only bypasses when inappropriate; `face_to_face` & `hybrid` courses route learners through the unified SCMS application form (`/courses/{course}/apply`) supporting staff 50% waivers, custom waivers, installment selection (`Full`, `Half`, `Quarter`), automatic user provisioning, invoice generation, and printable confirmation slips. |
| **R10** | **Dashboards, Filtering & Pagination** | Every Admin, Role, Student, and Instructor dashboard supports multi-criteria filtering (`search`, `date_range`, `status`, `per_page`, plus `branch_id` for multi-branch roles) and independent named paginators (`courses_page`, `tx_page`, `branches_page`, `venues_page`, `invoices_page`, etc.) preserving query state. |

---

## 5. Role-Based Access Control (RBAC) & Regional Branch Isolation

### 5.1 Enforcement Architecture
Authorization operates across **three defense-in-depth layers**:
1. **Portal Route Isolation (`CheckRole` Middleware — `app/Http/Middleware/CheckRole.php`)**:
   - Strictly isolates `/admin/*` (`role:admin`), `/instructor/*` (`role:instructor`), and `/student/*` (`role:student`), returning HTTP `403` on cross-portal access (with explicit session-guarded admin impersonation support via `ImpersonationController`).
2. **Route-Prefix Permission Matrix (`CheckRole` + Spatie Permissions)**:
   - For staff/scoped admin roles (`isStaff() === true`), `CheckRole` maps route prefixes (`admin.staff.*` -> `manage-staff`, `admin.campus.venues.*` -> `manage-venues`, `admin.campus.invoices.*` -> `manage-invoices`, `admin.reports.*` -> `view-reports`, etc.) and aborts with HTTP `403` when a permission is absent.
3. **Model & Query Branch Isolation (`app/Traits/BranchScoped.php` & `User::effectiveBranchId()`)**:
   - Models (`Applicant`, `Enrollment`, `Invoice`, `Payment`, `Venue`, `CourseAssignment`, `AttendanceRecord`, `SupportTicket`) apply `BranchScoped` global/query scoping (`forBranch($branchId)`).
   - Cross-branch users (`isMultiBranchAdmin()`: `super-admin`, `admin`, `system-administrator`, `director`, `deputy-director`, `marketing-officer`) can inspect all 17 regional centers or filter to a specific center (`?branch_id=`).
   - Branch-isolated roles (`branch-registrar`, `coordinator`, `accountant`, `staff`) are strictly pinned to `Auth::user()->branch_id`; URL tampering with `?branch_id=<other>` is ignored.

### 5.2 Granular Permission Groups (`Database\Seeders\RoleSeeder`)
Permissions are organized across 8 functional groups:
- **Courses & Curriculum**: `view-course`, `create-course`, `edit-course`, `delete-course`, `manage-courses`, `manage-bundles`
- **Students & Instructors**: `view-users`, `student:create`, `student:update`, `student:delete`, `instructor:create`, `instructor:update`, `instructor:delete`, `approve-instructor`, `reject-instructor`, `delete-users`
- **Finance & Billing**: `manage-payments`, `manage-withdrawals`, `manage-coupons`, `manage-invoices`, `manage-installments`
- **Campus & Short Courses**: `manage-branches`, `manage-venues`, `manage-countries`, `manage-applicants`, `manage-timetables`, `manage-attendance`
- **Reports & Analytics**: `view-reports`, `export-reports`, `view-analytics`
- **CMS, Certificates & Marketing**: `manage-certificates`, `manage-frontend`, `manage-notifications`
- **Support & Administration**: `manage-tickets`, `manage-staff`, `manage-roles`, `view-audit-logs`, `manage-settings`, `manage-backups`, `manage-all`
- **Learner Actions**: `enroll-course`, `unenroll-course`

### 5.3 Seeded Verification & Quick-Login Accounts (`config/demo_accounts.php`)
All demo accounts use domain `@lsmout.ac.tz` and default password `password` (with one-click RBAC demo cards on `/login`):

| Group | Role Label | Email (`@lsmout.ac.tz`) | Scope / Center | Key Allowed Modules | Key Restrictions |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Global | **Super Admin** | `superadmin@lsmout.ac.tz` | All 17 Branches | All LMS & SCMS modules, Roles, Backups, Updater | None (`manage-all`) |
| Global | **Admin** | `admin@lsmout.ac.tz` | All 17 Branches | Full academy, courses, finance, staff, CMS | None (`manage-all`) |
| Global | **System Admin** | `sysadmin@lsmout.ac.tz` | Global IT & Security | All modules, staff blocks/resets, backups, audit logs | None (`manage-all`) |
| Global | **Director** | `director@lsmout.ac.tz` | Executive • All Branches | Center analytics, reports, approvals, clearances, audit logs | Blocked (403): Roles, Staff CRUD, Backups, Settings, CMS, Coupons |
| Global | **Deputy Director** | `deputy.director@lsmout.ac.tz` | Executive • All Branches | Multi-branch analytics, reports, instructor approvals, audit logs | Blocked (403): Roles, Staff CRUD, Backups, Settings, CMS, Coupons |
| Global | **Marketing Officer** | `marketing@lsmout.ac.tz` | Cross-Branch • Growth | All-center applicants, courses, bundles, coupons, CMS, reports | Blocked (403): Venues, Timetables, Attendance, Branches, Roles, Staff, Backups |
| Branch | **Branch Manager** | `branch.manager@lsmout.ac.tz` | OUT Headquarters | HQ applicants, venues, timetables, attendance, invoices, clearances | Isolated to HQ; blocked (403): Branches, Courses, Staff, Roles, Backups, Settings |
| Branch | **Coordinator** | `coordinator@lsmout.ac.tz` | Singida Regional Center | Singida applicants, venues, timetables, attendance, clearances, courses | Isolated to Singida; blocked (403): Invoices, Installments, Payments, Staff, Settings |
| Branch | **Accountant** | `accountant@lsmout.ac.tz` | OUT Headquarters | HQ invoices, GePG control numbers, payments, installments, finance reports | Isolated to HQ; blocked (403): Applicants, Venues, Timetables, Courses, Staff |
| Branch | **Staff** | `staff@lsmout.ac.tz` | OUT Headquarters | HQ intake, attendance, clearances, support tickets, timetables | Isolated to HQ; blocked (403): Invoices, Payments, Venues, Exports, Staff |
| Portal | **Tutor / Instructor** | `instructor@lsmout.ac.tz` | `/instructor/*` Only | Own courses, assessments, assigned campus rosters, attendance, earnings | Blocked (403) on all `/admin/*` & `/student/*` |
| Portal | **Student** | `student@lsmout.ac.tz` | `/student/*` Only | Enrolled courses, campus timetable, GePG invoices, receipts, certificates | Blocked (403) on all `/admin/*` & `/instructor/*` |

---

## 6. Role-Specific Dashboards, Sidebars, Filters & Pagination

### 6.1 Role-Adaptive Dashboards (`RoleDashboardService`)
Instead of exposing a single global admin view to restricted staff, `Admin\DashboardController` delegates to `RoleDashboardService::buildForUser(User $user, array $filters)` which renders a tailored Blade dashboard under `resources/views/admin/dashboards/`:
- `admin`: Platform KPIs, 30-day/Yearly/12-month Revenue & Enrollment charts, Engagement gauges, Top Performing Courses, Recent Transactions, Recent Activity, Campus strip, Security Logs.
- `director` (for `director` & `deputy-director`): Regional Center Performance table, Ready & Issued Certificates, Active Scheduled Classes, Pending Instructor Approvals.
- `marketing`: 12-Month Intake & Enrollment Funnel chart, Top Centers by Intake, Top Demanded Short Courses (F2F vs Online), Recent Applicant Leads, Coupon campaign metrics.
- `branch_manager`: Branch Collection & Enrollment Trend chart, Branch Venues, Recent Center Invoices, Recent Branch Applicants.
- `coordinator`: Academic KPIs (zero financial totals), Center Timetable & Cohorts, Venue Capacity, Certificate Clearance Queue, Recent Attendance Check-Ins.
- `accountant`: Branch Receivable KPIs & 12-Month Collection chart, Pending Slip Verifications, Overdue Installments Queue, Recent GePG Invoices & Control Nos.
- `staff`: Front-desk KPIs (zero revenue totals), Recent Branch Applicants Desk, Open Support Tickets Queue, Active Center Classes.
- `custom`: Permission-composed fallback cards displaying only modules matching the custom role's granted permissions.

### 6.2 Shared Dashboard Filters & Card Pagination
To prevent long scrolling and keep data immediately actionable across **Admin**, **Student**, and **Instructor** dashboards:
- **Admin Dashboard Filters (`partials/filter-bar.blade.php`) & Card Footer (`partials/card-pagination.blade.php`)**: Supports `search`, `date_range` (`all|today|7d|30d|month`), role-specific `status`, `branch_id` (cross-branch roles only), and `per_page` (`5|10|15|25`, default `5`). Every card/table uses unique named paginators (`courses_page`, `tx_page`, `activity_page`, `branches_page`, `certs_page`, `classes_page`, `instructors_page`, `course_demand_page`, `branch_demand_page`, `leads_page`, `venues_page`, `invoices_page`, `applicants_page`, `clearances_page`, `attendance_page`, `verifications_page`, `overdue_page`, `tickets_page`) with `->withQueryString()`.
- **Student & Instructor Dashboard Filters (`App\Support\DashboardFilters`, `partials/portal-dashboard-filter.blade.php`, `partials/portal-card-pagination.blade.php`)**:
  - **Student Dashboard (`/student/dashboard`)**: Filters and paginates **Continue Learning** (`courses_page`) and **Invoices & Control Numbers** (`invoices_page`).
  - **Instructor Dashboard (`/instructor/dashboard`)**: Filters and paginates **My Active Courses** (`courses_page`) and **Recent Activity** (`activity_page`).

### 6.3 Clean Grouped Sidebars & Top-Right Account Navigation
- **Admin Sidebar (`partials/admin-sidebar.blade.php`)**: Permission-gated collapsible groups (`Courses & Curriculum`, `Users & Admissions`, `Campus Operations`, `Finance & Revenue`, `Reports & Analytics`) plus direct `Dashboard` link and clean `System Settings` entry. Account identity, **Edit Profile**, and **Logout** live exclusively in the top-right navbar dropdown ([admin-header.blade.php](file:///Applications/MAMP/htdocs/edux/resources/views/partials/admin-header.blade.php)).
- **Reusable System Settings Sub-Sidebar (`partials/system-settings-sidebar.blade.php` + `layouts/admin.blade.php`)**: Groups `Platform Configuration`, `Staff & Role Management`, `Account Deletions`, `Language Manager`, collapsible `Frontend & CMS`, `Push Notifications`, `Newsletter`, `Reminder Templates`, `Audit & Security Logs`, `System Maintenance`, `Backup Management`, `System Updater`, and `Online Help` cleanly inside a dedicated settings workspace card.
- **Admin Profile Auto-Resize (`Admin\ProfileController`)**: Supports instant client canvas preview and server-side GD / Intervention Image resizing with presets (`square_400`, `square_200`, `portrait_400x500`, `original`, or `custom` `cover`/`fit`/`exact` dimensions) and photo removal.
- **Student & Instructor Sidebars**: Collapsible Bootstrap 5 submenu groups in [student/partials/sidebar.blade.php](file:///Applications/MAMP/htdocs/edux/resources/views/student/partials/sidebar.blade.php) (`My Learning`, `Campus Life`, `Billing & Fees`, `Support & Tools`) and [instructor/partials/sidebar.blade.php](file:///Applications/MAMP/htdocs/edux/resources/views/instructor/partials/sidebar.blade.php) (`Courses & Catalog`, `Teaching & Classes`, `Communication`, `Reports & Payouts`), paired with [portal-header.blade.php](file:///Applications/MAMP/htdocs/edux/resources/views/partials/portal-header.blade.php) top-right Profile & Sign Out dropdown.

---

## 7. Edux Core LMS, Payment Gateways & AI Features

- **Course Authoring & AI Studio**: Supports modules/topics, video (uploaded/streamed/YouTube/Vimeo/iframe), PDF/document/text lessons, quizzes, question banks (CSV bulk import + randomized paper generation), and assignment originality scanning (`OriginalityCheckService`).
- **Synchronous Live Classrooms**: Built-in scheduling & launch controls (`LiveClassService`) surfaced on Student and Instructor dashboards.
- **Payment Gateways & Revenue Sharing**: Multi-gateway checkout (`Stripe`, `Paystack`, `PayPal`, `Razorpay`, `Flutterwave`, `Mollie`, `SSLCommerz`, `bKash`, `XPay`, `Offline` receipt approval) paired with automated platform/co-instructor revenue split ledger calculations (`RevenueShareService`).
- **Productivity & Collaboration**: Student **Focus Timer** (`FocusSession`), **Habit Tracker** (`Habit`, `HabitLog`), **Academic Community Q&A** (`CommunityQuestion`), per-course discussions, real-time student–instructor messaging (`ChatService`), and Gemini **AI Study Assistant** (`GeminiService`).
- **Drag-and-Drop Certificate Builder**: Prebuilt institutional templates (`CertificateTemplateSeeder`) with custom backgrounds, dynamic placeholders, serial codes, and QR verification tokens (`/verify-certificate/{code}`).

---

## 8. Non-Functional & Security Requirements

### 8.1 Security Controls (`EnforceSrsSecurity` & Authentication Stack)
1. **Confidentiality & Cryptography**: Sensitive credentials hashed via Laravel `Hash` (bcrypt/argon2); HTTPS enforcement supported for production; sensitive admin configuration masked cleanly across non-admin portals; Fortify 2FA (`two_factor_*` columns on `users`) and optional OTP login verification (`OtpService`).
2. **Authentication & Lockout Enforcement**:
   - **Forced First-Login Password Reset**: `force_password_reset` flag redirects to `/force-password-change` (`password.force_change`) before accessing any portal route.
   - **Strong Password Complexity**: Requires minimum length with mixed character complexity on user password changes/resets.
   - **Password Expiry**: Configurable maximum lifetime (`srs_password_expiry_days`, SRS default 90 days / 3 months) triggers mandatory password renewal.
   - **Brute-Force Lockout**: Configurable login failures threshold (4 failed attempts followed by 5-minute cooldown; repeated lockouts escalate per `lockout_count` / `is_blocked`).
3. **Session Lifecycle Management**:
   - **Idle / Inactivity Timeout**: `EnforceSrsSecurity` terminates inactive sessions after configurable timeout (`srs_idle_timeout_minutes`, default 5 minutes), clears `active_session_id`, invalidates the session, and logs `auth_session_idle_timeout`.
   - **Single Active Session**: Configurable `srs_single_session_enabled` invalidates concurrent logins from other devices (`auth_concurrent_session_terminated`).
   - **Account Suspension**: `is_blocked` immediately terminates sessions (`auth_blocked_session`) with audit logging.
4. **Log Audit & Traceability**: `spatie/laravel-activitylog` (`activity_log` table) logs security events, authentication violations, block/unblock/reset actions, and key entity changes for inspection via `AuditLogController`.
5. **Form Continuity & Integrity**: Client-side `form-draft-guard.blade.php` preserves partial form entries locally across accidental refreshes/interruptions alongside server-side transaction wrappers (`DB::transaction`) on financial and enrollment writes.

### 8.2 Software Quality Attributes
- **Scalability & Extensibility**: Service-oriented architecture (`app/Services/`), queue-ready notification channels, paginated SQL queries across dashboards and lists, and decoupled Sanctum REST API.
- **Device Awareness & Usability**: Responsive Stisla Admin UI, Bootstrap 5 Student/Instructor Portal UI with Safari `-webkit-backdrop-filter` compatibility, mobile drawer navigation, global search (`admin.campus.search`), alphabetical/status filtering, and built-in role-specific Online Help guides.
- **Performance**: Indexed lookups on `(branch_id, payment_status)`, `(branch_id, status)`, `(course_id, attendance_date)`, and default `5`-row paginated dashboard cards minimize payload size and render latency.

---

## 9. Database Schema & Domain Models

### 9.1 Core Campus & Unified Billing Tables (`database/migrations/2026_11_14_*`)
- `branches`: Regional centers (`id`, `name`, `branch_code`, `email`, `phone`, `address`, `country_id`, `is_active`, `legacy_scms_id`).
- `venues`: Center rooms/labs (`branch_id`, `name`, `capacity`, `equipment`, `notes`, `is_active`).
- `countries`: Lookup table with ISO codes and `phonecode` for nationality & applicant intake.
- `applicants`: Walk-in & self-applied learners (`user_id`, `branch_id`, `country_id`, `first_name`, `middle_name`, `last_name`, `full_name`, `email`, `phone`, `gender`, `education_level`, `nida_number`, `is_staff_student`, `staff_id_number`, `has_discount`, `discount_reason`, `discount_percentage`, `source`).
- `course_assignments`: Timetable cohorts (`course_id`, `instructor_id`, `branch_id`, `venue_id`, `session`, `start_date`, `end_date`, `schedule_days`, `start_time`, `end_time`, `max_capacity`, `status`).
- `invoices`: Unified billing & GePG control numbers (`invoice_number`, `control_number`, `control_number_generated_at`, `applicant_id`, `user_id`, `enrollment_id`, `course_id`, `branch_id`, `total_amount`, `discount_amount`, `net_amount`, `paid_amount`, `balance`, `payment_status`, `installment_plan`, `due_date`, `issued_at`, `paid_at`).
- `payment_schedules`: Installment splits (`invoice_id`, `enrollment_id`, `installment_number`, `installment_label`, `amount_due`, `amount_paid`, `due_date`, `status`, `paid_at`, `reminder_sent_at`, `reminder_count`).
- `attendance_records`: Cohort attendance & QR check-ins (`enrollment_id`, `course_id`, `course_assignment_id`, `user_id`, `branch_id`, `attendance_date`, `session_token`, `status`, `check_in_method`, `checked_in_at`, `marked_by`).
- `notification_templates`: Editable institutional alert templates (`key`, `name`, `channel`, `subject`, `body`, `variables`, `is_active`).

### 9.2 Core Edux LMS & Security Columns
- `users`: Extended with `branch_id`, `address`, `registration_number`, `national_id`, `force_password_reset`, `password_changed_at`, `is_blocked`, `blocked_at`, `blocked_reason`, `failed_login_attempts`, `lockout_count`, `locked_until`, `active_session_id`, `last_activity_at`, `two_factor_*`, `balance`.
- `courses`: Extended with `delivery_mode` (`online`, `face_to_face`, `hybrid`), `default_branch_id`, `default_venue_id`, `duration_label`, `has_discount`, `discount_amount`, `attendance_threshold` (default `75`), `allow_online_f2f_payment`, `f2f_commission_enabled`.
- `enrollments`: Supports multiple historical cohort enrollments per learner+course via `branch_id`, `venue_id`, `applicant_id`, `course_assignment_id`, `delivery_mode`, `session`, `attendance_percentage`, `certificate_status` (`pending`, `ready`, `issued`), `certificate_ready_at`.
- `payments`: Supports `invoice_id`, `applicant_id`, `bundle_id`, `coupon_id`, `verification_status`, `receipt_number`, `payment_date`, `tax_amount`, `platform_commission`, `instructor_earning`, plus gateway metadata.
- Additional LMS tables: `course_topics`, `lessons`, `lesson_progress`, `quizzes`, `quiz_questions`, `quiz_attempts`, `quiz_attempt_answers`, `assignments`, `assignment_files`, `assignment_submissions`, `assignment_submission_files`, `question_banks`, `question_bank_items`, `bundles`, `bundle_courses`, `bundle_enrollments`, `coupons`, `coupon_usages`, `reviews`, `review_replies`, `certificate_templates`, `live_classes`, `support_tickets`, `support_ticket_messages`, `support_ticket_attachments`, `conversations`, `chat_messages`, `chat_attachments`, `course_discussions`, `community_questions`, `community_answers`, `community_votes`, `ai_chat_messages`, `focus_sessions`, `habits`, `habit_logs`, `activity_log`, `roles`, `permissions`, `platform_settings`, `frontend_settings`, `custom_pages`, `menus`, `menu_items`, `banners`, `notifications`.

---

## 10. REST API Specification

Token-authenticated routes via **Laravel Sanctum** (`auth:sanctum`, rate-limited `throttle:api`) in `routes/api.php`:
- **Public Discovery & Verification**: `/api/settings`, `/api/home`, `/api/courses`, `/api/courses/{id}`, `/api/bundles`, `/api/instructors`, `/api/community/questions`, `POST /api/verify-certificate`, `GET /api/verify-certificate/{code}`.
- **Public Campus & Application Intake (`/api/campus/*`)**:
  - `GET /api/campus/meta`, `GET /api/campus/branches`, `GET /api/campus/branches/{branch}/venues`
  - `GET /api/campus/courses/{course}/fee`, `POST /api/campus/courses/{course}/apply`
  - `GET /api/campus/invoices/verify/{number}`
- **Authenticated Student & Learner Endpoints**:
  - Profile, Photo, Password, Notifications, Deletion Requests (`/api/user/*`)
  - Dashboard (`GET /api/dashboard`), Course/Bundle Checkout & 8 Gateway Verifiers (`/api/payments/*/verify`)
  - Learning Player, Quizzes, Assignments, Live Classes (`/api/user/courses/*`, `/api/live-classes/*`)
  - Campus Invoices, Control Numbers, Slip Uploads, Unenrollment, Attendance & QR Self Check-In (`/api/campus/invoices`, `POST /api/campus/checkin`)
  - Community Q&A, Direct Chat (`/api/messages/*`), AI Chat (`/api/ai-chat/*`), Course Discussions, Support Tickets (`/api/tickets/*`)
- **Authenticated Instructor Endpoints (`/api/instructor/*`)**:
  - Auth, OTP, Settings, Dashboard, Analytics, Courses, Bundles, Assignments (grading), Quizzes, Students, Reviews, Earnings & Withdrawals, Coupons, Discussions, Live Classes, Tickets
  - Campus Cohorts, Rosters, Attendance & Timed QR Sessions (`/api/instructor/campus/*`)

---

## 11. Operations, Backups & Automated Test Verification

- **Backups & Maintenance**: Admins with `manage-backups` can create, download, and delete system backups via `/admin/backups`; system health, cache clearing, storage symlink creation, and log viewing live under `/admin/settings/system/*`.
- **Automated Test Suite (`tests/Feature/`)**:
  - `tests/Feature/ScmsFullParityAndRbacTest.php` — verifies RBAC matrix & 403 isolation across all roles, idle timeout/lockout/forced reset security, reports/schedules/receipts/search/help portals, `@lsmout.ac.tz` quick-login isolation, role-specific dashboards, admin profile photo auto-resize, dashboard filters & named pagination (admin, director, branch manager, student, instructor), and grouped sidebars.
  - `tests/Feature/CampusUnifiedWorkflowTest.php` — verifies online vs. F2F checkout rules, quarter installment splits, revenue share & invoice sync, timetable conflict detection, attendance & certificate clearance, SRS lockout enforcement, QR verification, and Campus REST API parity.

```bash
# Run full unified SCMS + LMS parity & RBAC test suite
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=8889 DB_DATABASE=edux \
DB_USERNAME=root DB_PASSWORD=root DB_SOCKET=/Applications/MAMP/tmp/mysql/mysql.sock \
php artisan test tests/Feature/ScmsFullParityAndRbacTest.php tests/Feature/CampusUnifiedWorkflowTest.php
```
