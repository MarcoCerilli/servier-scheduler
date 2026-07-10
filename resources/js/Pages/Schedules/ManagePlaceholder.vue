<script setup>
import { Head } from '@inertiajs/vue3';
defineProps({ message: String });
</script>
<template>
    <Head title="Gestione Pianificazione" />
    <div style="min-height:100vh;background:#f9fafb;display:flex;align-items:center;justify-content:center;font-family:'Inter',sans-serif;padding:2rem;">
        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:20px;padding:2rem;max-width:480px;text-align:center;">
            <div style="font-size:2.5rem;margin-bottom:1rem;">🔧</div>
            <h1 style="font-size:1.25rem;color:#111827;margin-bottom:0.75rem;">Funzionalità in arrivo</h1>
            <p style="color:#6b7280;font-size:0.9rem;line-height:1.6;">{{ message }}</p>
            <a href="/crea" style="display:inline-block;margin-top:1.5rem;padding:0.65rem 1.5rem;background:#4f46e5;color:white;text-decoration:none;border-radius:10px;font-weight:600;">Crea nuova pianificazione</a>
        </div>
    </div>
</template>
