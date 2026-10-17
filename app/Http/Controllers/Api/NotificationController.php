<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Daftar notifikasi milik pengguna yang login.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $items = AppNotification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (AppNotification $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'type' => $n->type,
                'payroll_id' => $n->payroll_id,
                'is_read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => $items,
            'unread_count' => AppNotification::where('user_id', $user->id)->whereNull('read_at')->count(),
        ]);
    }

    /**
     * Daftarkan device token FCM untuk perangkat ini.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|max:255',
            'platform' => 'nullable|string|in:android,ios',
        ]);

        $deviceToken = DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $validated['platform'] ?? 'android',
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Device token tersimpan.',
            'data' => [
                'id' => $deviceToken->id,
                'platform' => $deviceToken->platform,
            ],
        ], 201);
    }

    /**
     * Hapus device token (dipakai saat logout).
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|max:255',
        ]);

        DeviceToken::where('token', $validated['token'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Device token dihapus.']);
    }

    public function markRead(Request $request, string $id)
    {
        $notification = AppNotification::where('user_id', $request->user()->id)->find($id);

        if (! $notification) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'message' => 'Notifikasi ditandai sudah dibaca.',
            'unread_count' => AppNotification::where('user_id', $request->user()->id)->whereNull('read_at')->count(),
        ]);
    }

    public function markAllRead(Request $request)
    {
        AppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Semua notifikasi ditandai sudah dibaca.',
            'unread_count' => 0,
        ]);
    }
}
