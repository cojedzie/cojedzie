/*
 * Copyright (C) 2026 Kacper Donat
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

import { defineStore } from "pinia";
import { computed, ref } from "vue";
import moment from "moment";
import type { Message, MessageType } from "@/model/message";
import api from "@/api";
import { useFetching } from "@/stores/fetching";

export const useMessagesStore = defineStore("messages", () => {
    const messages = ref<Message[]>([]);
    const { state, lastUpdate, error, fetching } = useFetching();

    const count = computed(() => messages.value.length);
    const counts = computed<Record<MessageType, number>>(() => ({
        info: messages.value.filter(message => message.type === "info").length,
        unknown: messages.value.filter(message => message.type === "unknown").length,
        breakdown: messages.value.filter(message => message.type === "breakdown").length,
    }));

    async function update() {
        await fetching(async () => {
            const response = await api.get("v1_message_all", {
                version: "^1.0",
                query: { embed: "$refs.stops,$refs.lines" },
            });

            messages.value = response.data.map(message => ({
                ...message,
                validFrom: moment.parseZone(message.validFrom).local(),
                validTo: moment.parseZone(message.validTo).local(),
            })) as Message[];
        });
    }

    return { messages, state, lastUpdate, error, count, counts, update };
});
