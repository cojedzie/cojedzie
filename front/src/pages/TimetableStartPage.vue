<template>
    <ui-section icon="calendar" title="Rozkład jazdy">
        <p class="hint">
            Rozkład jazdy przystanku otworzysz z jego szczegółów na tablicy odjazdów. Wyszukiwarka przystanków i linii
            jest w przygotowaniu.
        </p>
    </ui-section>
</template>

<script setup lang="ts">
import UiSection from "@/components/ui/UiSection.vue";

// a placeholder until the timetable's search (#156)
defineOptions({ name: "TimetableStartPage" });
</script>

<style scoped>
@reference "@styles/tailwind.css";

.hint {
    color: var(--color-muted);
}
</style>
