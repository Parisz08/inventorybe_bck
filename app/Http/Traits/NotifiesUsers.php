<?php

namespace App\Http\Traits;

use App\Notification;
use App\User;

trait NotifiesUsers
{
    private function notifyRole($role, $type, $title, $message, $spbId = null, $poId = null, $excludeUserId = null)
    {
        $userIds = User::where('role', $role)->pluck('id');

        foreach ($userIds as $userId) {
            if ($excludeUserId && (int) $userId === (int) $excludeUserId) {
                continue;
            }
            $this->notifyUser($userId, $type, $title, $message, $spbId, $poId);
        }
    }

    private function notifyUser($userId, $type, $title, $message, $spbId = null, $poId = null)
    {
        if (empty($userId)) {
            return;
        }

        Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'spb_id'  => $spbId,
            'po_id'   => $poId,
            'is_read' => false,
        ]);
    }
}
