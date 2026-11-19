<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Medecin;

class MedecinController extends Controller
{
    /**
     * Liste tous les médecins validés et complets.
     */
    public function index(Request $request)
    {
        $query = Medecin::with(['user', 'services'])
            ->where('is_validated', true)
            ->whereHas('user', function ($q) {
                $q->where('is_profile_completed', true);
            });

        if ($request->filled('specialite')) {
            $spec = $request->specialite;
            $query->where(function ($q) use ($spec) {
                $q->where('specialite', 'like', '%' . $spec . '%');
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            // Nested OR pour ne pas casser is_validated / is_profile_completed
            $query->where(function ($q) use ($search) {
                $q->where('specialite', 'like', '%' . $search . '%')
                    ->orWhere('hopital', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('nom', 'like', '%' . $search . '%');
                    });
            });
        }

        $medecins = $query->get()->map(fn($m) => $this->formatMedecin($m));

        return response()->json([
            'status' => true,
            'count'  => $medecins->count(),
            'data'   => $medecins,
        ]);
    }

    /**
     * Voir les détails d'un médecin spécifique.
     */
    public function show($id)
    {
        $medecin = Medecin::with(['user', 'services'])->find($id);

        if (!$medecin) {
            return response()->json(['message' => 'Médecin non trouvé'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $this->formatMedecin($medecin),
        ]);
    }

    /**
     * GET /api/medecins/autocomplete?q=derm
     * Retourne un petit ensemble de médecins pour suggestions pendant la saisie.
     */
    public function autocomplete(Request $request)
    {
        $q = trim($request->query('q', ''));
        if ($q === '') {
            return response()->json(['status' => true, 'data' => []]);
        }

        $query = Medecin::with(['user', 'services'])
            ->where('is_validated', true)
            ->whereHas('user', function ($uq) {
                $uq->where('is_profile_completed', true);
            })
            ->where(function ($w) use ($q) {
                $w->where('specialite', 'like', $q . '%')
                  ->orWhere('specialite', 'like', '%' . $q . '%')
                  ->orWhere('hopital', 'like', '%' . $q . '%')
                  ->orWhereHas('user', function ($u) use ($q) {
                      $u->where('nom', 'like', $q . '%')
                        ->orWhere('nom', 'like', '%' . $q . '%');
                  });
            })
            ->orderByRaw(
                'CASE WHEN lower(specialite) LIKE ? THEN 0 WHEN lower(hopital) LIKE ? THEN 1 ELSE 2 END',
                [mb_strtolower($q) . '%', mb_strtolower($q) . '%']
            )
            ->limit(10);

        $medecins = $query->get()->map(fn($m) => $this->formatMedecin($m));

        return response()->json([
            'status' => true,
            'data'   => $medecins,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'medecin') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $validated = $request->validate([
            'specialite'    => 'nullable|string|max:100',
            'hopital'       => 'nullable|string|max:150',
            'biographie'    => 'nullable|string',
            'disponibilite' => 'nullable|string',
        ]);

        $user->medecin()->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Informations professionnelles mises à jour.',
            'data'    => $this->formatMedecin($user->load('medecin')->medecin),
        ]);
    }

    // ── Helper : format unifié retourné au client Flutter ────────────────────

    private function formatMedecin(Medecin $m): array
    {
        $services = $m->relationLoaded('services')
            ? $m->services->map(fn ($s) => [
                'id' => $s->id,
                'nom_service' => $s->nom_service,
                'prix' => $s->prix,
                'mode' => $s->mode ?? 'presentiel',
                'specialite' => $s->specialite,
            ])->values()
            : collect();

        $modes = $services->pluck('mode')->unique()->values();
        $minPrice = $services->min('prix');

        return [
            'user_id'       => $m->user_id,
            'specialite'    => $m->specialite,
            'licence'       => $m->licence,
            'hopital'       => $m->hopital,
            'biographie'    => $m->biographie,
            'disponibilite' => $m->disponibilite,
            'is_validated'  => $m->is_validated,
            'services'      => $services,
            'modes'         => $modes,
            'prix_min'      => $minPrice,
            'user' => [
                'id'        => $m->user->id ?? null,
                'nom'       => $m->user->nom ?? null,
                'email'     => $m->user->email ?? null,
                'telephone' => $m->user->telephone ?? null,
                'photo_url' => $m->user->photo_url ?? null,
            ],
        ];
    }
}
