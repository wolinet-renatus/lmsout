# THE OPEN UNIVERSITY OF TANZANIA (OUT)

## THE INSTITUTE OF EDUCATIONAL AND MANAGEMENT TECHNOLOGIES (IEMT)

### SOFTWARE REQUIREMENTS SPECIFICATION (SRS)

## UNIFIED OUT LEARNING MANAGEMENT AND SHORT COURSES MANAGEMENT INFORMATION SYSTEM (`LMSOUT`)

| Document Control | Details |
| :--- | :--- |
| **Project Title** | Unified Learning Management and Short Courses Management Information System (`LMSOUT`) |
| **Document ID** | `OUT-IEMT-SRS-LMSOUT-001` |
| **Organization** | The Open University of Tanzania (OUT) — Institute of Educational and Management Technologies (IEMT) |
| **Reference Standards** | IEEE Std 830 / ISO/IEC/IEEE 29148; OUT `SCMIS SRS v1.0` |
| **Target Version** | `3.0` |
| **Target Stack** | PHP `8.3+` (`Laravel 13`), MySQL `8.0+` / MariaDB `10.6+`, Vite `7` / Tailwind CSS `4` / Bootstrap 5, Laravel Sanctum REST API, OUT Sovereign Media Storage (Local / MinIO + FFmpeg HLS), BigBlueButton, Optional Moodle OER/LTI Interoperability |

| Approval | Name | Job Title / Role | Signature | Date |
| :--- | :--- | :--- | :--- | :--- |
| **Prepared by:** | | Systems Engineering Unit, IEMT | | |
| **Reviewed by:** | | Head of Department, Short Courses & E-Learning, IEMT | | |
| **Approved by:** | | Director, Institute of Educational and Management Technologies (IEMT) | | |

---

## Table of Contents

- **1. Introduction**
  - 1.1 Purpose
  - 1.2 Intended Audience and Reading Suggestions
  - 1.3 Product Scope
  - 1.4 Definitions, Acronyms, and Abbreviations
  - 1.5 References
- **2. Overall Description of the System**
  - 2.1 Product Perspective
  - 2.2 Product Functions
  - 2.3 Key System Users
  - 2.4 Operating Environment
  - 2.5 Design and Implementation Constraints
  - 2.6 User Documentation
- **3. External Interface Requirements**
  - 3.1 User Interfaces
  - 3.2 Hardware Interfaces
  - 3.3 Software Interfaces
  - 3.4 Communications Interfaces
- **4. Functional Requirements**
  - `R1` Student (`ST`)
  - `R2` Training Coordinator / Branch Manager (`TC` / `BM`)
  - `R3` Systems Administrator / Super Administrator (`SA`)
  - `R4` Marketing Officer (`MO`)
  - `R5` Director and Deputy Director (`DR`)
  - `R6` Instructor / Tutor (`IN`)
  - `R7` Accountant (`AC`)
  - `R8` Center Staff (`CS`)
  - `R9` Public Storefront, Course Catalogue, Checkout, and GePG Billing
  - `R10` E-Learning Delivery, MOOCs, Sovereign Video Streaming, Interactive Labs, and Assessments
  - `R11` Certification, Micro-Badges, Verification, and Corporate Cohorts
  - `R12` Notifications, Communication, Analytics, Helpdesk, and Mobile API
- **5. Non-Functional Requirements**
  - 5.1 Security Requirements
  - 5.2 Software Quality Attributes
- **6. System Data Model Overview**

---

## 1. Introduction

### 1.1 Purpose
The purpose of this Software Requirements Specification (SRS) is to define the complete technical, functional, security, integration, and operational requirements for the **Unified OUT Learning Management and Short Courses Management Information System (`LMSOUT`)**. The specification establishes the authoritative blueprint for engineering, quality assurance, cybersecurity validation, and institutional acceptance within The Open University of Tanzania (OUT) under the Institute of Educational and Management Technologies (IEMT).

### 1.2 Intended Audience and Reading Suggestions
This document is intended for:
- **Software Engineers and System Architects** responsible for implementing and maintaining the Laravel 13 web platform, MySQL database schema, sovereign media services, payment integrations, and REST APIs.
- **Quality Assurance and Security Auditors** verifying functional compliance, Role-Based Access Control (RBAC), branch isolation, session security, and data integrity.
- **OUT Management and Operational Stakeholders** (Directors, Branch Managers, Training Coordinators, Accountants, Marketing Officers, Instructors, and Center Staff) reviewing business workflow coverage prior to system sign-off.

Readers should review Sections 1 through 3 for system context, technical stack, and external interfaces; Section 4 for itemized actor and module requirements (`R1`–`R12`); Section 5 for mandatory security and quality constraints; and Section 6 for core domain data structures.

### 1.3 Product Scope
The system provides one unified web and API platform enabling OUT and IEMT to administer, market, deliver, bill, track, and certify both **online self-paced learning (MOOCs and online short courses)** and **regional physical/blended training (Face-to-Face and Hybrid short courses)** across all OUT regional and coordination centers.

