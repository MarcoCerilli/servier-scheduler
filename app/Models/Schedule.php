<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Schedule extends Model
{
    use HasUlids;

    protected $fillable = [
        'public_token',
        'management_token',
        'title',
        'description',
        'timezone',
        'start_date',
        'end_date',
        'duration_days',
        'frequency',
        'days_of_week',
        'times_of_day',
        'event_duration_minutes',
        'excluded_dates',
        'reminder_minutes',
        'qr_code_path',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'days_of_week'    => 'array',
        'times_of_day'    => 'array',
        'excluded_dates'  => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Schedule $schedule) {
            // Genera token pubblico crittograficamente sicuro
            $schedule->public_token     = $schedule->public_token     ?? self::generateUniqueToken('public_token');
            $schedule->management_token = $schedule->management_token ?? self::generateUniqueToken('management_token');
        });
    }

    private static function generateUniqueToken(string $column): string
    {
        do {
            // bin2hex(random_bytes(32)) → 64 caratteri hex da CSPRNG
            $token = bin2hex(random_bytes(32));
        } while (self::where($column, $token)->exists());

        return $token;
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(ScheduleOccurrence::class)->orderBy('starts_at');
    }

    /**
     * Calcola la data di fine effettiva della pianificazione.
     */
    public function effectiveEndDate(): ?\Carbon\Carbon
    {
        if ($this->end_date) {
            return $this->end_date;
        }

        if ($this->duration_days) {
            return $this->start_date->copy()->addDays($this->duration_days - 1);
        }

        return null; // frequenza once: solo start_date
    }

    /**
     * Scope per lookup tramite token pubblico.
     */
    public function scopeByPublicToken($query, string $token)
    {
        return $query->where('public_token', $token);
    }

    /**
     * URL pubblico della pianificazione.
     */
    public function publicUrl(): string
    {
        return route('schedule.public', $this->public_token);
    }

    /**
     * URL di gestione (solo per il creatore).
     */
    public function managementUrl(): string
    {
        return route('schedule.manage', [
            'token'   => $this->public_token,
            'key'     => $this->management_token,
        ]);
    }
}
