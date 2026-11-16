<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Students export — formats filtered student records for Excel and CSV downloads.
 *
 * @author Renatus Bernardo
 */
class StudentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * Initialize export dataset.
     */
    public function __construct(private Collection $students)
    {
    }

    /**
     * Return student collection.
     */
    public function collection(): Collection
    {
        return $this->students;
    }

    /**
     * Export column headings.
     */
    public function headings(): array
    {
        return [
            'Student ID',
            'Registration Number',
            'Full Name',
            'Email',
            'Phone',
            'Regional Branch',
            'Account Status',
            'Total Enrollments',
            'Active Enrollments',
            'Completed Courses',
            'Total Spent',
            'Joined Date',
        ];
    }

    /**
     * Map each student record to spreadsheet row.
     */
    public function map($student): array
    {
        return [
            $student->id,
            $student->registration_number ?? '-',
            $student->name,
            $student->email,
            $student->phone ?? '-',
            $student->branch?->name ?? 'Online / Unassigned',
            $student->account_status_label ?? ($student->is_blocked ? 'Blocked' : ($student->is_approved ? 'Active' : 'Pending')),
            (int) ($student->total_enrollments ?? 0),
            (int) ($student->active_enrollments ?? 0),
            (int) ($student->completed_courses ?? 0),
            round((float) ($student->total_spent ?? 0), 2),
            optional($student->created_at)?->format('Y-m-d H:i') ?? '-',
        ];
    }
}
