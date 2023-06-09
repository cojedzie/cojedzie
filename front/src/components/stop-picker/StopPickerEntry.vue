<template>
    <div>
        <div class="d-flex">
            <div class="d-flex position-relative" style="min-width: 0; flex: 1 1 auto;">
                <slot name="primary-action" />
                <div class="overflow-hidden align-self-center">
                    <stop-label :stop="stop" />
                    <div v-if="destinations && destinations.length > 0" class="stop__destinations">
                        <ul>
                            <li v-for="destination in destinations" :key="destination.stop.id" class="stop__destination destination">
                                <ul class="destination__lines">
                                    <li v-for="line in destination.lines" :key="line.id">
                                        <line-symbol :key="line.symbol" :line="line" simple />
                                    </li>
                                </ul>
                                <span class="destination__name ml-1">{{ destination.stop.name }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="stop__actions">
                <slot name="actions">
                    <button ref="action-info" class="btn btn-action" @click="details = !details">
                        <ui-tooltip>dodatkowe informacje</ui-tooltip>
                        <ui-icon icon="info" />
                    </button>

                    <button ref="action-map" v-hover:map class="btn btn-action">
                        <ui-icon icon="map" />
                    </button>
                </slot>
            </div>
        </div>

        <keep-alive>
            <teleport to="#popups">
                <stop-details-dialog v-if="details" :stop="stop" style="display: grid; min-height: 70vh" @leave="details = false" />
            </teleport>
        </keep-alive>
        <keep-alive>
            <ui-dialog
                v-if="showMap"
                v-hover:inMap
                reference="action-map"
                arrow
                class="ui-popup--no-padding"
                style="width: 500px;"
                placement="right-start"
            >
                <stop-map :stop="stop" style="height: 300px" />
            </ui-dialog>
        </keep-alive>
    </div>
</template>

<script lang="ts">
import { Options, Vue } from "vue-class-component";
import { Prop } from "vue-property-decorator";
import { Line, StopWithDestinations as Stop } from "@/model";
import { match, unique } from "@/utils";
import StopDetailsDialog from "@/components/stop/StopDetailsDialog.vue";

@Options({ name: "StopPickerEntry", components: { StopDetailsDialog } })
export default class StopPickerEntry extends Vue {
    @Prop(Object)
    public stop: Stop;

    details: boolean = false;
    map: boolean = false;
    inMap: boolean = false;

    get showMap() {
        return this.inMap || this.map;
    }

    get destinations() {
        const compactLines = destination => ({
            ...destination,
            lines: Object.entries(groupLinesByType(destination.lines || [])).map(([type, lines]) => ({
                type: type,
                symbol: joinedSymbol(lines),
                night: lines.every(line => line.night),
                fast: lines.every(line => line.fast),
            })),
            all: destination.lines
        });

        const groupLinesByType = (lines: Line[]) => lines.reduce<{ [kind: string]: Line[] }>((groups, line) => ({
            ...groups,
            [line.type]: [...(groups[line.type] || []), line]
        }), {});

        const joinedSymbol = match<string, [Line[]]>(
            [lines => lines.length === 1, lines => lines[0].symbol],
            [lines => lines.length === 2, ([first, second]) => `${ first.symbol }, ${ second.symbol }`],
            [lines => lines.length > 2, ([first]) => `${ first.symbol }…`],
        );

        return unique(this.stop.destinations || [], destination => destination.stop && destination.stop.name).map(compactLines);
    }
}
</script>
