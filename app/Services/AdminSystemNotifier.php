<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AdminSystemNotification;

class AdminSystemNotifier
{
    public function notify(string $title, string $message, string $url, string $category): void
    {
        User::query()->where('role', 'admin')->each(function (User $admin) use ($title, $message, $url, $category): void {
            $admin->notify(new AdminSystemNotification($title, $message, $url, $category));
        });
    }
}
