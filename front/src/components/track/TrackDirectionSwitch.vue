<template>
    <ui-toggle-group v-model="track" :options="directions" label="Kierunek">
        <template v-if="side.length > 0" #after>
            <button
                ref="more"
                type="button"
                class="more"
                :class="{ holding: chosenSide }"
                :aria-label="chosenSide ? `Inne przebiegi: ${trackEndsLabel(chosenSide)}` : 'Inne przebiegi'"
                aria-haspopup="true"
                :aria-expanded="open ? 'true' : 'false'"
                @click="open = !open"
            >
                <ui-tooltip>Inne przebiegi</ui-tooltip>
                <ui-icon icon="overflow" />
            </button>
        </template>
    </ui-toggle-group>
    <teleport to="#popups">
        <ui-dialog
            v-if="open"
            :reference="more"
            arrow
            placement="bottom-end"
            @leave="open = false"
            @keydown.esc="close"
        >
            <ui-menu ref="menu" class="menu">
                <ui-menu-item
                    v-for="other in side"
                    :key="other.id"
                    :aria-current="other === modelValue ? 'true' : 'false'"
                    @click="pick(other)"
                >
                    {{ trackEndsLabel(other) }}
                </ui-menu-item>
            </ui-menu>
        </ui-dialog>
    </teleport>
</template>

<script lang="ts" setup>
import { computed, ref, useTemplateRef } from "vue";
import type { Track } from "@/model";
import { trackDestination, trackEndsLabel } from "@/timetable";
import { usePopupFocus } from "@/composables/usePopupFocus";
import type { UiToggleGroupOption } from "@/components/ui/UiToggleGroup.vue";
import UiDialog from "@/components/ui/UiDialog.vue";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiMenu from "@/components/ui/UiMenu.vue";
import UiMenuItem from "@/components/ui/UiMenuItem.vue";
import UiToggleGroup from "@/components/ui/UiToggleGroup.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";

// a line's direction as riders pick it: its main tracks side by side, named by where they go; the side tracks (shortened
// or otherwise different runs, ADR 0031) are folded into a menu after them, as they are seldom wanted
defineOptions({ name: "TrackDirectionSwitch" });

const props = defineProps<{
    /** The line's tracks, with their stops */
    tracks: Track[];
    modelValue?: Track;
}>();

const emit = defineEmits<{ "update:modelValue": [track: Track] }>();

// a track without a kind (from an API older than 1.1) counts as a side one, as in `orderTracks`
const main = computed(() => props.tracks.filter(track => track.kind === "main"));
const side = computed(() => props.tracks.filter(track => track.kind !== "main"));

// main tracks going to the same stop are told apart by where they start
const directions = computed<UiToggleGroupOption<Track>[]>(() =>
    main.value.map(track => {
        const destination = trackDestination(track)?.name ?? "";
        const shared = main.value.some(other => other !== track && trackDestination(other)?.name === destination);

        return { value: track, label: shared ? trackEndsLabel(track) : destination };
    }),
);

const chosenSide = computed(() => side.value.find(track => track === props.modelValue));

const track = computed({
    get: () => props.modelValue,
    set: (track: Track | undefined) => track && emit("update:modelValue", track),
});

const more = useTemplateRef<HTMLButtonElement>("more");
const menu = useTemplateRef<InstanceType<typeof UiMenu>>("menu");
const open = ref(false);

const close = usePopupFocus(
    open,
    () => menu.value?.$el as HTMLElement | undefined,
    () => more.value,
);

function pick(track: Track) {
    open.value = false;
    emit("update:modelValue", track);
}
</script>

<style scoped>
@reference "@styles/tailwind.css";

.more {
    @apply ui-control-segment;

    flex: none;
    padding-inline: 0;

    /* a side track is the choice: the menu holds it, filled like a pressed option */
    &.holding {
        &,
        &:hover {
            background: var(--ui-control-fill);
            color: white;
        }
    }
}

.menu {
    min-inline-size: --spacing(48);
}
</style>
