<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Creneau;
use App\Models\MedecinWorkingTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedecinWorkingTimeController extends Controller
{
    public function show(Request $request)
    {
        if ($request->user()->role !== 'medecin') {
            return response()->json(['message' => 'Accès réservé aux médecins.'], 403);
        }

        $schedule = MedecinWorkingTime::where('medecin_id', $request->user()->id)->first();

        return response()->json([
            'status' => true,
            'data' => $schedule
                ? $this->format($schedule)
                : $this->defaultSchedule(),
        ]);
    }

    public function update(Request $request)
    {
        if ($request->user()->role !== 'medecin') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'global_duration_minutes' => 'required|integer|min:10|max:120',
            'days' => 'required|array|size:7',
            'days.*.weekday' => 'required|integer|between:1,7',
            'days.*.active' => 'required|boolean',
            'days.*.duration_override_minutes' => 'nullable|integer|min:10|max:120',
            'days.*.slots' => 'required|array|min:1|max:20',
            'days.*.slots.*.start' => 'required|date_format:H:i',
            'days.*.slots.*.end' => 'required|date_format:H:i',
            'exceptions' => 'present|array|max:100',
            'exceptions.*.date' => 'required|date_format:Y-m-d',
            'exceptions.*.label' => 'required|string|max:120',
        ]);

        $validator->after(function ($validator) use ($request) {
            $globalDuration = $request->input('global_duration_minutes');
            if (is_numeric($globalDuration) && (int) $globalDuration % 10 !== 0) {
                $validator->errors()->add('global_duration_minutes', 'La durée doit avancer par pas de 10 minutes.');
            }

            $days = $request->input('days', []);
            if (! is_array($days)) {
                $days = [];
            }
            $weekdays = [];
            foreach ($days as $index => $day) {
                if (! is_array($day)) {
                    continue;
                }
                $weekday = $day['weekday'] ?? null;
                if ($weekday !== null) {
                    $weekdays[] = (int) $weekday;
                }
                $duration = $day['duration_override_minutes'] ?? null;
                if ($duration !== null && is_numeric($duration) && (int) $duration % 10 !== 0) {
                    $validator->errors()->add(
                        "days.$index.duration_override_minutes",
                        'La durée doit avancer par pas de 10 minutes.'
                    );
                }

                $slots = $day['slots'] ?? [];
                if (! is_array($slots)) {
                    continue;
                }
                $ordered = array_values($slots);
                usort($ordered, fn ($a, $b) => strcmp(
                    is_array($a) && is_string($a['start'] ?? null) ? $a['start'] : '',
                    is_array($b) && is_string($b['start'] ?? null) ? $b['start'] : ''
                ));
                for ($slotIndex = 0; $slotIndex < count($ordered); $slotIndex++) {
                    $slot = $ordered[$slotIndex];
                    if (
                        ! is_array($slot)
                        || ! is_string($slot['start'] ?? null)
                        || ! is_string($slot['end'] ?? null)
                    ) {
                        continue;
                    }
                    if ($slot['end'] <= $slot['start']) {
                        $validator->errors()->add(
                            "days.$index.slots.$slotIndex.end",
                            'L’heure de fin doit être après l’heure de début.'
                        );
                    }
                    $previous = $ordered[$slotIndex - 1] ?? null;
                    if (
                        $slotIndex > 0
                        && is_array($previous)
                        && is_string($previous['end'] ?? null)
                        && $slot['start'] < $previous['end']
                    ) {
                        $validator->errors()->add(
                            "days.$index.slots.$slotIndex.start",
                            'Les plages horaires d’une journée ne peuvent pas se chevaucher.'
                        );
                    }
                }
            }

            if (count(array_unique($weekdays)) !== count($weekdays)) {
                $validator->errors()->add('days', 'Chaque jour de la semaine doit être défini une seule fois.');
            }

            $exceptions = $request->input('exceptions', []);
            $exceptionDates = is_array($exceptions) ? array_column($exceptions, 'date') : [];
            if (count(array_unique($exceptionDates)) !== count($exceptionDates)) {
                $validator->errors()->add('exceptions', 'Une date d’exception ne peut apparaître qu’une fois.');
            }
        });

        $validated = $validator->validate();
        usort($validated['days'], fn (array $a, array $b) => $a['weekday'] <=> $b['weekday']);

        $schedule = MedecinWorkingTime::updateOrCreate(
            ['medecin_id' => $request->user()->id],
            [
                'global_duration_minutes' => $validated['global_duration_minutes'],
                'days' => array_values($validated['days']),
                'exceptions' => array_values($validated['exceptions']),
            ]
        );

        Creneau::where('medecin_id', $request->user()->id)
            ->whereDate('date', '>=', today()->toDateString())
            ->where('generated_by_working_time', true)
            ->where('disponible', true)
            ->delete();

        return response()->json([
            'status' => true,
            'message' => 'Horaires hebdomadaires enregistrés.',
            'data' => $this->format($schedule),
        ]);
    }

    private function format(MedecinWorkingTime $schedule): array
    {
        return [
            'global_duration_minutes' => $schedule->global_duration_minutes,
            'days' => $schedule->days,
            'exceptions' => $schedule->exceptions,
        ];
    }

    private function defaultSchedule(): array
    {
        $windows = [
            ['start' => '08:00', 'end' => '12:00'],
            ['start' => '14:00', 'end' => '17:30'],
        ];

        return [
            'global_duration_minutes' => 30,
            'days' => array_map(
                fn (int $weekday) => [
                    'weekday' => $weekday,
                    'active' => $weekday <= 5,
                    'duration_override_minutes' => null,
                    'slots' => $windows,
                ],
                range(1, 7)
            ),
            'exceptions' => [],
        ];
    }
}
