/*
 * Based on vue-removed-hook-mixin v0.1.0
 * (c) 2019 James Diacono
 * @license MIT
 */

import { ComponentOptionsMixin, ComponentPublicInstance } from "vue";

export const removedHookMixin: ComponentOptionsMixin = {
    unmounted(this: ComponentPublicInstance) {
        const removed = () => {
            // quick and dirty version of Vue's lifecycle callHook method
            const hook = this['removed'] || this.$options.removed;
            hook.call(this)
        }

        // element was immediately detached from DOM (no transition)
        if (!document.body.contains(this.$el)) {
            removed()
            return
        }

        const mutationHandler = (mutations, observer) => {
            for (let i = 0; i < mutations.length; i++) {
                const { removedNodes } = mutations[i]

                for (let j = 0; j < removedNodes.length; j++) {
                    if (removedNodes[j].contains(this.$el)) {
                        observer.disconnect()
                        removed()
                    }
                }
            }
        }

        // start observing parent element for changes to the DOM
        const observer = new MutationObserver(mutationHandler)

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        })
    },
}

export default removedHookMixin;
