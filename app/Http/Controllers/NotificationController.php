<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function read(string $id, Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        $n = DatabaseNotification::query()
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', \App\Models\User::class)
            ->whereNull('read_at')
            ->where('id', $id)
            ->first();
        if ($n) {
            $n->read_at = now();
            $n->save();
        }
        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        DatabaseNotification::query()
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', \App\Models\User::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        return back();
    }
}
