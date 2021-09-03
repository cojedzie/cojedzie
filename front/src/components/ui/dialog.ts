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
import Popper, { Placement } from "popper.js";
import { defaultBreakpoints } from "@/filters";
import { ComponentPublicInstance } from "vue";
import removedHookMixin from "@/mixins/removed";
import WithRenderer from "@templates/ui/dialog.html"

/**
 * How popup will be presented to user:
 *  - "modal" - modal window
 *  - "popup" - simple popup
 */
export type DialogBehaviour = "modal" | "popup";

let openModalCounter: number = 0;

function computeZIndexOfElement(element: HTMLElement): number {
    let current = element;

    while (true) {
        const zIndex = window.getComputedStyle(current).zIndex;

        if (zIndex !== "auto") {
            return parseInt(zIndex);
        }

        if (!current.parentElement) {
            break;
        }

        current = current.parentElement;
    }

    return 0;
}

function findClosestRef(component: ComponentPublicInstance, ref: string): HTMLElement | null {
    for (let current = component; current !== null; current = current.$parent) {
        if (current.$refs.hasOwnProperty(ref)) {
            return current.$refs[ref] as HTMLElement;
        }
    }

    return null;
}

function findClosestNonWrapperParent(component: ComponentPublicInstance): ComponentPublicInstance | null {
    let parent = component.$parent

    while (parent && (parent.$el === component.$el || !(parent.$el instanceof HTMLElement))) {
          parent = parent.$parent;
    }

    return parent;
}

@WithRenderer
@Options({
    name: "UiDialog",
    inheritAttrs: false,
    mixins: [ removedHookMixin ]
})
export class UiDialog extends Vue {
    @Prop({ type: String, default: "popup" })
    private behaviour: DialogBehaviour;

    @Prop({ type: String })
    private mobileBehaviour: DialogBehaviour;

    @Prop([String, HTMLElement])
    public reference: string | HTMLElement;

    @Prop(Object)
    public refs: string;

    @Prop({ type: String, default: "auto" })
    public placement: Placement;

    @Prop(Boolean)
    public arrow: boolean;

    @Prop({ type: Boolean, default: true })
    public responsive: boolean;

    @Prop(String)
    public title: string;

    private isMobile: boolean = false;
    private zIndex: number = 1000;

    private _focusOutEvent;
    private _resizeEvent;

    private _popper;

    get currentBehaviour(): DialogBehaviour {
        if (!this.mobileBehaviour) {
            return this.behaviour;
        }

        return this.isMobile ? this.mobileBehaviour : this.behaviour;
    }

    get hasFooter() {
        return this.$hasSlot('footer')
    }

    get hasHeader() {
        return this.$hasSlot('header')
    }

    private getReferenceElement() {
        if (typeof this.reference === 'string') {
            if (this.reference[0] === '#') {
                return document.getElementById(this.reference.substr(1));
            }

            if (this.refs) {
                return this.refs[this.reference];
            }

            return findClosestRef(this, this.reference);
        }

        if (this.reference instanceof HTMLElement) {
            return this.reference;
        }

        const parent = findClosestNonWrapperParent(this);

        return parent && parent.$el;
    }

    focusOut(event: MouseEvent) {
        if (this.$el.contains(event.target as Node)) {
            return;
        }

        this.$emit('leave', event);
    }

    mounted() {
        this.zIndex = computeZIndexOfElement(this.getReferenceElement()) + 100;

        this.handleWindowResize();

        if (this.behaviour === 'popup') {
            this.mountPopper();
        }

        window.addEventListener('resize', this._resizeEvent = this.handleWindowResize.bind(this));

        this._activated();
    }

    private _activated() {
        if (this.behaviour === 'modal') {
            this.mountModal();
        }
    }

    private _deactivated() {
        if (this.behaviour === 'modal') {
            this.dismountModal();
        }
    }

    private mountModal() {
        if (openModalCounter === 0) {
            document.body.style.paddingRight = `${window.screen.width - document.body.clientWidth}px`
            document.body.classList.add('contains-modal');
        }

        openModalCounter++;
    }

    private dismountModal() {
        openModalCounter--;

        if (openModalCounter === 0) {
            document.body.style.paddingRight = "";
            document.body.classList.remove('contains-modal');
        }
    }

    activated() {
        this._activated();
    }

    deactivated() {
        this._deactivated();
    }

    private mountPopper() {
        const reference = this.getReferenceElement();

        this._popper = new Popper(reference, this.$el, {
            placement: this.placement,
            modifiers: {
                arrow: { enabled: this.arrow, element: this.$refs['arrow'] as Element },
                responsive: {
                    enabled: this.responsive,
                    order: 890,
                    fn(data) {
                        if (window.innerWidth < 560) {
                            data.instance.options.placement = 'top';
                            data.styles.transform = `translate3d(0, ${ data.offsets.popper.top }px, 0)`;
                            data.styles.right = '0';
                            data.styles.left = '0';
                            data.styles.width = 'auto';
                            data.arrowStyles.left = `${ data.offsets.popper.left + data.offsets.arrow.left }px`;
                        }

                        return data;
                    }
                }
            }
        });

        this.$nextTick(() => {
            this._popper && this._popper.update();
            document.addEventListener('click', this._focusOutEvent = this.focusOut.bind(this), { capture: true });
        });
    }

    private removePopper() {
        this._popper.destroy()
        this._popper = null;
    }

    updated() {
        if (this._popper) {
            this._popper.update();
        }
    }

    beforeUnmount() {
        this._focusOutEvent && document.removeEventListener('click', this._focusOutEvent, { capture: true });

        this._deactivated()
    }

    removed() {
        if (this._popper) {
            this.removePopper();
        }
    }

    private handleBackdropClick(ev: Event) {
        const target = ev.target as HTMLElement;

        if (target.classList.contains("ui-backdrop")) {
            this.$emit('leave');
        }
    }

    private handleCloseClick() {
        this.$emit('leave');
        this.$emit('close');
    }

    private handleWindowResize() {
        this.isMobile = screen.width < defaultBreakpoints.md;
    }

    @Watch('currentBehaviour')
    private handleBehaviourChange(newBehaviour: DialogBehaviour, oldBehaviour: DialogBehaviour) {
        if (oldBehaviour === 'popup') {
            this.removePopper();
        }

        if (newBehaviour === 'popup') {
            this.$nextTick(() => this.mountPopper());
        }

        if (newBehaviour === 'modal') {
            this.mountModal();
        }

        if (oldBehaviour === 'modal') {
            this.dismountModal();
        }
    }
}

export default UiDialog;