Specifically, the system encompasses:
1. Public course marketplace, search, filtering, currency switching (`TZS` / `USD`), language switching (`en` / `sw`), course bundling, promotions, and corporate training enrollment.
2. Unified admission, invoicing, `99104*` GePG control number issuance, `Full`/`Half`/`Quarter` installment management, automated overdue alerts, and integrated online/mobile/card payment gateways alongside offline bank receipt verification.
3. Regional campus administration across all OUT centers: physical venue/lab capacity enforcement, cohort timetable conflict detection, QR and manual attendance verification, and attendance-gated certificate clearance.
4. Comprehensive digital learning delivery featuring OUT-owned sovereign video storage and adaptive HLS streaming (eliminating reliance on YouTube or external commercial video platforms), interactive H5P/SCORM microlearning, self-hosted BigBlueButton virtual classrooms, timed quizzes, question banks, assignment originality checks, gamification, digital badges, QR-verified PDF certificates, AI study assistance, and optional Moodle OER/SSO/question-bank interoperability.
5. Enterprise RBAC across 12 institutional roles, multi-center vs. branch-isolated governance, strict authentication/session security controls, comprehensive audit logging, and Sanctum REST API endpoints for mobile clients.

### 1.4 Definitions, Acronyms, and Abbreviations

| Term / Acronym | Meaning |
| :--- | :--- |
| **OUT** | The Open University of Tanzania |
| **IEMT** | Institute of Educational and Management Technologies |
| **LMSOUT** | Unified OUT Learning Management System and Short Courses Management Information System |
| **SCMIS** | Short Courses Management Information System |
| **MOOC** | Massive Open Online Course |
| **GePG** | Government e-Payment Gateway (represented via institutional control numbers) |
| **RBAC** | Role-Based Access Control |
| **HLS** | HTTP Live Streaming (adaptive multi-bitrate video streaming) |
| **H5P** | HTML5 Package interactive learning content standard |
| **SCORM** | Sharable Content Object Reference Model |
| **LTI 1.3** | Learning Tools Interoperability Version 1.3 |
| **OER** | Open Educational Resources |
| **QR Code** | Quick Response Matrix Barcode used for classroom attendance and certificate verification |

### 1.5 References
- IEEE Std 830-1998 / ISO/IEC/IEEE 29148: *Recommended Practice for Software Requirements Specifications*.
- The Open University of Tanzania (IEMT): *Software Requirements Specification — Short Courses Management Information System (SCMIS), Version 1.0* (1st February, 2023).
- The Open University of Tanzania (IEMT): *Enhancement Framework for Professional Short Courses and MOOCs* (26th August, 2026).

---

## 2. Overall Description of the System

### 2.1 Product Perspective
`LMSOUT` serves as OUT's centralized institutional platform unifying academic short-course operations, regional training logistics, commercial/MOOC online learning, financial control, and sovereign digital content management inside a single Laravel 13 application architecture.

The system is designed to:
1. Centralize institutional learner, applicant, staff, course, financial, and certificate records within one relational database, eliminating manual record duplication and inter-system synchronization gaps.
2. Enable rapid creation, scheduling, pricing, and publication of courses across `online`, `face_to_face`, and `hybrid` delivery modes.
3. Provide instant search, multi-criteria filtering, sorting, and export of learner, course, financial, attendance, and certificate datasets.
4. Maintain continuous real-time visibility over regional center operations, classroom utilization, fee collection, payment statuses (`paid`, `partial`, `unpaid`), and certificate clearance queues (`pending`, `ready`, `issued`).
5. Preserve full institutional ownership of course videos, documents, interactive packages, and user data on OUT-controlled servers and object storage.
6. Support interoperability with institutional portals, OUT's degree Moodle environment (for OER/question-bank exchange and single sign-on where needed), payment aggregators, and native mobile applications via token-authenticated JSON APIs.

### 2.2 Product Functions
The system is organized into ten core functional domains:
1. **Course, MOOC, and Bundle Management**: Multi-mode (`online`, `face_to_face`, `hybrid`) curriculum authoring, categories, languages, pricing (`free` / `paid`), `TZS`/`USD` currency support, course bundles, drip scheduling, and institutional course media management.
2. **Regional Center, Venue, and Timetable Management**: Regional center registry (covering OUT Headquarters and all regional/coordination centers), physical classroom/lab management with capacity bounds, and automated instructor/venue timetable conflict detection.
3. **Applicant Intake and Student Lifecycle Management**: Online instant enrollment, F2F/Hybrid application workflows, staff (50%) and custom fee waivers, automatic student account provisioning, directory search/filter/export, and cohort unenrollment.
4. **Invoicing, GePG Billing, Installments, and Payment Gateways**: Automatic invoice and `99104*` control number generation, `Full`/`Half`/`Quarter` installment schedules, overdue notifications, Pesapal v3 (Mobile Money & Cards), Stripe, PayPal, Flutterwave, offline receipt upload and verification, tax computation, and co-instructor revenue sharing.
5. **Sovereign Video Storage and Streaming Vault**: Self-hosted lecture video upload, private object/disk storage (local or MinIO), asynchronous `ffmpeg` adaptive HLS transcoding (`360p`/`480p`/`720p`), and enrollment-gated signed streaming URLs supporting low-bandwidth mobile playback without YouTube or third-party cloud video lock-in.
6. **Interactive Learning, Live Classes, and Assessments**: Video/PDF/text/H5P/SCORM lessons, BigBlueButton self-hosted virtual classrooms (plus configurable meeting URLs), timed quizzes, randomized question banks (with CSV and Moodle XML/GIFT import), assignments with originality similarity checking, and progression/completion tracking.
7. **Attendance, Clearance, and QR Certification**: Manual and timed QR classroom attendance capture, automated attendance threshold calculation, certificate clearance queue, automated tutor certificate-ready notifications, custom drag-and-drop PDF certificates, Open Badges micro-credentials, and public QR/serial verification.
8. **Corporate Packages and Marketing Engagement**: Corporate organization accounts, seat-pack bulk enrollment and consolidated invoicing, coupon campaigns, CMS banners, announcements, newsletter management, and multi-channel notifications.
9. **Communication, Community, and AI Assistance**: Support ticketing system, direct student-instructor real-time messaging, course discussion forums, peer Community Q&A, study Focus/Habit productivity tools, and AI Study Assistant / Course Authoring Studio.
10. **Governance, Security, Analytics, and Operations**: 12-role RBAC with 3-layer enforcement, branch data isolation, strict session/password security controls, security audit trail, role-specific analytics dashboards, automated backups, and CI/CD deployment automation.

