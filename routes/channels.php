<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('loans.{id}', function ($user, $id) {
    return true;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
