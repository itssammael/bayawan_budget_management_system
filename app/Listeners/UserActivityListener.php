<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

class UserActivityListener
{
    public function handleLogin(Login $event)
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'log_name' => 'user',
            'description' => "{$event->user->name} logged in",
            'subject_type' => get_class($event->user),
            'subject_id' => $event->user->id,
            'event' => 'login',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public function handleLogout(Logout $event)
    {
        if (!$event->user) {
            return;
        }

        ActivityLog::create([
            'user_id' => $event->user->id,
            'log_name' => 'user',
            'description' => "{$event->user->name} logged out",
            'subject_type' => get_class($event->user),
            'subject_id' => $event->user->id,
            'event' => 'logout',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
