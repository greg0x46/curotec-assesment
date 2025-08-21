<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tasks.user.{userId}', function ($user, int $userId) {
    return $user->id === $userId;
}, ['guards' => ['web']]);