### 2.3 Key System Users

The system defines twelve (12) distinct user roles spanning executive governance, regional center administration, academic instruction, and learner portals:

| Index | Role Title | Role Key | Operational Scope |
| :--- | :--- | :--- | :--- |
| 1 | **Super Administrator** | `super-admin` | Unrestricted system governance, RBAC role definition, system settings, backups, maintenance, and all 17 centers |
| 2 | **Administrator** | `admin` | Full cross-branch academic, operational, financial, user, CMS, and reporting administration |
| 3 | **System Administrator** | `system-administrator` | Cross-branch technical & security governance, user account provisioning, blocking/unblocking, password resets, backups, audit logs |
| 4 | **Director** | `director` | Cross-branch executive oversight of active scheduled classes, regional performance, ready certificates, instructor approvals, reports, and security audit logs |
| 5 | **Deputy Director** | `deputy-director` | Cross-branch academic oversight, center timetables, clearances, analytics, reports, instructor approvals, and audit trails |
| 6 | **Marketing Officer** | `marketing-officer` | Cross-branch course registration, bundles, coupons, CMS banners, notifications, applicant funnel tracking, and marketing/demand reports |
| 7 | **Branch Manager / Registrar** | `branch-registrar` | Branch-isolated administration of assigned regional center: applicants, venues, timetables, attendance, invoices, clearances, and center reports |
| 8 | **Training Coordinator** | `coordinator` | Branch-isolated academic coordination: course scheduling, venue assignment, tutor allocation, attendance monitoring, and certificate clearance |
| 9 | **Accountant** | `accountant` | Branch-isolated financial operations: GePG invoices, control numbers, offline payment verification, installment schedules, overdue reminders, coupons, and financial reports |
| 10 | **Center Staff** | `staff` | Branch-isolated front-desk operations: applicant walk-in intake, attendance assistance, certificate lookup, and support ticket resolution |
| 11 | **Instructor / Tutor** | `instructor` | Portal-isolated authoring and teaching (`/instructor/*`): courses, media/assessments, live classes, assigned campus cohorts, Paid/Unpaid student rosters, QR attendance sessions, and payout withdrawals |
| 12 | **Student** | `student` | Portal-isolated learning and billing (`/student/*`): course/MOOC catalog enrollment, learning player, quizzes, assignments, campus timetable, QR check-in, GePG invoices, receipts, badges, and certificates |

### 2.4 Operating Environment
- **Application Framework and Language**: Laravel `13.x` running on PHP `8.3` or higher (`8.3 / 8.4 / 8.5`) with PHP extensions `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`, and `zip`.
- **Web Server and Hosting**: Linux server (Ubuntu / AlmaLinux / RHEL) running OpenLiteSpeed (CyberPanel), Nginx with PHP-FPM, or Apache `2.4` with HTTPS/TLS termination (`X-Forwarded-*` proxy headers supported).
- **Database Engine**: MySQL `8.0+` or MariaDB `10.6+` using `utf8mb4_unicode_ci` collation.
- **Media and Background Processing**: `ffmpeg` worker binaries for adaptive HLS video transcoding; MinIO S3-compatible local object store or dedicated local server disk partitions (`16 GB+ RAM`, `2 TB+ storage`); system cron scheduler (`* * * * * php artisan schedule:run`) and Laravel Queue workers.
- **Frontend Asset Pipeline**: Vite `7`, Tailwind CSS `4`, Bootstrap `4/5`, with pre-compiled static production assets served directly from `public/assets/`, `public/themes/`, and `storage/app/public/`.
- **Supported Client Browsers**: Modern Google Chrome, Mozilla Firefox, Microsoft Edge, Apple Safari (macOS and iOS), and mobile Chromium/WebKit browsers across desktops, tablets, touch screens, and smartphones.

### 2.5 Design and Implementation Constraints
1. **Content Sovereignty**: Instructional videos uploaded by OUT instructors must be stored and streamed exclusively from OUT-controlled storage infrastructure without requiring third-party video platforms such as YouTube.
2. **Branch Isolation Integrity**: Branch-scoped administrative roles (`branch-registrar`, `coordinator`, `accountant`, `staff`) shall be prevented at both controller/middleware and ORM query levels from reading or mutating records belonging to other regional centers.
3. **Financial Transaction Atomicity**: All invoice creations, GePG payment reconciliations, commission splits, and enrollment activations shall execute within atomic database transactions.
4. **Public Repository Hygiene**: Production database credentials, private keys, API secrets, and personal learner records shall remain strictly outside version control (`.env` / environment secrets only).

