<?php
use Illuminate\Support\Facades\Broadcast;

//Broadcast::channel('familia.{familiaId}', function ($user, $familiaId) {
//    // Por ejemplo, permitimos sólo si el usuario pertenece a dicha familia
////    return (int)$user->familia_id === (int) $familiaId;
//    return true;
//
//});

//Broadcast::channel('dashboard.estados-cuenta', function ($user) {
//    return true;
//});

Broadcast::channel('dashboard.estados-cuenta', function ($user) {
    return $user !== null;
});

Broadcast::channel('estado-cuenta-pagado', function ($user) {
    return $user !== null;
});

Broadcast::channel('test-channel', function ($user) {
    return $user !== null;
});


