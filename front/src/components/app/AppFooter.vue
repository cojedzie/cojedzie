<template>
    <footer class="footer page-container">
        <span>
            <img src="@resources/images/logo.png" alt="co jedzie logo" class="logo" />
            {{ about.version }}
            <template v-for="link in about.links" :key="link.href">
                &bullet; <a :href="link.href">{{ link.label }}</a>
            </template>
        </span>
        <span class="credits">
            <a href="https://kadet.net"
                ><img src="@resources/images/kadet-net-logo.png" alt="kadet.net logo" class="credits-logo"
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

<style scoped>
@reference "@styles/tailwind.css";

.footer {
    display: none;
    flex-wrap: wrap;
    align-items: center;
    padding: --spacing(3);
    font-size: var(--text-sm);
    opacity: var(--opacity-faint);
    filter: grayscale(100%);
    transition-property: opacity, filter;
    transition-duration: var(--duration-normal);
    transition-timing-function: var(--ease-in-out);

    @variant md {
        display: flex;
    }

    &:hover {
        opacity: 1;
        filter: none;
    }

    & > * {
        flex: 0 1 auto;
        padding-block: --spacing(2);
    }

    & .logo {
        block-size: --spacing(6);
    }

    & .credits {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        margin-inline-start: auto;
    }

    & .credits-logo {
        block-size: --spacing(6);
        margin-inline: --spacing(1);
    }
}
</style>
