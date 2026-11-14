@extends('layouts.app')

@push('meta')
    <meta property="og:title" content="{{ $course->title }} | {{ site_name() }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($course->description), 160) }}">
    <meta property="og:image" content="{{ $course->thumbnail }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $course->title }}">
    <meta name="twitter:description" content="{{ Str::limit(strip_tags($course->description), 160) }}">
    <meta name="twitter:image" content="{{ $course->thumbnail }}">
@endpush

@push('styles')
<style>
    /* ==========================================================================
       Course Details Redesign (Gradient Hero + Stacked Content Cards + Sticky Sidebar)
       ========================================================================== */
    .edux-cd-page {
        background-color: #f8fafc;
        font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        padding-bottom: 64px;
    }

    /* 1. Top Gradient Hero Banner */
    .edux-cd-hero {
        background: linear-gradient(135deg, var(--theme) 0%, var(--theme-2, #228ddd) 100%);
        padding: 36px 0 40px;
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }
    .edux-cd-hero__cat-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.25);
        color: #ffffff;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 999px;
        margin-bottom: 12px;
        text-decoration: none;
    }
    .edux-cd-hero__cat-pill:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.24);
    }
    .edux-cd-hero__title {
        font-size: 28px;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.25;
        letter-spacing: -0.02em;
        margin: 0 0 8px;
        max-width: 780px;
    }
    .edux-cd-hero__subtitle {
        font-size: 13.5px;
        color: rgba(255, 255, 255, 0.86);
        line-height: 1.55;
        margin: 0 0 14px;
        max-width: 720px;
    }
    .edux-cd-hero__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }
    .edux-cd-hero__rating {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 12px;
        margin-right: 4px;
    }
    .edux-cd-hero__rating .is-gold {
        color: #fbbf24;
    }
    .edux-cd-hero__rating .is-muted {
        color: rgba(255, 255, 255, 0.35);
    }
    .edux-cd-hero__rating-text {
        color: #ffffff;
        font-weight: 600;
        margin-left: 4px;
        font-size: 12px;
    }
    .edux-cd-hero__pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.22);
        color: #ffffff;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 999px;
    }
    .edux-cd-hero__author {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12.5px;
        color: rgba(255, 255, 255, 0.9);
    }
    .edux-cd-hero__author-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        object-fit: cover;
        border: 1.5px solid rgba(255, 255, 255, 0.8);
    }
    .edux-cd-hero__author-initials {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.22);
        border: 1.5px solid rgba(255, 255, 255, 0.8);
        color: #ffffff;
        font-size: 10px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .edux-cd-hero__author strong {
        color: #ffffff;
        font-weight: 700;
    }

    /* 2. Main Body Container */
    .edux-cd-body {
        padding-top: 28px;
    }

    /* Video / Thumbnail Preview Card */
    .edux-cd-video-card {
        position: relative;
        border-radius: 16px;
        overflow: hidden;
        background: #0f172a;
        box-shadow: 0 6px 24px rgba(15, 23, 42, 0.08);
        margin-bottom: 20px;
        aspect-ratio: 16 / 9;
        max-height: 410px;
        width: 100%;
    }
    .edux-cd-video-card__img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .edux-cd-video-card__overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.55) 0%, rgba(15, 23, 42, 0.15) 45%, rgba(15, 23, 42, 0.55) 100%);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 16px 20px;
    }
    .edux-cd-video-card__topbar {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #ffffff;
    }
    .edux-cd-video-card__topbar-title {
        font-size: 13.5px;
        font-weight: 600;
        color: #ffffff;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .edux-cd-video-card__play-btn {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 64px;
        height: 46px;
        border-radius: 12px;
        background: #ef4444;
        color: #ffffff;
        border: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 8px 24px rgba(239, 68, 68, 0.45);
        cursor: pointer;
        transition: transform 0.2s ease, background-color 0.2s ease;
        text-decoration: none;
    }
    .edux-cd-video-card__play-btn:hover {
        transform: translate(-50%, -50%) scale(1.07);
        background: var(--theme);
        color: #ffffff;
    }
    .edux-cd-video-card__bottombar {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .edux-cd-video-card__watch-pill {
        margin-left: auto;
        background: rgba(15, 23, 42, 0.82);
        color: #ffffff;
        font-size: 11.5px;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }
    .edux-cd-video-card__watch-pill:hover {
        color: #ffffff;
        background: rgba(15, 23, 42, 0.95);
    }

    /* Stacked White Content Cards */
    .edux-cd-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 22px 24px;
        margin-bottom: 18px;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.02);
    }
    .edux-cd-card__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }
    .edux-cd-card__title {
        font-size: 15.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 14px;
    }
    .edux-cd-card__header .edux-cd-card__title {
        margin-bottom: 0;
    }
    .edux-cd-card__summary-meta {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }

    /* What you'll learn Grid */
    .edux-cd-learn-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px 20px;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    @media (max-width: 575.98px) {
        .edux-cd-learn-grid {
            grid-template-columns: 1fr;
        }
    }
    .edux-cd-learn-grid li {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        font-size: 13px;
        color: #334155;
        line-height: 1.45;
    }
    .edux-cd-learn-grid li i {
        color: var(--theme);
        font-size: 13.5px;
        margin-top: 2px;
        flex-shrink: 0;
    }

    /* Requirements List */
    .edux-cd-req-list {
        list-style: disc;
        padding-left: 18px;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .edux-cd-req-list li {
        font-size: 13px;
        color: #334155;
        line-height: 1.5;
    }

    /* Description Text */
    .edux-cd-desc {
        font-size: 13.5px;
        color: #475569;
        line-height: 1.7;
    }
    .edux-cd-desc p:last-child {
        margin-bottom: 0;
    }

    /* Course Content Curriculum Accordion */
    .edux-cd-curriculum {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .edux-cd-section {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
    }
    .edux-cd-section__btn {
        width: 100%;
        background: #f8fafc;
        border: 0;
        padding: 11px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-align: left;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .edux-cd-section__btn:hover {
        background: #f1f5f9;
    }
    .edux-cd-section__title {
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
    }
    .edux-cd-section__meta {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        font-size: 11.5px;
        color: #64748b;
        font-weight: 500;
    }
    .edux-cd-section__chevron {
        font-size: 11px;
        color: #64748b;
        transition: transform 0.2s ease;
    }
    .edux-cd-section__btn[aria-expanded="true"] .edux-cd-section__chevron {
        transform: rotate(180deg);
    }
    .edux-cd-lessons {
        list-style: none;
        padding: 0;
        margin: 0;
        border-top: 1px solid #e2e8f0;
    }
    .edux-cd-lesson-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 9px 16px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 12.5px;
        color: #334155;
    }
    .edux-cd-lesson-item:last-child {
        border-bottom: 0;
    }
    .edux-cd-lesson-item__left {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }
    .edux-cd-lesson-item__icon-play {
        color: var(--theme);
        font-size: 13px;
        flex-shrink: 0;
    }
    .edux-cd-lesson-item__icon-lock {
        color: #94a3b8;
        font-size: 12px;
        flex-shrink: 0;
    }
    .edux-cd-lesson-item__name {
        color: #334155;
        font-weight: 500;
        text-decoration: none;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    a.edux-cd-lesson-item__name:hover {
        color: var(--theme);
    }
    .edux-cd-lesson-item__preview-badge {
        background: #dcfce7;
        color: #15803d;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        flex-shrink: 0;
    }
    .edux-cd-lesson-item__duration {
        font-size: 11.5px;
        color: #94a3b8;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Quizzes Sub-box inside Course Content */
    .edux-cd-quiz-box {
        margin-top: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 16px;
        background: #ffffff;
    }
    .edux-cd-quiz-box__heading {
        font-size: 12.5px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 10px;
    }
    .edux-cd-quiz-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
    }
    .edux-cd-quiz-item:first-of-type {
        border-top: 0;
        padding-top: 0;
    }
    .edux-cd-quiz-item__title {
        font-size: 12.5px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .edux-cd-quiz-item__title i {
        color: var(--theme);
    }
    .edux-cd-quiz-item__sub {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
        padding-left: 20px;
    }
    .edux-cd-quiz-item__lock {
        font-size: 11px;
        color: #94a3b8;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    /* Instructor Card */
    .edux-cd-instructor {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }
    .edux-cd-instructor__avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--theme);
        padding: 2px;
        flex-shrink: 0;
    }
    .edux-cd-instructor__initials {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: var(--theme);
        color: #ffffff;
        font-size: 18px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .edux-cd-instructor__name {
        font-size: 14.5px;
        font-weight: 700;
        color: var(--theme);
        margin: 0 0 2px;
        text-decoration: none;
        display: inline-block;
    }
    .edux-cd-instructor__role {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 6px;
    }
    .edux-cd-instructor__bio {
        font-size: 12.5px;
        color: #475569;
        line-height: 1.55;
        margin: 0;
    }

    /* Student Reviews List */
    .edux-cd-reviews-list {
        display: flex;
        flex-direction: column;
    }
    .edux-cd-review-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .edux-cd-review-item:first-child {
        padding-top: 2px;
    }
    .edux-cd-review-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .edux-cd-review-item__avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }
    .edux-cd-review-item__initials {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        color: #ffffff;
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .edux-cd-review-item__top {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 3px;
    }
    .edux-cd-review-item__name {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
    }
    .edux-cd-review-item__stars {
        display: inline-flex;
        align-items: center;
        gap: 1px;
        font-size: 10.5px;
    }
    .edux-cd-review-item__stars .is-gold {
        color: #f59e0b;
    }
    .edux-cd-review-item__stars .is-muted {
        color: #e2e8f0;
    }
    .edux-cd-review-item__date {
        font-size: 11px;
        color: #94a3b8;
    }
    .edux-cd-review-item__comment {
        font-size: 12.5px;
        color: #475569;
        line-height: 1.5;
        margin: 0;
    }

    /* 3. Right Sticky Purchase Sidebar Card */
    .edux-cd-sidebar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 22px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        position: sticky;
        top: 96px;
    }
    .edux-cd-sidebar__price-row {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 6px;
    }
    .edux-cd-sidebar__price {
        font-size: 26px;
        font-weight: 800;
        color: var(--theme);
        line-height: 1.15;
    }
    .edux-cd-sidebar__price.is-free {
        color: #059669;
    }
    .edux-cd-sidebar__price-old {
        font-size: 14px;
        color: #94a3b8;
        text-decoration: line-through;
        font-weight: 500;
    }
    .edux-cd-sidebar__subnote {
        font-size: 11.5px;
        color: #64748b;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .edux-cd-sidebar__btn-primary {
        width: 100%;
        height: 42px;
        border-radius: 10px;
        background: var(--theme);
        color: #ffffff;
        border: 0;
        font-size: 13.5px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
        margin-bottom: 10px;
        transition: opacity 0.15s ease;
    }
    .edux-cd-sidebar__btn-primary:hover {
        opacity: 0.93;
        color: #ffffff;
    }
    .edux-cd-sidebar__btn-outline {
        width: 100%;
        height: 38px;
        border-radius: 10px;
        background: #ffffff;
        color: var(--theme);
        border: 1.5px solid #cbd5e1;
        font-size: 12.5px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        text-decoration: none;
        margin-bottom: 14px;
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }
    .edux-cd-sidebar__btn-outline:hover {
        border-color: var(--theme);
        background: rgba(1, 59, 166, 0.03);
        color: var(--theme);
    }
    .edux-cd-sidebar__sub-box {
        background: rgba(1, 59, 166, 0.06);
        border: 1px solid rgba(1, 59, 166, 0.18);
        border-radius: 10px;
        padding: 11px 13px;
        font-size: 11.5px;
        color: #334155;
        line-height: 1.45;
        margin-bottom: 12px;
    }
    .edux-cd-sidebar__sub-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: var(--theme);
        color: #ffffff;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 999px;
        margin-right: 4px;
    }
    .edux-cd-sidebar__sub-box a {
        color: var(--theme);
        font-weight: 700;
        text-decoration: underline;
    }
    .edux-cd-sidebar__gift {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        color: var(--theme);
        text-decoration: underline;
        margin-bottom: 16px;
    }
    .edux-cd-sidebar__divider {
        border-top: 1px solid #f1f5f9;
        margin: 0 0 14px;
    }
    .edux-cd-sidebar__includes-title {
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 10px;
    }
    .edux-cd-sidebar__includes-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .edux-cd-sidebar__includes-list li {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 12.5px;
        color: #475569;
    }
    .edux-cd-sidebar__includes-list li i {
        width: 16px;
        text-align: center;
        color: #64748b;
        font-size: 12px;
    }

    /* 4. Related Courses Section & Cards */
    .edux-cd-related {
        margin-top: 36px;
    }
    .edux-cd-related__eyebrow {
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--theme);
        margin-bottom: 3px;
    }
    .edux-cd-related__title {
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 18px;
    }

    /* Course Catalog Card Styles for Related Courses */
    .edux-catalog-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .edux-catalog-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
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
    }
    .edux-catalog-card__badges-tr {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 2;
    }
    .edux-catalog-card__badge-free {
        background: #10b981;
        color: #ffffff;
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
    }
    .edux-catalog-card__badge-discount {
        background: #ef4444;
        color: #ffffff;
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
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
        text-decoration: none;
    }
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
    .edux-catalog-card__stars .is-filled { color: #f59e0b; }
    .edux-catalog-card__stars .is-empty { color: #e2e8f0; }
    .edux-catalog-card__rating-num {
        font-size: 12px;
        font-weight: 700;
        color: #334155;
        margin-left: 4px;
    }
    .edux-catalog-card__students {
        font-size: 11.5px;
        color: #64748b;
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
    }
    .edux-catalog-card__instructor-name {
        font-size: 12px;
        color: #64748b;
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
    }
    .edux-catalog-card__price.is-free {
        color: #059669;
    }
    .edux-catalog-card__price-old {
        font-size: 12px;
        color: #94a3b8;
        text-decoration: line-through;
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
    }
    .edux-catalog-card__view-btn:hover {
        background: var(--theme);
        color: #ffffff;
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
    }
</style>
@endpush

@section('content')
    @php
        $displayRating = $averageRating > 0 ? $averageRating : 5.0;
        $roundedRating = (int) round($displayRating);
        $studentsCount = (int) ($course->students_count ?? 0);

        $hours = intdiv((int) $totalDuration, 60);
        $minutes = ((int) $totalDuration) % 60;
        $formattedDuration = ($hours > 0 ? $hours . 'h ' : '') . ($minutes > 0 ? $minutes . 'm' : ($hours === 0 ? '45m' : '0m'));

        $instructorName = $course->instructor->name ?? __('frontend.unknown_instructor');
        $instructorInitials = collect(explode(' ', trim($instructorName)))
            ->filter()
            ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        $isFree = $course->pricing_model === 'free' || ((float) ($course->regular_price ?? 0) <= 0 && (float) ($course->sale_price ?? 0) <= 0);
        $hasDiscount = !$isFree && $course->sale_price !== null && (float) $course->sale_price > 0 && (float) $course->regular_price > (float) $course->sale_price;

        $objectivesList = [];
        if (!empty($course->objectives)) {
            $objectivesList = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', strip_tags($course->objectives)))));
        }
        if (empty($objectivesList)) {
            $objectivesList = [
                'Build real-world production projects from scratch',
                'Understand core architecture & industry best practices',
                'Master practical workflows and hands-on techniques',
            ];
        }

        $requirementsList = [];
        if (!empty($course->requirements)) {
            $requirementsList = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', strip_tags($course->requirements)))));
        }
        if (empty($requirementsList)) {
            $requirementsList = [
                'Basic computer skills',
                'Internet connection and a modern web browser',
            ];
        }

        $allQuizzes = $course->topics->flatMap(fn($topic) => $topic->quizzes ?? collect());
        $firstPreviewLesson = $course->topics->flatMap(fn($topic) => $topic->lessons ?? collect())->first(fn($l) => $l->is_preview && ($l->video_url || $l->video_path));
        $avatarPalette = ['#013ba6', '#0d9488', '#7c3aed', '#0284c7', '#db2777', '#ea580c'];
    @endphp

    <div class="edux-cd-page">
        <!-- 1. Gradient Hero Banner -->
        <section class="edux-cd-hero">
            <div class="container">
                @if($course->category)
                    <a href="{{ route('courses', ['category' => $course->category]) }}" class="edux-cd-hero__cat-pill">
                        <i class="fa-solid fa-tag"></i> {{ $course->category }}
                    </a>
                @endif

                <h1 class="edux-cd-hero__title">
                    {{ $course->title }}
                    @if($course->is_live_course)
                        <span class="badge bg-danger ms-2" style="font-size: 12px; vertical-align: middle;">
                            <i class="fas fa-broadcast-tower me-1"></i> {{ __('frontend.live') }}
                        </span>
                    @endif
                </h1>

                <p class="edux-cd-hero__subtitle">
                    {{ Str::limit(trim(strip_tags($course->description)), 135) }}
                </p>

                <div class="edux-cd-hero__meta">
                    <span class="edux-cd-hero__rating">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fa-solid fa-star {{ $i <= $roundedRating ? 'is-gold' : 'is-muted' }}"></i>
                        @endfor
                        <span class="edux-cd-hero__rating-text">
                            {{ number_format($displayRating, 1) }} ({{ $totalReviews }} {{ $totalReviews === 1 ? 'review' : 'reviews' }})
                        </span>
                    </span>

                    <span class="edux-cd-hero__pill">
                        <i class="fa-solid fa-user-group"></i> {{ $studentsCount }} {{ $studentsCount === 1 ? 'student' : 'students' }}
                    </span>

                    <span class="edux-cd-hero__pill">
                        <i class="fa-solid fa-signal"></i> {{ ucfirst($course->difficulty ?? 'Beginner') }}
                    </span>

                    <span class="edux-cd-hero__pill">
                        <i class="fa-solid fa-globe"></i> {{ strtoupper(substr($course->language ?? 'EN', 0, 2)) }}
                    </span>
                </div>

                <div class="edux-cd-hero__author">
                    @if(!empty($course->instructor->avatar))
                        <img src="{{ $course->instructor->profile_photo_url }}" alt="{{ $instructorName }}" class="edux-cd-hero__author-avatar">
                    @else
                        <span class="edux-cd-hero__author-initials">{{ $instructorInitials ?: 'IN' }}</span>
                    @endif
                    <span>Created by <strong>{{ $instructorName }}</strong></span>
                </div>
            </div>
        </section>

        <!-- 2. Main Two-Column Content Area -->
        <section class="edux-cd-body">
            <div class="container">
                <div class="row g-4">
                    <!-- Left Column: Video Preview + Stacked Content Cards -->
                    <div class="col-lg-8">
                        <!-- Video / Thumbnail Preview Card -->
                        <div class="edux-cd-video-card">
                            <img src="{{ $course->thumbnail }}" alt="{{ $course->title }}" class="edux-cd-video-card__img">
                            <div class="edux-cd-video-card__overlay">
                                <div class="edux-cd-video-card__topbar">
                                    @if(!empty($course->instructor->avatar))
                                        <img src="{{ $course->instructor->profile_photo_url }}" alt="{{ $instructorName }}" class="edux-cd-hero__author-avatar">
                                    @else
                                        <span class="edux-cd-hero__author-initials">{{ $instructorInitials ?: 'IN' }}</span>
                                    @endif
                                    <span class="edux-cd-video-card__topbar-title">{{ $course->title }} — Course Overview</span>
                                </div>

                                @if($course->intro_video)
                                    <a href="javascript:void(0)" class="edux-cd-video-card__play-btn intro-video-play-btn" data-intro-video="{{ asset('storage/' . $course->intro_video) }}" aria-label="Play Intro Video">
                                        <i class="fa-solid fa-play"></i>
                                    </a>
                                @elseif($firstPreviewLesson)
                                    <a href="javascript:void(0)" class="edux-cd-video-card__play-btn lesson-preview-play"
                                       data-video-type="{{ $firstPreviewLesson->video_type }}"
                                       data-video-url="{{ $firstPreviewLesson->video_type === 'url' ? $firstPreviewLesson->video_url : asset('storage/' . $firstPreviewLesson->video_path) }}"
                                       data-lesson-title="{{ $firstPreviewLesson->title }}"
                                       aria-label="Play Lesson Preview">
                                        <i class="fa-solid fa-play"></i>
                                    </a>
                                @else
                                    <a href="#courseContentCard" class="edux-cd-video-card__play-btn" aria-label="View Curriculum">
                                        <i class="fa-solid fa-play"></i>
                                    </a>
                                @endif

                                <div class="edux-cd-video-card__bottombar">
                                    <a href="#courseContentCard" class="edux-cd-video-card__watch-pill">
                                        <span>Watch Preview</span>
                                        <i class="fa-brands fa-youtube text-danger"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Card: What you'll learn -->
                        <div class="edux-cd-card">
                            <h2 class="edux-cd-card__title">{{ __('frontend.what_learn') }}</h2>
                            <ul class="edux-cd-learn-grid">
                                @foreach($objectivesList as $objective)
                                    <li>
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span>{{ $objective }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- Card: Requirements -->
                        <div class="edux-cd-card">
                            <h2 class="edux-cd-card__title">{{ __('frontend.requirements') }}</h2>
                            <ul class="edux-cd-req-list">
                                @foreach($requirementsList as $req)
                                    <li>{{ $req }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- Card: Description -->
                        <div class="edux-cd-card">
                            <h2 class="edux-cd-card__title">{{ __('frontend.description') }}</h2>
                            <div class="edux-cd-desc">
                                {!! $course->description ?? '<p>' . __('frontend.no_description') . '</p>' !!}
                            </div>
                        </div>

                        <!-- Card: Course Content -->
                        <div class="edux-cd-card" id="courseContentCard">
                            <div class="edux-cd-card__header">
                                <h2 class="edux-cd-card__title">Course Content</h2>
                                <span class="edux-cd-card__summary-meta">
                                    {{ $course->topics->count() }} {{ $course->topics->count() === 1 ? 'section' : 'sections' }} &bull; {{ $lessonCount }} {{ $lessonCount === 1 ? 'lesson' : 'lessons' }} &bull; {{ $formattedDuration }}
                                </span>
                            </div>

                            @if($course->topics->count() > 0)
                                <div class="edux-cd-curriculum" id="courseCurriculumAccordion">
                                    @foreach($course->topics as $topicIndex => $topic)
                                        <div class="edux-cd-section">
                                            <button class="edux-cd-section__btn {{ $topicIndex === 0 ? '' : 'collapsed' }}"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#cdTopicCollapse{{ $topic->id }}"
                                                    aria-expanded="{{ $topicIndex === 0 ? 'true' : 'false' }}"
                                                    aria-controls="cdTopicCollapse{{ $topic->id }}">
                                                <span class="edux-cd-section__title">{{ $topic->title }}</span>
                                                <span class="edux-cd-section__meta">
                                                    <span>{{ $topic->lessons->count() }} {{ $topic->lessons->count() === 1 ? 'lesson' : 'lessons' }}</span>
                                                    <i class="fa-solid fa-chevron-down edux-cd-section__chevron"></i>
                                                </span>
                                            </button>

                                            <div id="cdTopicCollapse{{ $topic->id }}" class="collapse {{ $topicIndex === 0 ? 'show' : '' }}">
                                                <ul class="edux-cd-lessons">
                                                    @foreach($topic->lessons as $lessonIdx => $lesson)
                                                        @php
                                                            $canPlayPreview = $lesson->is_preview && (($lesson->video_type === 'url' && $lesson->video_url) || ($lesson->video_type === 'upload' && $lesson->video_path));
                                                            $lessonMins = (int) ($lesson->duration ?: 15);
                                                        @endphp
                                                        <li class="edux-cd-lesson-item">
                                                            <div class="edux-cd-lesson-item__left">
                                                                @if($lesson->is_preview || $lessonIdx === 0)
                                                                    <i class="fa-regular fa-circle-play edux-cd-lesson-item__icon-play"></i>
                                                                @else
                                                                    <i class="fa-solid fa-lock edux-cd-lesson-item__icon-lock"></i>
                                                                @endif

                                                                @if($canPlayPreview)
                                                                    <a href="javascript:void(0)"
                                                                       class="edux-cd-lesson-item__name lesson-preview-play"
                                                                       data-video-type="{{ $lesson->video_type }}"
                                                                       data-video-url="{{ $lesson->video_type === 'url' ? $lesson->video_url : asset('storage/' . $lesson->video_path) }}"
                                                                       data-lesson-title="{{ $lesson->title }}">
                                                                        {{ $lesson->title }}
                                                                    </a>
                                                                @else
                                                                    <span class="edux-cd-lesson-item__name">{{ $lesson->title }}</span>
                                                                @endif

                                                                @if($lesson->is_preview || $lessonIdx === 0)
                                                                    <span class="edux-cd-lesson-item__preview-badge">{{ __('frontend.preview') }}</span>
                                                                @endif
                                                            </div>

                                                            <span class="edux-cd-lesson-item__duration">{{ $lessonMins }}m</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Quizzes Box inside Course Content -->
                                @if($allQuizzes->count() > 0)
                                    <div class="edux-cd-quiz-box">
                                        <div class="edux-cd-quiz-box__heading">Quizzes ({{ $allQuizzes->count() }})</div>
                                        @foreach($allQuizzes as $quiz)
                                            <div class="edux-cd-quiz-item">
                                                <div>
                                                    <div class="edux-cd-quiz-item__title">
                                                        <i class="fa-regular fa-circle-question"></i>
                                                        <span>{{ $course->title }} — {{ $quiz->title }}</span>
                                                    </div>
                                                    <div class="edux-cd-quiz-item__sub">
                                                        Passing score: {{ $quiz->passing_score ?? 70 }}% &bull; {{ $quiz->questions ? $quiz->questions->count() : 5 }} questions
                                                    </div>
                                                </div>
                                                <span class="edux-cd-quiz-item__lock">
                                                    <i class="fa-solid fa-lock"></i> Enroll to unlock
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <p class="text-muted small mb-0">{{ __('frontend.no_curriculum') }}</p>
                            @endif
                        </div>

                        <!-- Card: Instructor -->
                        <div class="edux-cd-card">
                            <h2 class="edux-cd-card__title">{{ __('frontend.instructor') }}</h2>
                            @if($course->instructor)
                                <div class="edux-cd-instructor">
                                    @if(!empty($course->instructor->avatar))
                                        <img src="{{ $course->instructor->profile_photo_url }}" alt="{{ $instructorName }}" class="edux-cd-instructor__avatar">
                                    @else
                                        <span class="edux-cd-instructor__initials">{{ $instructorInitials ?: 'IN' }}</span>
                                    @endif
                                    <div>
                                        <a href="{{ route('instructors.show', $course->instructor->id) }}" class="edux-cd-instructor__name">
                                            {{ $instructorName }}
                                        </a>
                                        <div class="edux-cd-instructor__role">
                                            {{ $course->instructor->specialization ?? 'Senior Instructor & Subject Matter Expert' }}
                                        </div>
                                        <p class="edux-cd-instructor__bio">
                                            {{ $course->instructor->bio ?: __('frontend.instr_fallback_bio') }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                <p class="text-muted small mb-0">{{ __('frontend.instr_not_available') }}</p>
                            @endif
                        </div>

                        <!-- Card: Student Reviews -->
                        <div class="edux-cd-card">
                            <h2 class="edux-cd-card__title">Student Reviews</h2>

                            @if($totalReviews > 0)
                                <div class="edux-cd-reviews-list">
                                    @foreach($reviews as $revIdx => $review)
                                        @php
                                            $revUserName = $review->user->name ?? 'Student';
                                            $revInitials = collect(explode(' ', trim($revUserName)))
                                                ->filter()
                                                ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                                ->take(2)
                                                ->implode('');
                                            $bgColor = $avatarPalette[$revIdx % count($avatarPalette)];
                                        @endphp
                                        <div class="edux-cd-review-item">
                                            @if(!empty($review->user->avatar))
                                                <img src="{{ $review->user->profile_photo_url }}" alt="{{ $revUserName }}" class="edux-cd-review-item__avatar">
                                            @else
                                                <span class="edux-cd-review-item__initials" style="background: {{ $bgColor }};">
                                                    {{ $revInitials ?: 'ST' }}
                                                </span>
                                            @endif
                                            <div class="flex-grow-1">
                                                <div class="edux-cd-review-item__top">
                                                    <span class="edux-cd-review-item__name">{{ $revUserName }}</span>
                                                    <span class="edux-cd-review-item__stars">
                                                        @for($s = 1; $s <= 5; $s++)
                                                            <i class="fa-solid fa-star {{ $s <= $review->rating ? 'is-gold' : 'is-muted' }}"></i>
                                                        @endfor
                                                    </span>
                                                    <span class="edux-cd-review-item__date">{{ $review->created_at->diffForHumans() }}</span>
                                                </div>
                                                @if($review->comment)
                                                    <p class="edux-cd-review-item__comment">{{ $review->comment }}</p>
                                                @endif

                                                @if($review->replies && $review->replies->where('is_instructor', true)->count() > 0)
                                                    @foreach($review->replies->where('is_instructor', true) as $reply)
                                                        <div class="mt-2 p-2 rounded" style="background: #f8fafc; border-left: 3px solid var(--theme);">
                                                            <div class="small fw-bold" style="color: var(--theme);">
                                                                <i class="fa-solid fa-chalkboard-user me-1"></i> {{ __('frontend.instr_response') }}
                                                            </div>
                                                            <p class="small text-muted mb-0 mt-1">{{ $reply->reply }}</p>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted small mb-0">{{ __('frontend.no_reviews_yet') }}</p>
                            @endif

                            <!-- Review Form for Enrolled Students -->
                            @if($canReview && !$userReview)
                                <div class="mt-4 pt-3 border-top">
                                    <h5 class="fw-bold mb-3" style="font-size: 14px;">{{ __('frontend.write_review') }}</h5>
                                    <form action="{{ route('courses.reviews.store', $course->id) }}" method="POST" id="reviewForm">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Rating <span class="text-danger">*</span></label>
                                            <div class="rating-input" style="display: flex; gap: 5px; font-size: 22px; cursor: pointer;">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fas fa-star rating-star" data-rating="{{ $i }}" style="color: #cbd5e1; transition: color 0.2s;"></i>
                                                @endfor
                                            </div>
                                            <input type="hidden" name="rating" id="ratingInput" value="" required>
                                            <small class="text-danger d-none" id="ratingError">{{ __('frontend.select_rating_error') }}</small>
                                        </div>
                                        <div class="mb-3">
                                            <textarea name="comment" id="comment" class="form-control form-control-sm" rows="3" placeholder="{{ __('frontend.share_experience') }}"></textarea>
                                        </div>
                                        <button type="submit" class="edux-cd-sidebar__btn-primary" style="width: auto; padding: 0 20px; height: 38px;">
                                            <i class="fa-solid fa-paper-plane"></i> {{ __('frontend.submit_review') }}
                                        </button>
                                    </form>
                                </div>
                            @elseif($canReview && $userReview)
                                <div class="mt-4 pt-3 border-top">
                                    <h5 class="fw-bold mb-3" style="font-size: 14px;">{{ __('frontend.your_review') }}</h5>
                                    <form action="{{ route('courses.reviews.update', [$course->id, $userReview->id]) }}" method="POST" id="reviewForm">
                                        @csrf
                                        @method('PUT')
                                        <div class="mb-3">
                                            <div class="rating-input" style="display: flex; gap: 5px; font-size: 22px; cursor: pointer;">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fas fa-star rating-star" data-rating="{{ $i }}" style="color: {{ $i <= $userReview->rating ? '#f59e0b' : '#cbd5e1' }}; transition: color 0.2s;"></i>
                                                @endfor
                                            </div>
                                            <input type="hidden" name="rating" id="ratingInput" value="{{ $userReview->rating }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <textarea name="comment" id="comment" class="form-control form-control-sm" rows="3">{{ $userReview->comment }}</textarea>
                                        </div>
                                        <button type="submit" class="edux-cd-sidebar__btn-primary" style="width: auto; padding: 0 20px; height: 38px;">
                                            <i class="fa-solid fa-save"></i> {{ __('frontend.update_review') }}
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right Column: Sticky Purchase & Includes Sidebar -->
                    <div class="col-lg-4">
                        <aside class="edux-cd-sidebar">
                            <div class="edux-cd-sidebar__price-row">
                                @if($isFree)
                                    <span class="edux-cd-sidebar__price is-free">{{ __('frontend.free') }}</span>
                                @elseif($hasDiscount)
                                    <span class="edux-cd-sidebar__price">{{ currency_format($course->sale_price, 2, 'symbol') }}</span>
                                    <span class="edux-cd-sidebar__price-old">{{ currency_format($course->regular_price, 2, 'symbol') }}</span>
                                @else
                                    <span class="edux-cd-sidebar__price">{{ currency_format($course->regular_price, 2, 'symbol') }}</span>
                                @endif
                            </div>

                            <div class="edux-cd-sidebar__subnote">
                                <i class="fa-solid fa-shield-halved"></i>
                                @if ($course->requiresApplicantForm())
                                    <span>{{ $course->isHybrid() ? 'Hybrid' : 'Face-to-Face' }} Campus &bull; Offline Control No. or Online Pay</span>
                                @else
                                    <span>Full lifetime access &bull; Instant online enrollment</span>
                                @endif
                            </div>

                            @if($isEnrolled)
                                <a href="{{ route('student.courses.access', $course->id) }}" class="edux-cd-sidebar__btn-primary" style="background: #10b981;">
                                    <i class="fa-solid fa-circle-check"></i> {{ __('frontend.already_enrolled') }}
                                </a>
                            @elseif($course->requiresApplicantForm())
                                <a href="{{ route('courses.apply', $course->id) }}" class="edux-cd-sidebar__btn-primary">
                                    <i class="fa-solid fa-file-signature"></i>
                                    Apply Now (Campus Form)
                                </a>
                            @elseif(auth()->check())
                                <a href="{{ route('courses.checkout', $course->id) }}" class="edux-cd-sidebar__btn-primary">
                                    <i class="fa-solid fa-bag-shopping"></i>
                                    {{ $isFree ? __('frontend.enroll_now') : 'Enroll / Pay Online' }}
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="edux-cd-sidebar__btn-primary">
                                    <i class="fa-solid fa-bag-shopping"></i>
                                    {{ $isFree ? __('frontend.enroll_now') : 'Enroll / Pay Online' }}
                                </a>
                            @endif

                            <button type="button" class="edux-cd-sidebar__btn-outline" onclick="ToastMagic?.info('Added to your wishlist!');">
                                <i class="fa-regular fa-heart"></i> Add to Wishlist
                            </button>

                            <a href="{{ auth()->check() ? route('courses.checkout', $course->id) : route('login') }}" class="edux-cd-sidebar__gift">
                                <i class="fa-solid fa-gift"></i> Gift this course to a friend
                            </a>

                            <div class="edux-cd-sidebar__divider"></div>

                            <div class="edux-cd-sidebar__includes-title">THIS COURSE INCLUDES</div>
                            <ul class="edux-cd-sidebar__includes-list">
                                @if($course->requiresApplicantForm())
                                    <li>
                                        <i class="fa-solid fa-chalkboard-user"></i>
                                        <span>{{ $course->isHybrid() ? 'Hybrid (Campus + Online LMS)' : 'Face-to-Face Campus Delivery' }}</span>
                                    </li>
                                    @if($course->defaultBranch)
                                        <li>
                                            <i class="fa-solid fa-location-dot"></i>
                                            <span>{{ $course->defaultBranch->name }}{{ $course->defaultVenue ? ' · ' . $course->defaultVenue->name : '' }}</span>
                                        </li>
                                    @endif
                                    <li>
                                        <i class="fa-solid fa-barcode"></i>
                                        <span>GePG Control Number &amp; Installment Plans</span>
                                    </li>
                                    <li>
                                        <i class="fa-solid fa-clipboard-user"></i>
                                        <span>Min {{ $course->attendance_threshold ?? 75 }}% Attendance Clearance</span>
                                    </li>
                                @else
                                    <li>
                                        <i class="fa-regular fa-circle-play"></i>
                                        <span>{{ $lessonCount }} {{ $lessonCount === 1 ? 'lesson' : 'lessons' }}</span>
                                    </li>
                                    <li>
                                        <i class="fa-regular fa-clock"></i>
                                        <span>{{ $formattedDuration }} total duration</span>
                                    </li>
                                @endif
                                <li>
                                    <i class="fa-solid fa-signal"></i>
                                    <span>{{ ucfirst($course->difficulty ?? 'Beginner') }} level</span>
                                </li>
                                <li>
                                    <i class="fa-solid fa-award"></i>
                                    <span>Certificate of completion</span>
                                </li>
                            </ul>

                            @if($course->requiresApplicantForm())
                                @php
                                    $upcomingCohorts = \App\Models\CourseAssignment::withoutGlobalScope('branch')
                                        ->with(['branch:id,name', 'venue:id,name'])
                                        ->where('course_id', $course->id)
                                        ->where('status', 'active')
                                        ->orderBy('start_date')
                                        ->take(3)
                                        ->get();
                                @endphp
                                @if($upcomingCohorts->isNotEmpty())
                                    <div class="edux-cd-sidebar__divider"></div>
                                    <div class="edux-cd-sidebar__includes-title">UPCOMING COHORTS</div>
                                    <ul class="edux-cd-sidebar__includes-list">
                                        @foreach($upcomingCohorts as $cohort)
                                            <li>
                                                <i class="fa-regular fa-calendar"></i>
                                                <span>
                                                    {{ optional($cohort->start_date)->format('M d, Y') }}
                                                    &bull; {{ ucfirst($cohort->session) }}
                                                    @if($cohort->branch) ({{ $cohort->branch->name }}) @endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endif
                        </aside>
                    </div>
                </div>

                <!-- 3. Related Courses Section -->
                @if(isset($relatedCourses) && $relatedCourses->count() > 0)
                    <div class="edux-cd-related">
                        <div class="edux-cd-related__eyebrow">MORE LIKE THIS</div>
                        <h2 class="edux-cd-related__title">Related Courses</h2>
                        <div class="row g-4">
                            @include('courses.partials.course-list', ['courses' => $relatedCourses])
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <!-- Video Preview Modal -->
    <div id="lessonVideoModal" class="modal fade" tabindex="-1" aria-labelledby="lessonVideoModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lessonVideoModalTitle">{{ __('frontend.lesson_preview') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="lessonVideoContainer">
                        <video id="lessonVideoPlayer" controls style="width: 100%; max-height: 500px;" class="d-none">
                            Your browser does not support the video tag.
                        </video>
                        <div id="lessonVideoIframe" class="d-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Intro Video Modal -->
    <div id="introVideoModal" class="modal fade" tabindex="-1" aria-labelledby="introVideoModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="introVideoModalTitle">{{ __('frontend.course_intro') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="introVideoContainer">
                        <video id="introVideoPlayer" controls style="width: 100%; max-height: 500px;">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                const videoModal = new bootstrap.Modal(document.getElementById('lessonVideoModal'));
                const videoPlayer = document.getElementById('lessonVideoPlayer');
                const videoIframe = document.getElementById('lessonVideoIframe');
                const modalTitle = document.getElementById('lessonVideoModalTitle');

                $('.lesson-preview-play').on('click', function(e) {
                    e.preventDefault();
                    const videoType = $(this).data('video-type');
                    const videoUrl = $(this).data('video-url');
                    const lessonTitle = $(this).data('lesson-title');

                    modalTitle.textContent = lessonTitle || 'Lesson Preview';

                    if (videoType === 'url') {
                        let embedUrl = videoUrl;
                        if (videoUrl.includes('youtube.com/watch')) {
                            embedUrl = videoUrl.replace('watch?v=', 'embed/').split('&')[0];
                        } else if (videoUrl.includes('youtu.be/')) {
                            embedUrl = videoUrl.replace('youtu.be/', 'youtube.com/embed/');
                        } else if (videoUrl.includes('vimeo.com/')) {
                            const vimeoId = videoUrl.split('vimeo.com/')[1].split('?')[0];
                            embedUrl = 'https://player.vimeo.com/video/' + vimeoId;
                        }

                        videoPlayer.classList.add('d-none');
                        videoIframe.classList.remove('d-none');
                        videoIframe.innerHTML = '<iframe src="' + embedUrl +
                            '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="width: 100%; height: 460px;"></iframe>';
                    } else {
                        videoIframe.classList.add('d-none');
                        videoPlayer.classList.remove('d-none');
                        videoPlayer.src = videoUrl;
                    }

                    videoModal.show();
                });

                $('#lessonVideoModal').on('hidden.bs.modal', function() {
                    videoPlayer.pause();
                    videoPlayer.src = '';
                    videoIframe.innerHTML = '';
                });

                const introVideoModal = new bootstrap.Modal(document.getElementById('introVideoModal'));
                const introVideoPlayer = document.getElementById('introVideoPlayer');

                $('.intro-video-play-btn').on('click', function(e) {
                    e.preventDefault();
                    const introVideoUrl = $(this).data('intro-video');
                    introVideoPlayer.src = introVideoUrl;
                    introVideoModal.show();
                });

                $('#introVideoModal').on('hidden.bs.modal', function() {
                    introVideoPlayer.pause();
                    introVideoPlayer.src = '';
                });

                $('.rating-star').on('click', function() {
                    const rating = $(this).data('rating');
                    const container = $(this).closest('.rating-input');
                    const input = container.siblings('#ratingInput');
                    input.val(rating);

                    container.find('.rating-star').each(function() {
                        const starRating = $(this).data('rating');
                        $(this).css('color', starRating <= rating ? '#f59e0b' : '#cbd5e1');
                    });
                });
            });
        </script>
    @endpush
@endsection
