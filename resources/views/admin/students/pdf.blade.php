<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h2 { color: #013ba6; margin: 0 0 4px 0; }
        .meta { color: #6b7280; margin-bottom: 10px; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background-color: #013ba6; color: #ffffff; font-weight: bold; }
        tr:nth-child(even) td { background-color: #f9fafb; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h2>{{ site_name() }} &mdash; Students Directory Report</h2>
    <div class="meta">
        Generated: {{ $generatedAt }} &bull; Matching Students: {{ $students->count() }}
        @if (!empty($searchSummary))
            &bull; Filters: {{ $searchSummary }}
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student / Reg #</th>
                <th>Email &amp; Phone</th>
                <th>Branch</th>
                <th>Status</th>
                <th class="num">Enrollments</th>
                <th class="num">Completed</th>
                <th class="num">Total Spent</th>
                <th>Joined Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $idx => $student)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $student->name }}</strong>
                        @if ($student->registration_number)
                            <br><span style="color:#6b7280;">{{ $student->registration_number }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $student->email }}
                        @if ($student->phone)
                            <br>{{ $student->phone }}
                        @endif
                    </td>
                    <td>{{ $student->branch?->name ?? 'Online / Unassigned' }}</td>
                    <td>{{ $student->account_status_label ?? 'Active' }}</td>
                    <td class="num">{{ (int) ($student->total_enrollments ?? 0) }}</td>
                    <td class="num">{{ (int) ($student->completed_courses ?? 0) }}</td>
                    <td class="num">{{ currency_format($student->total_spent ?? 0) }}</td>
                    <td>{{ optional($student->created_at)?->format('M d, Y') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;">No matching students found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
