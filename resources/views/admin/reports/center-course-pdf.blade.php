<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h2 { color: #013ba6; margin-bottom: 4px; }
        .meta { color: #6b7280; margin-bottom: 12px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background-color: #013ba6; color: #ffffff; }
        .num { text-align: right; }
        .ctr { text-align: center; }
    </style>
</head>
<body>
    <h2>{{ site_name() }} — Ongoing Regional Center Course Report ({{ ucfirst($period) }})</h2>
    <div class="meta">Generated: {{ $generatedAt }}</div>
    <table>
        <thead>
            <tr>
                <th>Centre</th>
                <th>Course</th>
                <th>Delivery Mode</th>
                <th class="ctr">Students</th>
                <th class="ctr">Full Paid</th>
                <th class="ctr">Partial</th>
                <th class="ctr">Unpaid</th>
                <th class="num">Collected</th>
                <th class="num">Outstanding</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['branch_name'] }}</td>
                    <td>{{ $row['course_title'] }}</td>
                    <td>{{ $row['teaching_method'] }}</td>
                    <td class="ctr">{{ $row['total_students'] }}</td>
                    <td class="ctr">{{ $row['full_paid'] }}</td>
                    <td class="ctr">{{ $row['half_paid'] }}</td>
                    <td class="ctr">{{ $row['unpaid'] }}</td>
                    <td class="num">{{ number_format($row['collected'], 2) }}</td>
                    <td class="num">{{ number_format($row['outstanding'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="ctr">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
