<?php

namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        return view('member.notifications', [
            'notifications' => auth()->user()->notifications()->paginate(30),
        ]);
    }

    public function read(Notification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 404);

        $notification->update(['read_at' => now()]);

        return $notification->url ? redirect()->to($notification->url) : back();
    }

    public function readAll()
    {
        auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('status', 'Notificações marcadas como lidas.');
    }
}
