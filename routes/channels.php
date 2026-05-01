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
    return ['id' => $user->id, 'name' => $user->name];
});

// Private Channel for system notifications (Patient Arrivals etc)
Broadcast::channel('system', function ($user) {
    return true;
});

// Doctor Alerts Private Channel
Broadcast::channel('doctor.alerts.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
