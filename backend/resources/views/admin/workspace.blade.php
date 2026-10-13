<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>InternMatch Administration</title>
    @if ($entry)
        @foreach ($entry['css'] ?? [] as $css)
            <link rel="stylesheet" href="{{ asset('admin-ui/'.$css) }}">
        @endforeach
        <script type="module" src="{{ asset('admin-ui/'.$entry['file']) }}"></script>
    @endif
</head>
<body>
    @if ($entry)
        <div id="admin-workspace" data-section="{{ $section }}"></div>
    @else
        <p>The administrator interface assets have not been built. Please contact the system operator.</p>
    @endif
</body>
</html>
