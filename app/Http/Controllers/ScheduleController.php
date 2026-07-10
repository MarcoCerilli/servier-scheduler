<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleRequest;
use App\Models\Schedule;
use App\Models\ScheduleOccurrence;
use App\Services\IcsService;
use App\Services\QrCodeService;
use App\Services\RecurrenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly RecurrenceService $recurrenceService,
        private readonly QrCodeService     $qrCodeService,
        private readonly IcsService        $icsService,
    ) {}

    /**
     * Form di creazione — Pagina Vue/Inertia.
     */
    public function create(): Response
    {
        return Inertia::render('Schedules/Create', [
            'timezones' => $this->getGroupedTimezones(),
        ]);
    }

    /**
     * Salva la pianificazione, calcola occorrenze, genera QR.
     */
    public function store(StoreScheduleRequest $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validatedWithReminder();

        DB::transaction(function () use ($data, &$schedule) {
            $schedule = Schedule::create($data);

            // Calcola e salva le occorrenze
            $occurrences = $this->recurrenceService->calculate($schedule);

            $rows = [];
            foreach ($occurrences as $i => $occ) {
                $rows[] = [
                    'schedule_id' => $schedule->id,
                    'starts_at'   => $occ['starts_at']->toDateTimeString(),
                    'ends_at'     => $occ['ends_at']->toDateTimeString(),
                    'sort_order'  => $i,
                ];
            }

            if (! empty($rows)) {
                ScheduleOccurrence::insert($rows);
            }

            // Genera QR Code
            $qrPath = $this->qrCodeService->generate($schedule);
            $schedule->update(['qr_code_path' => $qrPath]);
        });

        return redirect()->route('schedule.success', $schedule->public_token);
    }

    /**
     * Pagina di successo dopo la creazione.
     * Mostra il link pubblico, il QR e il management URL.
     */
    public function success(string $token): Response
    {
        $schedule = Schedule::byPublicToken($token)->firstOrFail();

        return Inertia::render('Schedules/Success', [
            'schedule'       => [
                'title'          => $schedule->title,
                'public_url'     => $schedule->publicUrl(),
                'management_url' => $schedule->managementUrl(),
                'qr_url'         => $this->qrCodeService->publicUrl($schedule),
                'occurrences_count' => $schedule->occurrences()->count(),
            ],
        ]);
    }

    /**
     * Anteprima occorrenze via API (usata dal form Vue).
     * Restituisce le prime N occorrenze senza salvare.
     */
    public function preview(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'frequency'              => 'required|in:once,daily,weekly,monthly',
            'start_date'             => 'required|date_format:Y-m-d',
            'end_date'               => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'duration_days'          => 'nullable|integer|min:1|max:365',
            'times_of_day'           => 'required|array|min:1|max:10',
            'times_of_day.*'         => ['string', 'regex:/^([01]\d|2[0-3]):([0-5]\d)$/'],
            'days_of_week'           => 'nullable|array',
            'days_of_week.*'         => 'integer|between:1,7',
            'event_duration_minutes' => 'required|integer|min:1|max:1440',
            'excluded_dates'         => 'nullable|array|max:100',
            'excluded_dates.*'       => 'date_format:Y-m-d',
            'timezone'               => 'required|string|in:' . implode(',', timezone_identifiers_list()),
        ]);

        // Crea un modello temporaneo senza salvarlo
        $fake = new Schedule($request->only([
            'frequency', 'start_date', 'end_date', 'duration_days',
            'times_of_day', 'days_of_week', 'event_duration_minutes',
            'excluded_dates', 'timezone',
        ]));
        $fake->start_date = \Carbon\Carbon::parse($request->start_date);
        if ($request->end_date) {
            $fake->end_date = \Carbon\Carbon::parse($request->end_date);
        }

        $occurrences = $this->recurrenceService->calculate($fake);

        return response()->json([
            'total' => count($occurrences),
            'items' => array_map(fn ($o) => [
                'starts_at' => $o['starts_at']->toISOString(),
                'ends_at'   => $o['ends_at']->toISOString(),
            ], array_slice($occurrences, 0, 20)),
        ]);
    }

    /**
     * Lista dei timezone raggruppati per area geografica.
     */
    private function getGroupedTimezones(): array
    {
        $grouped = [];
        foreach (timezone_identifiers_list() as $tz) {
            $parts = explode('/', $tz);
            $area  = count($parts) > 1 ? $parts[0] : 'Other';
            $grouped[$area][] = $tz;
        }
        ksort($grouped);
        return $grouped;
    }
}
