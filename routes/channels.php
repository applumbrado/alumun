<?php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('alumun.periodos', function ($user) {
    return $user !== null;
});