### 2.6 User Documentation
The system shall provide built-in, role-scoped interactive **Online Help** documentation accessible from `/admin/help`, `/instructor/help`, and `/student/help`, alongside technical deployment and administration manuals (`deploy/README.md`).

---

## 3. External Interface Requirements

### 3.1 User Interfaces
1. **Public Storefront Portal**: Responsive web interface providing course catalogue search and filtering, category browsing, bundle discovery, regional F2F/Hybrid application forms, `TZS`/`USD` currency switching, `en`/`sw` language switching, public invoice verification (`/campus/invoice-verify/{number}`), and certificate QR/serial verification (`/verify-certificate`).
2. **Administrative Control Panel (`/admin/*`)**: Role-adaptive dashboard interface dynamically rendering widgets, charts, filters (`search`, `date_range`, `status`, `branch_id`, `per_page`), and named paginated tables tailored to the signed-in user's role, paired with a permission-gated collapsible sidebar and dedicated system settings workspace.
3. **Instructor Teaching Portal (`/instructor/*`)**: Course curriculum builder, media uploader, quiz/question-bank editor, assignment grader, live class scheduler, campus cohort timetable and `Paid`/`Unpaid` roster view, QR attendance launcher, and earnings/withdrawal dashboard.
4. **Student Learning Portal (`/student/*`)**: Responsive learning player supporting adaptive HTML5/HLS video, PDF/text/H5P/SCORM lessons, live class launch/recordings, interactive quizzes and assignments, Focus Timer and Habit Tracker, campus timetable, GePG invoice/receipt manager, micro-badges, and downloadable QR certificates.

### 3.2 Hardware Interfaces
- Server hardware sized for concurrent web, database, and media transcoding workloads (Gen 9+ enterprise server class, 16 GB+ RAM, 2 TB+ expandable storage).
- Client hardware support for standard desktop displays, touch-screen kiosks/tablets, and mobile smartphone cameras (for scanning QR classroom attendance and certificate verification codes).

### 3.3 Software Interfaces

| External System / Component | Protocol / Interface | Functional Purpose |
| :--- | :--- | :--- |
| **MySQL / MariaDB** | PDO TCP/Unix Socket | Primary relational persistence for all academic, campus, RBAC, billing, and audit entities |
| **Pesapal v3 Payment Gateway** | HTTPS REST + OAuth Token + IPN Callback | Tanzanian Mobile Money (`M-Pesa`, `Tigo Pesa`, `Airtel Money`, `Halopesa`) and card payments via embedded iframe and asynchronous IPN verification |
| **Stripe / PayPal / Flutterwave** | HTTPS REST + Redirect / Webhook Callbacks | International card, wallet, and regional online checkout processing |
| **GePG Billing Interface** | Structured `99104*` Control Number Generator / Webhook / Receipt Verification | Generation, validation, and reconciliation of Tanzania government payment control numbers and bank payment slips |
| **Sovereign Media Vault (`Local Disk` / `MinIO` + `FFmpeg`)** | S3 API / Filesystem + `HTTP 206` Range / HLS `.m3u8` + Laravel Signed URLs | Private storage, background bitrate transcoding, and time-limited authorized playback of lecture videos |
| **BigBlueButton Server** | HTTPS XML/REST API (`LiveClassService`) | Self-hosted OUT live classrooms, webinars, breakout sessions, and automated local recording ingestion |
| **H5P / SCORM Interactive Runtime** | Embedded HTML5 JS Player / `LTI 1.3` / JSON xAPI Callbacks | Execution of interactive microlearning packages, simulations, and automated score write-back |
| **OUT Degree Moodle / OER Bridge** | Moodle Web Services REST API / OAuth2 SSO / Moodle XML & `.mbz` Parser | Optional single sign-on with OUT's degree ODL portal, OER asset sharing, and import of legacy Moodle quizzes |
| **Google Gemini AI API** | HTTPS REST (`GeminiService`) | AI Study Assistant for learners and AI Course Curriculum Generator for instructors/admins |
| **SMTP Mail & Notification Channels** | SMTP (TLS/SSL) + Database Web Notifications | Automated transactional emails (invoices, overdue reminders, payment receipts, certificate-ready alerts, OTP verification) |
| **Document & QR Services** | `DomPDF`, `FPDF`, `PhpSpreadsheet` / `Laravel Excel`, `QrCodeService` | Printable dual-crested invoices, payment receipts, PDF certificates, XLSX/CSV/PDF reports, and QR code images |
| **Google reCAPTCHA & Two-Factor Auth** | HTTPS SiteVerify / TOTP | Bot protection on login/application forms and MFA verification |

### 3.4 Communications Interfaces
- All web portal and REST API communications shall enforce HTTPS/TLS (`1.2+`).
- Authenticated mobile and external system clients shall communicate via JSON over HTTPS authenticated by **Laravel Sanctum** Bearer tokens under configurable rate limiting (`throttle:api`).
- Real-time in-portal notifications and chat threads shall update asynchronously via JSON polling endpoints.

---

## 4. Functional Requirements

### `R1` Student (`ST`)

