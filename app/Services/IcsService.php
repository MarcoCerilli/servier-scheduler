<?php

namespace App\Services;

use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Spatie\IcalendarGenerator\Components\Alert;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class IcsService
{
    /**
     * Genera il contenuto del file .ics per una pianificazione.
     * Usa le occorrenze pre-calcolate dal DB.
     */
    public function generate(Schedule $schedule): string
    {
        $appDomain = config('app.domain',
            rtrim(preg_replace('#https?://#', '', config('app.url')), '/') ?: 'localhost'
        );

        $calendar = Calendar::create()
            ->name($schedule->title)
            ->description($schedule->description ?? '')
            ->withoutAutoTimezoneComponents();

        // Usa le occorrenze già precaricate se disponibili (evita N+1 e permette test unitari senza DB)
        if ($schedule->relationLoaded('occurrences')) {
            $occurrences = $schedule->getRelation('occurrences');
        } else {
            $occurrences = $schedule->occurrences()->orderBy('starts_at')->get();
        }

        // Ricava l'ID del modello in modo sicuro (ULID oppure stringa custom nei test)
        $scheduleId = $schedule->getKey() ?? $schedule->id ?? 'unknown';

        foreach ($occurrences as $index => $occurrence) {
            // UID stabile: schedule-id + index + dominio — RFC 5545 §3.8.4.7
            $uid = sprintf(
                '%s-%04d@%s',
                $scheduleId,
                $index + 1,
                $appDomain
            );

            $startsAt = CarbonImmutable::parse($occurrence->starts_at)
                ->setTimezone($schedule->timezone);
            $endsAt = CarbonImmutable::parse($occurrence->ends_at)
                ->setTimezone($schedule->timezone);

            $event = Event::create($schedule->title)
                ->uniqueIdentifier($uid)
                ->startsAt($startsAt)
                ->endsAt($endsAt)
                ->description($schedule->description ?? '');

            // Promemoria (VALARM) — usa API pubblica Alert::minutesBeforeStart()
            if ($schedule->reminder_minutes !== null && $schedule->reminder_minutes > 0) {
                $alert = Alert::minutesBeforeStart(
                    $schedule->reminder_minutes,
                    $schedule->title
                );
                $event->alert($alert);
            }

            $calendar->event($event);
        }

        return $calendar->get();
    }
}
