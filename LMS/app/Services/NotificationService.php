<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Surat;
use App\Models\User;
use App\Models\SuratAccess;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Create notifications for users when a new letter is created
     */
    public function notifyNewLetter(Surat $surat): void
    {
        $uploader = $surat->uploader;
        $recipients = collect();

        if ($surat->is_private) {
            // For private letters, notify only users who have access
            $accessibleUsers = SuratAccess::where('surat_id', $surat->id)
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter();
            
            $recipients = $recipients->merge($accessibleUsers);
        } else {
            // For public letters, notify all users in the same division except the uploader
            $divisionUsers = User::where('divisi_id', $surat->divisi_id)
                ->where('is_active', true)
                ->where('id', '!=', $surat->uploaded_by)
                ->get();
            
            $recipients = $recipients->merge($divisionUsers);
        }

        // Create notifications for each recipient
        foreach ($recipients as $user) {
            $this->createNewLetterNotification($user, $surat);
        }
    }

    /**
     * Create notifications when users are granted access to a private letter
     */
    public function notifyLetterAccessGranted(Surat $surat, Collection $users): void
    {
        foreach ($users as $user) {
            $this->createLetterAccessNotification($user, $surat);
        }
    }

    /**
     * Create a notification for a new letter
     */
    private function createNewLetterNotification(User $user, Surat $surat): void
    {
        $uploader = $surat->uploader;
        $letterType = $surat->is_private ? 'private' : 'public';
        
        $title = "Surat Baru: {$surat->perihal}";
        $message = sprintf(
            "Surat %s baru telah diupload oleh %s dengan nomor %s. Klik untuk melihat detail.",
            $letterType === 'private' ? 'private' : '',
            $uploader->full_name,
            $surat->nomor_surat
        );

        $data = [
            'uploader_name' => $uploader->full_name,
            'uploader_id' => $uploader->id,
            'letter_number' => $surat->nomor_surat,
            'letter_type' => $letterType,
            'division_name' => $surat->division->nama_divisi ?? 'N/A',
            'jenis_surat_name' => $surat->jenisSurat->nama_jenis ?? 'N/A',
        ];

        Notification::createNotification(
            $user->id,
            $surat->id,
            'new_letter',
            $title,
            $message,
            $data
        );
    }

    /**
     * Create a notification for letter access granted
     */
    private function createLetterAccessNotification(User $user, Surat $surat): void
    {
        $uploader = $surat->uploader;
        
        $title = "Akses Surat Private: {$surat->perihal}";
        $message = sprintf(
            "Anda telah diberikan akses ke surat private dengan nomor %s yang diupload oleh %s. Klik untuk melihat detail.",
            $surat->nomor_surat,
            $uploader->full_name
        );

        $data = [
            'uploader_name' => $uploader->full_name,
            'uploader_id' => $uploader->id,
            'letter_number' => $surat->nomor_surat,
            'letter_type' => 'private',
            'division_name' => $surat->division->nama_divisi ?? 'N/A',
            'jenis_surat_name' => $surat->jenisSurat->nama_jenis ?? 'N/A',
        ];

        Notification::createNotification(
            $user->id,
            $surat->id,
            'letter_access_granted',
            $title,
            $message,
            $data
        );
    }

    /**
     * Get notifications for a user with pagination
     */
    public function getUserNotifications(int $userId, int $page = 1, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Notification::forUser($userId)
            ->with(['surat.division', 'surat.jenisSurat', 'surat.uploader'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get unread notifications count for a user
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::getUnreadCountForUser($userId);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($notification) {
            return $notification->markAsRead();
        }

        return false;
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::markAllAsReadForUser($userId);
    }

    /**
     * Delete old notifications (cleanup)
     */
    public function cleanupOldNotifications(int $days = 90): int
    {
        return Notification::cleanupOldNotifications($days);
    }

    /**
     * Get recent notifications for dashboard/header display
     */
    public function getRecentNotifications(int $userId, int $limit = 5): Collection
    {
        return Notification::forUser($userId)
            ->with(['surat.division', 'surat.jenisSurat', 'surat.uploader'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