| Ref | Requirement Description |
| :--- | :--- |
| `R1.1` | The system shall allow the Student to register or sign in via `/login` with optional OTP / Two-Factor Authentication and automatic redirection to the Student Portal. |
| `R1.2` | The system shall allow the Student to sign out cleanly (`GET` / `POST` `/logout`). |
| `R1.3` | The system shall allow the Student to view and update profile details, contact information, and profile photo. |
| `R1.4` | The system shall allow the Student to change their password, enforcing password complexity and first-login reset rules. |
| `R1.5` | The system shall allow the Student to browse and enroll in Online courses, MOOCs, and Bundles, or submit F2F/Hybrid short-course applications (`/courses/{course}/apply`). |
| `R1.6` | The system shall allow the Student to unenroll from eligible enrollments prior to completion lockouts. |
| `R1.7` | The system shall automatically generate an official branded Invoice upon enrollment or F2F/Hybrid application. |
| `R1.8` | The system shall automatically generate a unique GePG `99104*` Control Number for each issued invoice and support control number display across billing and verification screens. |
| `R1.9` | The system shall allow the Student to pay invoices or course checkouts in `Full`, `Half` (50%/50%), or `Quarter` (25% installments) schedules where configured. |
| `R1.10` | The system shall allow the Student to complete payments immediately online via active payment gateways (Pesapal v3, Stripe, PayPal, Flutterwave) or record control number payments. |
| `R1.11` | The system shall allow the Student to upload scanned offline bank/GePG payment receipts against an invoice and inspect live approval status (`pending`, `completed`, `rejected`). |
| `R1.12` | The system shall automatically send payment confirmation, upcoming installment, and overdue installment alerts to the Student via email and portal notifications. |
| `R1.13` | The system shall allow the Student to view all enrolled courses, regional venue locations, class sessions, and course start/end dates and times in their timetable. |
| `R1.14` | The system shall allow the Student to view and download printable official Payment Receipts and Invoices displaying OUT dual crests and branding. |
| `R1.15` | The system shall allow the Student to check in to physical campus classes using instructor-issued timed QR codes or attendance tokens. |
| `R1.16` | The system shall allow the Student to access course modules, stream sovereign hosted video lectures, view H5P/SCORM/PDF lessons, attempt timed quizzes, submit assignments, view study progress, and download QR-verified PDF certificates and micro-badges upon meeting completion and fee requirements. |
| `R1.17` | The system shall allow Registry / Center Officers and authorized Staff to print overdue/fee alerts and official invoice slips for learners. |

### `R2` Training Coordinator / Branch Manager (`TC` / `BM`)

| Ref | Requirement Description |
| :--- | :--- |
| `R2.1` | The system shall allow the Training Coordinator / Branch Manager to sign in, sign out, view/update profile, and change password. |
| `R2.2` | The system shall allow the Training Coordinator / Branch Manager to register and manage instructors and walk-in/online applicants within their assigned regional center. |
| `R2.3` | The system shall allow authorized cross-branch managers to register and maintain regional centers (`branches`) and allow coordinators to maintain center physical rooms/computer labs (`venues`) including seating capacities and equipment metadata. |
| `R2.4` | The system shall allow the Training Coordinator to register courses and explicitly configure delivery modes (`face_to_face`, `online`, `hybrid`), pricing (`TZS` / `USD`), fee waivers, and attendance thresholds. |
| `R2.5` | The system shall allow the Training Coordinator to link courses with instructors, regional centers, and physical venues (`course_assignments`) with start/end dates, schedule days, and session times (`Morning`, `Afternoon`, `Evening`, `Weekend`), preventing double-booking conflicts on venues or instructors. |
| `R2.6` | The system shall allow the Training Coordinator to mark manual cohort attendance, launch QR attendance sessions, and monitor learner attendance percentages against certification thresholds. |
| `R2.7` | The system shall automatically send alert emails to assigned instructors and coordinators notifying them whenever enrolled students satisfy requirements and certificates reach `ready` status. |
| `R2.8` | The system shall allow the Training Coordinator / Branch Manager to generate Weekly, Monthly, Quarterly, and custom date-range center course reports structured by **Center Name**, **Course Name**, **Total Students per Course**, and **Payment Status (`Full`, `Half`, `Quarter`, `Paid`, `Partial`, `Unpaid`)**, exportable in PDF, Excel (`.xlsx`), and CSV formats. |

### `R3` Systems Administrator / Super Administrator (`SA`)

| Ref | Requirement Description |
| :--- | :--- |
| `R3.1` | The system shall allow the Systems Administrator to sign in and force mandatory password renewal upon initial login or credential reset. |
| `R3.2` | The system shall allow the Systems Administrator to view/update their profile, upload auto-resized profile photos, and change password. |
| `R3.3` | The system shall allow the Systems Administrator to create, update, and manage staff, instructor, and student user accounts across all centers. |
| `R3.4` | The system shall allow the Systems Administrator to define custom RBAC roles, assign granular permissions across 8 functional permission groups, and grant or revoke user roles/permissions dynamically. |
| `R3.5` | The system shall allow the Systems Administrator to inspect detailed user information, block/suspend user accounts with documented reasons, unblock locked users, and trigger forced password resets. |
| `R3.6` | The system shall allow the Systems Administrator to search, filter, paginate (`>= 6` items per page), and export system users and the Student Directory (`Excel`, `CSV`, `PDF`). |
| `R3.7` | The system shall allow the Systems Administrator to configure global platform settings, payment gateway credentials, SMTP settings, CMS pages/menus/themes, localization (`en`/`sw`), system backups, log viewer, and security audit trails. |

