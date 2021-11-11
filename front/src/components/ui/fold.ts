/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import { Options, Vue } from "vue-class-component";
import { Prop, Watch } from "vue-property-decorator";
import WithRender from "@templates/ui/fold.html"

export type UiFoldState = "folded" | "expanding" | "expanded" | "folding";

@WithRender
@Options({ name: "UiFold" })
export class UiFold extends Vue {
    @Prop(Boolean)
    public visible: boolean;

    @Prop(Boolean)
    public lazy: boolean;

    public state: UiFoldState;

    data() {
        return {
            state: this.visible ? "expanded" : "folded"
        }
    }

    @Watch('visible')
    private resize() {
        if (this.visible) {
            this.expand();
        } else {
            this.fold();
        }
    }

    private handleTransitionEnd(event: TransitionEvent) {
        if (event.target !== this.$el) {
            // it's not about us
            return;
        }

        const root = this.$el as HTMLDivElement;

        switch (this.state) {
            case "expanded":
            case "expanding":
                root.style.height = "auto";
                this.state = "expanded";
                break;
            case "folded":
            case "folding":
                this.state = "folded";
        }
    }

    private fold() {
        const root = this.$el as HTMLDivElement;
        const state = this.state;

        switch (state) {
            case "folded":
            case "folding":
                // no-op, it's already folded
                break;

            case "expanded":
                root.style.height = `${ root.clientHeight }px`;

                // DOM must be updated and style must be applied in order for this to work
                setTimeout(() => {
                    root.style.height = '0px';
                    this.state = "folding";
                })
                break;

            case "expanding":
                // reverse process
                root.style.height = '0px';
                this.state = "folding";
                break;
        }
    }

    private expand() {
        const root = this.$el as HTMLDivElement;
        const inner = this.$refs['inner'] as HTMLDivElement;
        const state = this.state;

        switch (state) {
            case "expanded":
            case "expanding":
                // no-op, it's already folded
                break;

            case "folded":
            case "folding":
                root.style.height = inner.clientHeight + 'px';
                this.state = "expanding";
                break;
        }
    }
}

export default UiFold;
