<template>
    <main class="d-flex">
        <router-view />
    </main>
</template>

<script lang="ts">
import store from "@/store";
import { Options, Vue } from "vue-class-component";
import { router } from "@/routes";

@Options({ router, store })
export default class Application extends Vue {
    mounted() {
        this.$el.classList.remove("not-ready");
    }
}
</script>
