<template>
    <footer class="app-footer page-container hidden flex-wrap items-center md:flex">
        <span>
            <img src="@resources/images/logo.png" alt="co jedzie logo" class="h-6" />
            {{ about.version }}
            <template v-for="link in about.links" :key="link.href">
                &bullet; <a :href="link.href">{{ link.label }}</a>
            </template>
        </span>
        <span class="ms-auto flex items-center justify-end">
            <a href="https://kadet.net"
                ><img src="@resources/images/kadet-net-logo.png" alt="kadet.net logo" class="mx-1 h-6"
            /></a>
            &copy; {{ about.year }}
        </span>
    </footer>
</template>

<script setup lang="ts">
import { useAboutLinks } from "@/composables/useAboutLinks";

defineOptions({ name: "AppFooter" });

const about = useAboutLinks();
</script>

<style lang="scss">
@import "../../../styles/_variables.scss";

.app-footer {
    padding: $alert-padding-y;
    font-size: $font-size-sm;
    opacity: 0.15;
    filter: grayscale(100%);
    transition:
        opacity 0.5s ease,
        filter 0.5s ease;

    &:hover {
        opacity: 1;
        filter: none;
    }

    > * {
        flex: 0 1 auto;
        padding-block: 0.4rem;
    }
}
</style>
