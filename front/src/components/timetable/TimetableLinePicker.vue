<template>
    <button
        ref="button"
        type="button"
        class="picker"
        :aria-label="`Linia ${line.symbol}, zmień linię`"
        aria-haspopup="dialog"
        :aria-expanded="open ? 'true' : 'false'"
        @click="open = !open"
    >
        <line-symbol :line="line" size="large" />
        <ui-icon icon="chevron-down" />
    </button>
    <teleport to="#popups">
        <ui-dialog
            v-if="open"
            :reference="button"
            arrow
            flush
            placement="bottom-start"
            class="lines-popup"
            aria-label="Linie na tym przystanku"
            @leave="open = false"
            @keydown.esc="close"
        >
            <div ref="content" class="content">
                <p class="title">Linie na tym przystanku</p>
                <ul class="rows">
                    <li v-for="{ line: other, directions } in lines" :key="other.id">
                        <router-link
                            :to="lineTo(other)"
                            class="row"
                            :aria-current="other.id === line.id ? 'page' : undefined"
                            @click="open = false"
                        >
                            <line-symbol :line="other" />
                            <span class="direction">{{ directions.join(", ") }}</span>
                        </router-link>
                    </li>
                </ul>
                <hr class="divider" />
                <router-link :to="allTo" class="row all" @click="open = false">Wszystkie linie razem</router-link>
            </div>
        </ui-dialog>
    </teleport>
</template>

<script setup lang="ts">
import { ref, useTemplateRef } from "vue";
import type { RouteLocationRaw } from "vue-router";
import type { Line } from "@/model";
import type { StopLine } from "@/timetable";
import { usePopupFocus } from "@/composables/usePopupFocus";
import LineSymbol from "@/components/line/LineSymbol.vue";
import UiDialog from "@/components/ui/UiDialog.vue";
import UiIcon from "@/components/ui/UiIcon.vue";

// the line of a line's timetable at a stop, in the page's heading, switching to another line at the same stop or to
// all of them together
defineOptions({ name: "TimetableLinePicker" });

defineProps<{
    /** The line shown */
    line: Line;
    /** The stop's lines, with where they go from it */
    lines: StopLine[];
    /** Where picking a line leads: its timetable at the stop */
    lineTo: (line: Line) => RouteLocationRaw;
    /** Where "Wszystkie linie razem" leads: the whole stop's timetable */
    allTo: RouteLocationRaw;
}>();

const button = useTemplateRef<HTMLButtonElement>("button");
const content = useTemplateRef<HTMLElement>("content");
const open = ref(false);

const close = usePopupFocus(
    open,
    () => content.value,
    () => button.value,
);
</script>

<style scoped>
@reference "@styles/tailwind.css";

/* the symbol in the heading, a button only by its chevron and hover */
.picker {
    @apply focus-ring;

    display: inline-flex;
    align-items: center;
    gap: --spacing(1.5);
    margin: calc(--spacing(1) * -1) calc(--spacing(1.5) * -1);
    padding: --spacing(1) --spacing(1.5);
    border: 0;
    border-radius: var(--radius-lg);
    background: transparent;
    color: inherit;
    font: inherit;
    cursor: pointer;

    &:hover,
    &[aria-expanded="true"] {
        background: var(--color-hover);
    }
}

/* under the button from md, across the page on a phone */
.lines-popup {
    /* flush: the padding is the content's, inside this width */
    inline-size: --spacing(78);
    font-size: var(--text-sm);
    font-weight: normal;
    line-height: var(--leading-tight);

    @variant max-md {
        inline-size: calc(100vw - --spacing(8));
    }
}

/* the rows' highlights reach close to the popup's edges, a popup's usual padding would leave a frame around them */
.content {
    padding: --spacing(1.5);
}

.title {
    margin: --spacing(1) 0 --spacing(1.5);
    padding-inline: --spacing(2);
    color: var(--color-muted);
    font-size: var(--text-xs);
    font-weight: bold;
}

/* the symbols in a column of their own, so the directions line up */
.rows {
    display: grid;
    grid-template-columns: auto 1fr;
    margin: 0;
    padding: 0;
    list-style: none;

    & li {
        display: grid;
        grid-column: 1 / -1;
        grid-template-columns: subgrid;
    }

    & .row {
        display: grid;
        grid-column: 1 / -1;
        grid-template-columns: subgrid;
    }
}

/* a 44px tap target on a phone, a control's height from md */
.row {
    display: flex;
    align-items: center;
    gap: --spacing(2.5);
    min-block-size: --spacing(11);
    /* directions wrapped onto several lines keep off the highlight's edges */
    padding: --spacing(1.5) --spacing(2);
    border-radius: var(--radius-sm);
    color: var(--color-body);
    text-decoration: none;

    @variant md {
        min-block-size: var(--control-height-lg);
    }

    &:is(:hover, :focus-visible) {
        background: var(--color-hover);
        color: var(--color-body);
        text-decoration: none;
    }

    &[aria-current="page"] {
        background: var(--color-hover-primary);
    }

    &.all {
        color: var(--color-primary);
    }
}

/* between the lines and all of them */
.divider {
    margin: --spacing(1.5) 0;
    border: 0;
    border-block-start: 1px dotted var(--color-divider);
}
</style>