### `R4` Marketing Officer (`MO`)

| Ref | Requirement Description |
| :--- | :--- |
| `R4.1` | The system shall allow the Marketing Officer to sign in, sign out, view/edit profile, and change password. |
| `R4.2` | The system shall allow the Marketing Officer to register and manage courses, course categories, course languages, promotional bundles, and discount coupons across all 17 centers. |
| `R4.3` | The system shall allow the Marketing Officer to view applicants, enrolled students alongside their courses, payment statuses, and intake conversion alerts/funnels across regional centers. |
| `R4.4` | The system shall allow the Marketing Officer to manage storefront banners, announcements, broadcast notifications, newsletter subscribers, and demand/center analytics without granting destructive access to backups, RBAC roles, staff CRUD, or venue operations. |

### `R5` Director and Deputy Director (`DR`)

| Ref | Requirement Description |
| :--- | :--- |
| `R5.1` | The system shall allow the Director and Deputy Director to sign in, sign out, view/edit profile, and change password. |
| `R5.2` | The system shall provide cross-branch executive dashboards displaying all **Active Scheduled Classes** across regional centers, center-by-center enrollment and collection rankings, and financial receivables summaries. |
| `R5.3` | The system shall enable the Director and Deputy Director to view, filter, and audit the complete list of **Ready and Issued Certificates**, pending instructor applications (with approve/reject workflows), executive reports, and security audit logs. |

### `R6` Instructor / Tutor (`IN`)

| Ref | Requirement Description |
| :--- | :--- |
| `R6.1` | The system shall allow the Instructor to sign in, sign out, view/update profile, and change password. |
| `R6.2` | The system shall allow the Instructor to author courses, topics, sovereign videos, documents, live classes, quizzes, question banks, and assignments. |
| `R6.3` | The system shall allow the Instructor to view enrolled students per course and per assigned regional center, clearly distinguishing **Paid**, **Partial**, and **Unpaid** learners in campus cohorts. |
| `R6.4` | The system shall allow the Instructor to view their class timetable, record attendance manually, and generate timed QR attendance check-in codes. |
| `R6.5` | The system shall automatically deliver email and portal alerts to the Instructor whenever student certificates reach `ready` status for their assigned cohorts. |
| `R6.6` | The system shall allow the Instructor to monitor earnings, revenue shares, and submit payout withdrawal requests (including bank and Pesapal payout methods). |

### `R7` Accountant (`AC`)

| Ref | Requirement Description |
| :--- | :--- |
| `R7.1` | The system shall restrict the Accountant strictly to financial operations within their assigned regional center (`branch_id`). |
| `R7.2` | The system shall allow the Accountant to view invoices, issue or regenerate GePG control numbers, verify or reject uploaded offline payment slips, automatically promote verified payments from `pending` to `completed` (reconciling invoice balances and installment schedules atomically), send overdue installment reminders, and export financial reports. |

### `R8` Center Staff (`CS`)

| Ref | Requirement Description |
| :--- | :--- |
| `R8.1` | The system shall restrict Center Staff strictly to front-desk operations within their assigned regional center (`branch_id`). |
| `R8.2` | The system shall allow Center Staff to register walk-in F2F/Hybrid applicants, check timetable schedules, assist with classroom attendance marking, look up certificate clearance statuses, and manage assigned learner support tickets without exposing system administration or payout controls. |

### `R9` Public Storefront, Course Catalogue, Checkout, and GePG Billing

| Ref | Requirement Description |
| :--- | :--- |
| `R9.1` | The system shall enforce delivery-mode checkout rules: `online` courses shall support immediate gateway checkout (preventing unauthorized offline bypasses when disabled), whereas `face_to_face` and `hybrid` courses shall route learners through the unified regional application form (`/courses/{course}/apply`). |
| `R9.2` | The system shall support dual display currency switching (`TZS` and `USD`) across public and admin views using configurable institutional exchange rates while settling gateway charges consistently in the course's authoritative currency. |
| `R9.3` | The system shall support institutional fee discount rules including automatic 50% staff waivers on eligible courses (e.g., Basic Computer Applications) and verifiable discount attachments. |
| `R9.4` | The system shall auto-promote pending online transactions to `completed` upon valid Pesapal IPN/callback verification, server-side status check, or manual administrative approval, and synchronize paid direct checkouts with official invoice and control number records. |

### `R10` E-Learning Delivery, MOOCs, Sovereign Video Streaming, Interactive Labs, and Assessments

