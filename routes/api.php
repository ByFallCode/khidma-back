<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DelegationController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\HostController;
use App\Http\Controllers\Api\PavilionController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\ReservationExportController;
use App\Http\Controllers\Api\ResidenceController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomManagerController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\UtilisateurController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/ressources/{resource}', [ResourceController::class, 'show']);

Route::middleware('jwt.auth')->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/utilisateurs', [UtilisateurController::class, 'index']);
    Route::post('/utilisateurs', [UtilisateurController::class, 'store']);
    Route::get('/utilisateurs/info', [UtilisateurController::class, 'account']);
    Route::put('/utilisateurs/me/password', [UtilisateurController::class, 'changeOwnPassword']);
    Route::put('/utilisateurs/statut/{user}', [UtilisateurController::class, 'toggleStatus']);
    Route::put('/utilisateurs/{user}/password', [UtilisateurController::class, 'changePassword']);
    Route::get('/utilisateurs/{user}', [UtilisateurController::class, 'show']);

    Route::get('/residences', [ResidenceController::class, 'index']);
    Route::post('/residences', [ResidenceController::class, 'store']);
    Route::put('/residences', [ResidenceController::class, 'update']);
    Route::get('/residences/responsable/{username}', [ResidenceController::class, 'byManager']);
    Route::get('/residences/{residence}', [ResidenceController::class, 'show']);
    Route::delete('/residences/{residence}', [ResidenceController::class, 'destroy']);

    Route::get('/pavillons', [PavilionController::class, 'index']);
    Route::post('/pavillons', [PavilionController::class, 'store']);
    Route::get('/pavillons/residence/{residence}', [PavilionController::class, 'byResidence']);
    Route::put('/pavillons/{pavilion}', [PavilionController::class, 'update']);
    Route::get('/pavillons/{pavilion}', [PavilionController::class, 'show']);
    Route::delete('/pavillons/{pavilion}', [PavilionController::class, 'destroy']);

    Route::post('/chambres', [RoomController::class, 'store']);
    Route::put('/chambres', [RoomController::class, 'update']);
    Route::get('/chambres/pavillon/{pavilion}', [RoomController::class, 'byPavilion']);
    Route::get('/chambres/residence/{residence}/disponible/{from}/{to}', [RoomController::class, 'availableByResidence']);
    Route::get('/chambres/{room}', [RoomController::class, 'show']);
    Route::delete('/chambres/{room}', [RoomController::class, 'destroy']);

    Route::post('/assignments', [AssignmentController::class, 'store']);
    Route::get('/assignments', [AssignmentController::class, 'index']);
    Route::get('/assignments/agent/{agent}', [AssignmentController::class, 'byAgent']);
    Route::put('/assignments/{assignment}', [AssignmentController::class, 'update']);
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show']);
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy']);

    Route::post('/accueillants', [HostController::class, 'store']);
    Route::put('/accueillants', [HostController::class, 'update']);
    Route::get('/accueillants', [HostController::class, 'index']);
    Route::get('/accueillants/user/{username}', [HostController::class, 'byUsername']);
    Route::get('/accueillants/{host}', [HostController::class, 'show']);
    Route::delete('/accueillants/{host}', [HostController::class, 'destroy']);

    Route::post('/responsables', [RoomManagerController::class, 'store']);
    Route::get('/responsables', [RoomManagerController::class, 'index']);
    Route::get('/responsables/{roomManager}', [RoomManagerController::class, 'show']);
    Route::delete('/responsables/{roomManager}', [RoomManagerController::class, 'destroy']);

    Route::post('/delegations', [DelegationController::class, 'store']);
    Route::put('/delegations', [DelegationController::class, 'update']);
    Route::get('/delegations', [DelegationController::class, 'index']);
    Route::get('/delegations/{delegation}', [DelegationController::class, 'show']);
    Route::delete('/delegations/{delegation}', [DelegationController::class, 'destroy']);

    Route::post('/invites', [GuestController::class, 'store']);
    Route::delete('/invites/{guest}', [GuestController::class, 'destroy']);

    Route::post('/evenements', [EventController::class, 'store']);
    Route::get('/evenements', [EventController::class, 'index']);
    Route::get('/evenements/{event}', [EventController::class, 'show']);
    Route::delete('/evenements/{event}', [EventController::class, 'destroy']);

    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::put('/reservations', [ReservationController::class, 'update']);
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/exportation/residence/{residence}', [ReservationExportController::class, 'excel']);
    Route::get('/reservations/exportation/pdf/residence/{residence}', [ReservationExportController::class, 'pdf']);
    Route::get('/reservations/pavillon/{pavilion}/{from}/{to}', [ReservationController::class, 'byPavilion']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);

    Route::get('/stats/{residence}', [StatsController::class, 'totals']);
    Route::get('/stats/{residence}/chambres', [StatsController::class, 'availableRooms']);
});
