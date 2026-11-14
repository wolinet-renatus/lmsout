<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h2 { color: #013ba6; margin-bottom: 4px; }
        .meta { color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 5px; text-align: left; }
        th { background-color: #013ba6; color: #ffffff; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h2>{{ site_name() }} — Applicant Registration &amp; Payment Report</h2>
    <div class="meta">Generated: {{ $generatedAt }} &bull; Records: {{ $applicants->count() }}</div>
    <table>
        <thead>
            <tr>
                <th>Applicant Name</th>
                <th>Email / Phone</th>
                <th>Branch</th>
                <th>Country</th>
                <th>Courses</th>
                <th class="num">Invoiced</th>
                <th class="num">Paid</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($applicants as $a)
                @php
                    $invoiced = (float) $a->invoices->sum('net_amount');
                    $paid = (float) $a->invoices->sum('paid_amount');
                    $balance = (float) $a->invoices->sum('balance');
                @endphp
                <tr>
                    <td>{{ $a->full_name }}</td>
                    <td>{{ $a->email }}<br>{{ $a->phone }}</td>
                    <td>{{ $a->branch?->name ?? '-' }}</td>
                    <td>{{ $a->country?->display_name ?? '-' }}</td>
                    <td>{{ $a->enrollments->map(fn ($e) => $e->course?->title)->filter()->unique()->implode(', ') ?: '-' }}</td>
                    <td class="num">{{ number_format($invoiced, 2) }}</td>
                    <td class="num">{{ number_format($paid, 2) }}</td>
                    <td class="num">{{ number_format($balance, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
