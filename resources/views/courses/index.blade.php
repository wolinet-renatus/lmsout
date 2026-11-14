@extends('layouts.app')

@push('styles')
<style>
    /* ==========================================================================
       Courses Catalog Redesign (Modern 3-Column + Unified Filter Sidebar)
       ========================================================================== */
    .edux-catalog-section {
        background-color: #f8fafc;
        padding: 32px 0 72px;
        min-height: 80vh;
        font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    /* Top Helper Accordion Bar */
    .edux-helper-bar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 28px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        overflow: hidden;
    }
    .edux-helper-bar__trigger {
        width: 100%;
        background: transparent;
        border: 0;
        padding: 13px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13.5px;
        font-weight: 600;
        color: #1e293b;
        cursor: pointer;
        text-align: left;
        transition: background-color 0.15s ease;
    }
    .edux-helper-bar__trigger:hover {
        background-color: #f8fafc;
    }
    .edux-helper-bar__left {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .edux-helper-bar__icon {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: rgba(var(--theme-rgb, 1, 59, 166), 0.1);
        color: var(--theme);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
    }
    .edux-helper-bar__chevron {
        color: #94a3b8;
        font-size: 12px;
        transition: transform 0.2s ease;
    }
    .edux-helper-bar__trigger[aria-expanded="true"] .edux-helper-bar__chevron {
        transform: rotate(180deg);
    }
    .edux-helper-bar__content {
        padding: 0 20px 16px 56px;
        font-size: 13px;
        color: #64748b;
        line-height: 1.6;
        border-top: 1px solid #f1f5f9;
        padding-top: 12px;
    }

    /* Catalog Header Row */
    .edux-catalog-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }
    .edux-catalog-header__eyebrow {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--theme);
        margin-bottom: 4px;
    }
    .edux-catalog-header__title {
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
        line-height: 1.2;
        margin: 0 0 4px;
    }
    .edux-catalog-header__title span {
        color: var(--theme);
    }
    .edux-catalog-header__count {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }
    .edux-catalog-header__sort {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .edux-catalog-header__sort-label {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
        white-space: nowrap;
    }
    .edux-catalog-sort-select {
        appearance: auto;
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 7px 14px;
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
        min-width: 150px;
        cursor: pointer;
        outline: none;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }
    .edux-catalog-sort-select:focus {
        border-color: var(--theme);
    }

    /* Left Filter Sidebar Card */
    .edux-filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
    }
    .edux-filter-group {
        padding-bottom: 18px;
        margin-bottom: 18px;
        border-bottom: 1px solid #f1f5f9;
    }
    .edux-filter-group:last-of-type {
        border-bottom: 0;
        margin-bottom: 16px;
        padding-bottom: 4px;
    }
    .edux-filter-group__title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #64748b;
        margin-bottom: 12px;
    }

    /* Search Box in Sidebar */
    .edux-filter-search {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .edux-filter-search__input {
        flex: 1;
        height: 38px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0 12px;
        font-size: 13px;
        color: #0f172a;
        background: #ffffff;
        outline: none;
        transition: border-color 0.15s ease;
    }
    .edux-filter-search__input::placeholder {
        color: #94a3b8;
    }
    .edux-filter-search__input:focus {
        border-color: var(--theme);
    }
    .edux-filter-search__btn {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: var(--theme);
        color: #ffffff;
        border: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        cursor: pointer;
        flex-shrink: 0;
        transition: opacity 0.15s ease;
    }
    .edux-filter-search__btn:hover {
        opacity: 0.92;
    }

    /* Radio / Checkbox Filter List */
    .edux-filter-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 9px;
    }
    .edux-filter-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13px;
        color: #334155;
        cursor: pointer;
        user-select: none;
        -webkit-user-select: none;
        margin: 0;
    }
    .edux-filter-item__left {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }
    .edux-filter-item input[type="radio"],
    .edux-filter-item input[type="checkbox"] {
        appearance: none;
        -webkit-appearance: none;
        width: 15px;
        height: 15px;
        border: 1.5px solid #cbd5e1;
        border-radius: 50%;
        background: #ffffff;
        margin: 0;
        cursor: pointer;
        position: relative;
        flex-shrink: 0;
        transition: all 0.15s ease;
    }
    .edux-filter-item input[type="radio"]:checked,
    .edux-filter-item input[type="checkbox"]:checked {
        border-color: var(--theme);
        background: var(--theme);
        box-shadow: inset 0 0 0 3px #ffffff;
    }
    .edux-filter-item__label {
        font-size: 13px;
        font-weight: 500;
        color: #334155;
        line-height: 1.3;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .edux-filter-item input:checked ~ .edux-filter-item__label {
        color: #0f172a;
        font-weight: 600;
    }
    .edux-filter-item__count {
        font-size: 11.5px;
        color: #94a3b8;
        font-weight: 500;
        margin-left: 8px;
    }
    .edux-filter-stars {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        font-size: 11.5px;
    }
    .edux-filter-stars .is-gold {
        color: #f59e0b;
    }
    .edux-filter-stars .is-muted {
        color: #e2e8f0;
    }
    .edux-filter-stars__text {
        font-size: 12.5px;
        color: #475569;
        margin-left: 5px;
        font-weight: 500;
    }

    /* Instructor Select & Reset Button */
    .edux-filter-select {
        width: 100%;
        height: 38px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0 12px;
        font-size: 13px;
        color: #334155;
        background-color: #ffffff;
        outline: none;
        cursor: pointer;
    }
    .edux-filter-select:focus {
        border-color: var(--theme);
    }
    .edux-reset-filters-btn {
        width: 100%;
        height: 38px;
        border-radius: 8px;
        border: 1.5px solid var(--theme);
        background: #ffffff;
        color: var(--theme);
        font-size: 12.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .edux-reset-filters-btn:hover {
        background: var(--theme);
        color: #ffffff;
    }

    /* 3-Column Course Card Design */
    .edux-catalog-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .edux-catalog-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        border-color: #cbd5e1;
    }
    .edux-catalog-card__thumb {
        position: relative;
        height: 175px;
        background: #0f172a;
        overflow: hidden;
    }
    .edux-catalog-card__thumb-link {
        display: block;
        width: 100%;
        height: 100%;
    }
    .edux-catalog-card__thumb-link img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }
    .edux-catalog-card:hover .edux-catalog-card__thumb-link img {
        transform: scale(1.04);
    }
    .edux-catalog-card__badges-tl {
        position: absolute;
        top: 10px;
        left: 10px;
        right: 64px;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        z-index: 2;
        pointer-events: none;
    }
    .edux-catalog-card__badge-cat {
        background: rgba(255, 255, 255, 0.96);
        color: #0f172a;
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 999px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        line-height: 1.3;
    }
    .edux-catalog-card__badge-sub {
        background: var(--theme);
        color: #ffffff;
        font-size: 10px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        line-height: 1.3;
    }
    .edux-catalog-card__badges-tr {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 2;
        pointer-events: none;
    }
    .edux-catalog-card__badge-free {
        background: #10b981;
        color: #ffffff;
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
        display: inline-block;
        line-height: 1.3;
    }
    .edux-catalog-card__badge-discount {
        background: #ef4444;
        color: #ffffff;
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.25);
        display: inline-block;
        line-height: 1.3;
    }
    .edux-catalog-card__wish-btn {
        position: absolute;
        bottom: 10px;
        left: 10px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.95);
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
        z-index: 2;
        text-decoration: none;
        transition: transform 0.15s ease, color 0.15s ease;
    }
    .edux-catalog-card__wish-btn:hover {
        transform: scale(1.08);
        color: var(--theme);
    }

    /* Card Body */
    .edux-catalog-card__body {
        padding: 14px 16px 16px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    .edux-catalog-card__rating-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 7px;
    }
    .edux-catalog-card__stars {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        font-size: 11px;
    }
    .edux-catalog-card__stars .is-filled {
        color: #f59e0b;
    }
    .edux-catalog-card__stars .is-empty {
        color: #e2e8f0;
    }
    .edux-catalog-card__rating-num {
        font-size: 12px;
        font-weight: 700;
        color: #334155;
        margin-left: 4px;
    }
    .edux-catalog-card__students {
        font-size: 11.5px;
        color: #64748b;
        font-weight: 500;
    }
    .edux-catalog-card__title {
        font-size: 14.5px;
        font-weight: 700;
        line-height: 1.38;
        margin: 0 0 9px;
        color: #0f172a;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 40px;
    }
    .edux-catalog-card__title a {
        color: inherit;
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .edux-catalog-card__title a:hover {
        color: var(--theme);
    }
    .edux-catalog-card__instructor {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 10px;
    }
    .edux-catalog-card__avatar {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }
    .edux-catalog-card__avatar-initials {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: var(--theme);
        color: #ffffff;
        font-size: 9px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .edux-catalog-card__instructor-name {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .edux-catalog-card__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 11.5px;
        color: #64748b;
        padding-bottom: 12px;
        margin-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .edux-catalog-card__meta span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .edux-catalog-card__meta i {
        font-size: 11px;
        color: #94a3b8;
    }
    .edux-catalog-card__footer {
        margin-top: auto;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 8px;
    }
    .edux-catalog-card__sub-note {
        font-size: 10.5px;
        font-weight: 600;
        color: #059669;
        margin-bottom: 3px;
        display: flex;
        align-items: center;
        gap: 4px;
        line-height: 1.25;
    }
    .edux-catalog-card__sub-note i {
        color: #10b981;
        font-size: 11px;
    }
    .edux-catalog-card__price-row {
        display: flex;
        align-items: baseline;
        gap: 6px;
    }
    .edux-catalog-card__price {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }
    .edux-catalog-card__price.is-free {
        color: #059669;
    }
    .edux-catalog-card__price-old {
        font-size: 12px;
        color: #94a3b8;
        text-decoration: line-through;
        font-weight: 500;
    }
    .edux-catalog-card__view-btn {
        border: 1.5px solid var(--theme);
        background: #ffffff;
        color: var(--theme);
        border-radius: 8px;
        padding: 5px 15px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease;
        flex-shrink: 0;
    }
    .edux-catalog-card__view-btn:hover {
        background: var(--theme);
        color: #ffffff;
    }

    /* Pagination Row */
    .edux-catalog-pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 36px;
    }
    .edux-catalog-pagination__info {
        font-size: 12.5px;
        color: #64748b;
    }
    .edux-catalog-pagination__list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .edux-catalog-pagination__list .page-numbers {
        width: 32px;
        height: 32px;
        border-radius: 7px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #334155;
        font-size: 12.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .edux-catalog-pagination__list li.active .page-numbers,
    .edux-catalog-pagination__list .page-numbers:hover:not(.disabled) {
        background: var(--theme);
        border-color: var(--theme);
        color: #ffffff;
    }
    .edux-catalog-pagination__list .page-numbers.disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    /* Bottom Newsletter Strip */
    .edux-newsletter-strip {
        background: #0b0f19;
        padding: 36px 0 12px;
    }
    .edux-newsletter-banner {
        background: linear-gradient(135deg, var(--theme) 0%, var(--theme-2, #228ddd) 100%);
        border-radius: 16px;
        padding: 28px 36px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
        box-shadow: 0 12px 30px rgba(1, 59, 166, 0.25);
    }
    .edux-newsletter-banner__title {
        font-size: 20px;
        font-weight: 800;
        color: #ffffff;
        margin: 0 0 4px;
    }
    .edux-newsletter-banner__desc {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.85);
        margin: 0;
    }
    .edux-newsletter-banner__form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        max-width: 440px;
    }
    .edux-newsletter-banner__input {
        flex: 1;
        height: 42px;
        border-radius: 8px;
        border: 0;
        padding: 0 14px;
        font-size: 13px;
        color: #0f172a;
        background: #ffffff;
        outline: none;
    }
    .edux-newsletter-banner__btn {
        height: 42px;
        padding: 0 20px;
        border-radius: 8px;
        border: 0;
        background: #ffffff;
        color: #0f172a;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: background-color 0.15s ease;
    }
    .edux-newsletter-banner__btn:hover {
        background: var(--theme-yellow, #f4ed2d);
    }

    /* Hide nice-select wrapper on catalog native selects if nice-select initialized */
    .edux-catalog-section .nice-select {
        display: none !important;
    }
    .edux-catalog-section select.edux-catalog-sort-select,
    .edux-catalog-section select.edux-filter-select {
        display: block !important;
    }
</style>
@endpush

@section('content')
    <section class="edux-catalog-section">
        <div class="container">
            <!-- Top Collapsible Helper Bar -->
            <div class="edux-helper-bar">
                <button class="edux-helper-bar__trigger" type="button" data-bs-toggle="collapse" data-bs-target="#howToFindCourseCollapse" aria-expanded="false" aria-controls="howToFindCourseCollapse">
                    <span class="edux-helper-bar__left">
                        <span class="edux-helper-bar__icon"><i class="fa-regular fa-lightbulb"></i></span>
                        <span>How to find the right course</span>
                    </span>
                    <i class="fa-solid fa-chevron-down edux-helper-bar__chevron"></i>
                </button>
                <div class="collapse" id="howToFindCourseCollapse">
                    <div class="edux-helper-bar__content">
                        Use the filters on the left to narrow down courses by <strong>Category</strong>, <strong>Skill Level</strong> (Beginner, Intermediate, Advanced), <strong>Price</strong>, or <strong>Student Rating</strong> to quickly find and enroll in the course that fits your goals.
                    </div>
                </div>
            </div>

            <!-- Catalog Header Row -->
            <div class="edux-catalog-header">
                <div class="edux-catalog-header__left">
                    <div class="edux-catalog-header__eyebrow">COURSE CATALOG</div>
                    <h1 class="edux-catalog-header__title">
                        Explore <span>{{ __('frontend.courses') }}</span>
                    </h1>
                    <p class="edux-catalog-header__count">
                        <span id="coursesFoundCount">{{ $courses->total() }}</span> {{ $courses->total() === 1 ? 'course' : 'courses' }} found
                    </p>
                </div>

                <div class="edux-catalog-header__sort">
                    <label for="sortFilterSelect" class="edux-catalog-header__sort-label">Sort by:</label>
                    <select id="sortFilterSelect" class="edux-catalog-sort-select">
                        <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Newest</option>
                        <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>Most Popular</option>
                        <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>Highest Rated</option>
                        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- Main 2-Column Catalog Grid -->
            <div class="row g-4">
                <!-- Left Filter Sidebar (col-lg-3) -->
                <div class="col-lg-3">
                    <aside class="edux-filter-card">
                        <!-- Search -->
                        <div class="edux-filter-group">
                            <div class="edux-filter-group__title">{{ __('frontend.search') }}</div>
                            <div class="edux-filter-search">
                                <input type="text" id="searchFilterInput" class="edux-filter-search__input" placeholder="Keyword..." value="{{ request('search') }}">
                                <button type="button" id="searchFilterBtn" class="edux-filter-search__btn" aria-label="Search">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="edux-filter-group">
                            <div class="edux-filter-group__title">{{ __('frontend.categories') }}</div>
                            <ul class="edux-filter-list">
                                <li>
                                    <label class="edux-filter-item">
                                        <span class="edux-filter-item__left">
                                            <input type="radio" name="category" value="" class="filter-control" {{ !request('category') ? 'checked' : '' }}>
                                            <span class="edux-filter-item__label">All Categories</span>
                                        </span>
                                    </label>
                                </li>
                                @foreach($categories as $categoryItem)
                                    <li>
                                        <label class="edux-filter-item">
                                            <span class="edux-filter-item__left">
                                                <input type="radio" name="category" value="{{ $categoryItem->category }}" class="filter-control" {{ request('category') == $categoryItem->category ? 'checked' : '' }}>
                                                <span class="edux-filter-item__label">{{ $categoryItem->category }}</span>
                                            </span>
                                            <span class="edux-filter-item__count">{{ $categoryItem->count }}</span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- Delivery Mode -->
                        <div class="edux-filter-group">
                            <div class="edux-filter-group__title">DELIVERY MODE</div>
                            <ul class="edux-filter-list">
                                <li>
                                    <label class="edux-filter-item">
                                        <span class="edux-filter-item__left">
                                            <input type="radio" name="delivery_mode" value="" class="filter-control" {{ !request('delivery_mode') ? 'checked' : '' }}>
                                            <span class="edux-filter-item__label">All Modes</span>
                                        </span>
                                    </label>
                                </li>
                                @foreach(['online' => 'Online (Self-Paced)', 'face_to_face' => 'Face-to-Face (Campus)', 'hybrid' => 'Hybrid (Online & Campus)'] as $modeKey => $modeLabel)
                                    <li>
                                        <label class="edux-filter-item">
                                            <span class="edux-filter-item__left">
                                                <input type="radio" name="delivery_mode" value="{{ $modeKey }}" class="filter-control" {{ request('delivery_mode') === $modeKey ? 'checked' : '' }}>
                                                <span class="edux-filter-item__label">{{ $modeLabel }}</span>
                                            </span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- Level -->
                        <div class="edux-filter-group">
                            <div class="edux-filter-group__title">LEVEL</div>
                            <ul class="edux-filter-list">
                                <li>
                                    <label class="edux-filter-item">
                                        <span class="edux-filter-item__left">
                                            <input type="radio" name="level" value="" class="filter-control" {{ !request('level') ? 'checked' : '' }}>
                                            <span class="edux-filter-item__label">All Levels</span>
                                        </span>
                                    </label>
                                </li>
                                @foreach(['advanced' => 'Advanced', 'beginner' => 'Beginner', 'intermediate' => 'Intermediate'] as $levelKey => $levelLabel)
                                    <li>
                                        <label class="edux-filter-item">
                                            <span class="edux-filter-item__left">
                                                <input type="radio" name="level" value="{{ $levelKey }}" class="filter-control" {{ strtolower((string) request('level')) === $levelKey ? 'checked' : '' }}>
                                                <span class="edux-filter-item__label">{{ $levelLabel }}</span>
                                            </span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- Price -->
                        <div class="edux-filter-group">
                            <div class="edux-filter-group__title">{{ __('frontend.price') }}</div>
                            <ul class="edux-filter-list">
                                <li>
                                    <label class="edux-filter-item">
                                        <span class="edux-filter-item__left">
                                            <input type="checkbox" name="price" value="free" class="filter-control price-radio" {{ request('price') === 'free' ? 'checked' : '' }}>
                                            <span class="edux-filter-item__label">{{ __('frontend.free') }}</span>
                                        </span>
                                    </label>
                                </li>
                                <li>
                                    <label class="edux-filter-item">
                                        <span class="edux-filter-item__left">
                                            <input type="checkbox" name="price" value="paid" class="filter-control price-radio" {{ request('price') === 'paid' ? 'checked' : '' }}>
                                            <span class="edux-filter-item__label">{{ __('frontend.paid') }}</span>
                                        </span>
                                    </label>
                                </li>
                            </ul>
                        </div>

                        <!-- Rating -->
                        <div class="edux-filter-group">
                            <div class="edux-filter-group__title">{{ __('frontend.rating') }}</div>
                            <ul class="edux-filter-list">
                                @foreach([
                                    ['val' => '4.5', 'stars' => 5, 'label' => '4.5 & up'],
                                    ['val' => '4',   'stars' => 4, 'label' => '4 & up'],
                                    ['val' => '3.5', 'stars' => 4, 'label' => '3.5 & up'],
                                    ['val' => '3',   'stars' => 3, 'label' => '3 & up'],
                                ] as $rItem)
                                    <li>
                                        <label class="edux-filter-item">
                                            <span class="edux-filter-item__left">
                                                <input type="checkbox" name="rating" value="{{ $rItem['val'] }}" class="filter-control rating-radio" {{ (string) request('rating') === $rItem['val'] ? 'checked' : '' }}>
                                                <span class="edux-filter-stars">
                                                    @for($s = 1; $s <= 5; $s++)
                                                        <i class="fa-solid fa-star {{ $s <= $rItem['stars'] ? 'is-gold' : 'is-muted' }}"></i>
                                                    @endfor
                                                    <span class="edux-filter-stars__text">{{ $rItem['label'] }}</span>
                                                </span>
                                            </span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- Instructor -->
                        <div class="edux-filter-group">
                            <div class="edux-filter-group__title">{{ __('frontend.instructors') }}</div>
                            <select id="instructorFilterSelect" name="instructor" class="edux-filter-select">
                                <option value="">All Instructors</option>
                                @foreach($instructors as $instructor)
                                    <option value="{{ $instructor->id }}" {{ (string) request('instructor') === (string) $instructor->id ? 'selected' : '' }}>
                                        {{ $instructor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Reset Filters Button -->
                        <button type="button" id="resetFiltersBtn" class="edux-reset-filters-btn">
                            <i class="fa-solid fa-rotate-left"></i> Reset Filters
                        </button>
                    </aside>
                </div>

                <!-- Right 3-Column Course Grid (col-lg-9) -->
                <div class="col-lg-9">
                    <div class="row g-4" id="coursesContainer">
                        @include('courses.partials.course-list', ['courses' => $courses])
                    </div>
                    <div id="paginationContainer">
                        @include('courses.partials.pagination', ['courses' => $courses])
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            $(document).ready(function() {
                let filterTimeout;
                const coursesUrl = '{{ route('courses') }}';

                function getFilters() {
                    const filters = {};

                    const search = $.trim($('#searchFilterInput').val() || '');
                    if (search) filters.search = search;

                    const category = $('input[name="category"]:checked').val();
                    if (category) filters.category = category;

                    const level = $('input[name="level"]:checked').val();
                    if (level) filters.level = level;

                    const deliveryMode = $('input[name="delivery_mode"]:checked').val();
                    if (deliveryMode) filters.delivery_mode = deliveryMode;

                    const price = $('input[name="price"]:checked').val();
                    if (price) filters.price = price;

                    const rating = $('input[name="rating"]:checked').val();
                    if (rating) filters.rating = rating;

                    const instructor = $('#instructorFilterSelect').val();
                    if (instructor) filters.instructor = instructor;

                    const sort = $('#sortFilterSelect').val();
                    if (sort && sort !== 'newest') filters.sort = sort;

                    return filters;
                }

                function loadCourses(filters) {
                    $.ajax({
                        url: coursesUrl,
                        method: 'GET',
                        data: filters,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        beforeSend: function() {
                            $('#coursesContainer').html(
                                '<div class="col-12 text-center py-5"><i class="fa-solid fa-spinner fa-spin fa-2x" style="color: var(--theme);"></i></div>'
                            );
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#coursesContainer').html(response.courses_html);
                                $('#paginationContainer').html(response.pagination_html);
                                if (typeof response.total !== 'undefined') {
                                    $('#coursesFoundCount').text(response.total);
                                }
                            }
                        },
                        error: function() {
                            $('#coursesContainer').html(
                                '<div class="col-12"><div class="alert alert-danger text-center">{{ __('frontend.error_loading_courses') }}</div></div>'
                            );
                        }
                    });
                }

                function triggerFilterReload() {
                    clearTimeout(filterTimeout);
                    filterTimeout = setTimeout(function() {
                        const filters = getFilters();
                        loadCourses(filters);

                        const urlObj = new URL(coursesUrl, window.location.origin);
                        Object.keys(filters).forEach(key => {
                            urlObj.searchParams.set(key, filters[key]);
                        });
                        window.history.replaceState({}, '', urlObj.toString());
                    }, 200);
                }

                $('.price-radio').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('.price-radio').not(this).prop('checked', false);
                    }
                });

                $('.rating-radio').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('.rating-radio').not(this).prop('checked', false);
                    }
                });

                $(document).on('change', '.filter-control, #instructorFilterSelect, #sortFilterSelect', function() {
                    triggerFilterReload();
                });

                $('#searchFilterBtn').on('click', function() {
                    triggerFilterReload();
                });

                $('#searchFilterInput').on('keyup', function(e) {
                    if (e.key === 'Enter') {
                        triggerFilterReload();
                    } else {
                        clearTimeout(filterTimeout);
                        filterTimeout = setTimeout(triggerFilterReload, 450);
                    }
                });

                $('#resetFiltersBtn').on('click', function() {
                    $('#searchFilterInput').val('');
                    $('input[name="category"][value=""]').prop('checked', true);
                    $('input[name="level"][value=""]').prop('checked', true);
                    $('input[name="delivery_mode"][value=""]').prop('checked', true);
                    $('.price-radio').prop('checked', false);
                    $('.rating-radio').prop('checked', false);
                    $('#instructorFilterSelect').val('');
                    $('#sortFilterSelect').val('newest');
                    triggerFilterReload();
                });

                $(document).on('click', '.edux-catalog-pagination__list a.page-numbers', function(e) {
                    e.preventDefault();
                    const url = $(this).attr('href');
                    if (url) {
                        const filters = getFilters();
                        const urlObj = new URL(url, window.location.origin);
                        Object.keys(filters).forEach(key => {
                            urlObj.searchParams.set(key, filters[key]);
                        });
                        loadCourses(Object.fromEntries(urlObj.searchParams));
                        window.history.pushState({}, '', urlObj.toString());
                        $('html, body').animate({ scrollTop: $('.edux-catalog-header').offset().top - 80 }, 250);
                    }
                });
            });
        </script>
    @endpush
@endsection
