<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->notificationService = $notificationService;
    }

    /**
     * View notification and redirect to appropriate page based on user role
     */
    public function viewNotification(Request $request, int $id)
    {
        $user = Auth::user();
        
        // Mark notification as read
        $this->notificationService->markAsRead($id, $user->id);
        
        // Get notification to find related letter
        $notification = $this->notificationService->getNotificationById($id, $user->id);
        
        if ($notification && $notification->surat) {
            if ($user->is_admin) {
                // Admin diarahkan ke halaman admin surat
                return redirect()->route('admin.surat.index')->with([
                    'highlight_letter' => $notification->surat->id,
                    'success' => 'Notifikasi telah dibaca. Surat terkait ditampilkan di bawah.'
                ]);
            } else {
                // User biasa diarahkan ke home
                return redirect()->route('home')->with([
                    'highlight_letter' => $notification->surat->id,
                    'success' => 'Notifikasi telah dibaca. Surat terkait ditampilkan di bawah.'
                ]);
            }
        }
        
        // If no letter found, redirect based on user role
        if ($user->is_admin) {
            return redirect()->route('admin.surat.index')->with('info', 'Notifikasi telah dibaca.');
        } else {
            return redirect()->route('home')->with('info', 'Notifikasi telah dibaca.');
        }
    }

    /**
     * Display notifications page - REMOVED (tidak diperlukan lagi)
     */
    public function index(Request $request): View
    {
        // Redirect to home instead
        return redirect()->route('home')->with('info', 'Notifikasi sekarang terintegrasi dengan beranda.');
    }

    /**
     * Get notifications data for AJAX requests
     */
    public function getData(Request $request): JsonResponse
    {
        $user = Auth::user();
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);
        
        $notifications = $this->notificationService->getUserNotifications($user->id, $page, $perPage);
        
        return response()->json([
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ]
        ]);
    }

    /**
     * Get recent notifications for header dropdown
     */
    public function getRecent(Request $request): JsonResponse
    {
        $user = Auth::user();
        $limit = $request->get('limit', 5);
        
        $notifications = $this->notificationService->getRecentNotifications($user->id, $limit);
        $unreadCount = $this->notificationService->getUnreadCount($user->id);
        
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(): JsonResponse
    {
        $user = Auth::user();
        $count = $this->notificationService->getUnreadCount($user->id);
        
        return response()->json([
            'unread_count' => $count
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $success = $this->notificationService->markAsRead($id, $user->id);
        
        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Notification not found or access denied'
        ], 404);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = Auth::user();
        $count = $this->notificationService->markAllAsRead($user->id);
        
        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read",
            'count' => $count
        ]);
    }

    /**
     * Delete/clean up old notifications (Admin only)
     */
    public function cleanup(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $days = $request->get('days', 90);
        $count = $this->notificationService->cleanupOldNotifications($days);
        
        return response()->json([
            'success' => true,
            'message' => "Cleaned up {$count} old notifications",
            'count' => $count
        ]);
    }
}
