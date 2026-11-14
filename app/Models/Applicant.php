<?php

namespace App\Models;

use App\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Applicant extends Model
{
    use HasFactory, BranchScoped, LogsActivity;

    protected $fillable = [
        'user_id',
        'branch_id',
        'country_id',
        'full_name',
        'home_address',
        'office_of_work',
        'occupation',
        'name_of_sponsor',
        'sponsor_phone',
        'source',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'education_level',
        'address',
        'nida_number',
        'is_staff_student',
        'staff_id_number',
        'parent_staff_id_path',
        'has_discount',
        'discount_reason',
        'discount_attachment_path',
        'discount_percentage',
        'notes',
        'legacy_scms_id',
    ];

    protected $casts = [
        'is_staff_student' => 'boolean',
        'has_discount' => 'boolean',
        'discount_percentage' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'first_name',
                'last_name',
                'email',
                'phone',
                'branch_id',
                'is_staff_student',
                'has_discount',
                'discount_reason',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getFullNameAttribute($value): string
    {
        if (! empty($value)) {
            return (string) $value;
        }

        return trim(implode(' ', array_filter([
            $this->attributes['first_name'] ?? null,
            $this->attributes['middle_name'] ?? null,
            $this->attributes['last_name'] ?? null,
        ])));
    }

    /**
     * Education level options exactly as the SCMS Applicants Form.
     *
     * @return array<string, string>
     */
    public static function educationLevels(): array
    {
        return [
            '1' => 'STD-VII',
            '2' => 'FORM-4',
            '3' => 'FORM-VI',
            '4' => 'COLLEGE',
            '5' => 'UNIVERSITY',
            '6' => 'OTHER',
        ];
    }

    /**
     * Duration options (weeks) exactly as the SCMS Applicants Form.
     *
     * @return array<string, string>
     */
    public static function durations(): array
    {
        $out = [];
        for ($i = 1; $i <= 12; $i++) {
            $out[(string) $i] = $i . '-week';
        }

        return $out;
    }

    /**
     * Discount reasons from the SCMS Applicants Form.
     *
     * @return array<string, string>
     */
    public static function discountReasons(): array
    {
        return [
            'staff_child' => 'Staff Child',
            'staff_member' => 'Staff Member',
            'out_student' => 'OUT Student',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
