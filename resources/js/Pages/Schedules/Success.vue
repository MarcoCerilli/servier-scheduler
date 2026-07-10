<script setup>
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    schedule: Object,
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).catch(() => {
        // Fallback silenzioso per browser senza Clipboard API
    });
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}

import { ref } from 'vue';
const copied = ref(false);
</script>

<template>
    <Head title="Pianificazione creata!" />

    <div class="page-wrap">
        <div class="success-card">
            <!-- Icona successo -->
            <div class="success-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>

            <h1>Pianificazione creata!</h1>
            <p class="subtitle">La tua pianificazione è pronta. Condividi il link o il QR Code.</p>

            <!-- Contatore -->
            <div class="count-badge">
                <span class="count-num">{{ schedule.occurrences_count }}</span>
                <span class="count-lbl">{{ schedule.occurrences_count === 1 ? 'evento generato' : 'eventi generati' }}</span>
            </div>

            <!-- QR Code -->
            <div v-if="schedule.qr_url" class="qr-wrap">
                <img :src="schedule.qr_url" :alt="'QR Code per ' + schedule.title" width="220" height="220" class="qr-img" />
            </div>

            <!-- Link pubblico -->
            <div class="link-section">
                <div class="link-label">Link pubblico da condividere</div>
                <div class="link-row">
                    <input
                        type="text"
                        :value="schedule.public_url"
                        readonly
                        class="link-input"
                        id="public-url-input"
                        aria-label="Link pubblico"
                    />
                    <button
                        type="button"
                        class="btn-copy"
                        @click="copyToClipboard(schedule.public_url)"
                        :aria-label="copied ? 'Copiato!' : 'Copia link'"
                    >
                        <span v-if="copied">✓</span>
                        <span v-else>⎘</span>
                    </button>
                </div>
            </div>

            <!-- Management URL -->
            <div class="management-section">
                <div class="management-label">
                    🔑 URL di gestione privato
                </div>
                <div class="management-hint">
                    Salva questo link: è l'unico modo per gestire questa pianificazione in futuro.
                    Non viene mai mostrato di nuovo.
                </div>
                <div class="link-row">
                    <input
                        type="text"
                        :value="schedule.management_url"
                        readonly
                        class="link-input management-input"
                        id="management-url-input"
                        aria-label="URL di gestione"
                    />
                    <button
                        type="button"
                        class="btn-copy"
                        @click="copyToClipboard(schedule.management_url)"
                        aria-label="Copia URL gestione"
                    >⎘</button>
                </div>
            </div>

            <!-- Azioni -->
            <div class="actions">
                <a :href="schedule.public_url" class="btn btn-primary">Vai alla pagina pubblica</a>
                <a href="/crea" class="btn btn-secondary">+ Crea un'altra</a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-wrap {
    min-height: 100vh; background: #f9fafb;
    display: flex; align-items: center; justify-content: center;
    padding: 2rem 1rem; font-family: 'Inter', sans-serif;
}
.success-card {
    background: #ffffff; border: 1px solid #e5e7eb; border-radius: 24px;
    padding: 2.5rem 2rem; max-width: 560px; width: 100%; text-align: center;
    animation: fadeIn 0.4s ease;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }

.success-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: linear-gradient(135deg, #10b981, #059669);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.5rem; color: white;
    box-shadow: 0 8px 24px rgba(16,185,129,0.35);
}
h1 { font-size: 1.75rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; }
.subtitle { color: #6b7280; font-size: 0.95rem; margin-bottom: 1.5rem; }

.count-badge {
    display: inline-flex; align-items: baseline; gap: 8px;
    background: rgba(79,70,229,0.1); border: 1px solid rgba(99,102,241,0.3);
    border-radius: 999px; padding: 6px 18px; margin-bottom: 1.5rem;
}
.count-num { font-size: 1.4rem; font-weight: 700; color: #4f46e5; }
.count-lbl { font-size: 0.85rem; color: #4b5563; }

.qr-wrap { margin-bottom: 1.5rem; }
.qr-img {
    border-radius: 12px; border: 4px solid #e5e7eb;
    background: white; padding: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}

.link-section, .management-section { margin-bottom: 1.25rem; text-align: left; }
.link-label, .management-label {
    font-size: 0.78rem; font-weight: 600; letter-spacing: 0.06em;
    text-transform: uppercase; color: #4f46e5; margin-bottom: 6px;
}
.management-label { color: #f59e0b; }
.management-hint { font-size: 0.78rem; color: #6b7280; margin-bottom: 8px; line-height: 1.5; }
.link-row { display: flex; gap: 8px; }
.link-input {
    flex: 1; background: #f3f4f6; border: 1px solid #e5e7eb;
    border-radius: 10px; padding: 8px 12px; color: #4b5563;
    font-size: 0.82rem; font-family: monospace; outline: none;
}
.management-input { border-color: rgba(245,158,11,0.3); }
.btn-copy {
    background: #f3f4f6; border: 1px solid #e5e7eb; color: #4f46e5;
    border-radius: 10px; padding: 8px 14px; cursor: pointer;
    font-size: 1rem; transition: all 0.2s; flex-shrink: 0;
}
.btn-copy:hover { background: rgba(79,70,229,0.1); }

.actions { display: flex; gap: 12px; margin-top: 1.5rem; flex-wrap: wrap; }
.btn {
    flex: 1; padding: 0.75rem 1rem; border-radius: 12px; font-size: 0.95rem;
    font-weight: 600; text-decoration: none; text-align: center;
    cursor: pointer; transition: all 0.2s; border: none;
}
.btn-primary { background: #4f46e5; color: white; }
.btn-primary:hover { background: #5558e3; transform: translateY(-1px); }
.btn-secondary { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
.btn-secondary:hover { background: #e5e7eb; }

@media (max-width: 480px) {
    .success-card { padding: 1.5rem 1rem; }
    .actions { flex-direction: column; }
}
</style>
