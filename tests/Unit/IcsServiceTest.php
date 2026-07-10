<?php

namespace Tests\Unit;

use App\Models\Schedule;
use App\Models\ScheduleOccurrence;
use App\Services\IcsService;
use Carbon\Carbon;
use Tests\TestCase;

class IcsServiceTest extends TestCase
{
    private IcsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IcsService();
    }

    private function makeScheduleWithOccurrences(array $occurrenceDates): Schedule
    {
        $schedule = new Schedule([
            'title'                  => 'Test Event',
            'description'            => 'Test description',
            'timezone'               => 'Europe/Rome',
            'event_duration_minutes' => 60,
            'reminder_minutes'       => null,
        ]);
        // Imposta un ID ULID valido manualmente per il test
        $schedule->setAttribute('id', '01ARZ3NDEKTSV4RRFFQ69G5FAV');

        $occs = collect($occurrenceDates)->map(function ($date, $i) {
            $occ = new ScheduleOccurrence([
                'starts_at'  => Carbon::parse($date)->setTimezone('UTC'),
                'ends_at'    => Carbon::parse($date)->setTimezone('UTC')->addHour(),
                'sort_order' => $i,
            ]);
            return $occ;
        });

        // Inietta le occorrenze tramite relazione mockata
        $schedule->setRelation('occurrences', $occs);

        return $schedule;
    }

    /**
     * TC1: Il file .ics deve contenere i marcatori standard VCALENDAR.
     */
    public function test_ics_contains_vcalendar_wrapper(): void
    {
        $schedule = $this->makeScheduleWithOccurrences(['2024-06-15 09:00:00']);
        $ics = $this->service->generate($schedule);

        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('END:VCALENDAR', $ics);
        $this->assertStringContainsString('VERSION:2.0', $ics);
        $this->assertStringContainsString('PRODID:', $ics);
    }

    /**
     * TC2: Ogni evento deve avere un UID stabile e univoco.
     */
    public function test_ics_events_have_stable_uid(): void
    {
        $schedule = $this->makeScheduleWithOccurrences([
            '2024-06-15 09:00:00',
            '2024-06-16 09:00:00',
            '2024-06-17 09:00:00',
        ]);

        $ics = $this->service->generate($schedule);

        // Conta i blocchi VEVENT
        $vevents = substr_count($ics, 'BEGIN:VEVENT');
        $this->assertEquals(3, $vevents);

        // Estrai tutti gli UID
        preg_match_all('/^UID:(.+)$/m', $ics, $matches);
        $uids = $matches[1];
        $this->assertCount(3, $uids);

        // Verifica unicità
        $this->assertEquals(count($uids), count(array_unique($uids)));

        // Verifica formato: contiene l'ID dello schedule
        foreach ($uids as $uid) {
            $this->assertStringContainsString('01ARZ3NDEKTSV4RRFFQ69G5FAV', trim($uid));
        }
    }

    /**
     * TC3: Il VALARM deve essere presente se reminder_minutes > 0.
     * Alert::minutesBeforeStart() genera TRIGGER:-PT{n}M
     */
    public function test_ics_contains_valarm_when_reminder_set(): void
    {
        $schedule = $this->makeScheduleWithOccurrences(['2024-06-15 09:00:00']);
        $schedule->reminder_minutes = 30;

        $ics = $this->service->generate($schedule);

        $this->assertStringContainsString('BEGIN:VALARM', $ics);
        // Il trigger è in formato iCal durata: -PT30M
        $this->assertMatchesRegularExpression('/TRIGGER[^\n]*-PT30M/', $ics);
        $this->assertStringContainsString('END:VALARM', $ics);
    }

    /**
     * TC4: Nessun VALARM se reminder_minutes è null o 0.
     */
    public function test_ics_no_valarm_when_no_reminder(): void
    {
        $schedule = $this->makeScheduleWithOccurrences(['2024-06-15 09:00:00']);
        $schedule->reminder_minutes = null;

        $ics = $this->service->generate($schedule);

        $this->assertStringNotContainsString('BEGIN:VALARM', $ics);
    }

    /**
     * TC5: Il titolo deve essere presente in ogni VEVENT.
     */
    public function test_ics_contains_event_summary(): void
    {
        $schedule = $this->makeScheduleWithOccurrences(['2024-06-15 09:00:00']);
        $ics = $this->service->generate($schedule);

        $this->assertStringContainsString('SUMMARY:Test Event', $ics);
    }
}
