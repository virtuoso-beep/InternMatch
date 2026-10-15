<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['status' => ['nullable', 'in:all,unread,read']]);
        $query = $request->user()->notifications();
        if (($data['status'] ?? 'all') === 'unread') $query->whereNull('read_at');
        if (($data['status'] ?? 'all') === 'read') $query->whereNotNull('read_at');
        $page = $query->paginate(30)->toArray();
        $page['unread_count'] = $request->user()->unreadNotifications()->count();

        return response()->json($page, headers: ['Cache-Control' => 'no-store']);
    }

    public function read(Request $request, string $notification)
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return response()->noContent();
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        return response()->noContent();
    }
}
