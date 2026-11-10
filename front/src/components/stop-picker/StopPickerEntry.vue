<template>
    <div>
        <div class="row">
            <div class="main">
                <slot name="primary-action" />
                <div class="description">
                    <stop-label :stop="stop" />
                    <div v-if="stop.destinations?.length > 0" class="destinations">
                        <stop-destinations :destinations="stop.destinations" />
                    </div>
                </div>
            </div>

            <div class="tools">
                <slot name="actions">
                    <button ref="action-info" class="btn btn-action" @click="details = !details">
                        <ui-tooltip>dodatkowe informacje</ui-tooltip>
                        <ui-icon icon="info" />
                    </button>

                    <button
                        ref="action-map"
                        v-hover="(hovered: boolean) => (mapButtonHovered = hovered)"
                        class="btn btn-action"
                    >
                        <ui-icon icon="map" />
                    </button>
                </slot>
            </div>
        </div>

        <keep-alive>
            <teleport to="#popups">
                <stop-details-dialog v-if="details" :stop="stop" @leave="details = false" />
            </teleport>
        </keep-alive>
        <keep-alive>
            <ui-dialog
                v-if="showMap"
                v-hover="(hovered: boolean) => (mapHovered = hovered)"
                reference="action-map"
                arrow
                flush
                class="map-popup"
                placement="right-start"
            >
                <stop-map :stop="stop" class="map" />
            </ui-dialog>
        </keep-alive>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import type { StopWithDestinations as Stop } from "@/model";
import StopDetailsDialog from "@/components/stop/StopDetailsDialog.vue";
import UiDialog from "@/components/ui/UiDialog.vue";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";
import StopLabel from "@/components/stop/StopLabel.vue";
import StopMap from "@/components/stop/StopMap.vue";
import StopDestinations from "@/components/stop/StopDestinations.vue";

defineOptions({ name: "StopPickerEntry" });

defineProps<{ stop: Stop }>();

const details = ref(false);
const mapButtonHovered = ref(false);
const mapHovered = ref(false);

const showMap = computed(() => mapHovered.value || mapButtonHovered.value);
</script>

<style scoped>
@reference "@styles/tailwind.css";

.row {
    display: flex;
}

.main {
    position: relative;
    display: flex;
    flex: 1 1 auto;
    min-inline-size: 0;
}

.description {
    align-self: center;
    overflow: hidden;
}

.destinations {
    display: flex;
    align-items: center;
    font-size: var(--text-xs);
}

.tools {
    display: flex;
}

/* the map popup's own size */
.map-popup {
    inline-size: 500px;
}

.map {
    block-size: 300px;
}
</style>
