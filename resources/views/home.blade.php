@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')

@push('styles')
<style>
    /* ==========================================================================
       Home Page Redesign (Matching Modern LMS Reference Layout & Brand Palette)
       ========================================================================== */
    .lmsout-home {
        font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: #0f172a;
        background-color: #ffffff;
    }

    /* 1. Gradient Hero Section */
    .lmsout-home-hero {
        background: linear-gradient(135deg, var(--theme) 0%, var(--theme-2, #228ddd) 100%);
        padding: 56px 0 68px;
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }
    .lmsout-home-hero__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.24);
        color: #ffffff;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 999px;
        margin-bottom: 16px;
    }
    .lmsout-home-hero__eyebrow-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--theme-yellow, #f4ed2d);
    }
    .lmsout-home-hero__title {
        font-size: 38px;
        font-weight: 800;
        line-height: 1.16;
        letter-spacing: -0.025em;
        color: #ffffff;
        margin: 0 0 14px;
    }
    .lmsout-home-hero__title span {
        color: var(--theme-yellow, #f4ed2d);
        display: block;
    }
    .lmsout-home-hero__desc {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.86);
        line-height: 1.6;
        margin: 0 0 22px;
        max-width: 500px;
    }
    .lmsout-home-hero__search {
        background: #ffffff;
        border-radius: 999px;
        padding: 5px 5px 5px 18px;
        display: flex;
        align-items: center;
        gap: 8px;
        max-width: 490px;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.16);
        margin-bottom: 14px;
    }
    .lmsout-home-hero__search i {
        color: #94a3b8;
        font-size: 13px;
    }
    .lmsout-home-hero__search input {
        flex: 1;
        border: 0;
        outline: none;
        font-size: 13px;
        color: #0f172a;
        background: transparent;
        min-width: 0;
    }
    .lmsout-home-hero__search button {
        background: var(--theme);
        color: #ffffff;
        border: 0;
        border-radius: 999px;
        padding: 8px 20px;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        transition: opacity 0.15s ease;
    }
    .lmsout-home-hero__search button:hover {
        opacity: 0.92;
    }
    .lmsout-home-hero__tags {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 18px;
        font-size: 11.5px;
        color: rgba(255, 255, 255, 0.78);
    }
    .lmsout-home-hero__tag {
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.22);
        color: #ffffff;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 500;
        text-decoration: none;
        transition: background-color 0.15s ease;
    }
    .lmsout-home-hero__tag:hover {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
    }
    .lmsout-home-hero__trust {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }
    .lmsout-home-hero__avatars {
        display: flex;
        align-items: center;
    }
    .lmsout-home-hero__avatar-dot {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        border: 2px solid #ffffff;
        color: #ffffff;
        font-size: 10px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: -7px;
    }
    .lmsout-home-hero__avatar-dot:first-child {
        margin-left: 0;
    }
    .lmsout-home-hero__trust-text {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.92);
    }
    .lmsout-home-hero__trust-stars {
        color: #fbbf24;
        font-size: 11px;
        margin-right: 4px;
    }
    .lmsout-home-hero__mini-stats {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 22px;
        padding-top: 8px;
    }
    .lmsout-home-hero__mini-stat {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: rgba(255, 255, 255, 0.85);
    }
    .lmsout-home-hero__mini-stat-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.16);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: #ffffff;
    }
    .lmsout-home-hero__mini-stat strong {
        display: block;
        font-size: 13px;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.1;
    }

    /* Right Hero Showcase Card */
    .lmsout-home-hero__visual {
        position: relative;
        max-width: 440px;
        margin: 0 auto;
    }
    .lmsout-home-hero__card {
        background: #ffffff;
        border-radius: 18px;
        padding: 12px;
        box-shadow: 0 24px 48px rgba(0, 0, 0, 0.25);
        transform: rotate(1.5deg);
        color: #0f172a;
    }
    .lmsout-home-hero__card-thumb {
        position: relative;
        height: 200px;
        border-radius: 12px;
        overflow: hidden;
        background: #0f172a;
        margin-bottom: 12px;
    }
    .lmsout-home-hero__card-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .lmsout-home-hero__card-play {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.95);
        color: var(--theme);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
        text-decoration: none;
    }
    .lmsout-home-hero__card-body {
        padding: 4px 6px 6px;
    }
    .lmsout-home-hero__card-title {
        font-size: 14.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 6px;
        text-decoration: none;
        display: block;
    }
    .lmsout-home-hero__card-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: #64748b;
    }
    .lmsout-home-hero__callout {
        position: absolute;
        background: #ffffff;
        color: #0f172a;
        border-radius: 12px;
        padding: 8px 12px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        z-index: 3;
    }
    .lmsout-home-hero__callout--tr {
        top: -12px;
        right: -10px;
    }
    .lmsout-home-hero__callout--bl {
        bottom: 18px;
        left: -18px;
    }
    .lmsout-home-hero__callout--br {
        bottom: -14px;
        right: 12px;
    }
    .lmsout-home-hero__callout-icon {
        width: 26px;
        height: 26px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        flex-shrink: 0;
    }

    /* Shared Section Headers */
    .lmsout-sec {
        padding: 56px 0;
    }
    .lmsout-sec--gray {
        background-color: #f8fafc;
    }
    .lmsout-sec__eyebrow {
        display: inline-block;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--theme);
        background: rgba(1, 59, 166, 0.08);
        padding: 3px 10px;
        border-radius: 999px;
        margin-bottom: 8px;
    }
    .lmsout-sec__title {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
        margin: 0;
    }
    .lmsout-sec__header-row {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 24px;
    }
    .lmsout-sec__link {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--theme);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .lmsout-sec__link:hover {
        text-decoration: underline;
        color: var(--theme);
    }

    /* 2. Why Learn With Us */
    .lmsout-why-card {
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 14px;
        padding: 20px;
        height: 100%;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.02);
    }
    .lmsout-why-card__icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: rgba(1, 59, 166, 0.08);
        color: var(--theme);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        margin-bottom: 12px;
    }
    .lmsout-why-card__title {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 5px;
    }
    .lmsout-why-card__desc {
        font-size: 12px;
        color: #64748b;
        line-height: 1.5;
        margin: 0;
    }

    /* 3. Explore Top Categories */
    .lmsout-cat-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-decoration: none;
        transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
    }
    .lmsout-cat-item:hover {
        border-color: var(--theme);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
    }
    .lmsout-cat-item__left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .lmsout-cat-item__icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(1, 59, 166, 0.08);
        color: var(--theme);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }
    .lmsout-cat-item--highlight .lmsout-cat-item__icon {
        background: var(--theme);
        color: #ffffff;
    }
    .lmsout-cat-item__name {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 2px;
    }
    .lmsout-cat-item--highlight .lmsout-cat-item__name {
        color: var(--theme);
    }
    .lmsout-cat-item__count {
        font-size: 11.5px;
        color: #94a3b8;
        margin: 0;
    }
    .lmsout-cat-item__arrow {
        font-size: 11px;
        color: #cbd5e1;
    }

    /* 4 & 5. Course Cards (Featured & Most Popular) */
    .lmsout-catalog-card {
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
    .lmsout-catalog-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }
    .lmsout-catalog-card__thumb {
        position: relative;
        height: 162px;
        background: #0f172a;
        overflow: hidden;
    }
    .lmsout-catalog-card__thumb-link {
        display: block;
        width: 100%;
        height: 100%;
    }
    .lmsout-catalog-card__thumb-link img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }
    .lmsout-catalog-card:hover .lmsout-catalog-card__thumb-link img {
        transform: scale(1.04);
    }
    .lmsout-catalog-card__badges-tl {
        position: absolute;
        top: 10px;
        left: 10px;
        right: 58px;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        z-index: 2;
        pointer-events: none;
    }
    .lmsout-catalog-card__badge-cat {
        background: rgba(255, 255, 255, 0.96);
        color: #0f172a;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
    }
    .lmsout-catalog-card__badge-sub {
        background: var(--theme);
        color: #ffffff;
        font-size: 9.5px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .lmsout-catalog-card__badges-tr {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 2;
    }
    .lmsout-catalog-card__badge-free {
        background: #10b981;
        color: #ffffff;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 999px;
    }
    .lmsout-catalog-card__badge-discount {
        background: #ef4444;
        color: #ffffff;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 999px;
    }
    .lmsout-catalog-card__wish-btn {
        position: absolute;
        bottom: 10px;
        left: 10px;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.95);
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 10.5px;
        text-decoration: none;
    }
    .lmsout-catalog-card__body {
        padding: 13px 14px 14px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    .lmsout-catalog-card__rating-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }
    .lmsout-catalog-card__stars {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        font-size: 10.5px;
    }
    .lmsout-catalog-card__stars .is-filled { color: #f59e0b; }
    .lmsout-catalog-card__stars .is-empty { color: #e2e8f0; }
    .lmsout-catalog-card__rating-num {
        font-size: 11.5px;
        font-weight: 700;
        color: #334155;
        margin-left: 3px;
    }
    .lmsout-catalog-card__students {
        font-size: 11px;
        color: #64748b;
    }
    .lmsout-catalog-card__title {
        font-size: 13.5px;
        font-weight: 700;
        line-height: 1.38;
        margin: 0 0 8px;
        color: #0f172a;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 38px;
    }
    .lmsout-catalog-card__title a {
        color: inherit;
        text-decoration: none;
    }
    .lmsout-catalog-card__title a:hover {
        color: var(--theme);
    }
    .lmsout-catalog-card__instructor {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 9px;
    }
    .lmsout-catalog-card__avatar {
        width: 19px;
        height: 19px;
        border-radius: 50%;
        object-fit: cover;
    }
    .lmsout-catalog-card__avatar-initials {
        width: 19px;
        height: 19px;
        border-radius: 50%;
        background: var(--theme);
        color: #ffffff;
        font-size: 8.5px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .lmsout-catalog-card__instructor-name {
        font-size: 11.5px;
        color: #64748b;
    }
    .lmsout-catalog-card__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        font-size: 11px;
        color: #64748b;
        padding-bottom: 10px;
        margin-bottom: 10px;
        border-bottom: 1px solid #f1f5f9;
    }
    .lmsout-catalog-card__meta span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .lmsout-catalog-card__footer {
        margin-top: auto;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 6px;
    }
    .lmsout-catalog-card__sub-note {
        font-size: 10px;
        font-weight: 600;
        color: #059669;
        margin-bottom: 2px;
    }
    .lmsout-catalog-card__price-row {
        display: flex;
        align-items: baseline;
        gap: 5px;
    }
    .lmsout-catalog-card__price {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }
    .lmsout-catalog-card__price.is-free {
        color: #059669;
    }
    .lmsout-catalog-card__price-old {
        font-size: 11.5px;
        color: #94a3b8;
        text-decoration: line-through;
    }
    .lmsout-catalog-card__view-btn {
        border: 1.5px solid var(--theme);
        background: #ffffff;
        color: var(--theme);
        border-radius: 7px;
        padding: 4px 12px;
        font-size: 11.5px;
        font-weight: 600;
        text-decoration: none;
    }
    .lmsout-catalog-card__view-btn:hover {
        background: var(--theme);
        color: #ffffff;
    }

    /* 6. Trusted by learners worldwide */
    .lmsout-stats-box {
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        padding: 28px 20px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
    }
    .lmsout-stats-item {
        text-align: center;
        padding: 8px;
    }
    .lmsout-stats-item__icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: var(--theme);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        margin-bottom: 10px;
    }
    .lmsout-stats-item__num {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
        margin-bottom: 3px;
    }
    .lmsout-stats-item__label {
        font-size: 11.5px;
        color: #64748b;
        margin: 0;
    }

    /* 7. Start learning in 3 simple steps */
    .lmsout-step-item {
        text-align: center;
        padding: 10px 18px;
    }
    .lmsout-step-item__icon-wrap {
        position: relative;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--theme);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        margin-bottom: 14px;
        box-shadow: 0 8px 20px rgba(1, 59, 166, 0.22);
    }
    .lmsout-step-item__num {
        position: absolute;
        top: -4px;
        right: -4px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ffffff;
        color: var(--theme);
        border: 1.5px solid var(--theme);
        font-size: 10px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .lmsout-step-item__title {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 5px;
    }
    .lmsout-step-item__desc {
        font-size: 12px;
        color: #64748b;
        line-height: 1.5;
        max-width: 260px;
        margin: 0 auto;
    }

    /* 8. Learn from top instructors */
    .lmsout-inst-grid {
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(15, 23, 42, 0.02);
    }
    .lmsout-inst-cell {
        padding: 24px 16px;
        text-align: center;
        border-bottom: 1px solid #f8fafc;
        border-right: 1px solid #f8fafc;
        height: 100%;
    }
    .lmsout-inst-cell__avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        object-fit: cover;
        border: 2.5px solid var(--theme);
        padding: 2px;
        margin-bottom: 10px;
    }
    .lmsout-inst-cell__initials {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        color: #ffffff;
        font-size: 20px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        border: 2.5px solid rgba(1, 59, 166, 0.2);
    }
    .lmsout-inst-cell__name {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        text-decoration: none;
        display: block;
        margin-bottom: 2px;
    }
    .lmsout-inst-cell__name:hover {
        color: var(--theme);
    }
    .lmsout-inst-cell__role {
        font-size: 11.5px;
        color: #64748b;
        margin-bottom: 6px;
    }
    .lmsout-inst-cell__meta {
        font-size: 11px;
        color: #94a3b8;
    }

    /* 9. What learners say */
    .lmsout-testi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .lmsout-testi-card__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    .lmsout-testi-card__stars {
        color: #f59e0b;
        font-size: 11px;
    }
    .lmsout-testi-card__quote-icon {
        color: #e2e8f0;
        font-size: 18px;
    }
    .lmsout-testi-card__text {
        font-size: 12.5px;
        color: #475569;
        line-height: 1.6;
        margin-bottom: 16px;
    }
    .lmsout-testi-card__author {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .lmsout-testi-card__avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        object-fit: cover;
    }
    .lmsout-testi-card__initials {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--theme);
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .lmsout-testi-card__name {
        font-size: 12.5px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .lmsout-testi-card__course {
        font-size: 11px;
        color: #94a3b8;
    }

    /* 11. Instructor CTA Banner + Inbox Card + Footer Newsletter */
    .lmsout-teach-cta {
        background: linear-gradient(135deg, var(--theme) 0%, var(--theme-2, #228ddd) 100%);
        border-radius: 18px;
        padding: 36px 28px;
        text-align: center;
        color: #ffffff;
        box-shadow: 0 14px 32px rgba(1, 59, 166, 0.22);
        margin-bottom: 24px;
    }
    .lmsout-teach-cta__title {
        font-size: 20px;
        font-weight: 800;
        color: #ffffff;
        margin: 0 0 6px;
    }
    .lmsout-teach-cta__desc {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.86);
        max-width: 480px;
        margin: 0 auto 16px;
    }
    .lmsout-teach-cta__btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ffffff;
        color: var(--theme);
        font-size: 12.5px;
        font-weight: 700;
        padding: 9px 20px;
        border-radius: 8px;
        text-decoration: none;
    }
    .lmsout-teach-cta__btn:hover {
        background: var(--theme-yellow, #f4ed2d);
        color: #0f172a;
    }

    .lmsout-inbox-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
    }
    .lmsout-inbox-box__left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .lmsout-inbox-box__icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--theme);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }
    .lmsout-inbox-box__title {
        font-size: 14.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 2px;
    }
    .lmsout-inbox-box__desc {
        font-size: 12px;
        color: #64748b;
        margin: 0;
    }
    .lmsout-inbox-box__form {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        max-width: 360px;
    }
    .lmsout-inbox-box__input {
        flex: 1;
        height: 38px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0 12px;
        font-size: 12.5px;
        outline: none;
    }
    .lmsout-inbox-box__btn {
        height: 38px;
        padding: 0 18px;
        border-radius: 8px;
        border: 0;
        background: var(--theme);
        color: #ffffff;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
    }

    .lmsout-newsletter-strip {
        background: #0b0f19;
        padding: 36px 0 12px;
    }
    .lmsout-newsletter-banner {
        background: linear-gradient(135deg, var(--theme) 0%, var(--theme-2, #228ddd) 100%);
        border-radius: 16px;
        padding: 28px 36px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
    }
    .lmsout-newsletter-banner__title {
        font-size: 20px;
        font-weight: 800;
        color: #ffffff;
        margin: 0 0 4px;
    }
    .lmsout-newsletter-banner__desc {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.85);
        margin: 0;
    }
    .lmsout-newsletter-banner__form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        max-width: 440px;
    }
    .lmsout-newsletter-banner__input {
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
    .lmsout-newsletter-banner__btn {
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
    $showcaseCourse = $featuredCourses->first();
    $instColors = ['#0d9488', '#013ba6', '#dc2626', '#7c3aed', '#0284c7', '#ea580c'];
@endphp

<div class="lmsout-home">
    <!-- 1. Hero Section -->
    <section class="lmsout-home-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="lmsout-home-hero__eyebrow">
                        <span class="lmsout-home-hero__eyebrow-dot"></span>
                        <span>{{ $hero['preheading'] }}</span>
                    </div>

                    <h1 class="lmsout-home-hero__title">
                        Learn without limits,
                        <span>grow without fear.</span>
                    </h1>

                    <p class="lmsout-home-hero__desc">
                        {{ $hero['description'] }}
                    </p>

                    <form action="{{ route('courses') }}" method="GET" class="lmsout-home-hero__search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" placeholder="What do you want to learn today?">
                        <button type="submit">{{ __('frontend.search') }}</button>
                    </form>

                    <div class="lmsout-home-hero__tags">
                        <span>Popular:</span>
                        @foreach($topCategories->take(4) as $catChip)
                            <a href="{{ route('courses', ['category' => $catChip['name']]) }}" class="lmsout-home-hero__tag">
                                {{ $catChip['name'] }}
                            </a>
                        @endforeach
                    </div>

                    <div class="lmsout-home-hero__trust">
                        <div class="lmsout-home-hero__avatars">
                            <span class="lmsout-home-hero__avatar-dot" style="background: #10b981;">AL</span>
                            <span class="lmsout-home-hero__avatar-dot" style="background: #f59e0b;">MK</span>
                            <span class="lmsout-home-hero__avatar-dot" style="background: #ec4899;">SR</span>
                            <span class="lmsout-home-hero__avatar-dot" style="background: #6366f1;">JD</span>
                        </div>
                        <div class="lmsout-home-hero__trust-text">
                            <span class="lmsout-home-hero__trust-stars">
                                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                            </span>
                            <strong>4.9/5</strong> from {{ number_format(max(120, $stats['students'] * 15)) }}+ learners
                        </div>
                    </div>

                    <div class="lmsout-home-hero__mini-stats">
                        <div class="lmsout-home-hero__mini-stat">
                            <span class="lmsout-home-hero__mini-stat-icon"><i class="fa-solid fa-book-open"></i></span>
                            <div>
                                <strong>{{ number_format($stats['courses']) }}+</strong>
                                <span>{{ __('frontend.courses') }}</span>
                            </div>
                        </div>
                        <div class="lmsout-home-hero__mini-stat">
                            <span class="lmsout-home-hero__mini-stat-icon"><i class="fa-solid fa-user-graduate"></i></span>
                            <div>
                                <strong>{{ number_format($stats['students']) }}+</strong>
                                <span>{{ __('frontend.students') }}</span>
                            </div>
                        </div>
                        <div class="lmsout-home-hero__mini-stat">
                            <span class="lmsout-home-hero__mini-stat-icon"><i class="fa-solid fa-chalkboard-user"></i></span>
                            <div>
                                <strong>{{ number_format($stats['instructors']) }}+</strong>
                                <span>{{ __('frontend.instructors') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="lmsout-home-hero__visual">
                        <div class="lmsout-home-hero__callout lmsout-home-hero__callout--tr">
                            <span class="lmsout-home-hero__callout-icon" style="background: rgba(1, 59, 166, 0.1); color: var(--theme);">
                                <i class="fa-solid fa-award"></i>
                            </span>
                            <div>
                                <div class="fw-bold" style="font-size: 11px;">Verified Certificate</div>
                                <div class="text-muted" style="font-size: 10px;">QR-enabled on completion</div>
                            </div>
                        </div>

                        <div class="lmsout-home-hero__card">
                            <div class="lmsout-home-hero__card-thumb">
                                <img src="{{ $showcaseCourse ? $showcaseCourse->thumbnail : asset('assets/front/img/home-1/courses/courses-01.jpg') }}"
                                     alt="{{ $showcaseCourse ? $showcaseCourse->title : site_name() }}">
                                <a href="{{ $showcaseCourse ? route('courses.show', $showcaseCourse->id) : route('courses') }}" class="lmsout-home-hero__card-play" aria-label="View Course">
                                    <i class="fa-solid fa-play"></i>
                                </a>
                            </div>
                            <div class="lmsout-home-hero__card-body">
                                <a href="{{ $showcaseCourse ? route('courses.show', $showcaseCourse->id) : route('courses') }}" class="lmsout-home-hero__card-title">
                                    {{ $showcaseCourse ? $showcaseCourse->title : 'Full-Stack Web Engineering Bootcamp' }}
                                </a>
                                <div class="lmsout-home-hero__card-meta">
                                    <span><i class="fa-regular fa-user me-1"></i> {{ $showcaseCourse?->instructor?->name ?? 'Expert Instructor' }}</span>
                                    <span class="fw-bold" style="color: #f59e0b;"><i class="fa-solid fa-star"></i> {{ number_format($showcaseCourse?->average_rating ?? 5.0, 1) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="lmsout-home-hero__callout lmsout-home-hero__callout--bl">
                            <span class="lmsout-home-hero__callout-icon" style="background: #dcfce7; color: #059669;">
                                <i class="fa-solid fa-circle-check"></i>
                            </span>
                            <div>
                                <div class="fw-bold" style="font-size: 11px;">{{ number_format($stats['enrollments']) }}+ Enrolled</div>
                                <div class="text-muted" style="font-size: 10px;">Active learning community</div>
                            </div>
                        </div>

                        <div class="lmsout-home-hero__callout lmsout-home-hero__callout--br">
                            <span class="lmsout-home-hero__callout-icon" style="background: #fef3c7; color: #d97706;">
                                <i class="fa-solid fa-bolt"></i>
                            </span>
                            <div>
                                <div class="fw-bold" style="font-size: 11px;">Verified Certificates</div>
                                <div class="text-muted" style="font-size: 10px;">Official course credentials</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Why learn with us -->
    <section class="lmsout-sec">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="lmsout-sec__title">Why learn with us</h2>
            </div>
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <div class="lmsout-why-card">
                        <div class="lmsout-why-card__icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                        <h3 class="lmsout-why-card__title">Expert Instructors</h3>
                        <p class="lmsout-why-card__desc">Learn directly from seasoned industry practitioners and domain specialists.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="lmsout-why-card">
                        <div class="lmsout-why-card__icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <h3 class="lmsout-why-card__title">Self-Paced & Live</h3>
                        <p class="lmsout-why-card__desc">Study on your schedule with lifetime video access and interactive live classes.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="lmsout-why-card">
                        <div class="lmsout-why-card__icon"><i class="fa-solid fa-certificate"></i></div>
                        <h3 class="lmsout-why-card__title">Verified Certificates</h3>
                        <p class="lmsout-why-card__desc">Earn shareable QR-verified certificates upon completing your courses.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="lmsout-why-card">
                        <div class="lmsout-why-card__icon"><i class="fa-solid fa-layer-group"></i></div>
                        <h3 class="lmsout-why-card__title">Hands-On Projects</h3>
                        <p class="lmsout-why-card__desc">Reinforce every concept with real-world assignments, quizzes, and feedback.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Explore top categories -->
    <section class="lmsout-sec lmsout-sec--gray">
        <div class="container">
            <div class="mb-4">
                <span class="lmsout-sec__eyebrow">CATEGORIES</span>
                <h2 class="lmsout-sec__title">Explore top categories</h2>
            </div>
            <div class="row g-3">
                @foreach($topCategories->take(5) as $catItem)
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('courses', ['category' => $catItem['name']]) }}" class="lmsout-cat-item">
                            <div class="lmsout-cat-item__left">
                                <span class="lmsout-cat-item__icon"><i class="{{ $catItem['icon_class'] }}"></i></span>
                                <div>
                                    <h3 class="lmsout-cat-item__name">{{ $catItem['name'] }}</h3>
                                    <p class="lmsout-cat-item__count">{{ $catItem['courses_count'] }} {{ $catItem['courses_count'] === 1 ? 'course' : 'courses' }}</p>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right lmsout-cat-item__arrow"></i>
                        </a>
                    </div>
                @endforeach

                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('courses') }}" class="lmsout-cat-item lmsout-cat-item--highlight">
                        <div class="lmsout-cat-item__left">
                            <span class="lmsout-cat-item__icon"><i class="fa-solid fa-table-cells-large"></i></span>
                            <div>
                                <h3 class="lmsout-cat-item__name">Browse All Categories</h3>
                                <p class="lmsout-cat-item__count">Explore full catalog &rarr;</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right lmsout-cat-item__arrow"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. Featured courses (4-Column Grid) -->
    <section class="lmsout-sec">
        <div class="container">
            <div class="lmsout-sec__header-row">
                <div>
                    <span class="lmsout-sec__eyebrow">HANDPICKED FOR YOU</span>
                    <h2 class="lmsout-sec__title">Featured courses</h2>
                </div>
                <a href="{{ route('courses') }}" class="lmsout-sec__link">
                    View all <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <div class="row g-4">
                @include('courses.partials.course-list', [
                    'courses' => $featuredCourses,
                    'colClass' => 'col-xl-3 col-lg-4 col-md-6',
                ])
            </div>
        </div>
    </section>

    <!-- 5. Most popular (4-Column Grid) -->
    <section class="lmsout-sec lmsout-sec--gray">
        <div class="container">
            <div class="lmsout-sec__header-row">
                <div>
                    <span class="lmsout-sec__eyebrow">TRENDING NOW</span>
                    <h2 class="lmsout-sec__title">Most popular</h2>
                </div>
                <a href="{{ route('courses', ['sort' => 'popular']) }}" class="lmsout-sec__link">
                    See all <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <div class="row g-4">
                @include('courses.partials.course-list', [
                    'courses' => $popularCourses ?? $featuredCourses,
                    'colClass' => 'col-xl-3 col-lg-4 col-md-6',
                ])
            </div>
        </div>
    </section>

    <!-- 6. Trusted by learners worldwide -->
    <section class="lmsout-sec">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="lmsout-sec__title">Trusted by learners worldwide</h2>
            </div>
            <div class="lmsout-stats-box">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="lmsout-stats-item">
                            <span class="lmsout-stats-item__icon"><i class="fa-solid fa-book-open"></i></span>
                            <div class="lmsout-stats-item__num">{{ number_format($stats['courses']) }}</div>
                            <p class="lmsout-stats-item__label">{{ __('frontend.courses_published') }}</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="lmsout-stats-item">
                            <span class="lmsout-stats-item__icon"><i class="fa-solid fa-user-graduate"></i></span>
                            <div class="lmsout-stats-item__num">{{ number_format($stats['students']) }}</div>
                            <p class="lmsout-stats-item__label">Active Learners</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="lmsout-stats-item">
                            <span class="lmsout-stats-item__icon"><i class="fa-solid fa-chalkboard-user"></i></span>
                            <div class="lmsout-stats-item__num">{{ number_format($stats['instructors']) }}</div>
                            <p class="lmsout-stats-item__label">{{ __('frontend.expert_instructors') }}</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="lmsout-stats-item">
                            <span class="lmsout-stats-item__icon"><i class="fa-solid fa-award"></i></span>
                            <div class="lmsout-stats-item__num">{{ number_format($stats['enrollments']) }}</div>
                            <p class="lmsout-stats-item__label">{{ __('frontend.enrollments_completed') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. Start learning in 3 simple steps -->
    <section class="lmsout-sec lmsout-sec--gray">
        <div class="container">
            <div class="text-center mb-4">
                <span class="lmsout-sec__eyebrow">HOW IT WORKS</span>
                <h2 class="lmsout-sec__title">Start learning in 3 simple steps</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="lmsout-step-item">
                        <div class="lmsout-step-item__icon-wrap">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span class="lmsout-step-item__num">1</span>
                        </div>
                        <h3 class="lmsout-step-item__title">Find your course</h3>
                        <p class="lmsout-step-item__desc">Explore courses across technology, design, business, and data science.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="lmsout-step-item">
                        <div class="lmsout-step-item__icon-wrap">
                            <i class="fa-solid fa-circle-play"></i>
                            <span class="lmsout-step-item__num">2</span>
                        </div>
                        <h3 class="lmsout-step-item__title">Learn at your pace</h3>
                        <p class="lmsout-step-item__desc">Watch HD lessons, practice with quizzes, and complete hands-on assignments.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="lmsout-step-item">
                        <div class="lmsout-step-item__icon-wrap">
                            <i class="fa-solid fa-award"></i>
                            <span class="lmsout-step-item__num">3</span>
                        </div>
                        <h3 class="lmsout-step-item__title">Get certified</h3>
                        <p class="lmsout-step-item__desc">Receive an official verifiable certificate to highlight your new skills.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 8. Learn from top instructors -->
    <section class="lmsout-sec">
        <div class="container">
            <div class="text-center mb-4">
                <span class="lmsout-sec__eyebrow">INSTRUCTORS</span>
                <h2 class="lmsout-sec__title mb-1">Learn from top instructors</h2>
                <a href="{{ route('instructors.index') }}" class="lmsout-sec__link">
                    View all <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <div class="lmsout-inst-grid">
                <div class="row g-0">
                    @foreach($featuredInstructors as $instIdx => $instructor)
                        @php
                            $instInitials = collect(explode(' ', trim($instructor['name'])))
                                ->filter()
                                ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->take(2)
                                ->implode('');
                            $instBg = $instColors[$instIdx % count($instColors)];
                        @endphp
                        <div class="col-lg-4 col-md-6">
                            <div class="lmsout-inst-cell">
                                @if(!empty($instructor['avatar']))
                                    <img src="{{ $instructor['avatar'] }}" alt="{{ $instructor['name'] }}" class="lmsout-inst-cell__avatar">
                                @else
                                    <span class="lmsout-inst-cell__initials" style="background: {{ $instBg }};">
                                        {{ $instInitials ?: 'IN' }}
                                    </span>
                                @endif
                                <a href="{{ route('instructors.show', $instructor['id']) }}" class="lmsout-inst-cell__name">
                                    {{ $instructor['name'] }}
                                </a>
                                <div class="lmsout-inst-cell__role">{{ $instructor['headline'] }}</div>
                                <div class="lmsout-inst-cell__meta">
                                    {{ $instructor['courses_count'] }} {{ $instructor['courses_count'] === 1 ? 'course' : 'courses' }}
                                    &bull; {{ number_format($instructor['students_count']) }} {{ $instructor['students_count'] === 1 ? 'student' : 'students' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- 9. What learners say -->
    <section class="lmsout-sec lmsout-sec--gray">
        <div class="container">
            <div class="text-center mb-4">
                <span class="lmsout-sec__eyebrow">TESTIMONIALS</span>
                <h2 class="lmsout-sec__title">What learners say</h2>
            </div>
            <div class="row g-4">
                @forelse($testimonials->take(3) as $tIdx => $testi)
                    @php
                        $tInitials = collect(explode(' ', trim($testi['student_name'])))
                            ->filter()
                            ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->take(2)
                            ->implode('');
                    @endphp
                    <div class="col-lg-4 col-md-6">
                        <div class="lmsout-testi-card">
                            <div>
                                <div class="lmsout-testi-card__top">
                                    <div class="lmsout-testi-card__stars">
                                        @for($s = 1; $s <= 5; $s++)
                                            <i class="fa-solid fa-star"></i>
                                        @endfor
                                    </div>
                                    <i class="fa-solid fa-quote-right lmsout-testi-card__quote-icon"></i>
                                </div>
                                <p class="lmsout-testi-card__text">"{{ $testi['comment'] }}"</p>
                            </div>
                            <div class="lmsout-testi-card__author">
                                <span class="lmsout-testi-card__initials" style="background: {{ $instColors[$tIdx % count($instColors)] }};">
                                    {{ $tInitials ?: 'ST' }}
                                </span>
                                <div>
                                    <div class="lmsout-testi-card__name">{{ $testi['student_name'] }}</div>
                                    <div class="lmsout-testi-card__course">{{ Str::limit($testi['course_title'] ?? 'Verified Learner', 34) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted small">No reviews available yet.</div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- 11. Become Instructor CTA + Inbox Card -->
    <section class="lmsout-sec pt-2">
        <div class="container">
            <div class="lmsout-teach-cta">
                <h2 class="lmsout-teach-cta__title">Ready to share what you know?</h2>
                <p class="lmsout-teach-cta__desc">
                    Become an instructor on {{ site_name() }}, reach learners worldwide, and earn revenue from every course enrollment.
                </p>
                <a href="{{ Route::has('instructor.become') ? route('instructor.become') : route('register') }}" class="lmsout-teach-cta__btn">
                    Start teaching <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="lmsout-inbox-box">
                <div class="lmsout-inbox-box__left">
                    <span class="lmsout-inbox-box__icon"><i class="fa-regular fa-envelope"></i></span>
                    <div>
                        <h3 class="lmsout-inbox-box__title">Get new courses in your inbox</h3>
                        <p class="lmsout-inbox-box__desc">Monthly digest of newly launched courses, study guides, and member offers.</p>
                    </div>
                </div>
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="lmsout-inbox-box__form">
                    @csrf
                    <input type="email" name="email" class="lmsout-inbox-box__input" placeholder="Enter your email..." required>
                    <button type="submit" class="lmsout-inbox-box__btn">Subscribe</button>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection
