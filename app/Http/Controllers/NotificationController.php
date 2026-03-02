<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        $userId = $this->getAuthUser()->id;
        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        Notification::where('user_id', $userId)->where('is_read', false)->update(['is_read' => true]);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(int $id)
    {
        $userId = $this->getAuthUser()->id;
        Notification::where('id', $id)->where('user_id', $userId)->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        $userId = $this->getAuthUser()->id;
        Notification::where('user_id', $userId)->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }
}
