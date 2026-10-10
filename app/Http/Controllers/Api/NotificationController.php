<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $items = AppNotification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $items,
            'unread_count' => AppNotification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    /**
     * GET /api/notifications/unread-count
     */
    public function unreadCount(Request $request)
    {
        $count = AppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'status' => true,
            'count' => $count,
        ]);
    }

    /**
     * POST /api/notifications/{id}/read
     */
    public function markRead(Request $request, int $id)
    {
        $notif = AppNotification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($notif->read_at === null) {
            $notif->update(['read_at' => now()]);
        }

        return response()->json(['status' => true, 'data' => $notif]);
    }

    /**
     * POST /api/notifications/read-all
     */
    public function markAllRead(Request $request)
    {
        AppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['status' => true, 'message' => 'Toutes les notifications sont lues.']);
    }
}
