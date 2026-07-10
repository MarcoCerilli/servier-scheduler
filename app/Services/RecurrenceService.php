<?php

namespace App\Services;

use App\Models\Schedule;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use RRule\RRule;

class RecurrenceService
{
    /**
     * Calcola le occorrenze per una pianificazione e le restituisce come array
     * di array ['starts_at' => CarbonImmutable, 'ends_at' => CarbonImmutable].
     *
     * Per ogni orario in times_of_day viene generata una serie separata,
     * poi i risultati vengono uniti e ordinati per starts_at.
     */
    public function calculate(Schedule $schedule): array
    {
        $tz           = new DateTimeZone($schedule->timezone);
        $maxOcc       = (int) config('schedule.max_occurrences', 365);
        $durationMins = $schedule->event_duration_minutes;
        $effectiveEnd = $schedule->effectiveEndDate();

        // Costruisce un Set O(1) di date escluse (stringa 'Y-m-d' nel timezone corretto)
        $excludedSet = [];
        foreach ($schedule->excluded_dates ?? [] as $d) {
            $excludedSet[CarbonImmutable::parse($d, $schedule->timezone)->format('Y-m-d')] = true;
        }

        $allOccurrences = [];

        foreach ($schedule->times_of_day as $time) {
            [$hour, $minute] = array_map('intval', explode(':', $time));

            $dtStart = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i',
                sprintf(
                    '%s %02d:%02d',
                    $schedule->start_date->format('Y-m-d'),
                    $hour,
                    $minute
                ),
                $tz
            );

            if ($dtStart === false) {
                continue;
            }

            if ($schedule->frequency === 'once') {
                $starts = CarbonImmutable::instance($dtStart);
                if (! isset($excludedSet[$starts->format('Y-m-d')])) {
                    $allOccurrences[] = $this->buildOccurrence($starts, $durationMins);
                }
                continue;
            }

            $rruleParams = $this->buildRruleParams(
                $schedule,
                $dtStart,
                $effectiveEnd,
                $maxOcc,
                $tz
            );

            $rrule = new RRule($rruleParams);

            foreach ($rrule as $occurrence) {
                /** @var DateTimeImmutable $occurrence */
                $starts = CarbonImmutable::instance($occurrence)->setTimezone($schedule->timezone);

                if (isset($excludedSet[$starts->format('Y-m-d')])) {
                    continue;
                }

                $allOccurrences[] = $this->buildOccurrence($starts, $durationMins);
            }
        }

        // Ordina per starts_at
        usort($allOccurrences, fn ($a, $b) => $a['starts_at'] <=> $b['starts_at']);

        return $allOccurrences;
    }

    private function buildRruleParams(
        Schedule $schedule,
        DateTimeImmutable $dtStart,
        ?\Carbon\Carbon $effectiveEnd,
        int $maxOcc,
        DateTimeZone $tz
    ): array {
        $freq = match ($schedule->frequency) {
            'daily'   => RRule::DAILY,
            'weekly'  => RRule::WEEKLY,
            'monthly' => RRule::MONTHLY,
            default   => RRule::DAILY,
        };

        $params = [
            'FREQ'    => $freq,
            'DTSTART' => $dtStart,
            'COUNT'   => $maxOcc,
        ];

        if ($effectiveEnd) {
            // UNTIL = fine del giorno in UTC (inclusive)
            $until = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $effectiveEnd->format('Y-m-d') . ' 23:59:59',
                $tz
            )->setTimezone(new DateTimeZone('UTC'));
            $params['UNTIL'] = $until;
            unset($params['COUNT']); // UNTIL ha precedenza su COUNT
        }

        // Giorni della settimana (weekly e monthly con selezione giorni)
        if (in_array($schedule->frequency, ['weekly', 'monthly'], true) && ! empty($schedule->days_of_week)) {
            $params['BYDAY'] = $this->mapDaysOfWeek($schedule->days_of_week);
        }

        return $params;
    }

    /**
     * Mappa i numeri 1-7 (Mon-Sun) ai codici RRULE (MO, TU, WE, ...)
     */
    private function mapDaysOfWeek(array $days): string
    {
        $map = [1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA', 7 => 'SU'];
        return implode(',', array_map(fn ($d) => $map[$d], $days));
    }

    /**
     * Controlla se un'occorrenza cade in una data esclusa — O(1) lookup via Set.
     */
    private function isExcluded(CarbonImmutable $starts, array $excludedSet): bool
    {
        return isset($excludedSet[$starts->format('Y-m-d')]);
    }

    private function buildOccurrence(CarbonImmutable $starts, int $durationMins): array
    {
        return [
            'starts_at' => $starts,
            'ends_at'   => $starts->addMinutes($durationMins),
        ];
    }
}
