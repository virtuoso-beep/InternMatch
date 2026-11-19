<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #1f1416; }
        h1 { font-size: 15px; color: #7a1f2b; margin: 0 0 6px; text-transform: capitalize; }
        .details { margin-bottom: 10px; border-collapse: collapse; }
        .details td { padding: 1px 10px 1px 0; vertical-align: top; }
        .details td:first-child { color: #6b5a5d; white-space: nowrap; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #f3e9eb; text-align: left; border-bottom: 1px solid #ccb8bb; padding: 4px; }
        table.data td { border-bottom: 1px solid #ead9dc; padding: 3px 4px; vertical-align: top; word-wrap: break-word; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .empty { color: #6b5a5d; }
        .notice { border: 1px solid #b7791f; background: #fdf3e1; color: #7a4a00; padding: 4px 6px; font-weight: bold; margin: 0 0 8px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    @if (! empty($notice))
        <p class="notice">{{ $notice }}</p>
    @endif
    <table class="details">
        @foreach ($details as [$label, $value])
            <tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>
        @endforeach
    </table>
    @if (count($rows) === 0)
        <p class="empty">This report has no records.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $value)
                            <td>{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
