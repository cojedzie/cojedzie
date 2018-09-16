import Vue from 'vue';
import { Component, Prop, Watch } from "vue-property-decorator";
import { Message } from "../model/message";
import urls from "../urls";

import { faInfoCircle, faExclamationTriangle, faQuestionCircle } from "@fortawesome/pro-light-svg-icons";
import { Jsonified } from "../utils";
import * as moment from "moment";

@Component({ template: require("../../components/messages.html") })
export class MessagesComponent extends Vue {
    public messages: Message[] = [];

    async mounted() {
        this.update();
        setInterval(() => this.update(), 5000);
    }

    async update() {
        const response = await fetch(urls.prepare(urls.messages));

        if (response.ok) {
            const messages = (await response.json()) as Jsonified<Message>[];
            this.messages = messages.map(m => {
                const message = m as Message;

                message.validFrom = moment(m.validFrom);
                message.validTo   = moment(m.validTo);

                return message;
            });
        }

        this.$emit('update', this.messages);
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