<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
defineProps({ message: String });
</script>

<template>
    <Head title="Gestione Pianificazione" />
    <AppLayout>
        <section class="placeholder-card">
            <div class="icon" aria-hidden="true">🔧</div>
            <h1>Funzionalità in arrivo</h1>
            <p>{{ message }}</p>
            <a href="/crea" class="btn-primary">Crea nuova pianificazione</a>
        </section>
    </AppLayout>
</template>

<style scoped>
.placeholder-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    padding: 2rem;
    max-width: 480px;
    margin: 0 auto;
    text-align: center;
}
.icon { font-size: 2.5rem; margin-bottom: 1rem; }
h1 { font-size: 1.25rem; color: #111827; margin-bottom: 0.75rem; font-weight: 700; }
p { color: #6b7280; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.5rem; }
.btn-primary {
    display: inline-block;
    padding: 0.65rem 1.5rem;
    background: #4f46e5;
    color: white;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    transition: background 0.2s;
}
.btn-primary:hover { background: #4338ca; }
.btn-primary:focus-visible { outline: 2px solid #4f46e5; outline-offset: 2px; }
</style>

