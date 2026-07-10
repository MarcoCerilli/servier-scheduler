<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ Str::limit($schedule->description ?? 'Pianificazione condivisa', 160) }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ e($schedule->title) }} — Servier Scheduler</title>

    {{-- Google Fonts: Inter --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #f9fafb;
            --surface: #ffffff;
            --surface2: #f3f4f6;
            --border: #e5e7eb;
            --text: #111827;
            --text-muted: #6b7280;
            --accent: #4f46e5;
            --accent-light: #4f46e5;
            --accent-glow: rgba(79,70,229,0.1);
            --success: #10b981;
            --warning: #f59e0b;
            --radius: 16px;
            --radius-sm: 8px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 1rem;
        }

        .container {
            max-width: 720px;
            margin: 0 auto;
            padding: 2rem 1rem 4rem;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--accent-glow);
            border: 1px solid var(--accent);
            color: var(--accent-light);
            padding: 4px 14px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: clamp(1.5rem, 5vw, 2.25rem);
            font-weight: 700;
            line-height: 1.2;
            background: linear-gradient(135deg, #111827 0%, var(--accent-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.75rem;
        }
        .description {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.6;
            max-width: 560px;
            margin: 0 auto;
        }

        /* Cards */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .card-title {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--accent-light);
            margin-bottom: 1rem;
        }

        /* Info grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
        }
        .info-item {
            background: var(--surface2);
            border-radius: var(--radius-sm);
            padding: 1rem;
        }
        .info-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }
        .info-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text);
        }

        /* Contatore eventi */
        .event-count {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: linear-gradient(135deg, var(--accent-glow) 0%, transparent 100%);
            border: 1px solid var(--accent);
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
        }
        .event-count-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent-light);
            line-height: 1;
        }
        .event-count-label {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* Occorrenze */
        .occurrences-list {
            max-height: 300px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--border) transparent;
        }
        .occurrence-day {
            margin-bottom: 0.75rem;
        }
        .occurrence-date {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.375rem;
            padding-bottom: 0.375rem;
            border-bottom: 1px solid var(--border);
        }
        .occurrence-times {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .time-chip {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 3px 12px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text);
        }

        /* Date escluse */
        .excluded-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .excluded-chip {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 999px;
            padding: 3px 12px;
            font-size: 0.8rem;
            color: #fca5a5;
        }

        /* QR Code */
        .qr-section {
            text-align: center;
        }
        .qr-image {
            width: 200px;
            height: 200px;
            border-radius: var(--radius-sm);
            border: 4px solid var(--border);
            background: white;
            padding: 8px;
            margin: 0 auto 1rem;
            display: block;
        }
        .qr-label {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Pulsante CTA */
        .btn-calendar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--accent) 0%, #4f46e5 100%);
            color: white;
            text-decoration: none;
            border-radius: var(--radius);
            font-size: 1.05rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 20px rgba(99,102,241,0.3);
        }
        .btn-calendar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(99,102,241,0.45);
        }
        .btn-calendar:active {
            transform: translateY(0);
        }
        .btn-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }
        .btn-note {
            text-align: center;
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 0.75rem;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .info-grid { grid-template-columns: 1fr; }
            .container { padding: 1rem 0.75rem 3rem; }
        }

        /* Animazioni */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card, .event-count { animation: fadeUp 0.4s ease both; }
        .card:nth-child(2) { animation-delay: 0.1s; }
        .card:nth-child(3) { animation-delay: 0.2s; }
    </style>
