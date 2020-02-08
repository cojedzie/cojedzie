import Vue from 'vue'
import { Component, Prop } from 'vue-property-decorator'
import { Line } from "../model";

@Component({ template: require('../../components/line.html' )})
export class LineComponent extends Vue {
    @Prop(Object)
    public line: Line;

    @Prop(Boolean)
    public simple: boolean;
}

Vue.component('LineSymbol', LineComponent);
