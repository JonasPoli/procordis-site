# Procordis Project Backlog

## Phase 1: Foundation & Infrastructure
- [x] Initialize Symfony 7.2 Project
    - [x] Install dependencies (start from scratch or verify current)
    - [ ] Configure Docker/Database connection
- [/] Frontend Setup
    - [x] Install TailwindCSS v3
    - [x] Install Flowbite/Flowbite Blocks
    - [x] Configure `webpack.encore` or `asset-mapper`
- [ ] Core Services Configuration
    - [ ] Configure AWS S3 (Flysystem + VichUploader)
    - [ ] Configure LiipImagineBundle (Image filters)
    - [ ] Configure WMailer (Email transport)

## Phase 2: Core Architecture & Security
- [x] Authentication System
    - [x] Create `User` entity
    - [x] Configure Security (Firewall, `ROLE_ADMIN`)
    - [x] Create Login Form
    - [x] Create `app:admin-user` command
    - [ ] Implement "Forgot Password" flow
- [x] Base Layout & Templating
    - [x] Create Base Template (`base.html.twig`) with Tailwind
    - [x] Create Header & Footer components
- [ ] SEO Architecture (The 3 Layers)
    - [x] Create `GlobalTags` entity
    - [x] Create `PageSeo` entity for static pages
    - [x] Create `TemplateService` for global variable injection

## Phase 3: Administrative Area (Back-end)
- [x] Dashboard
    - [x] Create Admin Dashboard (Stats: News, Services, Requests)
- [x] Content Entities & CRUDs
    - [x] **News/Blog**: Entity `News` (title, slug, content, image, SEO fields) + CRUD
    - [x] **Services**: Entity `Service` + CRUD
    - [x] **Team**: Entity `Doctor`/`TeamMember` + CRUD
    - [x] **Transparency**: Entity `TransparencyDoc` + CRUD
    - [x] **General Info**: Entity `GeneralData` (Address, Phone, etc.) + CRUD

## Phase 4: Frontend Implementation (Public Pages)
- [x] Home Page
    - [x] Hero Section
    - [x] Highlights/Action Buttons
    - [x] Latest News Feed
- [x] Institutional Pages
    - [x] "Quem Somos" (History, Mission, Values)
    - [x] "Equipe Médica" (List & Detail)
- [x] Services & Specialties
    - [x] Services List
    - [x] Service Detail Page
- [x] News & Education
    - [x] News List (Pagination)
    - [x] News Detail Page
- [x] Functional Pages
    - [x] Contact & Location (Map, Form)
    - [x] Transparency/Accountability
    - [x] FAQ & Research
- [x] Error Pages (404, 500) Custom Design

## Phase 5: Optimization & Launch
- [ ] Performance Tuning (PageSpeed 100 target)
    - [ ] Image Optimization (WebP)
    - [ ] Tailwind Purge/Minification
- [ ] Final SEO Verification
- [ ] Browser Testing (Cross-device)
