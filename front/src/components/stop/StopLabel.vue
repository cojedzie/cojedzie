<template>
    <div class="stop">
        <span class="stop__name">{{ stop.name }}</span>
        <span v-if="stop.variant" class="stop__variant">{{ stop.variant }}</span>
    </div>
</template>

<script lang="ts">
import { Options, Vue } from "vue-class-component";
import { Prop } from "vue-property-decorator";
import { Stop } from "@/model";

@Options({ name: "StopLabel" })
export default class StopLabel extends Vue {
    @Prop(Object)
    public stop: Stop;
}
</script>
