<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Nessun login richiesto per MVP
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:3',
                'max:255',
                // Nessun tag HTML nei titoli
                function ($attribute, $value, $fail) {
                    if ($value !== strip_tags($value)) {
                        $fail('Il campo titolo non può contenere HTML.');
                    }
                },
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
                function ($attribute, $value, $fail) {
                    if ($value && $value !== strip_tags($value)) {
                        $fail('Il campo descrizione non può contenere HTML.');
                    }
                },
            ],
            'timezone' => [
                'required',
                'string',
                // Valida solo timezone PHP ufficiali — evita injection
                Rule::in(timezone_identifiers_list()),
            ],
            'start_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'end_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                'required_without:duration_days',
            ],
            'duration_days' => [
                'nullable',
                'integer',
                'min:1',
                'max:3650',
                'required_without:end_date',
            ],
            'frequency' => [
                'required',
                Rule::in(['once', 'daily', 'weekly', 'monthly']),
            ],
            'days_of_week' => [
                'nullable',
                'array',
                'min:1',
                Rule::requiredIf(fn () => $this->input('frequency') === 'weekly'),
            ],
            'days_of_week.*' => [
                'integer',
                Rule::in([1, 2, 3, 4, 5, 6, 7]), // 1=Lun, 7=Dom
            ],
            'times_of_day' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],
            'times_of_day.*' => [
                'required',
                'string',
                'regex:/^([01]\d|2[0-3]):([0-5]\d)$/', // HH:MM
            ],
            'event_duration_minutes' => [
                'required',
                'integer',
                'min:1',
                'max:1440',
            ],
            'excluded_dates' => [
                'nullable',
                'array',
                'max:100',
            ],
            'excluded_dates.*' => [
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
            'reminder_value' => [
                'nullable',
                'integer',
                'min:0',
                'max:99999',
            ],
            'reminder_unit' => [
                'nullable',
                'string',
                Rule::in(['minutes', 'hours', 'days']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'                => 'Il titolo è obbligatorio.',
            'title.max'                     => 'Il titolo non può superare 255 caratteri.',
            'timezone.in'                   => 'Il fuso orario selezionato non è valido.',
            'start_date.required'           => 'La data di inizio è obbligatoria.',
            'end_date.after_or_equal'       => 'La data di fine deve essere successiva alla data di inizio.',
            'end_date.required_without'     => 'Specificare la data di fine o la durata in giorni.',
            'duration_days.required_without'=> 'Specificare la durata in giorni o la data di fine.',
            'frequency.in'                  => 'La frequenza selezionata non è valida.',
            'days_of_week.required_if'      => 'Per la frequenza settimanale è necessario selezionare almeno un giorno.',
            'times_of_day.required'         => 'È necessario specificare almeno un orario.',
            'times_of_day.*.regex'          => 'Il formato orario deve essere HH:MM.',
            'event_duration_minutes.min'    => 'La durata minima dell\'evento è 1 minuto.',
            'event_duration_minutes.max'    => 'La durata massima dell\'evento è 1440 minuti (24 ore).',
            'excluded_dates.*.date_format'  => 'Le date escluse devono essere nel formato AAAA-MM-GG.',
            'reminder_unit.in'              => 'L\'unità di promemoria non è valida.',
        ];
    }

    /**
     * Prepara i dati prima della validazione.
     * Converte reminder_value + reminder_unit in reminder_minutes.
     */
    protected function prepareForValidation(): void
    {
        // Normalizza times_of_day: rimuovi duplicati e ordina
        if ($this->has('times_of_day')) {
            $times = array_values(array_unique($this->input('times_of_day', [])));
            sort($times);
            $this->merge(['times_of_day' => $times]);
        }

        // Normalizza days_of_week: rimuovi duplicati
        if ($this->has('days_of_week')) {
            $days = array_values(array_unique(array_map('intval', $this->input('days_of_week', []))));
            sort($days);
            $this->merge(['days_of_week' => $days]);
        }
    }

    /**
     * Ritorna i dati validati con reminder_minutes calcolati.
     */
    public function validatedWithReminder(): array
    {
        $data = $this->validated();

        // Calcola reminder_minutes da value + unit
        $value = (int) ($data['reminder_value'] ?? 0);
        $unit  = $data['reminder_unit'] ?? 'minutes';

        if ($value > 0) {
            $data['reminder_minutes'] = match ($unit) {
                'hours' => $value * 60,
                'days'  => $value * 1440,
                default => $value,
            };
        } else {
            $data['reminder_minutes'] = null;
        }

        unset($data['reminder_value'], $data['reminder_unit']);

        return $data;
    }
}
