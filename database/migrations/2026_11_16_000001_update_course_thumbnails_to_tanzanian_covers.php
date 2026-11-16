<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Updates default/null course and banner cover paths in database to
 * Tanzanian OUT-watermarked thumbnails stored in storage/app/public/courses/images.
 *
 * @author Renatus Bernardo
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courses')) {
            return;
        }

        $demoCovers = [
            'Complete Full-Stack Web Development Bootcamp (Laravel & React)' => 'courses/images/out-web-development.jpg',
            'Applied Generative AI, LLMs & Machine Learning Engineering' => 'courses/images/out-ai-data-science.jpg',
            'Mastering UI/UX Product Design & Figma Design Systems' => 'courses/images/out-uiux-design.jpg',
            'Growth Marketing, SEO & Conversion Rate Optimization Masterclass' => 'courses/images/out-digital-marketing.jpg',
            'Agile Product Management & Tech Leadership Essentials' => 'courses/images/out-business-leadership.jpg',
            'Cloud Native DevOps, Docker, Kubernetes & AWS Architecture' => 'courses/images/out-cloud-networking.jpg',
        ];

        foreach ($demoCovers as $title => $image) {
            DB::table('courses')
                ->where('title', $title)
                ->where(function ($q) {
                    $q->whereNull('featured_image')
                        ->orWhere('featured_image', '')
                        ->orWhere('featured_image', 'like', 'assets/front/img/home-1/courses/%');
                })
                ->update(['featured_image' => $image]);
        }

        $scmsCovers = [
            1 => 'courses/images/out-computer-apps.jpg',
            2 => 'courses/images/out-video-production.jpg',
            3 => 'courses/images/out-campus-ict.jpg',
            4 => 'courses/images/out-uiux-design.jpg',
            5 => 'courses/images/out-hardware-repair.jpg',
            6 => 'courses/images/out-ai-data-science.jpg',
            7 => 'courses/images/out-web-development.jpg',
            8 => 'courses/images/out-web-development.jpg',
            9 => 'courses/images/out-business-leadership.jpg',
            10 => 'courses/images/out-digital-marketing.jpg',
            11 => 'courses/images/out-hardware-repair.jpg',
            12 => 'courses/images/out-computer-apps.jpg',
            13 => 'courses/images/out-cloud-networking.jpg',
            14 => 'courses/images/out-cloud-networking.jpg',
            15 => 'courses/images/out-campus-ict.jpg',
            16 => 'courses/images/out-ai-data-science.jpg',
        ];

        foreach ($scmsCovers as $legacyId => $image) {
            DB::table('courses')
                ->where('legacy_scms_id', $legacyId)
                ->where(function ($q) {
                    $q->whereNull('featured_image')
                        ->orWhere('featured_image', '')
                        ->orWhere('featured_image', 'like', 'assets/front/img/home-1/courses/%');
                })
                ->update(['featured_image' => $image]);
        }

        // Any remaining course without a cover defaults cleanly in DB to the Tanzanian campus cover
        DB::table('courses')
            ->where(function ($q) {
                $q->whereNull('featured_image')->orWhere('featured_image', '');
            })
            ->update(['featured_image' => 'courses/images/out-campus-ict.jpg']);

        if (Schema::hasTable('banners')) {
            DB::table('banners')
                ->where('image_path', 'assets/front/img/home-1/courses/courses-01.jpg')
                ->update(['image_path' => 'courses/images/out-campus-ict.jpg']);
        }
    }

    public function down(): void
    {
        // Non-destructive content update
    }
};
