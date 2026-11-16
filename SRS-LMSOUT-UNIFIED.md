# THE OPEN UNIVERSITY OF TANZANIA (OUT)

## INSTITUTE OF EDUCATIONAL AND MANAGEMENT TECHNOLOGIES (IEMT)

### MASTER SOFTWARE REQUIREMENTS SPECIFICATION (SRS)

## UNIFIED OUT LEARNING MANAGEMENT AND SHORT COURSES INFORMATION SYSTEM (`LMSOUT`)

| Document Attribute | Specification |
| :--- | :--- |
| **Document ID** | `OUT-IEMT-SRS-LMSOUT-2026-001` |
| **System Name** | Unified OUT Learning Management & Short Courses Management Information System (`LMSOUT`) |
| **Baseline Sources** | 1. `SCMIS SRS v1.0` (OUT IEMT, 1st February 2023 — Beatus Winifrid Mbunda, Evodius Albart, Mahfoudh Katakweba)<br>2. `Enhancement of the Moodle LMS for Short Courses and MOOCs` (OUT IEMT, 26th August 2026)<br>3. Production Implementation Audit (`LMSOUT v3.0.0`, Laravel 13) |
| **Version** | `3.0.0-Unified` (Production Baseline + Phase 2 Sovereign Media & Hybrid Bridge Roadmap) |
| **Prepared For** | The Open University of Tanzania (OUT) — IEMT Directorate & Systems Engineering |
| **Runtime Baseline** | PHP `8.3 / 8.4 / 8.5`, Laravel Framework `^13.0`, MySQL `8.0+` / MariaDB `10.6+`, Vite `^7.0`, Tailwind CSS `^4.0` |
| **Document Date** | 16th November, 2026 |

---

### Document Approval Sign-Off

| Approval Role | Name | Designation / Unit | Signature | Date |
| :--- | :--- | :--- | :--- | :--- |
| **Prepared By** | | Software Architecture & Systems Engineering | | |
| **Reviewed By** | | Head of Department, IEMT — Open University of Tanzania | | |
| **Reviewed By** | | Coordinator, Professional Short Courses & MOOCs | | |
| **Approved By** | | Director, Institute of Educational and Management Technologies (IEMT) | | |

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Architectural Rationale: Single Laravel 13 Platform vs. Separate SCMIS and Moodle](#2-architectural-rationale-single-laravel-13-platform-vs-separate-scmis-and-moodle)
3. [Sovereign Video Storage and Hybrid Moodle Strategy](#3-sovereign-video-storage-and-hybrid-moodle-strategy)
4. [Overall System Description](#4-overall-system-description)
5. [External Interface Requirements](#5-external-interface-requirements)
6. [Functional Requirements (`SCMIS` + `Edux` + `MOOC/Sovereign` Unified)](#6-functional-requirements)
7. [Role-Based Access Control (RBAC) and Regional Branch Isolation](#7-role-based-access-control-rbac-and-regional-branch-isolation)
8. [Non-Functional, Security, and Operational Requirements](#8-non-functional-security-and-operational-requirements)
9. [Database Schema and Domain Model Inventory](#9-database-schema-and-domain-model-inventory)
10. [REST API, Deployment, and CI/CD Architecture](#10-rest-api-deployment-and-cicd-architecture)
11. [Requirements Traceability Matrix](#11-requirements-traceability-matrix)

---

## 1. Introduction

### 1.1 Purpose
This Software Requirements Specification (SRS) defines the complete functional, architectural, security, integration, and operational requirements for **LMSOUT** — the unified **Learning Management System (LMS)** and **Short Courses Management Information System (SCMIS)** of **The Open University of Tanzania (OUT)**, managed under the **Institute of Educational and Management Technologies (IEMT)**.

This specification merges three institutional inputs into a single authoritative baseline:
1. All operational, billing, regional center, timetable, reporting, and security mandates from **`SCMIS SRS v1.0`** (1st February 2023).
2. All 21 digital learning, Massive Open Online Course (MOOC), interactivity, corporate packaging, and analytics objectives from the **`Moodle Enhancement Framework for Professional Short Courses`** (26th August 2026).
3. The production architecture, sovereign video storage design (eliminating dependency on YouTube or commercial cloud video vendors), optional Moodle interoperability bridge, and automated CI/CD deployment pipeline implemented in **`LMSOUT v3.0.0`**.

Requirement items throughout this document are explicitly classified by delivery state:
- **`[IMPLEMENTED v3.0.0]`**: Implemented in the Laravel 13 codebase and verified against automated tests and MySQL schemas.
- **`[PHASE 2 ROADMAP]`**: Formally specified target extensions for institutional rollout (sovereign adaptive HLS transcoding, BigBlueButton, H5P/SCORM runtime, micro-badges, corporate bulk packages, and optional Moodle OER/SSO bridge).

### 1.2 Intended Audience
- **Institutional Leadership (Director, Deputy Director, Branch Registrars/Managers)**: Governance across 17 regional centers, revenue assurance, certificate clearance, and KPI tracking.
- **Systems Engineering and Security Teams**: Authoritative reference for controllers, service classes, RBAC middleware, database migrations, API contracts, and security hardening.
- **Academic & Training Staff (Training Coordinators, Instructors, Content Developers, Accountants, Center Staff, Marketing Officers)**: Definition of operational workflows across online, hybrid, and face-to-face programs.

### 1.3 Scope
**LMSOUT** replaces fragmented operation across a standalone registration/finance database (SCMIS) and a separate course delivery portal (Moodle) with one unified platform supporting:
- **Face-to-Face (`face_to_face`) & Hybrid (`hybrid`) Regional Center Operations**: 17 OUT regional and coordination centers, physical venue/lab capacity checks, slot-conflict timetable scheduling, staff/merit fee waivers, GePG `99104*` control numbers, `Full`/`Half`/`Quarter` installment plans, automated overdue notices, QR/manual attendance marking, and attendance-gated certificate clearance.
- **Online Short Courses & MOOCs (`online`)**: Public catalog, instant paid gateway checkout (Pesapal v3, Stripe, PayPal, Flutterwave, Offline slip verification), self-paced modular curriculum, assessments, originality checks, live virtual classrooms, community forums, direct student-instructor messaging, and public QR certificate verification.
- **Institutional Content Sovereignty**: Self-hosted course video upload and streaming inside OUT-controlled infrastructure, removing operational dependence on YouTube, Vimeo, or external SaaS LMS vendors.

### 1.4 Definitions and Acronyms

| Term / Acronym | Definition |
| :--- | :--- |
| **OUT** | The Open University of Tanzania |
| **IEMT** | Institute of Educational and Management Technologies |
| **SCMIS** | Short Courses Management Information System |
| **LMSOUT** | Unified OUT Learning Management System and SCMIS codebase (`Laravel 13`) |
| **MOOC** | Massive Open Online Course (open or low-fee self-paced online course) |
| **GePG** | Government e-Payment Gateway (represented via `99104*` control numbers in billing workflows) |
| **RBAC** | Role-Based Access Control (enforced via `spatie/laravel-permission` and `CheckRole`) |
| **HLS** | HTTP Live Streaming (adaptive multi-bitrate video streaming protocol) |
| **OER / H5P / SCORM** | Open Educational Resources / HTML5 Interactive Package / Sharable Content Object Reference Model |
| **LTI 1.3** | Learning Tools Interoperability standard v1.3 |

---

## 2. Architectural Rationale: Single Laravel 13 Platform vs. Separate SCMIS and Moodle

IEMT historically operated two disconnected software layers: **SCMIS** for regional applicant intake, GePG invoicing, and reporting, alongside **Moodle** as an LMS for course delivery. The August 2026 Moodle Enhancement Framework identified 21 modernization needs—including modern catalog UI (`Edwiser RemUI`, ~\$700; `Edwiser Catalogue`, ~\$300), Mobile Money and GePG payment plugins, gamification (`Level Up! XP`, ~\$250–\$500), executive analytics (`LearnerScript`, ~\$500), corporate packages, MFA, and Web Services integration with external OUT portals—and noted the potential need to maintain two separate Moodle servers for internal versus corporate learners.

Consolidating both workloads into **LMSOUT (Laravel 13)** resolves the structural friction of a two-system stack:

| Architectural Dimension | Separate SCMIS + Moodle Stack | Unified `LMSOUT` (Laravel 13) |
| :--- | :--- | :--- |
| **Identity & Enrolment Synchronization** | Requires fragile Web Services polling/webhooks between SCMIS accounts and Moodle user tables after payment. | Single `users`, `applicants`, `enrollments`, and `invoices` schema; payment completion activates course access in one `DB::transaction`. |
| **Delivery Mode Parity** | Separate portals for Face-to-Face regional applicants vs. online distance learners. | Unified `courses.delivery_mode` (`online`, `face_to_face`, `hybrid`) enforces checkout or SCMS intake rules dynamically from one catalog. |
| **Regional Center & Financial Governance** | Moodle lacks native branch-isolated Finance, Accountant, Coordinator, and Venue conflict models across 17 regional centers. | Built-in `BranchScoped` trait isolates records across all 17 regional branches; `Accountant`, `Coordinator`, `Branch Manager`, and `Center Staff` roles share one database safely. |
| **Payment & Tanzanian Currency Support** | Requires custom Moodle enrolment plugins for Pesapal, GePG control numbers, `TZS`/`USD` switching, and split installments. | Native `PesapalService` (v3 iframe + IPN), Stripe, PayPal, Flutterwave, Offline slip approval, `99104*` control numbers, and `Full`/`Half`/`Quarter` installment schedules. |
| **Total Cost of Ownership** | Recurring commercial Moodle plugin licenses (~USD 1,750+ upfront plus annual renewals), PHP upgrade breakages across third-party plugins, and dual server administration. | Zero third-party theme/plugin licensing fees; single Laravel 13 codebase, automated PHPUnit regression suite, and automated CI/CD deployment. |
| **Mobile & Third-Party Extensibility** | Constrained to Moodle Web Services token model and Moodle Mobile App fork maintenance. | Clean versioned JSON REST API protected by Laravel Sanctum (`/api/*`), enabling native web/PWA/mobile clients cleanly. |

---

## 3. Sovereign Video Storage and Hybrid Moodle Strategy

### 3.1 Sovereign Video Content Storage (Replacing YouTube)
OUT policy requires institutional control over instructional video lectures without relying on YouTube (which introduces third-party advertisements, algorithmic distractions, external takedown risk, bandwidth throttling, and privacy leakage) or proprietary cloud video SaaS lock-in.

**Critical Architectural Principle:** Standard Moodle is **not** a dedicated video streaming engine. Storing high-definition lecture recordings directly inside Moodle's hashed `moodledata/filedir` forces raw file delivery through PHP workers without HTTP byte-range optimization or adaptive bitrate switching, degrading playback for learners on variable Tanzanian regional mobile networks.

Accordingly, LMSOUT adopts a sovereign media architecture built directly into OUT's infrastructure:

1. **Direct Self-Hosted Upload Pipeline `[IMPLEMENTED v3.0.0]`**:
   - `StoreCourseRequest` and `StudentCourseController::prepareLessonVideoData()` natively support `video_type = 'upload'` (`mp4`, `avi`, `mov`, `webm` up to `500 MB`) alongside external URLs (`youtube`/`vimeo` kept solely as optional legacy compatibility) and live class recordings (`LiveClass::$recording_video_path`).
   - Uploaded lesson media resides on server-controlled storage (`storage/app/public/` or private object disks) and plays natively in HTML5 video players with zero external ads.

2. **Signed Private Streaming, Range Requests, and Adaptive HLS `[PHASE 2 ROADMAP]`**:
   - **Private Disk Isolation**: Store protected lecture masters on an OUT-controlled **MinIO** (self-hosted S3-compatible object store) or local private filesystem disk rather than unauthenticated public URLs.
   - **Time-Limited Signed Playback**: Serve lesson video playlists and segments through expiring Laravel signed routes (`/stream/lessons/{lesson}?expires=...&signature=...`) gated by active enrollment and payment clearance verification, paired with Nginx `X-Accel-Redirect` / `HTTP 206 Partial Content` byte-range support.
   - **Adaptive Multi-Bitrate HLS (FFmpeg Queue)**: Transcode uploaded lectures asynchronously via Laravel queues + `ffmpeg` into `360p`, `480p`, and `720p` `.m3u8`/`.ts` HLS streams so learners in low-bandwidth regional centers (`Kigoma`, `Katavi`, `Rukwa`, `Singida`, `Lindi`, etc.) can stream reliably on 3G/4G connections.
   - **Optional Institutional PeerTube Media Node**: Should OUT establish a shared university-wide video portal across degree and short courses, LMSOUT embeds private HLS manifests from OUT-hosted PeerTube without external vendor dependencies.

### 3.2 Where Moodle Remains Valuable (Optional Hybrid Interoperability)
Rather than operating Moodle as a duplicate admissions, catalog, or payment portal, OUT may deploy or retain Moodle strictly as an **optional backend specialist node** integrated via API/LTI where institutional legacy assets already exist:

| Integration Pattern | Institutional Value for OUT | Architectural Role with `LMSOUT` |
| :--- | :--- | :--- |
| **1. Degree Moodle & ODL Portal SSO Bridge** `[PHASE 2 ROADMAP]` | Avoids duplicate credentials for existing OUT degree students accessing short courses or MOOCs. | `LMSOUT` acts as the unified public storefront, billing, and certificate portal; an OAuth2/JWT `MoodleBridgeService` synchronizes user identities or passes enrolment tokens to OUT's degree Moodle when required. |
| **2. Legacy Question Bank & OER Import** `[PHASE 2 ROADMAP]` | Preserves years of instructor-authored quizzes and OER packages stored in older OUT Moodle courses. | Admin/Instructor curriculum tools import Moodle XML / GIFT question formats directly into LMSOUT `question_banks` and `quiz_questions` (extending existing CSV bulk import). |
| **3. Interactive H5P & SCORM Execution** `[PHASE 2 ROADMAP]` | Supports branching scenarios, interactive video checkpoints, and legacy SCORM 1.2/2004 modules (Moodle Enhancement Areas #4 and #6). | Prefer native JavaScript `h5p-standalone` playback inside LMSOUT; optionally launch complex legacy Moodle SCORM/H5P activities via LTI 1.3 and pull completion grades back to LMSOUT for certificate clearance. |
| **4. Self-Hosted BigBlueButton Live Classes** `[PHASE 2 ROADMAP]` | Replaces Zoom/Google Meet external dependencies for synchronous classes (Moodle Enhancement Area #10). | `LiveClassService` connects directly to OUT-hosted BigBlueButton REST APIs, storing session recordings directly in OUT's sovereign media vault without requiring Moodle as middleware. |

---

## 4. Overall System Description

### 4.1 System Perspective and Operating Context
LMSOUT consolidates IEMT's digital and regional campus operations into a modular Laravel 13 web application backed by MySQL 8+ and Sanctum REST APIs. The architecture supports:
- Single-source-of-truth administrative control across 17 OUT regional centers.
- Strict separation between public storefront discovery, portal-isolated learner/instructor workspaces, and permission-gated administrative modules.
- Automated background execution for installment reminders (`InstallmentReminderService`), live class reminders (`SendLiveClassReminders`), and backup archiving (`SystemBackupService`).

### 4.2 Target Learning Models

1. **Professional Short Courses (`face_to_face`, `hybrid`, and `online` paid tracks)**:
   - Structured cohort durations (e.g., 4–8 weeks), regional center and venue capacity allocation, assigned facilitator, `attendance_threshold` enforcement (default `75%`), tuition fee with optional 50% OUT staff waiver or custom discount, `Full`/`Half`/`Quarter` installment schedules with GePG `99104*` control numbers, continuous practical/quiz assessment, and QR-verified official certification.
2. **Massive Open Online Courses — MOOCs (`online` free/open or low-cost certificate tracks)** `[IMPLEMENTED BASELINE + PHASE 2 EXPANSION]`:
   - Self-paced modular delivery (`topics` -> `lessons`), free open enrollment or optional paid certificate upgrade (`pricing_model` = `free` / `paid`), peer community Q&A (`community_questions`), course discussions, AI Study Assistant (`GeminiService`), and automated completion certificates. Phase 2 extends MOOCs with stackable micro-badges, XP gamification, and low-bandwidth offline mobile packs.
3. **Corporate & Institutional Cohorts** `[PHASE 2 ROADMAP]`:
   - Organization seat packs, bulk voucher/CSV learner onboarding, consolidated corporate GePG/bank invoicing, and organization-level completion reporting.

### 4.3 System Modules Overview

| Module | Primary Responsibilities | Codebase Anchors (`LMSOUT v3.0.0`) |
| :--- | :--- | :--- |
| **Catalog & Content Authoring** | Online, F2F, and Hybrid courses; categories, languages, bundles, visual curriculum, YouTube/self-hosted video, Gemini AI studio | `Course`, `Bundle`, `CourseCategory`, `CourseLanguage`, `Lesson`, `YouTubePlaylistService`, `GeminiService` |
| **Regional Campus & Timetabling** | 17 regional centers, physical rooms/labs, capacity checks, timetable slot conflict detection, global country registry | `Branch`, `Venue`, `Country`, `CourseAssignment`, `BranchController`, `VenueController`, `CourseAssignmentController` |
| **Admissions & Student Directory** | Public F2F/Hybrid application intake, automatic learner provisioning, real-time admin student search/filter/export | `Applicant`, `CampusApplicationService`, `StudentController`, `StudentsExport` |
| **Billing, GePG & Gateways** | Invoices, `99104*` control numbers, `Full`/`Half`/`Quarter` installment schedules, Pesapal v3 iframe/IPN, Stripe/PayPal/Flutterwave, offline slip verification, revenue split | `Invoice`, `PaymentSchedule`, `Payment`, `PesapalService`, `PaymentGatewayService`, `SrsWorkflowService`, `RevenueShareService` |
| **Attendance & Certification** | Manual/QR timed check-in, attendance percentage computation, threshold clearance queue, tutor notifications, DomPDF/FPDF drag-and-drop certificates, QR verification | `AttendanceRecord`, `CampusAttendanceService`, `CertificateTemplate`, `QrCodeService`, `CertificateVerificationService` |
| **Assessments & Academic Delivery** | Timed quizzes, randomized question banks + CSV import, merit lists, assignments with originality similarity checking, synchronous live classes | `Quiz`, `QuestionBank`, `Assignment`, `OriginalityCheckService`, `LiveClassService` |
| **Communication, AI & Support** | Real-time notifications (`PortalNotificationService`), support tickets, direct chat (`ChatService`), community Q&A, course discussions, Gemini AI chat, newsletter | `SupportTicket`, `Conversation`, `ChatMessage`, `CommunityQuestion`, `AiChatMessage`, `NewsletterSubscriber` |
| **Security, Governance & Ops** | 12-role RBAC, branch isolation, idle/session/password enforcement, activity audit logs, backups, deployment inspector, CMS, themes, i18n (`en`, `sw`) | `CheckRole`, `EnforceSrsSecurity`, `BranchScoped`, `RoleDashboardService`, `SystemBackupService`, `LanguageManagerService` |

### 4.4 Key System Users (12-Role Unified Model)

Expanding the 6 baseline `SCMIS SRS v1.0` roles (`Student`, `Training Coordinator`, `Systems Administrator`, `Instructor`, `Marketing Officer`, `Director`) to enforce separation of duties across finance, regional operations, and executive governance:

| ID | Role Title | Spatie Role Key | Operational Scope & Isolation Boundary |
| :--- | :--- | :--- | :--- |
| 1 | **Super Administrator** | `super-admin` | Unrestricted global access (`manage-all`), roles, backups, system settings, deployment tools across all 17 branches |
| 2 | **Administrator** | `admin` | Full cross-branch academy governance, courses, finance, staff, CMS, and operations |
| 3 | **System Administrator** | `system-administrator` | Cross-branch IT & security administration, user blocking/unblocking, password resets, backups, security audit logs |
| 4 | **Director** | `director` | Cross-branch executive visibility, analytics, active class oversight, certificate audit, instructor approvals, activity logs |
| 5 | **Deputy Director** | `deputy-director` | Multi-branch academic oversight, analytics, reports, timetables, clearances, instructor approvals |
| 6 | **Marketing Officer** | `marketing-officer` | Cross-branch growth, courses, bundles, coupons, CMS/banners, notifications, intake funnels, applicant reports |
| 7 | **Branch Manager / Registrar** | `branch-registrar` | Strictly isolated to assigned `branch_id`: center intake, venues, timetables, attendance, invoices, clearances, center reports |
| 8 | **Training Coordinator** | `coordinator` | Strictly isolated to assigned `branch_id`: courses, venues, timetables, attendance, clearances (blocked from finance CRUD) |
| 9 | **Accountant** | `accountant` | Strictly isolated to assigned `branch_id`: invoices, GePG control numbers, payment verification, installments, coupons, withdrawals |
| 10 | **Center Staff** | `staff` | Strictly isolated to assigned `branch_id`: front-desk walk-in applications, attendance, clearances, support tickets |
| 11 | **Instructor / Tutor** | `instructor` | Portal-isolated to `/instructor/*`: own courses, curricula, assessments, assigned campus cohorts/rosters, attendance, earnings |
| 12 | **Student** | `student` | Portal-isolated to `/student/*`: own enrolled courses, lessons, quizzes, live classes, timetable, GePG invoices, receipts, certificates |

### 4.5 Operating Environment
- **Server Runtime**: Linux / OpenLiteSpeed (CyberPanel), Apache 2.4, or Nginx executing **PHP `^8.3` / `8.4` / `8.5`** with required extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`, `zip`.
- **Database**: MySQL `8.0+` or MariaDB `10.6+` (`utf8mb4_unicode_ci`).
- **Asset Delivery**: Pre-compiled frontend assets (`public/assets/`, `public/themes/`, `public/packages/`) and public storage media (`storage/app/public/` linked via `public/storage`) served directly without requiring Node.js on shared production hosts (`Vite 7` / `Tailwind CSS 4` supported for source builds).
- **Client Browsers & Devices**: Responsive HTML5/CSS3 compatible with desktop, tablet, and mobile browsers (Chrome, Firefox, Edge, Safari 9+ with `-webkit-backdrop-filter` support, touch-screen friendly).

---

## 5. External Interface Requirements

### 5.1 User Interfaces
- **Public Storefront**: Responsive course & bundle catalog, live search, real-time dual-currency switcher (`TZS` / `USD`), multi-language switch (`en` / `sw`), unified F2F/Hybrid application form, public invoice verifier (`/campus/invoice-verify/{number}`), and certificate QR verifier (`/verify-certificate`).
- **Role-Adaptive Admin Dashboard (`/admin/*`)**: Stisla-based modular workspace dynamically tailored by `RoleDashboardService` into role-specific dashboards (`admin`, `director`, `marketing`, `branch_manager`, `coordinator`, `accountant`, `staff`, `custom`) with shared filters (`search`, `date_range`, `status`, `branch_id`, `per_page`), named multi-card pagination, and grouped collapsible navigation.
- **Student & Instructor Portals (`/student/*`, `/instructor/*`)**: Bootstrap 5 workspaces with grouped sidebars, interactive learning player, quiz/assignment workflows, campus timetable/invoice cards, and real-time bell notification polling.

### 5.2 Hardware and Network Interfaces
- Server hardware scaled for institutional traffic (baseline 16 GB RAM, SSD/NVMe storage expandable for course videos, uploaded payment proofs, and ZIP backups).
- Client camera/QR URI support for smartphone classroom check-ins and certificate verification.

### 5.3 Software and External Service Interfaces

| Interface | Integration Component | Purpose & Status |
| :--- | :--- | :--- |
| **Pesapal v3 API** | `PesapalService` | Sandbox/Live OAuth token, IPN registration, native iframe checkout, server-side transaction verification (`Payment` auto-promotion) `[IMPLEMENTED v3.0.0]` |
| **Online Card / Mobile Gateways** | `PaymentGatewayService` | Stripe (`stripe/stripe-php`), PayPal, Flutterwave, plus configurable Offline bank transfer instructions `[IMPLEMENTED v3.0.0]` |
| **GePG Control Number Workflow** | `SrsWorkflowService` | Unique `99104*` control number allocation, invoice PDF printing with dual brand crests, receipt upload & finance verification `[IMPLEMENTED v3.0.0]` |
| **Google Gemini AI** | `GeminiService` | Student AI Study Assistant and Admin/Instructor AI Course Studio `[IMPLEMENTED v3.0.0]` |
| **Document & QR Engines** | `dompdf`, `laravel-fpdf`, `maatwebsite/excel`, `QrCodeService` | Branded printable invoices, receipts, QR certificates, Excel/CSV/PDF student & center reports `[IMPLEMENTED v3.0.0]` |
| **Mail, OTP & Security** | `SmtpConfigurator`, `OtpService`, `RecaptchaService` | Dynamic SMTP configuration, login OTP verification, Google reCAPTCHA, `spatie/laravel-activitylog` `[IMPLEMENTED v3.0.0]` |
| **Legacy SCMIS Importer** | `ImportScmsCommand` (`scms:import`) | CLI data migration from legacy `scms` database schema (`branches`, `venues`, `courses`, `applicants`, `invoices`, `enrollments`, `payments`) `[IMPLEMENTED v3.0.0]` |
| **Sovereign Video / HLS / MinIO** | Proposed `MediaVaultService` | Signed streaming, FFmpeg HLS transcoding, optional OUT MinIO or PeerTube origin `[PHASE 2 ROADMAP]` |
| **BigBlueButton & Moodle LTI/API** | Proposed `BigBlueButtonDriver`, `MoodleBridgeService` | Self-hosted virtual classrooms, Moodle XML/H5P/SCORM import, optional OUT degree Moodle SSO `[PHASE 2 ROADMAP]` |

---

## 6. Functional Requirements

### 6.1 Student Portal (`FR-ST`)

| Req ID | Requirement Description | Implementation Status |
| :--- | :--- | :--- |
| `FR-ST-01` | Authenticate via unified `/login` with optional OTP/2FA, password reset, and automatic portal redirection. | `[IMPLEMENTED v3.0.0]` |
| `FR-ST-02` | View and update learner profile, photo, and password (`/student/settings`, `/password/force-change`). | `[IMPLEMENTED v3.0.0]` |
| `FR-ST-03` | Enroll in online courses/bundles via gateway checkout or submit F2F/Hybrid regional applications (`/courses/{course}/apply`). | `[IMPLEMENTED v3.0.0]` |
| `FR-ST-04` | Automatically generate course invoice, unique GePG `99104*` control number, and selected `Full`/`Half`/`Quarter` payment schedule upon F2F/Hybrid application (and auto-sync paid online direct checkouts to an invoice record). | `[IMPLEMENTED v3.0.0]` |
| `FR-ST-05` | Pay online via Pesapal v3 native iframe, Stripe, PayPal, or Flutterwave, or upload offline bank slip proof (`POST /student/campus/invoices/{invoice}/receipt`) with real-time status promotion (`unpaid` -> `partial` -> `paid` / `pending` -> `completed`). | `[IMPLEMENTED v3.0.0]` |
| `FR-ST-06` | Unenroll from eligible enrollments (`DELETE /student/campus/enrollments/{enrollment}`). | `[IMPLEMENTED v3.0.0]` |
| `FR-ST-07` | View enrolled courses, start/end dates, regional venue, timetable schedules (`/student/campus/timetable`), invoices (`/student/campus/invoices`), and official printable payment receipts. | `[IMPLEMENTED v3.0.0]` |
| `FR-ST-08` | Receive automated email and in-portal bell notifications for login events, invoice issuance, upcoming/overdue installments (`InstallmentReminderService`), payment verification, and certificate readiness (`PortalNotificationService`). | `[IMPLEMENTED v3.0.0]` |
| `FR-ST-09` | Access interactive course player (`/student/courses/{id}/learn`), stream uploaded or embedded videos, complete lessons, progress sequentially or unlock paid modules, take quizzes/retakes, submit assignments, and earn Focus/Habit streaks. | `[IMPLEMENTED v3.0.0]` |
| `FR-ST-10` | Check in to physical campus classes via instructor QR token (`/student/campus/checkin/{token}`) and download QR-verified PDF certificates once attendance and fee thresholds are cleared. | `[IMPLEMENTED v3.0.0]` |
| `FR-ST-11` | Earn competency micro-badges and gamification XP/leaderboards, and download compressed offline learning packets via mobile API. | `[PHASE 2 ROADMAP]` |

### 6.2 Training Coordinator & Branch Manager (`FR-TC`)

| Req ID | Requirement Description | Implementation Status |
| :--- | :--- | :--- |
| `FR-TC-01` | Register and manage regional instructors, venues/computer labs (`venues` with seating capacities), and F2F/Hybrid applicants within branch scope. | `[IMPLEMENTED v3.0.0]` |
| `FR-TC-02` | Register or configure courses with explicit `delivery_mode` (`online`, `face_to_face`, `hybrid`), `price_currency`, `attendance_threshold`, and fee rules. | `[IMPLEMENTED v3.0.0]` |
| `FR-TC-03` | Schedule campus cohorts (`course_assignments`) linking course, instructor, branch, venue, session (`Morning`/`Afternoon`/`Evening`/`Weekend`), days, and start/end times with automated venue and instructor slot-conflict detection. | `[IMPLEMENTED v3.0.0]` |
| `FR-TC-04` | Mark manual cohort attendance or open time-limited QR attendance sessions, automatically updating `enrollments.attendance_percentage` and evaluating certificate readiness. | `[IMPLEMENTED v3.0.0]` |
| `FR-TC-05` | Automatically notify instructors via configurable `NotificationTemplate` emails when learner certificates transition to `ready`. | `[IMPLEMENTED v3.0.0]` |
| `FR-TC-06` | Generate weekly, monthly, quarterly, and custom date-range regional center reports segmented by center name, course name, enrolled learner count, and payment status (`Full`, `Half`, `Quarter`, `Paid`, `Partial`, `Unpaid`) in HTML, PDF, XLSX, and CSV formats. | `[IMPLEMENTED v3.0.0]` |

### 6.3 Systems Administrator & Super Admin (`FR-SA`)

| Req ID | Requirement Description | Implementation Status |
| :--- | :--- | :--- |
| `FR-SA-01` | Create, edit, and manage staff/system user accounts across all 12 roles and regional branches. | `[IMPLEMENTED v3.0.0]` |
| `FR-SA-02` | Define custom roles and assign/revoke granular permissions (`RoleSeeder::permissionGroups()` — 42 permissions across 8 functional groups). | `[IMPLEMENTED v3.0.0]` |
| `FR-SA-03` | Enforce forced password reset (`force_password_reset`), block/unblock user accounts with reason tracking, trigger password resets, and upload/crop/remove admin profile photos (`Admin\ProfileController`). | `[IMPLEMENTED v3.0.0]` |
| `FR-SA-04` | Search and filter all students and users across cohorts with real-time search, filters, configurable pagination (`>= 6` rows per page), and XLSX/CSV/PDF exports (`StudentController`, `StudentsExport`). | `[IMPLEMENTED v3.0.0]` |
| `FR-SA-05` | Manage platform settings, payment gateway credentials, SMTP, frontend CMS/themes, translations (`en`/`sw`), backups (`SystemBackupService`), deployment health, and security audit logs (`AuditLogController`). | `[IMPLEMENTED v3.0.0]` |

### 6.4 Marketing Officer, Director, Accountant, Center Staff, and Instructor (`FR-RL`)

| Req ID | Actor | Functional Requirements & Implemented Capabilities | Implementation Status |
| :--- | :--- | :--- | :--- |
| `FR-RL-01` | **Marketing Officer** | Register/manage short courses, categories, languages, course bundles, discount coupons, CMS banners, announcements, broadcast notifications, and intake funnel/demand analytics without destructive access to staff/roles/backups/venues. | `[IMPLEMENTED v3.0.0]` |
| `FR-RL-02` | **Director / Deputy Director** | Cross-branch executive dashboard displaying active scheduled classes across 17 centers, regional revenue & fee collection rankings, ready/issued certificates queue, pending instructor approvals, and security audit logs. | `[IMPLEMENTED v3.0.0]` |
| `FR-RL-03` | **Accountant** | Branch-isolated finance desk for GePG invoice issuance & control number regeneration, offline slip verification/rejection (`pending` -> `completed` auto-promotion), installment payment schedule reconciliation, overdue alerts, and revenue reports. | `[IMPLEMENTED v3.0.0]` |
| `FR-RL-04` | **Center Staff** | Branch-isolated front desk for walk-in applicant intake (`/admin/campus/applicants/create`), attendance check-in assistance, certificate clearance lookup, and support ticket handling without finance payout access. | `[IMPLEMENTED v3.0.0]` |
| `FR-RL-05` | **Instructor / Tutor** | Author online/blended courses, quizzes, and assignments; view assigned campus cohort rosters segmented cleanly by `Paid`/`Unpaid` tuition status; mark class attendance or launch QR sessions; receive certificate-ready alerts; request payout withdrawals (including Pesapal payout). | `[IMPLEMENTED v3.0.0]` |

### 6.5 Sovereign Content, H5P/SCORM, Corporate & Moodle Bridge (`FR-EXT`)

| Req ID | Capability Domain | Specified Behavior | Target Status |
| :--- | :--- | :--- | :--- |
| `FR-EXT-01` | **Sovereign Media Vault & HLS Streaming** | Store lecture videos on OUT-controlled disk/MinIO, transcode to adaptive `360p`/`480p`/`720p` HLS via queued `ffmpeg` jobs, and stream exclusively through expiring signed enrollment-checked URLs (`HTTP 206` Range / `X-Accel-Redirect`). | `[PHASE 2 ROADMAP]` |
| `FR-EXT-02` | **Self-Hosted Virtual Classrooms (BigBlueButton)** | Extend `LiveClassService` with a BigBlueButton provider driver hosted on OUT infrastructure, automatically archiving recordings into the sovereign media vault. | `[PHASE 2 ROADMAP]` |
| `FR-EXT-03` | **H5P, SCORM & Moodle XML Importer** | Render H5P interactive units (`h5p-standalone`) and SCORM packages with grade/completion callbacks into `lesson_progress`, plus import Moodle `.xml`/`.mbz` question banks cleanly. | `[PHASE 2 ROADMAP]` |
| `FR-EXT-04` | **Corporate & Institutional Packages** | Enable organization accounts (`organizations`, `corporate_cohorts`), bulk seat invoicing, voucher redemption codes, and HR sponsor completion reports. | `[PHASE 2 ROADMAP]` |
| `FR-EXT-05` | **Optional Moodle OER / Degree SSO Bridge** | Provide `MoodleBridgeService` supporting OAuth2/LTI 1.3 SSO between OUT's degree Moodle (`ODL Portal`) and `LMSOUT`, plus selective OER content sync without exposing Moodle as the public short-course portal. | `[PHASE 2 ROADMAP]` |
| `FR-EXT-06` | **Digital Badges & Accessibility QA** | Issue Open Badges v2/v3 micro-credentials linked to course modules/competencies and enforce automated WCAG 2.1 AA alt-text/contrast validation during course publishing. | `[PHASE 2 ROADMAP]` |

---

## 7. Role-Based Access Control (RBAC) and Regional Branch Isolation

Authorization is enforced identically across web UI and API layers through three defense-in-depth mechanisms:

1. **Portal Middleware Isolation (`App\Http\Middleware\CheckRole`)**:
   - Requests to `/admin/*`, `/instructor/*`, and `/student/*` are strictly confined to matching roles, returning `HTTP 403` on unauthorized cross-portal traversal (with audited session flags for admin impersonation via `ImpersonationController`).
2. **Route-Prefix Permission Gate (`CheckRole` + `spatie/laravel-permission`)**:
   - Staff and scoped admin roles (`isStaff() === true`) are checked against 42 granular permissions grouped across 8 domains:
     - *Courses & Curriculum*: `view-course`, `create-course`, `edit-course`, `delete-course`, `manage-courses`, `manage-bundles`
     - *Students & Instructors*: `view-users`, `student:create`, `student:update`, `student:delete`, `instructor:create`, `instructor:update`, `instructor:delete`, `approve-instructor`, `reject-instructor`, `delete-users`
     - *Finance & Billing*: `manage-payments`, `manage-withdrawals`, `manage-coupons`, `manage-invoices`, `manage-installments`
     - *Campus & Short Courses*: `manage-branches`, `manage-venues`, `manage-countries`, `manage-applicants`, `manage-timetables`, `manage-attendance`
     - *Reports & Analytics*: `view-reports`, `export-reports`, `view-analytics`
     - *CMS, Certificates & Marketing*: `manage-certificates`, `manage-frontend`, `manage-notifications`
     - *Support & Administration*: `manage-tickets`, `manage-staff`, `manage-roles`, `view-audit-logs`, `manage-settings`, `manage-backups`, `manage-all`
     - *Learner Actions*: `enroll-course`, `unenroll-course`
3. **Model-Level Branch Scoping (`App\Traits\BranchScoped` & `User::effectiveBranchId()`)**:
   - `Applicant`, `Enrollment`, `Invoice`, `Payment`, `Venue`, `CourseAssignment`, `AttendanceRecord`, and `SupportTicket` apply `BranchScoped`.
   - Multi-branch roles (`super-admin`, `admin`, `system-administrator`, `director`, `deputy-director`, `marketing-officer`) can inspect all 17 regional centers or filter by `?branch_id=`.
   - Branch-isolated roles (`branch-registrar`, `coordinator`, `accountant`, `staff`) are locked to `Auth::user()->branch_id`; request tampering with another branch ID is ignored server-side.

### Seeded Verification Accounts (`config/demo_accounts.php`)

| Role | Seeded Email (`@lsmout.ac.tz`) | Default Branch Scope |
| :--- | :--- | :--- |
| Super Admin | `superadmin@lsmout.ac.tz` | All 17 Branches |
| Admin | `admin@lsmout.ac.tz` | All 17 Branches |
| System Admin | `sysadmin@lsmout.ac.tz` | All 17 Branches |
| Director | `director@lsmout.ac.tz` | All 17 Branches |
| Deputy Director | `deputy.director@lsmout.ac.tz` | All 17 Branches |
| Marketing Officer | `marketing@lsmout.ac.tz` | All 17 Branches |
| Branch Manager | `branch.manager@lsmout.ac.tz` | OUT Headquarters |
| Coordinator | `coordinator@lsmout.ac.tz` | Singida Regional Center |
| Accountant | `accountant@lsmout.ac.tz` | OUT Headquarters |
| Center Staff | `staff@lsmout.ac.tz` | OUT Headquarters |
| Instructor | `instructor@lsmout.ac.tz` | OUT Headquarters (`/instructor/*`) |
| Student | `student@lsmout.ac.tz` | OUT Headquarters (`/student/*`) |

---

## 8. Non-Functional, Security, and Operational Requirements

### 8.1 Security Controls (`SCMIS SRS v1.0` Parity Verified in `EnforceSrsSecurity`)

| NFR ID | Requirement (`SCMIS SRS` Clause) | Enforced Behavior in `LMSOUT` |
| :--- | :--- | :--- |
| `NFR-SEC-01` | Encryption & Password Hygiene (#1, #2, #7) | Passwords hashed via bcrypt (`BCRYPT_ROUNDS=12`); strong complexity enforced on password creation, resets, and user profile updates (`PasswordValidationRules`); sensitive gateway/SMTP credentials masked from non-privileged views. |
| `NFR-SEC-02` | Transport Security (#3) | Production `.env` template enforces HTTPS `APP_URL`, `SESSION_SECURE_COOKIE=true`, and proxy-aware header trust (`bootstrap/app.php`: `$middleware->trustProxies(at: '*')`). Root `.htaccess` blocks direct web access to dotfiles (`.env`, `.git`). |
| `NFR-SEC-03` | Forced First-Login Change & Password Expiry (#6, #9) | `users.force_password_reset` forces redirect to `/password/force-change` before portal access; configurable `srs_password_expiry_days` (SRS policy: 90 days / 3 months) enforces periodic renewal. |
| `NFR-SEC-04` | Brute-Force Lockout (#11, #12) | Configurable login failure tracking (`failed_login_attempts`, `lockout_count`, `locked_until`): 4 consecutive invalid attempts trigger a 5-minute cooldown; repeated lockouts escalate to account suspension (`is_blocked`). |
| `NFR-SEC-05` | Inactivity Idle Timeout & Concurrent Session Control (#13, #18–#20) | `EnforceSrsSecurity` enforces configurable idle logout (`srs_idle_timeout_minutes`, default 5 minutes), invalidates expired sessions, logs security event `auth_session_idle_timeout`, and supports single active session invalidation (`active_session_id` -> `auth_concurrent_session_terminated`). |
| `NFR-SEC-06` | Audit Logging & Traceability (#14, #25) | `spatie/laravel-activitylog` (`activity_log` table) records logins, lockouts, idle timeouts, concurrent session terminations, account blocks/resets, invoice/payment changes, and enrollment mutations. |
| `NFR-SEC-07` | Access Mode & Interrupted Form Continuity (#21–#26) | Web HTTPS access across roles; administrative SSH restricted to systems administrators; financial/enrollment mutations wrapped in `DB::transaction`; `form-draft-guard.blade.php` retains interrupted form inputs client-side (targeting 15+ minute browser continuity). |

### 8.2 Quality Attributes

| NFR ID | Attribute | Specification |
| :--- | :--- | :--- |
| `NFR-QLT-01` | **Scalability & Mobile Readiness** | Service-layer decoupling (`app/Services/`), database-indexed queries (`branch_id`, `payment_status`, `status`), paginated dashboards, and Sanctum JSON API ready for Flutter/React Native or PWA mobile apps. |
| `NFR-QLT-02` | **Usability & Accessibility** | Clean grouped sidebars, alphabetical/status filters across directories (`/admin/students`, `/courses`), inline help documentation (`/admin/help`, `/instructor/help`, `/student/help`), English/Swahili localization (`en`, `sw`), and keyboard-accessible navigation. |
| `NFR-QLT-03` | **Response Time** | Indexed dashboard queries and 5-to-15-row named paginators keep standard portal page loads and regional reports well within the `<= 8 seconds` SCMIS SRS threshold. |
| `NFR-QLT-04` | **Media Bandwidth Efficiency** | Progressive optimized course cover imagery (`960x540` JPEG ~117–141 KB with institutional OUT watermark) and Phase 2 `360p` adaptive HLS video profiles support low-bandwidth regional connectivity. |

---

## 9. Database Schema and Domain Model Inventory

The schema comprises **95 database migrations** and **68 Eloquent models**:

### 9.1 Institutional Campus & Billing Tables
- `branches`: 17 OUT regional and coordination centers (`name`, `branch_code`, `email`, `phone`, `address`, `country_id`, `is_active`, `legacy_scms_id`).
- `venues`: Regional classrooms and ICT labs (`branch_id`, `name`, `capacity`, `equipment`, `notes`, `is_active`, `legacy_scms_id`).
- `countries`: 239 ISO country lookup records (`iso`, `code`, `name`, `nicename`, `iso3`, `phonecode`).
- `applicants`: Walk-in and online F2F/Hybrid applicants (`user_id`, `branch_id`, `country_id`, `first_name`, `middle_name`, `last_name`, `full_name`, `email`, `phone`, `gender`, `education_level`, `nida_number`, `is_staff_student`, `staff_id_number`, `has_discount`, `discount_reason`, `discount_percentage`, `source`).
- `course_assignments`: Timetable cohorts (`course_id`, `instructor_id`, `branch_id`, `venue_id`, `session`, `start_date`, `end_date`, `schedule_days`, `start_time`, `end_time`, `max_capacity`, `status`).
- `invoices`: Unified billing & GePG control numbers (`invoice_number`, `control_number`, `control_number_generated_at`, `applicant_id`, `user_id`, `enrollment_id`, `course_id`, `branch_id`, `total_amount`, `discount_amount`, `net_amount`, `paid_amount`, `balance`, `payment_status`, `installment_plan`, `due_date`, `issued_at`, `paid_at`).
- `payment_schedules`: Installment ledger (`invoice_id`, `enrollment_id`, `installment_number`, `installment_label`, `amount_due`, `amount_paid`, `due_date`, `status`, `paid_at`, `reminder_sent_at`, `reminder_count`).
- `attendance_records`: Attendance & QR check-in audit (`enrollment_id`, `course_id`, `course_assignment_id`, `user_id`, `branch_id`, `attendance_date`, `session_token`, `status`, `check_in_method`, `checked_in_at`, `marked_by`).
- `notification_templates`: Editable transactional email/alert templates (`key`, `name`, `channel`, `subject`, `body`, `variables`, `is_active`).

### 9.2 Core LMS, Commerce, Security & Seeded Media Extensions
- `users`: Security and campus fields (`branch_id`, `registration_number`, `national_id`, `force_password_reset`, `password_changed_at`, `is_blocked`, `blocked_at`, `blocked_reason`, `failed_login_attempts`, `lockout_count`, `locked_until`, `active_session_id`, `last_activity_at`, `two_factor_*`, `balance`).
- `courses`: Unified `delivery_mode` (`online`, `face_to_face`, `hybrid`), `price_currency` (`TZS`/`USD`), `featured_image` (seeded dynamically via `storage/app/public/courses/images/out-*.jpg` and `2026_11_16_000001_update_course_thumbnails_to_tanzanian_covers.php`), `default_branch_id`, `default_venue_id`, `duration_label`, `attendance_threshold`, `allow_online_f2f_payment`, `f2f_commission_enabled`, `legacy_scms_id`.
- `enrollments` & `payments`: Extended with `branch_id`, `venue_id`, `applicant_id`, `course_assignment_id`, `attendance_percentage`, `certificate_status`, `certificate_ready_at`, `invoice_id`, `verification_status`, `receipt_number`, `tax_amount`, `platform_commission`, `instructor_earning`, and Pesapal tracking fields.
- Learning, collaboration & CMS tables: `course_topics`, `lessons`, `lesson_progress`, `quizzes`, `quiz_questions`, `quiz_attempts`, `quiz_attempt_answers`, `assignments`, `assignment_files`, `assignment_submissions`, `assignment_submission_files`, `question_banks`, `question_bank_items`, `bundles`, `bundle_courses`, `bundle_enrollments`, `coupons`, `coupon_usages`, `reviews`, `review_replies`, `certificate_templates`, `live_classes`, `support_tickets`, `support_ticket_messages`, `support_ticket_attachments`, `conversations`, `chat_messages`, `chat_attachments`, `course_discussions`, `community_questions`, `community_answers`, `community_votes`, `ai_chat_messages`, `focus_sessions`, `habits`, `habit_logs`, `activity_log`, `roles`, `permissions`, `payment_gateway_settings`, `platform_settings`, `frontend_settings`, `custom_pages`, `menus`, `menu_items`, `banners`, `notifications`.

---

## 10. REST API, Deployment, and CI/CD Architecture

### 10.1 Sanctum REST API (`routes/api.php`)
- **Public Catalog & Verification**: `/api/settings`, `/api/home`, `/api/courses`, `/api/courses/{id}`, `/api/bundles`, `/api/instructors`, `POST /api/verify-certificate`, `GET /api/verify-certificate/{code}`, `/api/campus/meta`, `/api/campus/branches`, `/api/campus/branches/{branch}/venues`, `POST /api/campus/courses/{course}/apply`, `/api/campus/invoices/verify/{number}`.
- **Authenticated Learner & Instructor Endpoints (`auth:sanctum`)**: Profile/notifications, payment gateway verification (`/api/payments/*/verify` including Pesapal IPN & verify), lesson progression player, quizzes, assignments, live classes, campus invoices/receipt upload/unenroll/QR self-check-in (`/api/campus/*`), direct messaging, AI study chat, and instructor cohort/attendance/roster endpoints (`/api/instructor/campus/*`).

### 10.2 Automated CI/CD & Production Server Operations
- **Continuous Integration (`.github/workflows/ci.yml`)**: Runs on every push/PR to `main` using PHP `8.3`, Node `20`, and MySQL `8.0` (`lmsout_ci`): checks `php -l`, builds assets, executes fresh `migrate --force` (with `CACHE_STORE=array`), `package:discover`, `db:seed --force`, verifies `config:cache`, `route:cache`, and `view:cache`, and runs all 5 PHPUnit feature suites.
- **Continuous Deployment (`.github/workflows/deploy.yml`)**: Triggers after CI success on `main` (or manual dispatch) using encrypted GitHub Secrets (`SSH_HOST`, `SSH_USER`, `SSH_PORT`, `SSH_PRIVATE_KEY`, `DEPLOY_PATH`, and optional `.env` / Pesapal secrets) to run `deploy/deploy.sh` cleanly with zero secrets committed to Git.
- **Server Provisioning Scripts (`deploy/`)**:
  - `deploy/first-time-server-setup.sh`: Auto-detects PHP 8.3+ (`lsphp83`/`php`), prompts or ingests DB credentials into `.env` (`chmod 600`), verifies PDO MySQL connectivity (including `127.0.0.1` to `localhost` socket fallback), installs Composer packages, runs migrations and all institutional seeders, links `public/storage`, sets permissions, and registers the cron scheduler (`* * * * * php artisan schedule:run`).
  - `deploy/deploy.sh`: Idempotent deployment script preserving CyberPanel OpenLiteSpeed `nobody` vHost directory ownership, reloading `lswsctrl`, warming Laravel caches, and running post-deploy HTTP verification.
  - `deploy/push-db-and-assets-to-server.sh`: Private SSH/SCP transfer tool for syncing local database snapshots and uploaded storage assets without exposing personal data to public version control.
- **Scheduled Background Tasks (`routes/console.php`)**:
  - `live-classes:send-reminders` every 5 minutes.
  - `InstallmentReminderService::processDueReminders(true)` daily at `08:00`.

### 10.3 Automated Verification Baseline (`tests/Feature/`)
Verified against MySQL (`37 passed, 665 assertions`):
1. `tests/Feature/CampusUnifiedWorkflowTest.php` — F2F/online checkout segregation, quarter installments, timetable conflicts, QR attendance & clearance, SRS security lockout/forced reset, dual-logo invoice print, real-time student portal invoice/payment/timetable sync, bell notifications, and direct checkout invoice creation.
2. `tests/Feature/ScmsFullParityAndRbacTest.php` — 12-role RBAC matrix & 403 cross-portal/cross-branch enforcement, idle/lockout/strong-password rules, role dashboards, admin profile photo auto-resize, real-time `/admin/students` search/filter/pagination/export.
3. `tests/Feature/CurrencyExchangeSwitchTest.php` — Dual `TZS`/`USD` formatting, admin exchange rate updates, and base-currency checkout accuracy.
4. `tests/Feature/PesapalIntegrationTest.php` — Pesapal v3 gateway config, native iframe checkout, IPN/Sanctum callbacks, payout withdrawals, and auto-promotion from pending to completed payment status.
5. `tests/Feature/StudentCourseAccessTest.php` — Video player rendering, lesson completion & sequential/paid module unlocking, quiz retakes, assignments, and PDF certificates.

---

## 11. Requirements Traceability Matrix

### 11.1 `SCMIS SRS v1.0` Functional & Security Traceability

| Source `SCMIS SRS` Section | Requirement Scope | `LMSOUT v3.0.0` Implementation Anchor | Status |
| :--- | :--- | :--- | :--- |
| `R1` | Student auth, enrollment, unenroll, invoice, control number, payment, slip upload, overdue alert, timetable, receipt | `CampusController`, `CourseApplyController`, `SrsWorkflowService`, `InstallmentReminderService` | Implemented |
| `R2` | Training Coordinator branch/venue/course/teacher assignment, delivery modes, tutor cert email, periodic regional reports | `BranchController`, `VenueController`, `CourseAssignmentController`, `CertificateClearanceController`, `ReportsController` | Implemented |
| `R3` | Systems Admin user creation, role/permission grants, block/unblock, user search & filter | `StaffController`, `UserSecurityController`, `StudentController`, `GlobalSearchController`, `RoleSeeder` | Implemented |
| `R4` | Marketing Officer course registration, learner/payment views, alerts | `RoleDashboardService::buildMarketingDashboard`, `Courses`, `Bundles`, `Coupons`, `ReportsController` | Implemented |
| `R5` | Director active classes, ready certificates oversight, profile & password | `RoleDashboardService::buildDirectorDashboard`, `CertificateClearanceController` | Implemented |
| `R6` | Instructor enrolled students per center, paid filter, schedule, cert-ready email | `Instructor\CampusController`, `CertificateClearanceController` | Implemented |
| `Sec 5.1` (#1–#26) | Encryption, first-login reset, complexity, expiry, 4-fail lockout, 5-min idle timeout, concurrent session termination, audit log, 15-min form draft guard | `EnforceSrsSecurity`, `LoginController`, `ForcePasswordController`, `spatie/laravel-activitylog`, `form-draft-guard.blade.php` | Implemented |

### 11.2 IEMT Moodle Enhancement Framework (`#1–#21`) Traceability

| Framework # | Moodle Enhancement Area | How Addressed in `LMSOUT` Unified Architecture | Status |
| :--- | :--- | :--- | :--- |
| `#1` | Modern UI & Learner Experience | Custom responsive storefront + Stisla Admin + Bootstrap 5 Student/Instructor dashboards | Implemented (`LMSOUT`) |
| `#2` | Course Catalogue & Marketplace | Public `/courses`, `/bundles`, live search, filters, `TZS`/`USD` switcher | Implemented (`LMSOUT`) |
| `#3` | Modular Learning & Pathways | `CourseTopic` / `Lesson` hierarchy, drip scheduling, completion gating | Implemented (`LMSOUT`) |
| `#4`, `#6` | Microlearning & Interactive Content (H5P) | Modular video/text/quiz units; H5P & SCORM interactive runtime | Partial / Phase 2 (`FR-EXT-03`) |
| `#5` | Modular Payment & Flexible Enrolment | Pesapal v3, Stripe, PayPal, Flutterwave, GePG `99104*`, `Full`/`Half`/`Quarter` plans | Implemented (`LMSOUT`) |
| `#7` | Gamification (XP & Leaderboards) | Student Focus Timer & Habit Tracker implemented; XP & leaderboards in Phase 2 | Partial / Phase 2 (`FR-ST-11`) |
| `#8` | Digital Certificates with QR Verification | Drag-and-drop builder (`DomPDF`/`FPDF`) + `/verify-certificate/{code}` | Implemented (`LMSOUT`) |
| `#9` | Digital Badges & Micro-Credentials | Stackable competency Open Badges extension | Phase 2 (`FR-EXT-06`) |
| `#10` | Virtual Classroom | `LiveClassService` (Zoom/Meet/self-hosted recording); BigBlueButton driver in Phase 2 | Partial / Phase 2 (`FR-EXT-02`) |
| `#11` | Learning Analytics & Dashboards | `RoleDashboardService`, `AnalyticsController`, `ReportsController` | Implemented (`LMSOUT`) |
| `#12` | Marketing & Learner Engagement | Coupons, bundles, banners, `NewsletterController`, broadcast notifications | Implemented (`LMSOUT`) |
| `#13` | Corporate Packages & Bulk Enrolment | Organization accounts, seat-pack invoices & bulk learner vouchers | Phase 2 (`FR-EXT-04`) |
| `#14` | Quality Assurance & Accessibility | Semantic responsive UI; automated WCAG checklist gate in Phase 2 | Partial / Phase 2 (`FR-EXT-06`) |
| `#15` | Automated Notifications & Reminders | `InstallmentReminderService`, `SendLiveClassReminders`, `PortalNotificationService` | Implemented (`LMSOUT`) |
| `#16` | Mobile & Offline Learning | Tokenized Sanctum API (`/api/*`) implemented; offline media sync packs in Phase 2 | Partial / Phase 2 (`FR-ST-11`) |
| `#17` | System Integration & Automation | `routes/api.php`, `scms:import`, optional Moodle/ODL SSO bridge in Phase 2 | Partial / Phase 2 (`FR-EXT-05`) |
| `#18` | Content Repository, OER & Sovereign Video | Self-hosted `storage/app/public/` uploads & OUT-watermarked covers; Signed HLS/MinIO/PeerTube vault + OER bank in Phase 2 | Partial / Phase 2 (`FR-EXT-01`) |
| `#19` | Security, Privacy & Data Protection | `EnforceSrsSecurity`, Fortify 2FA/OTP, reCAPTCHA, backups, audit logs | Implemented (`LMSOUT`) |
| `#20` | Assessment & Evaluation | Quizzes, randomized question banks + CSV import, merit lists, assignment originality checks | Implemented (`LMSOUT`) |
| `#21` | Learner Support & Helpdesk | Support tickets, Community Q&A, course discussions, AI Study Assistant, role Online Help | Implemented (`LMSOUT`) |
