<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;

class ServiceController extends Controller
{
    /**
     * GET /api/services
     * Retourne la liste des services disponibles avec leur prix.
     * Utilisé par DoctorBookingPage pour le sélecteur de type de consultation.
     * Flutter attend : { data: [ { id, nom_service, prix } ] }
     */
    public function index()
    {
        $services = Service::orderBy('nom_service')->get(['id', 'nom_service', 'prix']);

        return response()->json([
            'status' => true,
            'data'   => $services
        ]);
    }
}
