<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy notice · InternMatch</title>
    <style>
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f8f5f5; color: #1f1416; }
        main { max-width: 44rem; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; border: 1px solid #ead9dc; border-radius: .75rem; padding: 1.5rem; }
        h1 { margin: 0 0 .25rem; font-size: 1.4rem; color: #7a1f2b; }
        h2 { margin: 1.25rem 0 .25rem; font-size: 1rem; }
        p { margin: 0; line-height: 1.55; font-size: .95rem; }
        .muted { color: #6b5a5d; font-size: .85rem; }
        label { display: flex; gap: .5rem; align-items: flex-start; margin-top: 1.5rem; font-size: .95rem; }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.25rem; }
        button { border: 0; border-radius: .5rem; padding: .65rem 1.1rem; font-size: .95rem; cursor: pointer; }
        .primary { background: #7a1f2b; color: #fff; }
        .ghost { background: transparent; border: 1px solid #ccb8bb; color: #1f1416; }
        .error { color: #a3172b; margin-top: .75rem; }
    </style>
</head>
<body>
<main>
    <div class="card">
        <h1>{{ $notice['title'] }}</h1>
        <p class="muted">Version {{ $notice['version'] }} · Republic Act No. 10173 (Data Privacy Act of 2012)</p>
        @foreach ($notice['sections'] as $section)
            <h2>{{ $section['heading'] }}</h2>
            <p>{{ $section['body'] }}</p>
        @endforeach
        <form method="POST" action="{{ route('manage.privacy.acknowledge') }}">
            @csrf
            <label><input type="checkbox" name="acknowledged" value="1" required> I have read and understood this privacy notice.</label>
            @error('acknowledged')<p class="error">{{ $message }}</p>@enderror
            <div class="actions">
                <button type="submit" class="primary">Acknowledge and continue</button>
            </div>
        </form>
        <form method="POST" action="{{ url('/manage/logout') }}" class="actions">
            @csrf
            <button type="submit" class="ghost">Sign out</button>
        </form>
    </div>
</main>
</body>
</html>