| Ref | Requirement Description |
| :--- | :--- |
| `R10.1` | The system shall provide a Coursera/Udemy-style modular course player organizing learning units by topics (`CourseTopic`), lessons (`Lesson`), quizzes (`Quiz`), assignments (`Assignment`), and synchronous live classes (`LiveClass`). |
| `R10.2` | The system shall store instructional video uploads exclusively within OUT-controlled storage (`storage/` or private OUT MinIO object storage), process uploaded lectures via background `ffmpeg` queues into adaptive multi-bitrate HLS (`360p`/`480p`/`720p`) streams for low-bandwidth regional connectivity, and authorize playback through expiring signed streaming URLs supporting HTTP Byte-Range seeking. |
| `R10.3` | The system shall unlock all paid curriculum lessons appropriately upon verified payment and persist lesson completion, progress percentages, and sequential next-unit navigation cleanly without server errors. |
| `R10.4` | The system shall support interactive H5P and SCORM 1.2/2004 microlearning units, recording learner interactions and completion scores directly into course progression and gradebooks. |
| `R10.5` | The system shall support self-hosted BigBlueButton virtual classrooms directly via API (archiving recordings inside OUT sovereign video storage) alongside external meeting URLs. |
| `R10.6` | The system shall support randomized quiz papers drawn from question banks, CSV bulk import, Moodle XML/GIFT question-bank import, quiz retakes, assignment file submissions with automated originality checking, and optional LTI 1.3 / Moodle OER bridge synchronization. |

### `R11` Certification, Micro-Badges, Verification, and Corporate Cohorts

| Ref | Requirement Description |
| :--- | :--- |
| `R11.1` | The system shall evaluate certificate eligibility automatically against curriculum completion, quiz passing scores, payment settlement (`paid`), and configured `attendance_threshold` (default `75%` for F2F/Hybrid cohorts). |
| `R11.2` | The system shall generate customizable drag-and-drop PDF certificates embedding unique serial identifiers and public QR verification codes (`/verify-certificate/{code}`). |
| `R11.3` | The system shall issue competency-aligned digital micro-badges (`Open Badges` metadata) and gamification XP/level milestones upon module and course completion. |
| `R11.4` | The system shall support Corporate and Institutional Training Packages allowing organizations to register corporate cohorts, settle consolidated invoices, assign bulk learner seats/vouchers, and view corporate completion reports. |

### `R12` Notifications, Communication, Analytics, Helpdesk, and Mobile API

| Ref | Requirement Description |
| :--- | :--- |
| `R12.1` | The system shall trigger real-time bell notifications and configurable transactional emails (`NotificationTemplate`) across authentication, enrollment, invoice generation, payment receipt verification, installment reminders, live class reminders, support replies, and certificate readiness. |
| `R12.2` | The system shall provide direct real-time student-instructor messaging, per-course discussion threads, public Community Q&A, student Focus Timer & Habit Tracker tools, AI Study Assistant, and department-routed Support Tickets. |
| `R12.3` | The system shall expose tokenized Laravel Sanctum JSON endpoints (`/api/*`) for mobile applications covering course discovery, enrollment, payment verification, lesson/HLS playback, offline packet download sync, quizzes, campus invoices, QR attendance check-in, messaging, and instructor rosters. |

---

## 5. Non-Functional Requirements

### 5.1 Security Requirements

The system shall enforce preventive, detective, compensating, corrective, and recovery controls across all modules:

#### 5.1.1 Confidentiality
1. All sensitive credentials, passwords, and payment secrets shall be encrypted or hashed using industry-standard cryptographic algorithms (bcrypt `>= 12` rounds for passwords; encrypted casts for stored gateway/SMTP secrets).
2. The system shall provide secure self-service and administrator-assisted password update workflows during account initialization and ongoing use.
3. All production traffic shall enforce HTTPS/TLS (`SESSION_SECURE_COOKIE=true`, trusted reverse-proxy header validation), and web server configuration shall deny direct HTTP access to hidden dotfiles (`.env`, `.git`).
4. Sensitive configuration and cross-branch datasets shall be strictly hidden from unauthorized roles.
5. The system shall provide automated credential security lifecycle controls and password resets to minimize administrative overhead.

#### 5.1.2 Authentication
6. Accounts provisioned with temporary passwords (`force_password_reset = true`) shall be intercepted and forced to change passwords immediately on first login before accessing any portal feature.
7. Passwords shall enforce complexity rules (minimum 8 characters containing mixed uppercase, lowercase, numeric, and symbolic characters).
8. Access credentials shall be centrally validated across web sessions and Sanctum API tokens (`auth:sanctum`).
9. Passwords shall enforce configurable expiration (default maximum lifetime: **3 months / 90 days**).
10. Automated password reset workflows shall operate via time-limited signed email tokens.
11. The system shall enforce login failure limits: **maximum 4 failed attempts** followed by **5 minutes of account cooldown lockout**.
12. Repeated lockout thresholds (**2 or more lockout events**) shall escalate to administrative account suspension (`is_blocked = true`) requiring authorized administrator intervention.
13. Automated security alerts and audit entries shall be generated upon lockout, inactivity timeout, and concurrent session events.

#### 5.1.3 Log Audit and Traceability
14. All authentication events, login violations, lockouts, idle terminations, concurrent session invalidations, user blocks/unblocks, financial transactions, and role changes shall be recorded immutably in `activity_log` with actor identity, IP address, timestamp, and metadata.

#### 5.1.4 Information Access
15. Access to every route, controller action, and dataset shall follow RBAC and least-privilege ("need to know") principles across all 12 roles.
16. Read, write, update, export, and delete privileges shall be strictly mapped to verified Spatie permissions and `CheckRole` route guards.
17. Permissions shall be grouped logically into institutional roles mapped to regional branch assignments.
18. User session lifetimes shall be actively bounded and invalidated on sign-out, expiry, or suspension.
19. Sessions shall automatically terminate after configurable user inactivity (default: **5 minutes** of idle time).
20. The system shall support single-active-session enforcement, automatically invalidating prior active device sessions when an account signs in from a new device.

