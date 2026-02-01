<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(15);

        return Inertia::render('Notification/Index', [
            'notifications' => $notifications
        ]);
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect()->back();
    }

    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return redirect()->back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function destroy($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->delete();

        return redirect()->back();
    }

    public function destroyAllRead()
    {
        auth()->user()->readNotifications()->delete();
        return redirect()->back()->with('success', 'Riwayat notifikasi bersih.');
    }

    public function getJson()
    {
        $user = auth()->user();

        $notifications = $user->notifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'title' => $n->data['title'] ?? 'Notifikasi',
                    'message' => $n->data['message'] ?? '',
                    'type' => $n->data['type'] ?? 'info',
                    'url' => $n->data['url'] ?? null,
                    'read_at' => $n->read_at,
                    'created_at' => $n->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications->count(),
        ]);
    }
}
