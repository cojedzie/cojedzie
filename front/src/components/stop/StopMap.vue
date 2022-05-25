<template>
    <div>
        <ui-map :center="stop.location" :zoom="17" :options="{ zoomControl: false, dragging: false }">
            <ui-map-pin :lat-lng="stop.location" class="ui-pin--tram">
                <ui-icon icon="line-tram" />
            </ui-map-pin>
        </ui-map>
    </div>
</template>

<script lang="ts">
import { Options, Vue } from "vue-class-component";
import { Prop } from "vue-property-decorator";
import { Stop } from "@/model";
import UiMapPin from "@/components/ui/UiMapPin.vue";

@Options({ name: "StopMap",
    components: { UiMapPin }
})
export default class StopMap extends Vue {
    @Prop(Object)
    public stop: Stop;
}
</script>
