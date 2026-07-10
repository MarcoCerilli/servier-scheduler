<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OccurrencePreview from '@/Components/Schedule/OccurrencePreview.vue';

const props = defineProps({
    timezones: Object,
});

// --- Step logic ---
const currentStep = ref(1);
const totalSteps  = 5;

const stepLabels = ['Base', 'Periodo', 'Frequenza', 'Orari', 'Riepilogo'];

function nextStep() { if (currentStep.value < totalSteps) currentStep.value++; }
function prevStep() { if (currentStep.value > 1) currentStep.value--; }

// --- Form ---
const form = useForm({
    title:                   '',
    description:             '',
    timezone:                'Europe/Rome',
    start_date:              '',
    end_date:                '',
    duration_days:           '',
    end_mode:                'end_date', // 'end_date' | 'duration_days'
    frequency:               'weekly',
    days_of_week:            [],
    times_of_day:            ['09:00'],
    event_duration_minutes:  60,
    excluded_dates:          [],
    reminder_value:          '',
    reminder_unit:           'minutes',
});

// --- Helpers ---
const dayNames = [
    { value: 1, label: 'Lun' },
    { value: 2, label: 'Mar' },
    { value: 3, label: 'Mer' },
    { value: 4, label: 'Gio' },
    { value: 5, label: 'Ven' },
    { value: 6, label: 'Sab' },
    { value: 7, label: 'Dom' },
];

function toggleDay(day) {
    const idx = form.days_of_week.indexOf(day);
    if (idx === -1) form.days_of_week.push(day);
    else form.days_of_week.splice(idx, 1);
}

function addTime() {
    if (form.times_of_day.length < 10) {
        form.times_of_day.push('09:00');
    }
}

function removeTime(idx) {
    if (form.times_of_day.length > 1) {
        form.times_of_day.splice(idx, 1);
    }
}

function addExcludedDate() {
    form.excluded_dates.push('');
}

function removeExcludedDate(idx) {
    form.excluded_dates.splice(idx, 1);
}

// --- Submit ---
function submit() {
    // Prepara payload per la submission
    const payload = { ...form };
    if (payload.end_mode === 'duration_days') {
        payload.end_date = null;
    } else {
        payload.duration_days = null;
    }
    delete payload.end_mode;

    // Filtra date escluse vuote
    payload.excluded_dates = payload.excluded_dates.filter(Boolean);

    form.post(route('schedule.store'), {
        data: payload,
        onError: () => {
            // Torna al primo step con errori
            currentStep.value = 1;
        },
    });
}

// --- Validazione step per step ---
const stepValid = computed(() => {
    switch (currentStep.value) {
        case 1:
            return form.title.trim().length >= 3 && form.timezone;
        case 2:
            if (!form.start_date) return false;
            if (form.end_mode === 'end_date') return !!form.end_date;
            if (form.end_mode === 'duration_days') return parseInt(form.duration_days) >= 1;
            return true;
        case 3:
            if (form.frequency === 'weekly') return form.days_of_week.length > 0;
            return true;
        case 4:
            return form.times_of_day.length > 0 &&
                   form.times_of_day.every(t => /^([01]\d|2[0-3]):([0-5]\d)$/.test(t)) &&
                   parseInt(form.event_duration_minutes) >= 1;
        default:
            return true;
    }
});
</script>

