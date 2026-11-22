<template>
    <nav ref="nav" class="breadcrumbs" aria-label="Ścieżka">
        <ol>
            <li v-for="(crumb, index) in crumbs" :key="String(crumb.to.name)" :class="{ current: crumb.current }">
                <ui-icon v-if="index > 0" icon="chevron-right" class="separator" aria-hidden="true" />
                <span v-if="crumb.current" class="crumb" :class="{ pending: crumb.pending }" aria-current="page">
                    <component :is="crumb.content.component" v-if="crumb.content" v-bind="crumb.content.props" />
                    <span v-else class="text">{{ crumb.label }}</span>
                </span>
                <!-- the last resort: the middle crumbs give way to "…", still links for screen readers -->
                <template v-else-if="collapse >= 2 && index > 0">
                    <span class="gap" aria-hidden="true">…</span>
                    <router-link :to="crumb.to" class="visually-hidden">{{ crumb.label }}</router-link>
                </template>
                <!-- the label names a crumb shown otherwise, e.g. "Linia 8" for the line's symbol -->
                <router-link
                    v-else
                    :to="crumb.to"
                    class="crumb"
                    :class="{ pending: crumb.pending, 'icon-only': collapse >= 1 && crumb.icon }"
                    :aria-label="crumb.content || (collapse >= 1 && crumb.icon) ? crumb.label : undefined"
                >
                    <ui-icon v-if="collapse >= 1 && crumb.icon" :icon="crumb.icon" />
                    <component :is="crumb.content.component" v-else-if="crumb.content" v-bind="crumb.content.props" />
                    <template v-else>{{ crumb.label }}</template>
                </router-link>
            </li>
        </ol>
    </nav>
</template>

<script setup lang="ts">
import { nextTick, ref, useTemplateRef, watch } from "vue";
import { useResizeObserver } from "@vueuse/core";
import type { Crumb } from "@/breadcrumbs";
import UiIcon from "@/components/ui/UiIcon.vue";

defineOptions({ name: "UiBreadcrumbs" });

const props = defineProps<{ crumbs: Crumb[] }>();

const nav = useTemplateRef<HTMLElement>("nav");

/**
 * How far the trail collapsed to stay on one line: 0 – only the current crumb shortens, 1 – crumbs with an icon show
 * just the icon, 2 – the middle crumbs are "…" too. The trail collapses further only once the current crumb would be
 * shortened below `minCurrent`, so it keeps naming the page; the links before it never shorten.
 */
const collapse = ref<0 | 1 | 2>(0);

const minCurrentRem = 6;

/** Whether the current crumb is cut short below the width it is allowed to shrink to */
function currentTooShort(): boolean {
    const current = nav.value?.querySelector<HTMLElement>(".current .crumb");
    if (!current) {
        return false;
    }

    const rem = parseFloat(getComputedStyle(document.documentElement).fontSize);
    const truncated = [current, ...current.querySelectorAll<HTMLElement>("*")].some(
        element => element.scrollWidth > element.clientWidth,
    );

    return truncated && current.clientWidth < minCurrentRem * rem;
}

// measured before the browser paints (resize observers and Vue's updates both run first), so no step flashes
async function fit() {
    for (const level of [0, 1, 2] as const) {
        collapse.value = level;
        await nextTick();

        if (!currentTooShort()) {
            return;
        }
    }
}

useResizeObserver(nav, () => void fit());
watch(() => props.crumbs, fit, { flush: "post" });
</script>

<style scoped>
@reference "@styles/tailwind.css";

/* no box: wayfinding on the page itself, one line at every width */
ol {
    display: flex;
    align-items: center;
    /* the first crumb's text lines up with the page, its hover fill reaching into the gutter */
    margin: 0 0 0 calc(--spacing(1.5) * -1);
    padding: 0;
    font-size: var(--text-sm);
    line-height: var(--leading-tight);
    white-space: nowrap;
    list-style: none;
}

li {
    display: flex;
    flex: none;
    align-items: center;

    /* only the current page shortens, the links before it stay whole */
    &.current {
        flex: 0 1 auto;
        min-inline-size: 0;
    }
}

.separator {
    flex: none;
    color: var(--color-muted);
}

/* every crumb fills the row: a 44px tap target on a phone, a control's height from md */
.crumb {
    display: inline-flex;
    align-items: center;
    min-block-size: --spacing(11);
    padding-inline: --spacing(1.5);
    border-radius: var(--radius-sm);

    @variant md {
        min-block-size: var(--control-height-md);
    }
}

a.crumb {
    color: var(--color-primary);
    text-decoration: none;

    &:hover {
        text-decoration: underline;
        background: var(--color-hover-primary);
    }
}

.icon-only {
    justify-content: center;
    min-inline-size: --spacing(11);
    padding-inline: 0;
}

[aria-current="page"] {
    min-inline-size: 0;
    color: var(--color-body);
    font-weight: var(--font-weight-medium);
}

/* until the page loaded what the crumb names, it only says what it is */
.crumb.pending {
    color: var(--color-muted);
    font-weight: normal;
}

.text {
    overflow: hidden;
    text-overflow: ellipsis;
}

.gap {
    padding-inline: --spacing(1);
    color: var(--color-muted);
}
</style>
