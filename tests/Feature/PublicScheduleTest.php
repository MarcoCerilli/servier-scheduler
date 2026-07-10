<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\ScheduleOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function createScheduleWithOccurrence(): Schedule
    {
        $schedule = Schedule::create([
            'title'                  => 'Riunione Test',
            'description'            => 'Descrizione test',
            'timezone'               => 'Europe/Rome',
            'start_date'             => '2024-06-01',
            'end_date'               => '2024-06-30',
            'frequency'              => 'weekly',
            'days_of_week'           => [1, 3],
            'times_of_day'           => ['09:00', '14:00'],
            'event_duration_minutes' => 60,
            'excluded_dates'         => [],
        ]);

        ScheduleOccurrence::create([
            'schedule_id' => $schedule->id,
            'starts_at'   => '2024-06-03 07:00:00', // 09:00 Roma
            'ends_at'     => '2024-06-03 08:00:00',
            'sort_order'  => 0,
        ]);

        return $schedule;
    }

    /**
     * TC1: Accesso con token valido → 200.
     */
    public function test_public_page_with_valid_token_returns_200(): void
    {
        $schedule = $this->createScheduleWithOccurrence();

        $response = $this->get('/s/' . $schedule->public_token);

        $response->assertStatus(200);
        $response->assertSee($schedule->title);
        $response->assertSee('Aggiungi al calendario');
    }

    /**
     * TC2: Accesso con token non valido → 404.
     */
    public function test_public_page_with_invalid_token_returns_404(): void
    {
        $invalidToken = str_repeat('a', 64); // 64 char validi come formato ma inesistenti
        $response = $this->get('/s/' . $invalidToken);
        $response->assertStatus(404);
    }

    /**
     * TC3: Token con formato non corretto → 404 (regex route).
     */
    public function test_public_page_with_malformed_token_returns_404(): void
    {
        // Token con caratteri non validi (non hex)
        $response = $this->get('/s/invalid-token-with-hyphens');
        $response->assertStatus(404);
    }

    /**
     * TC4: Endpoint .ics con token valido → 200 + Content-Type text/calendar.
     */
    public function test_ics_endpoint_with_valid_token_returns_calendar(): void
    {
        $schedule = $this->createScheduleWithOccurrence();

        $response = $this->get('/s/' . $schedule->public_token . '/calendar.ics');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/calendar', $response->headers->get('Content-Type'));
        $response->assertSee('BEGIN:VCALENDAR');
    }

    /**
     * TC5: Endpoint .ics con token non valido → 404.
     */
    public function test_ics_endpoint_with_invalid_token_returns_404(): void
    {
        $invalidToken = str_repeat('b', 64);
        $response = $this->get('/s/' . $invalidToken . '/calendar.ics');
        $response->assertStatus(404);
    }

    /**
     * TC6: La pagina pubblica mostra il numero corretto di eventi.
     */
    public function test_public_page_shows_occurrences_count(): void
    {
        $schedule = $this->createScheduleWithOccurrence();

        $response = $this->get('/s/' . $schedule->public_token);
        $response->assertStatus(200);
        $response->assertSee('1'); // 1 evento creato
    }

    /**
     * TC7: Header di sicurezza presenti nella risposta.
     */
    public function test_security_headers_present_on_public_page(): void
    {
        $schedule = $this->createScheduleWithOccurrence();

        $response = $this->get('/s/' . $schedule->public_token);

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    /**
     * TC8: L'endpoint .ics include header Content-Disposition: inline.
     */
    public function test_ics_endpoint_has_inline_content_disposition(): void
    {
        $schedule = $this->createScheduleWithOccurrence();

        $response = $this->get('/s/' . $schedule->public_token . '/calendar.ics');

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('inline', $disposition);
        $this->assertStringContainsString('.ics', $disposition);
    }
}
