<?php
// routes/channels.php

use App\Models\Trip;
use Illuminate\Support\Facades\Broadcast;

// Canal público para trips (qualquer aluno pode acompanhar)
Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
    // Verifica se o usuário tem permissão para ver esta trip
    // Exemplo: aluno está matriculado nesta rota?
    return true; // Simplificado - implemente sua lógica
});

// Canal privado da escola
Broadcast::channel('school.{schoolId}', function ($user, $schoolId) {
    return (int) $user->school_id === (int) $schoolId;
});
