<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for direct messages
Broadcast::channel('chat.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Presence channel for global "Online" dots tracking
Broadcast::channel('messenger', function ($user) {
    if (auth()->check()) {
        return ['id' => $user->id, 'name' => $user->name, 'roles' => $user->getRoleNames()];
    }
});

// Presence/Private Channel for system notifications (Patient Arrivals etc)
Broadcast::channel('system', function ($user) {
    if (auth()->check()) {
        return ['id' => $user->id];
    }
});
