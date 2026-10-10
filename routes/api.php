<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\MedecinController;
use App\Http\Controllers\Api\MedecinProfileController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\CreneauController;
use App\Http\Controllers\Api\MedecinPatientController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SpecialiteController;
use App\Http\Controllers\Api\PatientAppointmentController;
use App\Http\Controllers\Api\PatientProfileController;
use App\Http\Controllers\Api\MedecinDashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\MessageController;

/*
|--------------------------------------------------------------------------
| Routes Publiques (sans token)
|--------------------------------------------------------------------------
*/

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Réinitialisation mot de passe — 3 étapes
Route::post('/password/forgot',      [PasswordResetController::class, 'sendCode']);
Route::post('/password/verify-code', [PasswordResetController::class, 'verifyCode']);
Route::post('/password/reset',       [PasswordResetController::class, 'resetPassword']);

// Spécialités et services — publics pour la recherche patient
Route::get('/specialites', [SpecialiteController::class, 'index']);
Route::get('/services',    [ServiceController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Routes Protégées — Bearer token Sanctum requis
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |------------------------------------------------------------------
    | Auth & Profil utilisateur
    |------------------------------------------------------------------
    */
    Route::post('/logout', [AuthController::class, 'logout']);

    // Retourne l'utilisateur connecté avec ses relations patient/medecin
    Route::get('/me', [ProfileController::class, 'me']);

    // Onboarding — route générique (détecte le rôle automatiquement)
    Route::post('/complete-profile', [ProfileController::class, 'completeProfile']);

    // Onboarding — routes dédiées par rôle (appelées par Flutter AuthService.completeProfile)
    Route::post('/medecin/complete-profile', [ProfileController::class, 'completeProfile']);
    Route::post('/patient/complete-profile', [ProfileController::class, 'completeProfile']);

    /*
    |------------------------------------------------------------------
    | Module Médecin — Profil
    | MedecinApiService.getProfile()   → GET  /api/medecin/profile
    | MedecinApiService.updateProfile()→ PUT  /api/medecin/profile
    |------------------------------------------------------------------
    */
    Route::get('/medecin/profile', [MedecinProfileController::class, 'show']);
    Route::put('/medecin/profile', [MedecinProfileController::class, 'update']);
    Route::post('/medecin/profile/photo', [MedecinProfileController::class, 'uploadPhoto']); // upload photo
    Route::get('/medecin/dashboard', [MedecinDashboardController::class, 'show']);

    // Mise à jour via l'ancienne route (rétro-compatibilité)
    Route::put('/medecin/update', [MedecinController::class, 'update']);

    /*
    |------------------------------------------------------------------
    | Module Médecin — Créneaux de disponibilité
    | MedecinApiService.getCreneaux()   → GET    /api/medecin/creneaux?from=&to=
    | MedecinApiService.createCreneau() → POST   /api/medecin/creneaux
    |                                  → DELETE /api/medecin/creneaux/{id}
    |------------------------------------------------------------------
    */
    Route::get('/medecin/creneaux',             [CreneauController::class, 'index']);
    Route::post('/medecin/creneaux',            [CreneauController::class, 'store']);
    Route::post('/medecin/creneaux/bulk',       [CreneauController::class, 'bulk']);
    Route::put('/medecin/creneaux/{id}',        [CreneauController::class, 'update']);
    Route::delete('/medecin/creneaux/{id}',     [CreneauController::class, 'destroy']);

    /*
    |------------------------------------------------------------------
    | Module Médecin — Carnet de patients (SQLite ↔ MySQL sync)
    | ApiService.fetchPatients()    → GET    /api/medecin/patients
    | ApiService.createPatient()   → POST   /api/medecin/patients
    | ApiService.updatePatient(id) → PUT    /api/medecin/patients/{id}
    | ApiService.deletePatient(id) → DELETE /api/medecin/patients/{id}
    |------------------------------------------------------------------
    */
    Route::get('/medecin/patients',         [MedecinPatientController::class, 'index']);
    Route::post('/medecin/patients',        [MedecinPatientController::class, 'store']);
    Route::put('/medecin/patients/{id}',    [MedecinPatientController::class, 'update']);
    Route::delete('/medecin/patients/{id}', [MedecinPatientController::class, 'destroy']);

    /*
    |------------------------------------------------------------------
    | Module Médecin — Rendez-vous
    |
    | IMPORTANT : la route /today-count DOIT être déclarée AVANT
    | /{medecin_id}/{date} pour éviter un conflit de routing.
    |
    | MedecinApiService.getTodayAppointmentsCount() → GET /api/medecin/appointments/today-count
    | MedecinApiService.getAppointments()           → GET /api/medecin/appointments[?today=1]
    |------------------------------------------------------------------
    */
    Route::get('/medecin/appointments/today-count', [AppointmentController::class, 'todayCount']);
    Route::get('/medecin/appointments',             [AppointmentController::class, 'index']);
    Route::post('/medecin/appointments/{id}/accept', [AppointmentController::class, 'acceptByMedecin']);
    Route::post('/medecin/appointments/{id}/refuse', [AppointmentController::class, 'refuseByMedecin']);
    Route::post('/medecin/appointments/{id}/cancel', [AppointmentController::class, 'cancelByMedecin']);

    /*
    |------------------------------------------------------------------
    | Module Patient — Profil & Rendez-vous
    |------------------------------------------------------------------
    */
    Route::get('/patients/{id}',  [PatientController::class, 'show']);
    Route::put('/patients/{id}',  [PatientController::class, 'update']);
    // Dossier complet patient — accessible au médecin
    Route::get('/medecin/patients/{id}/dossier', [PatientController::class, 'dossier']);

    // Profil patient dédié (sans id dans l'URL)
    Route::get('/patient/profile', [PatientProfileController::class, 'show']);
    Route::put('/patient/profile', [PatientProfileController::class, 'update']);
    Route::post('/patient/profile/photo', [PatientProfileController::class, 'uploadPhoto']);

    // Notifications in-app (patient + médecin)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);

    // Tokens FCM — enregistrement/suppression
    Route::post('/device-token',   [DeviceTokenController::class, 'register']);
    Route::delete('/device-token', [DeviceTokenController::class, 'remove']);

    // Messagerie instantanée — liée à un RDV
    Route::get('/appointments/{id}/messages',              [MessageController::class, 'index']);
    Route::post('/appointments/{id}/messages',             [MessageController::class, 'store']);
    Route::get('/appointments/{id}/messages/unread-count', [MessageController::class, 'unreadCount']);

    // Rendez-vous patient — un endpoint par action
    Route::get('/patient/appointments', [PatientAppointmentController::class, 'index']);
    Route::post('/patient/appointments', [PatientAppointmentController::class, 'store']);
    Route::post('/patient/appointments/{id}/cancel', [PatientAppointmentController::class, 'cancel']);
    Route::post('/patient/appointments/{id}/pay', [PatientAppointmentController::class, 'pay']);

    // Recherche et réservation côté patient
    Route::get('/patient/doctors', [MedecinController::class, 'index']);
    Route::get('/patient/doctors/{id}', [MedecinController::class, 'show']);
    Route::get('/patient/doctors/{id}/slots/{date}', [AppointmentController::class, 'patientSlots']);
    Route::get('/patient/doctors/{id}/services', [ServiceController::class, 'forDoctor']);
    Route::get('/patient/services', [ServiceController::class, 'index']);

    // Compatibilité des anciens écrans
    Route::get('/appointments', [PatientAppointmentController::class, 'index']);

    /*
    |------------------------------------------------------------------
    | Module Médecins — Liste publique (avec token)
    | AuthService.fetchDoctors() → GET /api/medecins[?specialite=&search=]
    |------------------------------------------------------------------
    */
    Route::get('/medecins',      [MedecinController::class, 'index']);
    Route::get('/medecins/{id}', [MedecinController::class, 'show']);

    /*
    |------------------------------------------------------------------
    | Réservation & Paiement
    | DoctorBookingPage → POST /api/appointments/request
    | InvoiceController → POST /api/invoices/{id}/pay
    |------------------------------------------------------------------
    */
    Route::get('/appointments/slots/{medecin_id}/{date}', [AppointmentController::class, 'getAvailableSlots']);
    Route::post('/appointments/request', [PatientAppointmentController::class, 'store']);
    Route::post('/invoices/{id}/pay',    [InvoiceController::class, 'pay']);

    /*
    |------------------------------------------------------------------
    | Consultations (Dossier médical)
    | ConsultationFormPage → POST /api/consultations
    | HealthDetailPage     → GET  /api/consultations/{appointment_id}
    | HealthHistoryPage    → GET  /api/patient/medical-record
    |------------------------------------------------------------------
    */
    Route::post('/consultations',              [ConsultationController::class, 'store']);
    Route::get('/consultations/{rdv_id}',      [ConsultationController::class, 'show']);
    Route::get('/patient/medical-record',      [ConsultationController::class, 'getMedicalRecord']);

    /*
    |------------------------------------------------------------------
    | Administration
    |------------------------------------------------------------------
    */
    Route::post('/admin/validate-medecin/{id}', [AdminController::class, 'validateMedecin']);
    Route::get('/admin/pending-medecins',        [AdminController::class, 'getPendingMedecins']);
});

/*
|--------------------------------------------------------------------------
| Routes de débogage — SUPPRIMÉES (ne pas remettre en production)
|--------------------------------------------------------------------------
*/
