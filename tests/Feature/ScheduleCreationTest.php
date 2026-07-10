<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\ScheduleOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScheduleCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'                  => 'Riunione settimanale',
            'description'            => 'Note della riunione',
            'timezone'               => 'Europe/Rome',
            'start_date'             => '2024-06-03',
            'end_date'               => '2024-06-30',
            'frequency'              => 'weekly',
            'days_of_week'           => [1, 3, 5],
            'times_of_day'           => ['09:00', '14:00'],
            'event_duration_minutes' => 60,
            'excluded_dates'         => [],
            'reminder_value'         => 30,
            'reminder_unit'          => 'minutes',
        ], $overrides);
    }

    /**
     * TC1: Creazione pianificazione valida → redirect alla success page.
     */
    public function test_valid_schedule_creation_redirects_to_success(): void
    {
        $response = $this->post('/crea', $this->validPayload());

        // Deve fare redirect (302)
        $response->assertStatus(302);

        // Verifica che la schedule sia stata salvata
        $this->assertDatabaseCount('schedules', 1);

        $schedule = Schedule::first();
        $this->assertNotNull($schedule);
        $this->assertEquals('Riunione settimanale', $schedule->title);
        $this->assertEquals(30, $schedule->reminder_minutes);

        $response->assertRedirect('/crea/' . $schedule->public_token . '/successo');
    }

    /**
     * TC2: Il form con titolo mancante deve restituire errore di validazione.
     */
    public function test_missing_title_returns_validation_error(): void
    {
        $response = $this->post('/crea', $this->validPayload(['title' => '']));
        $response->assertSessionHasErrors('title');
    }

    /**
     * TC3: Il form con timezone non valida deve fallire.
     */
    public function test_invalid_timezone_returns_validation_error(): void
    {
        $response = $this->post('/crea', $this->validPayload(['timezone' => 'Europe/Fake_City']));
        $response->assertSessionHasErrors('timezone');
    }

    /**
     * TC4: Frequenza settimanale senza giorni selezionati → errore.
     */
    public function test_weekly_without_days_returns_error(): void
    {
        $response = $this->post('/crea', $this->validPayload([
            'frequency'    => 'weekly',
            'days_of_week' => [],
        ]));
        $response->assertSessionHasErrors('days_of_week');
    }

    /**
     * TC5: Orario con formato non valido → errore.
     */
    public function test_invalid_time_format_returns_error(): void
    {
        $response = $this->post('/crea', $this->validPayload([
            'times_of_day' => ['25:00'], // ora non valida
        ]));
        $response->assertSessionHasErrors();
    }

    /**
     * TC6: Titolo con HTML → errore di validazione (protezione XSS).
     */
    public function test_html_in_title_returns_validation_error(): void
    {
        $response = $this->post('/crea', $this->validPayload([
            'title' => '<script>alert("xss")</script>',
        ]));
        $response->assertSessionHasErrors('title');
    }

    /**
     * TC7: Il QR Code viene generato e il path viene salvato nel DB.
     */
    public function test_qr_code_is_generated_on_creation(): void
    {
        $this->post('/crea', $this->validPayload());

        $schedule = Schedule::first();
        $this->assertNotNull($schedule->qr_code_path);
        $this->assertStringStartsWith('qrcodes/', $schedule->qr_code_path);

        Storage::disk('public')->assertExists($schedule->qr_code_path);
    }

    /**
     * TC8: Token pubblico e management token vengono generati e sono univoci e sicuri.
     */
    public function test_public_and_management_tokens_are_generated(): void
    {
        $this->post('/crea', $this->validPayload());

        $schedule = Schedule::first();

        $this->assertNotNull($schedule->public_token);
        $this->assertNotNull($schedule->management_token);
        $this->assertEquals(64, strlen($schedule->public_token));
        $this->assertEquals(64, strlen($schedule->management_token));
        $this->assertNotEquals($schedule->public_token, $schedule->management_token);

        // Verifico che i token siano hex validi (da bin2hex(random_bytes(32)))
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $schedule->public_token);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $schedule->management_token);
    }

    /**
     * TC9: Le occorrenze vengono calcolate e salvate nel DB.
     */
    public function test_occurrences_are_calculated_and_saved(): void
    {
        $this->post('/crea', $this->validPayload([
            'frequency'    => 'weekly',
            'start_date'   => '2024-06-03', // Lunedì
            'end_date'     => '2024-06-16', // Domenica della 2a settimana
            'days_of_week' => [1, 3, 5],   // Lun Mer Ven
            'times_of_day' => ['09:00'],
        ]));

        $schedule = Schedule::first();
        // 2 settimane × 3 giorni × 1 orario = 6 occorrenze
        $this->assertEquals(6, $schedule->occurrences()->count());
    }

    /**
     * TC10: reminder_minutes viene calcolato correttamente da value+unit.
     */
    public function test_reminder_minutes_calculated_from_value_and_unit(): void
    {
        $this->post('/crea', $this->validPayload([
            'reminder_value' => 2,
            'reminder_unit'  => 'days',
        ]));

        $schedule = Schedule::first();
        $this->assertEquals(2880, $schedule->reminder_minutes); // 2 × 1440
    }
}