<template>
    <Head title="Crea Pianificazione" />

    <AppLayout>
        <form class="form-card" @submit.prevent="submit">

            <!-- Progress bar -->
            <div class="progress-bar">
                <div
                    v-for="(label, i) in stepLabels"
                    :key="i"
                    class="progress-step"
                    :class="{
                        'active':    currentStep === i + 1,
                        'completed': currentStep > i + 1
                    }"
                >
                    <div class="step-dot">
                        <svg v-if="currentStep > i + 1" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        <span v-else>{{ i + 1 }}</span>
                    </div>
                    <span class="step-label">{{ label }}</span>
                </div>
            </div>

            <!-- Errori globali -->
            <div v-if="Object.keys(form.errors).length" class="error-banner">
                <strong>Correggi i seguenti errori:</strong>
                <ul>
                    <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                </ul>
            </div>

            <!-- Step 1: Informazioni base -->
            <div v-show="currentStep === 1" class="step-content">
                <h2 class="step-title">Informazioni base</h2>

                <div class="field">
                    <label for="title" class="field-label">Titolo <span class="required">*</span></label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        class="field-input"
                        :class="{ 'error': form.errors.title }"
                        placeholder="es. Riunione settimanale team"
                        maxlength="255"
                        required
                        autocomplete="off"
                    />
                    <span v-if="form.errors.title" class="field-error">{{ form.errors.title }}</span>
                </div>

                <div class="field">
                    <label for="description" class="field-label">Descrizione / Note</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        class="field-input"
                        :class="{ 'error': form.errors.description }"
                        placeholder="Aggiungi note o dettagli..."
                        rows="3"
                        maxlength="5000"
                    ></textarea>
                    <span v-if="form.errors.description" class="field-error">{{ form.errors.description }}</span>
                </div>

                <div class="field">
                    <label for="timezone" class="field-label">Fuso orario <span class="required">*</span></label>
                    <select
                        id="timezone"
                        v-model="form.timezone"
                        class="field-input"
                    >
                        <optgroup
                            v-for="(zones, area) in timezones"
                            :key="area"
                            :label="area"
                        >
                            <option v-for="tz in zones" :key="tz" :value="tz">{{ tz }}</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <!-- Step 2: Periodo -->
            <div v-show="currentStep === 2" class="step-content">
                <h2 class="step-title">Periodo</h2>

                <div class="field">
                    <label for="start_date" class="field-label">Data di inizio <span class="required">*</span></label>
                    <input
                        id="start_date"
                        v-model="form.start_date"
                        type="date"
                        class="field-input"
                        :class="{ 'error': form.errors.start_date }"
                        required
                    />
                    <span v-if="form.errors.start_date" class="field-error">{{ form.errors.start_date }}</span>
                </div>

                <div class="field">
                    <label class="field-label">Fine del periodo <span class="required">*</span></label>
                    <div class="toggle-group">
                        <button
                            type="button"
                            class="toggle-btn"
                            :class="{ 'active': form.end_mode === 'end_date' }"
                            @click="form.end_mode = 'end_date'"
                        >Data di fine</button>
                        <button
                            type="button"
                            class="toggle-btn"
                            :class="{ 'active': form.end_mode === 'duration_days' }"
                            @click="form.end_mode = 'duration_days'"
                        >Durata in giorni</button>
                        <button
                            type="button"
                            class="toggle-btn"
                            :class="{ 'active': form.frequency === 'once' }"
                            @click="form.frequency = 'once'; form.end_mode = 'none'"
                        >Evento singolo</button>
                    </div>
                </div>

                <div v-if="form.end_mode === 'end_date'" class="field">
                    <label for="end_date" class="field-label">Data di fine</label>
                    <input
                        id="end_date"
                        v-model="form.end_date"
                        type="date"
                        class="field-input"
                        :class="{ 'error': form.errors.end_date }"
                        :min="form.start_date"
                    />
                    <span v-if="form.errors.end_date" class="field-error">{{ form.errors.end_date }}</span>
                </div>

                <div v-if="form.end_mode === 'duration_days'" class="field">
                    <label for="duration_days" class="field-label">Durata in giorni</label>
                    <input
                        id="duration_days"
                        v-model.number="form.duration_days"
                        type="number"
                        class="field-input"
                        :class="{ 'error': form.errors.duration_days }"
                        min="1"
                        max="3650"
                        placeholder="es. 30"
                    />
                    <span v-if="form.errors.duration_days" class="field-error">{{ form.errors.duration_days }}</span>
                </div>
            </div>

            <!-- Step 3: Frequenza -->
            <div v-show="currentStep === 3" class="step-content">
                <h2 class="step-title">Frequenza</h2>

                <div class="field" v-if="form.frequency !== 'once'">
                    <label class="field-label">Frequenza <span class="required">*</span></label>
                    <div class="freq-grid">
                        <button
                            v-for="f in [
                                { value: 'daily',   label: 'Giornaliera', icon: '📅' },
                                { value: 'weekly',  label: 'Settimanale', icon: '📆' },
                                { value: 'monthly', label: 'Mensile',     icon: '🗓️' },
                            ]"
                            :key="f.value"
                            type="button"
                            class="freq-btn"
                            :class="{ 'active': form.frequency === f.value }"
                            @click="form.frequency = f.value"
                        >
                            <span class="freq-icon">{{ f.icon }}</span>
                            <span>{{ f.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Giorni della settimana -->
                <div v-if="form.frequency === 'weekly' || form.frequency === 'monthly'" class="field">
                    <label class="field-label">
                        {{ form.frequency === 'weekly' ? 'Giorni della settimana' : 'Giorni della settimana (opzionale)' }}
                        <span v-if="form.frequency === 'weekly'" class="required">*</span>
                    </label>
                    <div class="days-grid">
                        <button
                            v-for="day in dayNames"
                            :key="day.value"
                            type="button"
                            class="day-btn"
                            :class="{ 'active': form.days_of_week.includes(day.value) }"
                            @click="toggleDay(day.value)"
                        >{{ day.label }}</button>
                    </div>
                    <span v-if="form.errors.days_of_week" class="field-error">{{ form.errors.days_of_week }}</span>
                </div>

                <!-- Date da escludere -->
                <div class="field">
                    <label class="field-label">Date da escludere</label>
                    <div v-for="(_, idx) in form.excluded_dates" :key="idx" class="excluded-row">
                        <input
                            :id="'excluded_date_' + idx"
                            v-model="form.excluded_dates[idx]"
                            type="date"
                            class="field-input"
                            :min="form.start_date"
                        />
                        <button type="button" class="btn-remove" @click="removeExcludedDate(idx)" aria-label="Rimuovi data esclusa">✕</button>
                    </div>
                    <button type="button" class="btn-add" @click="addExcludedDate">
                        + Aggiungi data da escludere
                    </button>
                </div>
            </div>

            <!-- Step 4: Orari -->
            <div v-show="currentStep === 4" class="step-content">
                <h2 class="step-title">Orari e durata</h2>

                <div class="field">
                    <label class="field-label">Orari <span class="required">*</span></label>
                    <p class="field-hint">Aggiungi uno o più orari per lo stesso giorno. Ogni orario genererà una serie di eventi separata.</p>
                    <div v-for="(_, idx) in form.times_of_day" :key="idx" class="time-row">
                        <input
                            :id="'time_' + idx"
                            v-model="form.times_of_day[idx]"
                            type="time"
                            class="field-input time-input"
                            required
                        />
                        <button
                            v-if="form.times_of_day.length > 1"
                            type="button"
                            class="btn-remove"
                            @click="removeTime(idx)"
                            :aria-label="'Rimuovi orario ' + (idx + 1)"
                        >✕</button>
                    </div>
                    <button
                        v-if="form.times_of_day.length < 10"
                        type="button"
                        class="btn-add"
                        @click="addTime"
                    >+ Aggiungi orario</button>
                    <span v-if="form.errors['times_of_day.0']" class="field-error">{{ form.errors['times_of_day.0'] }}</span>
                </div>

                <div class="field">
                    <label for="event_duration" class="field-label">Durata evento <span class="required">*</span></label>
                    <div class="duration-row">
                        <input
                            id="event_duration"
                            v-model.number="form.event_duration_minutes"
                            type="number"
                            class="field-input"
                            :class="{ 'error': form.errors.event_duration_minutes }"
                            min="1"
                            max="1440"
                            style="width: 120px"
                        />
                        <span class="field-unit">minuti</span>
                        <span class="field-hint-inline" v-if="form.event_duration_minutes >= 60">
                            ({{ Math.floor(form.event_duration_minutes / 60) }}h{{ form.event_duration_minutes % 60 > 0 ? ' ' + (form.event_duration_minutes % 60) + 'min' : '' }})
                        </span>
                    </div>
                    <span v-if="form.errors.event_duration_minutes" class="field-error">{{ form.errors.event_duration_minutes }}</span>
                </div>

                <div class="field">
                    <label for="reminder_value" class="field-label">Promemoria</label>
                    <div class="reminder-row">
                        <input
                            id="reminder_value"
                            v-model.number="form.reminder_value"
                            type="number"
                            class="field-input"
                            :class="{ 'error': form.errors.reminder_value }"
                            min="0"
                            max="99999"
                            placeholder="0"
                            style="width: 100px"
                        />
                        <select
                            id="reminder_unit"
                            v-model="form.reminder_unit"
                            class="field-input"
                            style="width: 130px"
                        >
                            <option value="minutes">Minuti</option>
                            <option value="hours">Ore</option>
                            <option value="days">Giorni</option>
                        </select>
                        <span class="field-hint-inline">prima dell'evento</span>
                    </div>
                    <span v-if="form.errors.reminder_value" class="field-error">{{ form.errors.reminder_value }}</span>
                </div>
            </div>

            <!-- Step 5: Riepilogo -->
            <div v-show="currentStep === 5" class="step-content">
                <h2 class="step-title">Riepilogo e anteprima</h2>

                <div class="summary-card">
                    <div class="summary-row"><span>Titolo</span><strong>{{ form.title || '—' }}</strong></div>
                    <div class="summary-row"><span>Timezone</span><strong>{{ form.timezone }}</strong></div>
                    <div class="summary-row"><span>Data inizio</span><strong>{{ form.start_date || '—' }}</strong></div>
                    <div class="summary-row" v-if="form.end_mode === 'end_date'">
                        <span>Data fine</span><strong>{{ form.end_date || '—' }}</strong>
                    </div>
                    <div class="summary-row" v-if="form.end_mode === 'duration_days'">
                        <span>Durata</span><strong>{{ form.duration_days }} giorni</strong>
                    </div>
                    <div class="summary-row"><span>Frequenza</span><strong>{{ { once: 'Singolo', daily: 'Giornaliera', weekly: 'Settimanale', monthly: 'Mensile' }[form.frequency] }}</strong></div>
                    <div class="summary-row" v-if="form.days_of_week.length">
                        <span>Giorni</span>
                        <strong>{{ form.days_of_week.map(d => ['','Lun','Mar','Mer','Gio','Ven','Sab','Dom'][d]).join(', ') }}</strong>
                    </div>
                    <div class="summary-row">
                        <span>Orari</span><strong>{{ form.times_of_day.join(', ') }}</strong>
                    </div>
                    <div class="summary-row">
                        <span>Durata evento</span><strong>{{ form.event_duration_minutes }} min</strong>
                    </div>
                    <div class="summary-row" v-if="form.reminder_value > 0">
                        <span>Promemoria</span>
                        <strong>{{ form.reminder_value }} {{ { minutes: 'min', hours: 'ore', days: 'giorni' }[form.reminder_unit] }} prima</strong>
                    </div>
                    <div class="summary-row" v-if="form.excluded_dates.filter(Boolean).length">
                        <span>Escluse</span>
                        <strong>{{ form.excluded_dates.filter(Boolean).join(', ') }}</strong>
                    </div>
                </div>

                <!-- Anteprima occorrenze -->
                <OccurrencePreview :form="form" />
            </div>

            <!-- Navigazione --->
            <div class="step-nav">
                <button
                    v-if="currentStep > 1"
                    type="button"
                    class="btn btn-secondary"
                    @click="prevStep"
                >← Indietro</button>
                <div v-else></div>

                <button
                    v-if="currentStep < totalSteps"
                    type="button"
                    class="btn btn-primary"
                    :disabled="!stepValid"
                    @click="nextStep"
                >Avanti →</button>

                <button
                    v-else
                    type="submit"
                    class="btn btn-success"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Salvataggio...</span>
                    <span v-else>✓ Crea Pianificazione</span>
                </button>
            </div>

        </form>
    </AppLayout>
</template>

<style scoped>
.page-wrap {
    min-height: 100vh;
    background: #f9fafb;
    padding: 2rem 1rem;
    font-family: 'Inter', sans-serif;
}
/* page-wrap e brand sono ora in AppLayout.vue */

.form-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    padding: 2rem;
    max-width: 680px;
    margin: 0 auto;
}

/* Progress Bar */
.progress-bar {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 0;
    margin-bottom: 2.5rem;
    overflow-x: auto;
}
.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex: 1;
    position: relative;
}
.progress-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 16px;
    left: 50%;
    width: 100%;
    height: 2px;
    background: #e5e7eb;
    z-index: 0;
}
.progress-step.completed:not(:last-child)::after { background: #4f46e5; }
.step-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid #e5e7eb;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    color: #6b7280;
    z-index: 1;
    transition: all 0.3s ease;
}
.progress-step.active   .step-dot { border-color: #4f46e5; background: #4f46e5; color: white; }
.progress-step.completed .step-dot { border-color: #4f46e5; background: #4f46e5; color: white; }
.step-label {
    font-size: 0.7rem;
    color: #6b7280;
    font-weight: 500;
    white-space: nowrap;
}
.progress-step.active   .step-label { color: #4f46e5; }
.progress-step.completed .step-label { color: #4f46e5; }

/* Error banner */
.error-banner {
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    color: #fca5a5;
    font-size: 0.85rem;
}
.error-banner ul { margin-top: 0.5rem; padding-left: 1.2rem; }

/* Step content */
.step-content { min-height: 300px; }
.step-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 1.5rem;
}

/* Fields */
.field { margin-bottom: 1.25rem; }
.field-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 6px;
    letter-spacing: 0.02em;
}
.required { color: #f87171; }
.field-hint { font-size: 0.78rem; color: #6b7280; margin-bottom: 8px; }
.field-hint-inline { font-size: 0.82rem; color: #6b7280; }
.field-unit { color: #6b7280; font-size: 0.9rem; }
.field-input {
    width: 100%;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 0.625rem 0.875rem;
    color: #111827;
    font-size: 0.95rem;
    font-family: 'Inter', sans-serif;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    appearance: none;
}
.field-input:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
.field-input.error { border-color: #f87171; }
.field-input option { background: #f3f4f6; }
.field-input optgroup { background: #ffffff; color: #6b7280; }
.field-error { display: block; font-size: 0.78rem; color: #f87171; margin-top: 4px; }

/* Toggle group */
.toggle-group { display: flex; gap: 8px; flex-wrap: wrap; }
.toggle-btn {
    padding: 6px 14px;
    border-radius: 999px;
    border: 1px solid #e5e7eb;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}
.toggle-btn.active { border-color: #4f46e5; background: rgba(79,70,229,0.1); color: #4f46e5; }

/* Frequency grid */
.freq-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.freq-btn {
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    padding: 1rem 0.5rem;
    border-radius: 12px; border: 1px solid #e5e7eb; background: #f3f4f6;
    color: #4b5563; font-size: 0.85rem; cursor: pointer; transition: all 0.2s;
}
.freq-btn.active { border-color: #4f46e5; background: rgba(79,70,229,0.1); color: #4f46e5; }
.freq-icon { font-size: 1.5rem; }

/* Days grid */
.days-grid { display: flex; gap: 8px; flex-wrap: wrap; }
.day-btn {
    width: 44px; height: 44px; border-radius: 10px;
    border: 1px solid #e5e7eb; background: #f3f4f6;
    color: #4b5563; font-size: 0.8rem; font-weight: 600;
    cursor: pointer; transition: all 0.2s;
}
.day-btn.active { border-color: #4f46e5; background: #4f46e5; color: white; }

/* Time / Excluded rows */
.time-row, .excluded-row {
    display: flex; gap: 8px; align-items: center; margin-bottom: 8px;
}
.time-input { width: 140px !important; }
.duration-row, .reminder-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

.btn-remove {
    background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3);
    color: #f87171; border-radius: 8px; padding: 6px 10px; cursor: pointer;
    font-size: 0.75rem; transition: all 0.2s; flex-shrink: 0;
}
.btn-remove:hover { background: rgba(239,68,68,0.2); }

.btn-add {
    background: transparent; border: 1px dashed #e5e7eb; color: #6b7280;
    border-radius: 10px; padding: 8px 16px; cursor: pointer; font-size: 0.85rem;
    width: 100%; transition: all 0.2s; margin-top: 4px;
}
.btn-add:hover { border-color: #4f46e5; color: #4f46e5; background: rgba(99,102,241,0.05); }

/* Summary */
.summary-card {
    background: #f3f4f6; border-radius: 12px;
    border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 1.5rem;
}
.summary-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 0.625rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.9rem;
}
.summary-row:last-child { border-bottom: none; }
.summary-row span { color: #6b7280; }
.summary-row strong { color: #111827; text-align: right; max-width: 60%; }

/* Navigation */
.step-nav {
    display: flex; justify-content: space-between; align-items: center;
    margin-top: 2rem; padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}
.btn {
    padding: 0.7rem 1.5rem; border-radius: 12px; font-size: 0.95rem;
    font-weight: 600; cursor: pointer; border: none; transition: all 0.2s;
}
.btn-primary { background: #4f46e5; color: white; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); }
.btn-primary:hover:not(:disabled) { background: #4338ca; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(79, 70, 229, 0.3); }
.btn-secondary { background: white; color: #4b5563; border: 1px solid #d1d5db; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
.btn-secondary:hover:not(:disabled) { background: #f9fafb; border-color: #9ca3af; color: #111827; }
.btn-success { background: linear-gradient(135deg, #4f46e5, #4338ca); color: white; padding: 0.7rem 2rem; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); }
.btn-success:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; box-shadow: none !important; }

@media (max-width: 480px) {
    .form-card { padding: 1.25rem; }
    .freq-grid { grid-template-columns: 1fr; }
    .progress-bar { gap: 0; }
    .step-label { display: none; }
}
</style>
