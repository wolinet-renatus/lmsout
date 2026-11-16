<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Applicant report export — formats structured spreadsheet/CSV export datasets.
 *
 * @author Renatus Bernardo
 */
class ApplicantReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(private Collection $applicants)
    {
    }

    /**
     * Collection.
     */
    public function collection(): Collection
    {
        return $this->applicants;
    }

    /**
     * Headings.
     */
    public function headings(): array
    {
        return [
            'Applicant ID',
            'Full Name',
            'Email',
            'Phone',
            'Gender',
            'Education Level',
            'Country',
            'Regional Branch',
            'Courses',
            'Staff / Discount',
            'Total Invoiced (TZS)',
            'Paid (TZS)',
            'Balance (TZS)',
            'Payment Status',
            'Registered At',
        ];
    }

    /**
     * Map.
     */
    public function map($applicant): array
    {
        $invoices = $applicant->invoices ?? collect();
        $invoiced = (float) $invoices->sum('net_amount');
        $paid = (float) $invoices->sum('paid_amount');
        $balance = (float) $invoices->sum('balance');

        $status = 'Unpaid';
        if ($invoiced <= 0 && $invoices->isEmpty()) {
            $status = 'No Invoice';
        } elseif ($balance <= 0 && $paid > 0) {
            $status = 'Full Paid';
        } elseif ($paid > 0) {
            $status = 'Partial';
        }

        $courses = $applicant->enrollments
            ? $applicant->enrollments->map(fn ($e) => $e->course?->title)->filter()->unique()->implode(', ')
            : '';

        return [
            $applicant->id,
            $applicant->full_name,
            $applicant->email,
            $applicant->phone,
            ucfirst((string) ($applicant->gender ?? '-')),
            \App\Models\Applicant::educationLevels()[$applicant->education_level] ?? ($applicant->education_level ?? '-'),
            $applicant->country?->display_name ?? '-',
            $applicant->branch?->name ?? '-',
            $courses ?: '-',
            $applicant->has_discount ? ($applicant->discount_reason ?: 'Yes') : ($applicant->is_staff_student ? 'Staff' : 'Standard'),
            number_format($invoiced, 2, '.', ''),
            number_format($paid, 2, '.', ''),
            number_format($balance, 2, '.', ''),
            $status,
            optional($applicant->created_at)->format('Y-m-d H:i'),
        ];
    }
}