</head>
<body>
<main class="container">

    {{-- Header --}}
    <div class="header">
        <div class="header-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/></svg>
            Pianificazione Condivisa
        </div>
        <h1>{{ e($schedule->title) }}</h1>
        @if($schedule->description)
            <p class="description">{{ e($schedule->description) }}</p>
        @endif
    </div>

    {{-- Contatore eventi --}}
    <div class="event-count">
        <div class="event-count-number">{{ $totalCount }}</div>
        <div>
            <div style="font-weight:600; color:var(--text);">{{ $totalCount === 1 ? 'Evento' : 'Eventi' }} in calendario</div>
            <div class="event-count-label">
                @if($firstOccurrence && $lastOccurrence)
                    Dal {{ \Carbon\Carbon::parse($firstOccurrence->starts_at)->setTimezone($schedule->timezone)->translatedFormat('d M Y') }}
                    al {{ \Carbon\Carbon::parse($lastOccurrence->ends_at)->setTimezone($schedule->timezone)->translatedFormat('d M Y') }}
                @endif
            </div>
        </div>
    </div>

    {{-- Dettagli pianificazione --}}
    <div class="card">
        <div class="card-title">Dettagli</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Frequenza</div>
                <div class="info-value">
                    @switch($schedule->frequency)
                        @case('once') Evento singolo @break
                        @case('daily') Giornaliera @break
                        @case('weekly') Settimanale @break
                        @case('monthly') Mensile @break
                    @endswitch
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Timezone</div>
                <div class="info-value">{{ e($schedule->timezone) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Durata evento</div>
                <div class="info-value">
                    @if($schedule->event_duration_minutes >= 60)
                        {{ floor($schedule->event_duration_minutes / 60) }}h 
                        @if($schedule->event_duration_minutes % 60 > 0)
                            {{ $schedule->event_duration_minutes % 60 }}min
                        @endif
                    @else
                        {{ $schedule->event_duration_minutes }} min
                    @endif
                </div>
            </div>
            @if($schedule->reminder_minutes)
            <div class="info-item">
                <div class="info-label">Promemoria</div>
                <div class="info-value">
                    @if($schedule->reminder_minutes >= 1440)
                        {{ intdiv($schedule->reminder_minutes, 1440) }} {{ intdiv($schedule->reminder_minutes, 1440) === 1 ? 'giorno' : 'giorni' }} prima
                    @elseif($schedule->reminder_minutes >= 60)
                        {{ intdiv($schedule->reminder_minutes, 60) }}h prima
                    @else
                        {{ $schedule->reminder_minutes }} min prima
                    @endif
                </div>
            </div>
            @endif
            @if($schedule->days_of_week)
            <div class="info-item">
                <div class="info-label">Giorni</div>
                <div class="info-value">
                    @php
                        $dayNames = [1=>'Lun', 2=>'Mar', 3=>'Mer', 4=>'Gio', 5=>'Ven', 6=>'Sab', 7=>'Dom'];
                    @endphp
                    {{ implode(', ', array_map(fn($d) => $dayNames[$d] ?? $d, $schedule->days_of_week)) }}
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Orari ed occorrenze --}}
    @if($occurrencesByDate->isNotEmpty())
    <div class="card">
        <div class="card-title">Orari (prime {{ min($occurrencesByDate->count(), 30) }} date)</div>
        <div class="occurrences-list">
            @foreach($occurrencesByDate->take(30) as $date => $times)
            <div class="occurrence-day">
                <div class="occurrence-date">
                    {{ \Carbon\Carbon::parse($date)->locale('it')->isoFormat('dddd D MMMM YYYY') }}
                </div>
                <div class="occurrence-times">
                    @foreach($times as $t)
                    <span class="time-chip">{{ $t['starts_at'] }} – {{ $t['ends_at'] }}</span>
                    @endforeach
                </div>
            </div>
            @endforeach
            @if($occurrencesByDate->count() > 30)
            <p style="color:var(--text-muted); font-size:0.82rem; margin-top:0.75rem; text-align:center;">
                + altri {{ $occurrencesByDate->count() - 30 }} giorni nel file calendario
            </p>
            @endif
        </div>
    </div>
    @endif

    {{-- Date escluse --}}
    @if($schedule->excluded_dates && count($schedule->excluded_dates) > 0)
    <div class="card">
        <div class="card-title">Date escluse</div>
        <div class="excluded-list">
            @foreach($schedule->excluded_dates as $d)
            <span class="excluded-chip">{{ \Carbon\Carbon::parse($d)->locale('it')->isoFormat('D MMM YYYY') }}</span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- QR Code --}}
    @if($qrUrl)
    <div class="card">
        <div class="card-title">QR Code</div>
        <div class="qr-section">
            <img src="{{ e($qrUrl) }}" alt="QR Code pianificazione {{ e($schedule->title) }}" class="qr-image" width="200" height="200">
            <div class="qr-label">Scansiona per aprire questa pagina</div>
        </div>
    </div>
    @endif

    {{-- CTA Aggiungi al calendario --}}
    @if($totalCount > 0)
    <div style="margin-top: 1.5rem;">
        <a href="{{ str_replace(['http://', 'https://'], 'webcal://', route('schedule.ics', $schedule->public_token)) }}"
           class="btn-calendar"
           id="add-to-calendar-btn"
           aria-label="Aggiungi al calendario">
            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
                <line x1="12" y1="14" x2="12" y2="18"/>
                <line x1="10" y1="16" x2="14" y2="16"/>
            </svg>
            Aggiungi al calendario
        </a>
        <p class="btn-note">
            Apre il file .ics compatibile con Google Calendar, Apple Calendar, Outlook e altri
        </p>
    </div>
    @endif

</main>
</body>
</html>
