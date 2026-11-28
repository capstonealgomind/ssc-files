<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSystemNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->role === 'admin', 403);

        return response()->json([
            'unreadCount' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->latest()->limit(12)->get()->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'System notification',
                'message' => $notification->data['message'] ?? '',
                'url' => $notification->data['url'] ?? '/dashboard',
                'category' => $notification->data['category'] ?? 'system',
                'read' => $notification->read_at !== null,
                'createdAt' => $notification->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->role === 'admin', 403);

        $user->unreadNotifications()->whereKey($notification)->update(['read_at' => now()]);

        return response()->json(['unreadCount' => $user->unreadNotifications()->count()]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->role === 'admin', 403);

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['unreadCount' => 0]);
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->role === 'admin', 403);

        $deleted = $user->notifications()->whereKey($notification)->delete();
        abort_unless($deleted, 404);

        return response()->json([
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
