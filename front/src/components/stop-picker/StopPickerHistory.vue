<template>
    <ul class="list-underlined history" v-if="all.length > 0">
        <li v-for="entry in all" class="history__entry">
            <stop-picker-entry :stop="entry.stop" class="flex-grow-1 finder__stop">
                <template #primary-action>
                    <button @click="select(entry.stop, $event)" class="btn btn-action stretched-link">
                        <ui-icon icon="history" />
                    </button>
                </template>
            </stop-picker-entry>
        </li>
    </ul>
</template>

<script lang="ts">
import { Options, Vue } from "vue-class-component";
import { History } from "@/store";
import { HistoryEntry } from "@/store/modules/history";
import { Mutation } from "vuex-class";
import { Stop } from "@/model";
import StopPickerEntry from "./StopPickerEntry.vue";

@Options({
    name: "StopPickerHistory",
    components: { StopPickerEntry },
})
export default class StopPickerHistory extends Vue {
    @History.Getter all: HistoryEntry[];

    @Mutation("add") select: (stops: Stop[]) => void;
}
</script>
