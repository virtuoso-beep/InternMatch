<?php

namespace App\Http\Controllers;

use App\Filament\Pages\Administration;
use Illuminate\Http\Request;

class AdminWorkspaceController extends Controller
{
    public function __invoke(Request $request, string $section = 'dashboard')
    {
        abort_unless($section === 'dashboard' || array_key_exists($section, Administration::SECTIONS), 404);
        $path = public_path('admin-ui/.vite/manifest.json');
        $manifest = is_file($path) ? json_decode(file_get_contents($path), true) : [];
        $entry = $manifest['src/admin-entry.tsx'] ?? null;

        return response()->view('admin.workspace', compact('section', 'entry'), $entry ? 200 : 503)
            ->header('Cache-Control', 'no-store')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }
}
