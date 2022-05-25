<template>
    <div class="ui-pin" :class="[`ui-pin--${variant}`]">
        <svg
            class="ui-pin__pin"
            viewBox="0 0 24 35"
            version="1.1"
            xmlns="http://www.w3.org/2000/svg"
            xml:space="preserve"
            style="fill-rule: evenodd; clip-rule: evenodd; stroke-linejoin: round; stroke-miterlimit:2;"
        >
            <g id="Pin" transform="matrix(0.5,0,0,0.5,-49,0)">
                <path
                    d="M509.113,279.233C520.33,260.324 526.773,238.254 526.773,214.692C526.773,144.723 469.968,87.918 400,87.918C330.032,87.918 273.227,144.723 273.227,214.692C273.227,238.261 279.674,260.337 290.897,279.25C290.897,279.25 364.716,407.109 387.972,447.391C390.453,451.688 395.038,454.335 400,454.335C404.962,454.335 409.547,451.688 412.028,447.391C435.284,407.109 509.113,279.233 509.113,279.233Z"
                    transform="matrix(0.189314,0,0,0.189314,46.2742,-16.6442)"
                    style="fill: var(--ui-pin-color);"
                />
                <path
                    v-if="variant !== 'filled'"
                    d="M500.027,273.843L402.879,442.109C402.285,443.137 401.187,443.771 400,443.771C398.813,443.771 397.715,443.137 397.121,442.109L299.982,273.859C289.697,256.526 283.791,236.293 283.791,214.692C283.791,150.554 335.863,98.483 400,98.483C464.137,98.483 516.209,150.554 516.209,214.692C516.209,236.286 510.307,256.514 500.027,273.843Z"
                    transform="matrix(0.189314,0,0,0.189314,46.2742,-16.6442)"
                    style="fill:white;"
                />
                <path
                    v-if="variant === 'filled-outline'"
                    d="M400,425.966L309.068,268.467C299.719,252.714 294.356,234.324 294.356,214.692C294.356,156.385 341.693,109.047 400,109.047C458.307,109.047 505.644,156.385 505.644,214.692C505.644,234.318 500.284,252.703 490.941,268.453L400,425.966Z"
                    transform="matrix(0.189314,0,0,0.189314,46.2742,-16.6442)"
                    style="fill: var(--ui-pin-color);"
                />
            </g>
        </svg>
        <div class="ui-pin__content">
            <slot />
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent, PropType } from "vue";

export type UiPinVariant = "filled" | "outline" | "filled-outline";

export const UiPinProps = {
    variant: {
        type: String as PropType<UiPinVariant>,
        default: "outline" as UiPinVariant,
    }
}

export default defineComponent({
    name: "UiPin",
    props: UiPinProps,
})
</script>

<style lang="scss">
@import "../../../styles/_variables.scss";

$ui-pin-width: 32px;

.ui-pin {
    position: relative;
    --ui-pin-color: #{$primary};
    --ui-pin-width: #{$ui-pin-width};
}

.ui-pin__pin {
    width: var(--ui-pin-width);
}

.ui-pin__content {
    position: absolute;
    top: calc(var(--ui-pin-width) / 2);
    left: calc(var(--ui-pin-width) / 2);
    text-align: center;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 16px;
}

.ui-pin--outline .ui-pin__content {
    color: var(--ui-pin-color);
}

@each $type, $color in $line-types {
    .ui-pin.ui-pin--#{$type} {
        --ui-pin-color: $color;
    }
}
</style>
