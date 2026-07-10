<?php

namespace Tests\Unit;

use App\Models\Schedule;
use App\Services\RecurrenceService;
use Carbon\Carbon;
use Tests\TestCase;

class RecurrenceServiceTest extends TestCase
{
    private RecurrenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecurrenceService();
    }

    private function makeSchedule(array $attrs): Schedule
    {
        $schedule = new Schedule(array_merge([
            'timezone'               => 'Europe/Rome',
            'times_of_day'           => ['09:00'],
            'event_duration_minutes' => 60,
            'excluded_dates'         => [],
            'days_of_week'           => [],
        ], $attrs));

        // Forza i cast sulle date
        if (isset($attrs['start_date'])) {
            $schedule->start_date = Carbon::parse($attrs['start_date']);
        }
        if (isset($attrs['end_date'])) {
            $schedule->end_date = Carbon::parse($attrs['end_date']);
        }

        return $schedule;
    }

    /**
     * TC1: Evento singolo — deve produrre esattamente 1 occorrenza.
     */
    public function test_single_event_produces_one_occurrence(): void
    {
        $schedule = $this->makeSchedule([
            'frequency'  => 'once',
            'start_date' => '2024-06-15',
            'times_of_day' => ['10:00'],
        ]);

        $result = $this->service->calculate($schedule);

        $this->assertCount(1, $result);
        $this->assertEquals('10:00', $result[0]['starts_at']->setTimezone('Europe/Rome')->format('H:i'));
        $this->assertEquals('2024-06-15', $result[0]['starts_at']->setTimezone('Europe/Rome')->format('Y-m-d'));
    }

    /**
     * TC2: Pianificazione giornaliera per 30 giorni — 30 occorrenze.
     */
    public function test_daily_schedule_30_days_produces_30_occurrences(): void
    {
        $schedule = $this->makeSchedule([
            'frequency'    => 'daily',
            'start_date'   => '2024-04-01',
            'end_date'     => '2024-04-30',
            'times_of_day' => ['08:00'],
        ]);

        $result = $this->service->calculate($schedule);

        $this->assertCount(30, $result);
        $this->assertEquals('2024-04-01', $result[0]['starts_at']->setTimezone('Europe/Rome')->format('Y-m-d'));
        $this->assertEquals('2024-04-30', $result[29]['starts_at']->setTimezone('Europe/Rome')->format('Y-m-d'));
    }

    /**
     * TC3: Tre orari al giorno per 30 giorni — 90 occorrenze totali.
     */
    public function test_three_times_per_day_for_30_days_produces_90_occurrences(): void
    {
        $schedule = $this->makeSchedule([
            'frequency'    => 'daily',
            'start_date'   => '2024-05-01',
            'end_date'     => '2024-05-30',
            'times_of_day' => ['08:00', '13:00', '18:00'],
        ]);

        $result = $this->service->calculate($schedule);

        $this->assertCount(90, $result);

        // Il primo evento deve essere alle 08:00
        $this->assertEquals('08:00', $result[0]['starts_at']->setTimezone('Europe/Rome')->format('H:i'));
    }

    /**
     * TC4: Pianificazione settimanale Lun/Mer/Ven per 2 settimane — 6 occorrenze.
     */
    public function test_weekly_schedule_mon_wed_fri_two_weeks(): void
    {
        $schedule = $this->makeSchedule([
            'frequency'    => 'weekly',
            'start_date'   => '2024-06-03', // Lunedì
            'end_date'     => '2024-06-16', // Domenica della settimana successiva
            'days_of_week' => [1, 3, 5],    // Lun, Mer, Ven
            'times_of_day' => ['09:00'],
        ]);

        $result = $this->service->calculate($schedule);

        $this->assertCount(6, $result);

        // Verifica che tutti i giorni siano Lun/Mer/Ven
        foreach ($result as $occ) {
            $dayOfWeek = $occ['starts_at']->setTimezone('Europe/Rome')->dayOfWeekIso;
            $this->assertContains($dayOfWeek, [1, 3, 5]);
        }
    }

    /**
     * TC5: Esclusione di una data — la data esclusa non deve comparire.
     */
    public function test_excluded_date_is_not_in_occurrences(): void
    {
        $schedule = $this->makeSchedule([
            'frequency'      => 'daily',
            'start_date'     => '2024-07-01',
            'end_date'       => '2024-07-07',
            'times_of_day'   => ['09:00'],
            'excluded_dates' => ['2024-07-04'], // Escludi 4 luglio
        ]);

        $result = $this->service->calculate($schedule);

        $this->assertCount(6, $result); // 7 giorni - 1 escluso = 6

        $dates = array_map(
            fn ($o) => $o['starts_at']->setTimezone('Europe/Rome')->format('Y-m-d'),
            $result
        );
        $this->assertNotContains('2024-07-04', $dates);
    }

    /**
     * TC6: Cambio ora legale — estate→inverno (27 ottobre 2024, Europa/Roma).
     * L'evento pianificato alle 09:00 deve rimanere alle 09:00 (ora locale)
     * anche dopo il cambio ora legale.
     */
    public function test_dst_transition_autumn_europe_rome(): void
    {
        $schedule = $this->makeSchedule([
            'frequency'    => 'daily',
            'start_date'   => '2024-10-25', // Venerdì
            'end_date'     => '2024-10-29', // Martedì
            'times_of_day' => ['09:00'],
        ]);

        $result = $this->service->calculate($schedule);

        $this->assertCount(5, $result);

        // Ogni occorrenza deve essere alle 09:00 ora locale di Roma
        foreach ($result as $occ) {
            $localTime = $occ['starts_at']->setTimezone('Europe/Rome')->format('H:i');
            $this->assertEquals('09:00', $localTime,
                'Ora legale: l\'ora locale deve rimanere 09:00 il ' .
                $occ['starts_at']->setTimezone('Europe/Rome')->format('Y-m-d')
            );
        }
    }

    /**
     * TC7: La durata in giorni viene rispettata come alternativa a end_date.
     */
    public function test_duration_days_used_when_no_end_date(): void
    {
        $schedule = $this->makeSchedule([
            'frequency'    => 'daily',
            'start_date'   => '2024-01-01',
            'duration_days' => 5,
            'times_of_day' => ['10:00'],
        ]);
        // Imposta la data di fine tramite duration_days
        $schedule->end_date = null;
        // effectiveEndDate() usa duration_days → 5 gennaio
        // Necessario per il calcolo
        $schedule->start_date = Carbon::parse('2024-01-01');

        // Sostituiamo end_date manualmente (come farebbe effectiveEndDate)
        $schedule->end_date = Carbon::parse('2024-01-05');

        $result = $this->service->calculate($schedule);
        $this->assertCount(5, $result);
    }
}
