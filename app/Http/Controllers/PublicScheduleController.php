<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Services\IcsService;
use App\Services\QrCodeService;
use Illuminate\Http\Response;

class PublicScheduleController extends Controller
{
    public function __construct(
        private readonly IcsService    $icsService,
        private readonly QrCodeService $qrCodeService,
    ) {}

    /**
     * Pagina pubblica di riepilogo della pianificazione.
     * Accessibile a tutti con il token pubblico.
     */
    public function show(string $token): \Illuminate\Http\Response|\Illuminate\Contracts\View\View
    {
        // Lookup solo per token — nessun ID esposto nell'URL
        $schedule = Schedule::byPublicToken($token)
            ->with(['occurrences' => fn ($q) => $q->orderBy('starts_at')->limit(500)])
            ->firstOrFail();

        $qrUrl = $this->qrCodeService->publicUrl($schedule);

        // Raggruppa le occorrenze per data per la visualizzazione
        $occurrencesByDate = $schedule->occurrences
            ->groupBy(fn ($o) => \Carbon\Carbon::parse($o->starts_at)
                ->setTimezone($schedule->timezone)
                ->format('Y-m-d'))
            ->map(fn ($group) => $group->map(fn ($o) => [
                'starts_at' => \Carbon\Carbon::parse($o->starts_at)
                    ->setTimezone($schedule->timezone)
                    ->format('H:i'),
                'ends_at' => \Carbon\Carbon::parse($o->ends_at)
                    ->setTimezone($schedule->timezone)
                    ->format('H:i'),
            ]));

        // Statistiche
        $totalCount    = $schedule->occurrences()->count();
        $firstOccurrence = $schedule->occurrences()->orderBy('starts_at')->first();
        $lastOccurrence  = $schedule->occurrences()->orderByDesc('starts_at')->first();

        return view('public.schedule', compact(
            'schedule',
            'qrUrl',
            'occurrencesByDate',
            'totalCount',
            'firstOccurrence',
            'lastOccurrence',
        ));
    }

    /**
     * Endpoint che genera e restituisce il file .ics.
     * Header MIME corretti senza forzare il download.
     */
    public function ics(string $token): Response
    {
        $schedule = Schedule::byPublicToken($token)
            ->with('occurrences')
            ->firstOrFail();

        $icsContent = $this->icsService->generate($schedule);

        $filename = \Str::slug($schedule->title) . '.ics';

        return response($icsContent, 200, [
            // text/calendar senza attachment: lascia al browser/OS decidere come aprirlo
            'Content-Type'        => 'text/calendar; charset=utf-8',
            // inline: il browser prova ad aprirlo con un'app; se non può, scarica
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            // Sicurezza
            'X-Content-Type-Options' => 'nosniff',
            // No cache: dati sempre freschi
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }
}
