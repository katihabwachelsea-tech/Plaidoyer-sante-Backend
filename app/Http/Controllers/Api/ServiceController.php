<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medecin;
use App\Models\Service;

class ServiceController extends Controller
{
    /**
     * GET /api/services — catalogue global
     */
    public function index()
    {
        $services = Service::orderBy('nom_service')
            ->get(['id', 'nom_service', 'prix', 'mode', 'specialite']);

        return response()->json([
            'status' => true,
            'data'   => $services,
        ]);
    }

    /**
     * GET /api/patient/doctors/{id}/services
     * Services liés au médecin (pivot), sinon fallback spécialité.
     */
    public function forDoctor($id)
    {
        $medecin = Medecin::with('services')->find($id);

        if (!$medecin) {
            return response()->json(['status' => false, 'message' => 'Médecin introuvable'], 404);
        }

        $services = $medecin->services()
            ->orderBy('nom_service')
            ->get(['services.id', 'nom_service', 'prix', 'mode', 'specialite']);

        if ($services->isEmpty() && $medecin->specialite) {
            $services = Service::where('specialite', $medecin->specialite)
                ->orWhere('specialite', 'Générale')
                ->orderBy('nom_service')
                ->get(['id', 'nom_service', 'prix', 'mode', 'specialite']);
        }

        if ($services->isEmpty()) {
            $services = Service::orderBy('nom_service')
                ->limit(4)
                ->get(['id', 'nom_service', 'prix', 'mode', 'specialite']);
        }

        return response()->json([
            'status' => true,
            'data'   => $services,
        ]);
    }
}
