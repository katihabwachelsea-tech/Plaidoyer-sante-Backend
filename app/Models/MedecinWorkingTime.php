<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class MedecinWorkingTime extends Model
{
    protected $fillable = [
        'medecin_id',
        'global_duration_minutes',
        'days',
        'exceptions',
    ];

    protected $casts = [
        'global_duration_minutes' => 'integer',
        'days' => 'array',
        'exceptions' => 'array',
    ];

    public function slotsForDate(CarbonInterface $date): array
    {
        $dateString = $date->toDateString();
        foreach ($this->exceptions ?? [] as $exception) {
            if (($exception['date'] ?? null) === $dateString) {
                return [];
            }
        }

        $day = collect($this->days ?? [])->first(
            fn ($item) => is_array($item)
                && (int) ($item['weekday'] ?? 0) === $date->dayOfWeekIso
        );

        if (! $day || ! ($day['active'] ?? false)) {
            return [];
        }

        $duration = (int) ($day['duration_override_minutes'] ?? $this->global_duration_minutes);
        $slots = [];
        foreach ($day['slots'] ?? [] as $window) {
            $start = $this->minutesSinceMidnight($window['start']);
            $end = $this->minutesSinceMidnight($window['end']);

            for ($minute = $start; $minute + $duration <= $end; $minute += $duration) {
                $slots[] = [
                    'heure_debut' => $this->formatTime($minute),
                    'heure_fin' => $this->formatTime($minute + $duration),
                ];
            }
        }

        usort($slots, fn (array $a, array $b) => strcmp($a['heure_debut'], $b['heure_debut']));

        return $slots;
    }

    private function minutesSinceMidnight(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour * 60 + $minute;
    }

    private function formatTime(int $minute): string
    {
        return sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
    }
}
