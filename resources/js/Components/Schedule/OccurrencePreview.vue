<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    form: Object,
});

const loading    = ref(false);
const error      = ref(null);
const preview    = ref(null);
const debounceTimer = ref(null);

watch(() => props.form, () => {
    clearTimeout(debounceTimer.value);
    debounceTimer.value = setTimeout(fetchPreview, 600);
}, { deep: true, immediate: true });

async function fetchPreview() {
    const f = props.form;

    // Condizioni minime per fare la preview
    if (!f.start_date || !f.times_of_day?.length || !f.frequency) {
        preview.value = null;
        return;
    }
    if (f.frequency !== 'once' && f.end_mode === 'end_date' && !f.end_date) {
        preview.value = null;
        return;
    }
    if (f.frequency !== 'once' && f.end_mode === 'duration_days' && !f.duration_days) {
        preview.value = null;
        return;
    }

    loading.value = true;
    error.value   = null;

    try {
        const payload = {
            frequency:              f.frequency,
            start_date:             f.start_date,
            end_date:               f.end_mode === 'end_date' ? f.end_date : null,
            duration_days:          f.end_mode === 'duration_days' ? f.duration_days : null,
            times_of_day:           f.times_of_day,
            days_of_week:           f.days_of_week,
            event_duration_minutes: f.event_duration_minutes || 60,
            excluded_dates:         f.excluded_dates?.filter(Boolean) || [],
            timezone:               f.timezone || 'Europe/Rome',
        };

        const { data } = await axios.post('/api/schedule/preview', payload);
        preview.value = data;
    } catch (e) {
        // Non mostrare errori durante la digitazione
        preview.value = null;
    } finally {
        loading.value = false;
    }
}

function formatDate(iso) {
    const d = new Date(iso);
    return d.toLocaleDateString('it-IT', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatTime(iso) {
    const d = new Date(iso);
    return d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <div class="preview-wrap">
        <div class="preview-header">
            <span class="preview-label">Anteprima occorrenze</span>
            <span v-if="loading" class="preview-loading">⟳</span>
        </div>

        <div v-if="preview && !loading">
            <div class="preview-total">
                <span class="total-num">{{ preview.total }}</span>
                <span class="total-txt">{{ preview.total === 1 ? 'evento calcolato' : 'eventi calcolati' }}</span>
            </div>

            <div v-if="preview.items.length" class="preview-list">
                <div
                    v-for="(item, i) in preview.items"
                    :key="i"
                    class="preview-item"
                >
                    <span class="item-date">{{ formatDate(item.starts_at) }}</span>
                    <span class="item-time">{{ formatTime(item.starts_at) }}–{{ formatTime(item.ends_at) }}</span>
                </div>
                <div v-if="preview.total > 20" class="preview-more">
                    + altri {{ preview.total - 20 }} eventi nel calendario
                </div>
            </div>
        </div>

        <div v-else-if="!loading" class="preview-empty">
            Compila le date e la frequenza per vedere l'anteprima
        </div>
    </div>
</template>

<style scoped>
.preview-wrap {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
}
.preview-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 0.75rem;
}
.preview-label {
    font-size: 0.75rem; font-weight: 600; letter-spacing: 0.08em;
    text-transform: uppercase; color: #4f46e5;
}
.preview-loading {
    font-size: 1rem; color: #4f46e5;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.preview-total {
    display: flex; align-items: baseline; gap: 8px; margin-bottom: 0.75rem;
}
.total-num { font-size: 1.75rem; font-weight: 700; color: #4f46e5; }
.total-txt { font-size: 0.85rem; color: #6b7280; }

.preview-list { max-height: 220px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #e5e7eb transparent; }
.preview-item {
    display: flex; justify-content: space-between; align-items: center;
    padding: 5px 0; border-bottom: 1px solid #e5e7eb;
    font-size: 0.85rem;
}
.preview-item:last-child { border-bottom: none; }
.item-date { color: #4b5563; }
.item-time { color: #4f46e5; font-weight: 500; font-size: 0.8rem; }
.preview-more { text-align: center; color: #6b7280; font-size: 0.78rem; padding: 8px 0; }
.preview-empty { color: #9ca3af; font-size: 0.85rem; text-align: center; padding: 1rem 0; }
</style>
