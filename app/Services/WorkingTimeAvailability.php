<?php

namespace App\Services;

use App\Models\Creneau;
use App\Models\MedecinWorkingTime;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkingTimeAvailability
{
    /**
     * Materialize one date from the recurring schedule. A null result means the
     * doctor has no recurring schedule and should use legacy dated availability.
     */
    public function ensureDateSlots(int $medecinId, string $date): ?Collection
    {
        $schedule = MedecinWorkingTime::where('medecin_id', $medecinId)->first();
        if (! $schedule) {
            return null;
        }

        $day = Carbon::parse($date);
        $dateString = $day->toDateString();
        $scheduleSlots = $schedule->slotsForDate($day);
        $expectedStarts = collect($scheduleSlots)->pluck('heure_debut')->all();

        $obsoleteSlots = Creneau::where('medecin_id', $medecinId)
            ->whereDate('date', $dateString)
            ->where('generated_by_working_time', true)
            ->where('disponible', true);
        if ($expectedStarts !== []) {
            $obsoleteSlots->whereNotIn('heure_debut', $expectedStarts);
        }
        $obsoleteSlots->delete();

        foreach ($scheduleSlots as $slot) {
            $creneau = Creneau::where('medecin_id', $medecinId)
                ->whereDate('date', $dateString)
                ->where('heure_debut', $slot['heure_debut'])
                ->first();

            if (! $creneau) {
                $creneau = Creneau::create([
                    'medecin_id' => $medecinId,
                    'date' => $dateString,
                    'heure_debut' => $slot['heure_debut'],
                    'heure_fin' => $slot['heure_fin'],
                    'disponible' => true,
                    'generated_by_working_time' => true,
                ]);
            }

            if (
                $creneau->generated_by_working_time
                && $creneau->disponible
                && $creneau->heure_fin !== $slot['heure_fin']
            ) {
                $creneau->update(['heure_fin' => $slot['heure_fin']]);
            }
        }

        return Creneau::where('medecin_id', $medecinId)
            ->whereDate('date', $dateString)
            ->orderBy('heure_debut')
            ->get();
    }
}
