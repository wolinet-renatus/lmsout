<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Certificate template Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class CertificateTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'orientation',
        'background_image',
        'valid_for_months',
        'canvas_width',
        'canvas_height',
        'elements',
        'custom_css',
        'custom_html',
        'is_default',
        'is_active',
        'issued_count',
        'created_by',
    ];

    protected $casts = [
        'elements' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'valid_for_months' => 'integer',
        'canvas_width' => 'integer',
        'canvas_height' => 'integer',
        'issued_count' => 'integer',
    ];

    /**
     * Booted.
     */
    protected static function booted()
    {
        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name) . '-' . Str::random(5);
            }
        });

        static::saving(function ($template) {
            if ($template->is_default) {
                // Ensure only one template is marked as default
                static::where('id', '!=', $template->id)->update(['is_default' => false]);
            }
        });
    }

    /**
     * Eloquent belongsTo relationship for Courses.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Eloquent belongsTo relationship for Creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the active default template
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first()
            ?? static::where('is_active', true)->first();
    }

    /**
     * 6 Pre-built professional certificate designs
     */
    public static function getPredefinedDesigns(): array
    {
        return [
            'classic_academic' => [
                'name' => 'Classic Academic',
                'description' => 'Traditional university & academy style with navy & gold double border, ornate typography, and dual signatures.',
                'orientation' => 'landscape',
                'elements' => static::classicAcademicElements(),
            ],
            'modern_emerald' => [
                'name' => 'Modern Emerald',
                'description' => 'Prestigious institutional emerald green styling with compliance badges, gold accents, and accredited seal.',
                'orientation' => 'landscape',
                'elements' => static::modernEmeraldElements(),
            ],
            'executive_gold' => [
                'name' => 'Executive Gold',
                'description' => 'Luxury corporate leadership styling with obsidian slate and radiant gold trim for executive programs.',
                'orientation' => 'landscape',
                'elements' => static::executiveGoldElements(),
            ],
            'tech_bootcamp' => [
                'name' => 'Tech & Bootcamp',
                'description' => 'Cutting-edge technology & engineering style with dark midnight slate, cyan digital accents, and QR verification stamp.',
                'orientation' => 'landscape',
                'elements' => static::techBootcampElements(),
            ],
            'creative_vibrant' => [
                'name' => 'Creative Modern',
                'description' => 'Dynamic contemporary design with rich indigo and violet highlights, modern card borders, and elegant layout.',
                'orientation' => 'landscape',
                'elements' => static::creativeModernElements(),
            ],
            'minimalist_elegant' => [
                'name' => 'Minimalist Elegant',
                'description' => 'Swiss-inspired minimalist layout with ample whitespace, refined typography, and unobtrusive accreditation marks.',
                'orientation' => 'landscape',
                'elements' => static::minimalistElements(),
            ],
        ];
    }

    /**
     * Design 1: Classic Academic Elements
     */
    public static function classicAcademicElements(): array
    {
        return [
            ['id' => 'el_border', 'type' => 'box', 'label' => 'Outer Frame', 'x' => 15, 'y' => 15, 'width' => 812, 'height' => 565, 'border_color' => '#1e3a8a', 'border_width' => 4, 'border_style' => 'solid', 'background_color' => '#ffffff', 'z_index' => 1],
            ['id' => 'el_inner_border', 'type' => 'box', 'label' => 'Inner Gold Frame', 'x' => 22, 'y' => 22, 'width' => 798, 'height' => 551, 'border_color' => '#d4af37', 'border_width' => 1.5, 'border_style' => 'solid', 'background_color' => 'transparent', 'z_index' => 2],
            ['id' => 'el_pill', 'type' => 'text', 'label' => 'Accreditation Pill', 'content' => 'ACADEMIC EXCELLENCE & STANDARDS', 'x' => 271, 'y' => 42, 'width' => 300, 'height' => 24, 'font_family' => 'sans-serif', 'font_size' => 10, 'font_weight' => '700', 'color' => '#1e3a8a', 'text_align' => 'center', 'letter_spacing' => 1.5, 'background_color' => '#eff6ff', 'border_color' => '#1e3a8a', 'border_width' => 1, 'border_radius' => 4, 'z_index' => 3],
            ['id' => 'el_inst_name', 'type' => 'text', 'label' => 'Academy Title', 'content' => 'The Open University of Tanzania', 'x' => 121, 'y' => 74, 'width' => 600, 'height' => 36, 'font_family' => 'serif', 'font_size' => 26, 'font_weight' => '700', 'color' => '#1e3a8a', 'text_align' => 'center', 'letter_spacing' => 2, 'z_index' => 4],
            ['id' => 'el_gold_rule', 'type' => 'line', 'label' => 'Gold Divider', 'x' => 271, 'y' => 120, 'width' => 300, 'height' => 2, 'border_color' => '#d4af37', 'border_width' => 1.5, 'z_index' => 4],
            ['id' => 'el_cert_title', 'type' => 'text', 'label' => 'Award Title', 'content' => 'Certificate of Academic Achievement', 'x' => 71, 'y' => 135, 'width' => 700, 'height' => 34, 'font_family' => 'serif', 'font_size' => 22, 'font_weight' => '700', 'color' => '#0f172a', 'text_align' => 'center', 'letter_spacing' => 1.5, 'z_index' => 4],
            ['id' => 'el_cert_sub', 'type' => 'text', 'label' => 'Lead Statement', 'content' => 'This is to proudly certify that', 'x' => 171, 'y' => 178, 'width' => 500, 'height' => 22, 'font_family' => 'serif', 'font_size' => 14, 'font_weight' => 'normal', 'font_style' => 'italic', 'color' => '#475569', 'text_align' => 'center', 'z_index' => 4],
            ['id' => 'el_student_name', 'type' => 'placeholder', 'label' => 'Student Name', 'content' => '{student_name}', 'placeholder_key' => '{student_name}', 'x' => 121, 'y' => 208, 'width' => 600, 'height' => 48, 'font_family' => 'serif', 'font_size' => 32, 'font_weight' => '700', 'color' => '#1e3a8a', 'text_align' => 'center', 'letter_spacing' => 1, 'border_bottom' => '2px solid #d4af37', 'z_index' => 5],
            ['id' => 'el_lead', 'type' => 'text', 'label' => 'Curriculum Statement', 'content' => 'has successfully fulfilled all requirements and completed the certified program in', 'x' => 71, 'y' => 272, 'width' => 700, 'height' => 20, 'font_family' => 'sans-serif', 'font_size' => 12, 'font_weight' => 'normal', 'color' => '#334155', 'text_align' => 'center', 'z_index' => 4],
            ['id' => 'el_course_title', 'type' => 'placeholder', 'label' => 'Course Title', 'content' => '{course_title}', 'placeholder_key' => '{course_title}', 'x' => 71, 'y' => 298, 'width' => 700, 'height' => 32, 'font_family' => 'serif', 'font_size' => 20, 'font_weight' => '700', 'color' => '#0f172a', 'text_align' => 'center', 'z_index' => 5],
            ['id' => 'el_accreditation', 'type' => 'text', 'label' => 'Accreditation Statement', 'content' => 'Conducted with verified academic coursework and verified practical evaluations under OUT Curriculum Standards.', 'x' => 146, 'y' => 342, 'width' => 550, 'height' => 32, 'font_family' => 'sans-serif', 'font_size' => 10, 'font_weight' => 'normal', 'color' => '#64748b', 'text_align' => 'center', 'z_index' => 4],
            ['id' => 'el_sig_left_line', 'type' => 'line', 'label' => 'Instructor Line', 'x' => 80, 'y' => 430, 'width' => 200, 'height' => 2, 'border_color' => '#1e3a8a', 'border_width' => 1.5, 'z_index' => 4],
            ['id' => 'el_sig_left_name', 'type' => 'placeholder', 'label' => 'Instructor Name', 'content' => '{instructor_name}', 'placeholder_key' => '{instructor_name}', 'x' => 60, 'y' => 436, 'width' => 240, 'height' => 20, 'font_family' => 'serif', 'font_size' => 13, 'font_weight' => '700', 'color' => '#0f172a', 'text_align' => 'center', 'z_index' => 5],
            ['id' => 'el_sig_left_title', 'type' => 'text', 'label' => 'Instructor Title', 'content' => 'Lead Academic Instructor', 'x' => 50, 'y' => 456, 'width' => 260, 'height' => 18, 'font_family' => 'sans-serif', 'font_size' => 9.5, 'color' => '#64748b', 'text_align' => 'center', 'z_index' => 4],
            ['id' => 'el_seal', 'type' => 'box', 'label' => 'Official Seal', 'x' => 366, 'y' => 395, 'width' => 110, 'height' => 110, 'border_color' => '#d4af37', 'border_width' => 2, 'border_radius' => 55, 'background_color' => '#fefce8', 'z_index' => 4],
            ['id' => 'el_seal_text', 'type' => 'text', 'label' => 'Seal Content', 'content' => "OFFICIAL SEAL\nOUT TANZANIA\nVERIFIED", 'x' => 370, 'y' => 425, 'width' => 102, 'height' => 50, 'font_family' => 'sans-serif', 'font_size' => 8.5, 'font_weight' => '700', 'color' => '#1e3a8a', 'text_align' => 'center', 'letter_spacing' => 0.5, 'z_index' => 5],
            ['id' => 'el_sig_right_line', 'type' => 'line', 'label' => 'Director Line', 'x' => 525, 'y' => 430, 'width' => 190, 'height' => 2, 'border_color' => '#1e3a8a', 'border_width' => 1.5, 'z_index' => 4],
            ['id' => 'el_sig_right_name', 'type' => 'text', 'label' => 'Director Name', 'content' => 'Dr. Alexander Vance', 'x' => 500, 'y' => 436, 'width' => 240, 'height' => 20, 'font_family' => 'serif', 'font_size' => 13, 'font_weight' => '700', 'color' => '#0f172a', 'text_align' => 'center', 'z_index' => 5],
            ['id' => 'el_sig_right_title', 'type' => 'text', 'label' => 'Director Title', 'content' => 'Academic Director & Dean', 'x' => 490, 'y' => 456, 'width' => 260, 'height' => 18, 'font_family' => 'sans-serif', 'font_size' => 9.5, 'color' => '#64748b', 'text_align' => 'center', 'z_index' => 4],
            ['id' => 'el_qr', 'type' => 'qr', 'label' => 'QR Code', 'x' => 735, 'y' => 440, 'width' => 70, 'height' => 70, 'z_index' => 5],
            ['id' => 'el_meta_divider', 'type' => 'line', 'label' => 'Footer Divider', 'x' => 40, 'y' => 520, 'width' => 660, 'height' => 1, 'border_color' => '#e2e8f0', 'border_width' => 1, 'z_index' => 3],
            ['id' => 'el_cert_no', 'type' => 'placeholder', 'label' => 'Cert No', 'content' => 'Certificate No: {certificate_no}', 'placeholder_key' => '{certificate_no}', 'x' => 40, 'y' => 532, 'width' => 220, 'height' => 18, 'font_family' => 'sans-serif', 'font_size' => 9.5, 'font_weight' => '600', 'color' => '#64748b', 'text_align' => 'left', 'z_index' => 4],
            ['id' => 'el_issued_at', 'type' => 'placeholder', 'label' => 'Issue Date', 'content' => 'Date: {issued_at}', 'placeholder_key' => '{issued_at}', 'x' => 270, 'y' => 532, 'width' => 200, 'height' => 18, 'font_family' => 'sans-serif', 'font_size' => 9.5, 'font_weight' => '600', 'color' => '#64748b', 'text_align' => 'center', 'z_index' => 4],
            ['id' => 'el_verify_link', 'type' => 'placeholder', 'label' => 'Verification URL', 'content' => '{verify_url}', 'placeholder_key' => '{verify_url}', 'x' => 480, 'y' => 532, 'width' => 220, 'height' => 18, 'font_family' => 'sans-serif', 'font_size' => 9, 'color' => '#64748b', 'text_align' => 'right', 'z_index' => 4],
        ];
    }

    /**
     * Design 2: Modern Emerald Elements
     */
    public static function modernEmeraldElements(): array
    {
        $elements = static::classicAcademicElements();
        foreach ($elements as &$el) {
            if (isset($el['border_color']) && $el['border_color'] === '#1e3a8a') {
                $el['border_color'] = '#006837';
            }
            if (isset($el['color']) && $el['color'] === '#1e3a8a') {
                $el['color'] = '#006837';
            }
            if (isset($el['background_color']) && $el['background_color'] === '#eff6ff') {
                $el['background_color'] = '#f4fbf7';
            }
        }
        return $elements;
    }

    /**
     * Design 3: Executive Gold Elements
     */
    public static function executiveGoldElements(): array
    {
        $elements = static::classicAcademicElements();
        foreach ($elements as &$el) {
            if ($el['id'] === 'el_border') {
                $el['border_color'] = '#18181b';
                $el['border_width'] = 6;
            }
            if ($el['id'] === 'el_inner_border') {
                $el['border_color'] = '#eab308';
                $el['border_width'] = 2;
            }
            if ($el['id'] === 'el_pill') {
                $el['content'] = 'EXECUTIVE LEADERSHIP FELLOWSHIP';
                $el['color'] = '#eab308';
                $el['background_color'] = '#18181b';
                $el['border_color'] = '#eab308';
            }
            if ($el['id'] === 'el_cert_title') {
                $el['content'] = 'Executive Certificate of Distinction';
            }
            if ($el['id'] === 'el_student_name') {
                $el['color'] = '#18181b';
                $el['border_bottom'] = '2px solid #eab308';
            }
        }
        return $elements;
    }

    /**
     * Design 4: Tech & Bootcamp Elements
     */
    public static function techBootcampElements(): array
    {
        return [
            ['id' => 'el_border', 'type' => 'box', 'label' => 'Outer Frame', 'x' => 15, 'y' => 15, 'width' => 812, 'height' => 565, 'border_color' => '#0f172a', 'border_width' => 3, 'border_style' => 'solid', 'background_color' => '#ffffff', 'z_index' => 1],
            ['id' => 'el_accent_bar', 'type' => 'box', 'label' => 'Cyan Accent Bar', 'x' => 15, 'y' => 15, 'width' => 812, 'height' => 8, 'background_color' => '#06b6d4', 'z_index' => 2],
            ['id' => 'el_pill', 'type' => 'text', 'label' => 'Verified Skills Pill', 'content' => '< CERTIFIED PROFESSIONAL DEVELOPER / ENGINEER >', 'x' => 191, 'y' => 45, 'width' => 460, 'height' => 24, 'font_family' => 'sans-serif', 'font_size' => 10, 'font_weight' => '700', 'color' => '#0891b2', 'text_align' => 'center', 'letter_spacing' => 1.5, 'background_color' => '#ecfeff', 'border_color' => '#06b6d4', 'border_width' => 1, 'border_radius' => 4, 'z_index' => 3],
            ['id' => 'el_cert_title', 'type' => 'text', 'label' => 'Award Title', 'content' => 'CERTIFICATE OF CODE PROFICIENCY', 'x' => 71, 'y' => 90, 'width' => 700, 'height' => 36, 'font_family' => 'sans-serif', 'font_size' => 24, 'font_weight' => '800', 'color' => '#0f172a', 'text_align' => 'center', 'letter_spacing' => 2, 'z_index' => 4],
            ['id' => 'el_cert_sub', 'type' => 'text', 'label' => 'Lead Statement', 'content' => 'This credential certifies that', 'x' => 171, 'y' => 140, 'width' => 500, 'height' => 22, 'font_family' => 'sans-serif', 'font_size' => 13, 'font_weight' => 'normal', 'color' => '#64748b', 'text_align' => 'center', 'z_index' => 4],
            ['id' => 'el_student_name', 'type' => 'placeholder', 'label' => 'Student Name', 'content' => '{student_name}', 'placeholder_key' => '{student_name}', 'x' => 121, 'y' => 175, 'width' => 600, 'height' => 48, 'font_family' => 'sans-serif', 'font_size' => 30, 'font_weight' => '800', 'color' => '#0f172a', 'text_align' => 'center', 'letter_spacing' => 0.5, 'border_bottom' => '2px solid #06b6d4', 'z_index' => 5],
            ['id' => 'el_lead', 'type' => 'text', 'label' => 'Curriculum Statement', 'content' => 'has mastered the advanced curriculum and verified hands-on lab milestones for', 'x' => 71, 'y' => 245, 'width' => 700, 'height' => 20, 'font_family' => 'sans-serif', 'font_size' => 12, 'color' => '#475569', 'text_align' => 'center', 'z_index' => 4],
            ['id' => 'el_course_title', 'type' => 'placeholder', 'label' => 'Course Title', 'content' => '{course_title}', 'placeholder_key' => '{course_title}', 'x' => 71, 'y' => 275, 'width' => 700, 'height' => 32, 'font_family' => 'sans-serif', 'font_size' => 20, 'font_weight' => '700', 'color' => '#0891b2', 'text_align' => 'center', 'z_index' => 5],
            ['id' => 'el_accreditation', 'type' => 'text', 'label' => 'Tech Verification Statement', 'content' => 'Verified through automated test suites, code repository reviews, and production grade capstone projects.', 'x' => 146, 'y' => 320, 'width' => 550, 'height' => 32, 'font_family' => 'sans-serif', 'font_size' => 10, 'color' => '#94a3b8', 'text_align' => 'center', 'z_index' => 4],
            ['id' => 'el_qr', 'type' => 'qr', 'label' => 'QR Code', 'x' => 376, 'y' => 380, 'width' => 90, 'height' => 90, 'z_index' => 5],
            ['id' => 'el_sig_left_line', 'type' => 'line', 'label' => 'Instructor Line', 'x' => 80, 'y' => 440, 'width' => 200, 'height' => 2, 'border_color' => '#0f172a', 'border_width' => 1.5, 'z_index' => 4],
            ['id' => 'el_sig_left_name', 'type' => 'placeholder', 'label' => 'Instructor Name', 'content' => '{instructor_name}', 'placeholder_key' => '{instructor_name}', 'x' => 60, 'y' => 446, 'width' => 240, 'height' => 20, 'font_family' => 'sans-serif', 'font_size' => 12, 'font_weight' => '700', 'color' => '#0f172a', 'text_align' => 'center', 'z_index' => 5],
            ['id' => 'el_sig_left_title', 'type' => 'text', 'label' => 'Instructor Title', 'content' => 'Head of Engineering Curriculum', 'x' => 50, 'y' => 466, 'width' => 260, 'height' => 18, 'font_family' => 'sans-serif', 'font_size' => 9, 'color' => '#64748b', 'text_align' => 'center', 'z_index' => 4],
            ['id' => 'el_sig_right_line', 'type' => 'line', 'label' => 'Director Line', 'x' => 562, 'y' => 440, 'width' => 200, 'height' => 2, 'border_color' => '#0f172a', 'border_width' => 1.5, 'z_index' => 4],
            ['id' => 'el_sig_right_name', 'type' => 'text', 'label' => 'Director Name', 'content' => 'OUT Verification Authority', 'x' => 542, 'y' => 446, 'width' => 240, 'height' => 20, 'font_family' => 'sans-serif', 'font_size' => 12, 'font_weight' => '700', 'color' => '#0f172a', 'text_align' => 'center', 'z_index' => 5],
            ['id' => 'el_sig_right_title', 'type' => 'text', 'label' => 'Director Title', 'content' => 'Cryptographic Badge Authority', 'x' => 532, 'y' => 466, 'width' => 260, 'height' => 18, 'font_family' => 'sans-serif', 'font_size' => 9, 'color' => '#64748b', 'text_align' => 'center', 'z_index' => 4],
            ['id' => 'el_meta_divider', 'type' => 'line', 'label' => 'Footer Divider', 'x' => 40, 'y' => 520, 'width' => 762, 'height' => 1, 'border_color' => '#cbd5e1', 'border_width' => 1, 'z_index' => 3],
            ['id' => 'el_cert_no', 'type' => 'placeholder', 'label' => 'Cert No', 'content' => 'HASH ID: {certificate_no}', 'placeholder_key' => '{certificate_no}', 'x' => 40, 'y' => 532, 'width' => 250, 'height' => 18, 'font_family' => 'sans-serif', 'font_size' => 9.5, 'font_weight' => '700', 'color' => '#0891b2', 'text_align' => 'left', 'z_index' => 4],
            ['id' => 'el_issued_at', 'type' => 'placeholder', 'label' => 'Issue Date', 'content' => 'TIMESTAMP: {issued_at}', 'placeholder_key' => '{issued_at}', 'x' => 300, 'y' => 532, 'width' => 242, 'height' => 18, 'font_family' => 'sans-serif', 'font_size' => 9.5, 'font_weight' => '600', 'color' => '#64748b', 'text_align' => 'center', 'z_index' => 4],
            ['id' => 'el_verify_link', 'type' => 'placeholder', 'label' => 'Verification URL', 'content' => '{verify_url}', 'placeholder_key' => '{verify_url}', 'x' => 550, 'y' => 532, 'width' => 252, 'height' => 18, 'font_family' => 'sans-serif', 'font_size' => 9, 'color' => '#64748b', 'text_align' => 'right', 'z_index' => 4],
        ];
    }

    /**
     * Design 5: Creative Modern Elements
     */
    public static function creativeModernElements(): array
    {
        $elements = static::classicAcademicElements();
        foreach ($elements as &$el) {
            if ($el['id'] === 'el_border') {
                $el['border_color'] = '#4f46e5';
            }
            if ($el['id'] === 'el_inner_border') {
                $el['border_color'] = '#a855f7';
            }
            if ($el['id'] === 'el_pill') {
                $el['content'] = 'CREATIVE & INNOVATIVE MASTERY';
                $el['color'] = '#4f46e5';
                $el['background_color'] = '#eef2ff';
                $el['border_color'] = '#818cf8';
            }
            if ($el['id'] === 'el_student_name') {
                $el['color'] = '#4f46e5';
                $el['border_bottom'] = '2px solid #a855f7';
            }
        }
        return $elements;
    }

    /**
     * Design 6: Minimalist Elements
     */
    public static function minimalistElements(): array
    {
        $elements = static::classicAcademicElements();
        foreach ($elements as &$el) {
            if ($el['id'] === 'el_border') {
                $el['border_color'] = '#94a3b8';
                $el['border_width'] = 1;
            }
            if ($el['id'] === 'el_inner_border') {
                $el['border_width'] = 0;
            }
            if ($el['id'] === 'el_pill') {
                $el['background_color'] = '#f8fafc';
                $el['border_color'] = '#cbd5e1';
                $el['color'] = '#475569';
            }
            if ($el['id'] === 'el_gold_rule') {
                $el['border_color'] = '#e2e8f0';
            }
            if ($el['id'] === 'el_student_name') {
                $el['color'] = '#0f172a';
                $el['border_bottom'] = '1px solid #cbd5e1';
            }
            if ($el['id'] === 'el_seal') {
                $el['border_color'] = '#cbd5e1';
                $el['background_color'] = '#f8fafc';
            }
            if ($el['id'] === 'el_seal_text') {
                $el['color'] = '#475569';
            }
        }
        return $elements;
    }

    /**
     * Pre-configured default elements for The Open University of Tanzania (OUT)
     */
    public static function defaultPBZElements(): array
    {
        return static::classicAcademicElements();
    }
}

