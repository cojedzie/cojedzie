<template>
    <div v-responsive class="departures">
        <ul class="departures__list list-underlined list-underlined--condensed">
            <li v-for="departure in departures" :key="departure.key">
                <departures-departure :departure="departure" />
            </li>
        </ul>
    </div>
</template>

<script lang="ts">
import { DeparturesDeparture } from "@/components";
import { computed, defineComponent, PropType } from "vue";
import { Departure } from "@/model";
import { useStore } from "vuex";
import { StoreDefinition } from "@/store/initializer";

export default defineComponent({
    name: "DeparturesList",
    components: {
        DeparturesDeparture,
    },
    props: {
        departures: {
            type: Array as PropType<Departure[]>,
            required: false,
            default: () => null,
        },
    },
    setup(props) {
        const store = useStore<StoreDefinition>();

        const departures = computed(() => props.departures || store.state.departures.departures);

        // eslint-disable-next-line vue/no-dupe-keys
        return { departures };
    },
});
</script>
