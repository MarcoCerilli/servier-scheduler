<script setup>
// Layout condiviso per tutte le pagine Vue dell'applicazione.
defineProps({ title: String });
</script>

<template>
    <main class="page-wrap">
        <header class="brand" role="banner">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span>Servier Scheduler</span>
        </header>
        <slot />
    </main>
</template>

<style scoped>
.page-wrap {
    min-height: 100vh;
    background: #f9fafb;
    padding: 2rem 1rem;
    font-family: 'Inter', sans-serif;
}
.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #4f46e5;
    font-weight: 700;
    font-size: 1.1rem;
    max-width: 680px;
    margin: 0 auto 1.5rem;
}
</style>