#### 5.1.5 Access Mode
21. End-user access shall occur strictly via authenticated HTTPS web browsers or authorized HTTPS mobile API clients.
22. Direct server/SSH administrative access shall be restricted exclusively to authorized Systems Administrators using key-based authentication.

#### 5.1.6 Data Integrity and Continuity
23. The system shall preserve referential and transactional integrity across enrollments, invoices, payments, installments, and attendance logs via foreign-key constraints and database transactions.
24. Automated regression and integrity test suites shall validate security, RBAC isolation, financial reconciliation, and academic workflows prior to every deployment.
25. Complete financial and administrative transaction histories shall be retained with receipt numbers, timestamps, and approving user IDs.
26. Web forms shall retain partially entered user form data locally in the browser across accidental interruptions or page reloads for at least **15 minutes** (`form-draft-guard`).

### 5.2 Software Quality Attributes

#### 5.2.1 Scalability and Modularity
27. The application shall use a modular service-oriented architecture (`app/Services/`) supporting horizontal scaling, queued background jobs, and independent mobile/API consumers.
28. The interface shall be device-aware and responsive across desktop monitors, laptops, tablets, touch screens, and mobile smartphones (including low-bandwidth mobile optimization and adaptive HLS streaming).
29. Modular webhook, gateway, LTI 1.3, and REST API adapters shall allow integration with additional institutional stakeholders (such as national payment gateways, accreditation portals, and OUT academic systems) without refactoring core workflows.

#### 5.2.2 Usability
30. First-time visitors and learners shall navigate from course discovery to enrollment or payment intuitively without external training.
31. Live search bars, multi-criteria filters, alphabetical sorting, and named pagination controls shall appear consistently across catalogs, directories, and dashboards.
32. Clean, consistent institutional branding (OUT navy `#013BA6`, secondary blue `#228DDD`, gold accent `#F4ED2D`, native Tanzanian course media) and clear visual hierarchy shall guide user actions.
33. Contextual role-specific Online Help guides shall remain accessible from every portal.
34. General end users shall require no formal training beyond system availability awareness to complete enrollment, payment, and learning workflows.
35. Interactive controls shall support touch-screen gestures on mobile and tablet devices.
36. Average administrative user onboarding/learning time for core workflows shall be **less than 1 day**.
37. Client and server form validation, confirmation prompts, and conflict checks (e.g., venue/instructor timetable collisions, duplicate enrollments) shall prevent accidental operational mistakes.
38. Full keyboard, mouse, and screen-reader-friendly navigation (`WCAG 2.1 AA` semantics) shall be maintained across interactive templates.
39. Menus, navigation groups, and drop-down lists shall use clear, domain-standard institutional terminology in English and Swahili.

#### 5.2.3 Performance and Response Time
40. Standard portal queries, filtered listings, and regional center reports shall render and return data within **8 seconds** under normal operating load, utilizing indexed SQL queries and 5-to-25-row paginated result sets.

---

## 6. System Data Model Overview

The relational database schema organizes the system into coherent, normalized entity groups:

| Domain Group | Core Tables | Key Relationships & Responsibilities |
| :--- | :--- | :--- |
| **Identity & RBAC** | `users`, `roles`, `permissions`, `model_has_roles`, `role_has_permissions`, `personal_access_tokens`, `activity_log` | Authentication credentials, security lockout/expiry columns, `branch_id`, 12 institutional roles, 42 permissions, Sanctum tokens, audit trail |
| **Campus Infrastructure** | `branches`, `venues`, `countries`, `course_assignments`, `applicants`, `attendance_records` | 17 regional centers, physical labs & capacities, 239 country records, cohort timetables, F2F/Hybrid applicants, QR/manual attendance logs |
| **Curriculum & Sovereign Media** | `courses`, `course_categories`, `course_languages`, `course_topics`, `lessons`, `lesson_progress`, `live_classes`, `media_assets` | Online/F2F/Hybrid courses (`price_currency`), modular topics/lessons, sovereign video/HLS asset metadata, completion state, BigBlueButton/live sessions |
| **Assessments & Certification** | `quizzes`, `quiz_questions`, `quiz_attempts`, `quiz_attempt_answers`, `question_banks`, `assignments`, `assignment_submissions`, `certificate_templates`, `badges` | Timed quizzes, randomized banks, assignments & originality scores, certificate clearance status, QR serial templates, Open Badges |
| **Billing & Commerce** | `enrollments`, `invoices`, `payment_schedules`, `payments`, `payment_gateway_settings`, `bundles`, `coupons`, `instructor_withdrawals`, `corporate_cohorts` | Cohort/course enrollments, GePG `99104*` control numbers, installment splits, gateway transaction logs (Pesapal/Stripe/PayPal/Flutterwave/Offline), revenue splits, corporate packages |
| **Communication, AI & CMS** | `notifications`, `notification_templates`, `support_tickets`, `conversations`, `chat_messages`, `course_discussions`, `community_questions`, `ai_chat_messages`, `focus_sessions`, `habits`, `platform_settings`, `frontend_settings`, `custom_pages`, `menus`, `banners` | Transactional alert templates, helpdesk ticketing, real-time chat, Q&A forums, AI study logs, productivity tools, CMS branding & site configuration |
