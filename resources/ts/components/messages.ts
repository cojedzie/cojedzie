import Vue from 'vue';
import { Component } from "vue-property-decorator";
import { Message } from "../model/message";
import { faInfoCircle, faExclamationTriangle, faQuestionCircle } from "@fortawesome/pro-light-svg-icons";
import { namespace } from 'vuex-class';
import store from '../store'

const { State } = namespace('messages');

@Component({ template: require("../../components/messages.html"), store })
export class MessagesComponent extends Vue {
    @State messages: Message[];

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