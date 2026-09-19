<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->user()->notifications()->paginate(30)->toArray();
        $page['unread_count'] = $request->user()->unreadNotifications()->count();

        return response()->json($page, headers: ['Cache-Control' => 'no-store']);
    }

    public function read(Request $request, string $notification)
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return response()->noContent();
    }
}
