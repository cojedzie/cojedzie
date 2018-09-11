import Vue from 'vue';
import { Component, Prop, Watch } from "vue-property-decorator";

import messages = require("../../components/messages.html");
import { Message } from "../model/message";
import urls from "../urls";

import { faInfoCircle, faExclamationTriangle, faQuestionCircle } from "@fortawesome/pro-light-svg-icons";

@Component({ template: messages })
export class MessagesComponent extends Vue {
    public messages: Message[] = [];

    async mounted() {
        this.update();
    }

    async update() {
        const response = await fetch(urls.prepare(urls.messages));

        if (response.ok) {
            this.messages = await response.json();
        }

        this.$emit('updated', this.messages);
    }

    public icon(message: Message) {
        switch (message.type) {
            case "breakdown": return faExclamationTriangle;
            case "info": return faInfoCircle;
            case "unknown": return faQuestionCircle;
        }
    }

    public type(message: Message) {
        switch (message.type) {
            case "breakdown": return "danger";
            case "info": return "info";
            case "unknown": return "warning";
        }
    }
}

Vue.component('Messages', MessagesComponent);