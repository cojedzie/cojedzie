<template>
    <div class="finder">
        <input class="form-control form-control--framed" :value="filter" placeholder="Zacznij pisać nazwę aby szukać..." @input="filter = $event.target.value">

        <div v-if="filter.length < 3" class="mt-2">
            <favourites-list />
            <stop-picker-history />
        </div>

        <div v-if="state === 'fetching'" class="text-center p-4">
            <ui-icon icon="spinner" />
        </div>
        <div v-else-if="filter.length > 2 && Object.keys(filtered).length > 0" class="finder__stops">
            <div v-for="(group, name) in filtered" :key="group" class="stop-group">
                <div class="stop-group__header">
                    <h3 class="stop-group__name">
                        {{ name }}
                    </h3>

                    <div class="actions flex-space-left">
                        <button class="btn btn-action" @click="select(group)">
                            <ui-tooltip>wybierz wszystkie</ui-tooltip>
                            <ui-icon icon="add-all" />
                        </button>
                    </div>
                </div>
                <ul class="stop-group__stops list-underlined">
                    <li v-for="stop in group" :key="stop.id" class="d-flex">
                        <stop-picker-entry :stop="stop" class="flex-grow-1 finder__stop">
                            <template #primary-action>
                                <button class="btn btn-action stretched-link" @click="select(stop, $event)">
                                    <ui-tooltip>dodaj przystanek</ui-tooltip>
                                    <ui-icon icon="add" />
                                </button>
                            </template>
                        </stop-picker-entry>
                    </li>
                </ul>
            </div>
        </div>
        <div v-else-if="filter.length > 2" class="alert alert-warning">
            <ui-icon icon="warning" />
            Nie znaleziono więcej przystanków, spełniających te kryteria.
        </div>
    </div>
</template>

<script lang="ts">
import { Options, Vue } from "vue-class-component";
import { StopGroup, StopGroups, StopWithDestinations as Stop } from "@/model";
import { FetchingState, map, filter } from "@/utils";
import { Prop, Watch } from "vue-property-decorator";
import { Mutation } from "vuex-class";
import { HistoryEntry, HistoryMutations } from "@/store/modules/history";
import { debounce } from "@/decorators";
import api from "@/api";
import StopPickerEntry from "./StopPickerEntry.vue";
import StopPickerHistory from "./StopPickerHistory.vue";

@Options({
    name: "StopPicker",
    components: { StopPickerEntry, StopPickerHistory }
})
export default class StopPicker extends Vue {
    protected found?: StopGroups = {};

    public state: FetchingState = 'ready';
    public filter: string = "";

    @Prop({ default: [], type: Array })
    public blacklist: Stop[];

    @Mutation(`history/${ HistoryMutations.Push }`) pushToHistory: (entry: HistoryEntry) => void;

    get filtered(): StopGroups {
        const groups = map(
            this.found,
            (group: StopGroup) =>
                group.filter(stop => !this.blacklist.some(blacklisted => blacklisted.id === stop.id))
        ) as StopGroups;

        return filter(groups, group => group.length > 0);
    }

    @Watch('filter')
    @debounce(400)
    async fetch() {
        if (this.filter.length < 3) {
            return;
        }

        this.state = 'fetching';
        try {
            const response = await api.get('v1_stop_groups', {
                query: {
                    name: this.filter,
                    'include-destinations': true
                },
                version: "^1.0",
            });

            this.found = response.data.reduce((accumulator, {
                name,
                stops
            }) => Object.assign(accumulator, { [name]: stops }), {});
            this.state = 'ready';
        } catch (error) {
            this.state = 'error';
        }
    }

    private select(stop) {
        this.pushToHistory({
            date: this.$moment(),
            stop: stop,
        })

        this.$emit('select', stop);
    }
}
</script>
