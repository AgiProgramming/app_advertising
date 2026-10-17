<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

/**
 * Notifikasi slip gaji: simpan ke feed in-app, lalu kirim push FCM
 * ke semua perangkat milik pengguna tersebut.
 */
class NotificationService
{
    public function __construct(protected FcmService $fcm) {}

    public function notify(
        User $user,
        string $title,
        string $body,
        string $type = 'info',
        ?string $payrollId = null,
        array $data = []
    ): AppNotification {
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'payroll_id' => $payrollId,
        ]);

        $tokens = $user->deviceTokens()->pluck('token')->all();

        if ($tokens !== []) {
            $this->fcm->sendToTokens($tokens, $title, $body, array_merge([
                'type' => $type,
                'payroll_id' => (string) ($payrollId ?? ''),
                'notification_id' => $notification->id,
            ], $data));
        }

        return $notification;
    }
}
