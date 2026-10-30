// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount } from "@vue/test-utils";
import MessagesList from "@/components/messages/MessagesList.vue";
import { useMessagesStore } from "@/stores/messages";
import { useMessagesSettingsStore } from "@/stores/messages-settings";

const message = (id: string) =>
    ({
        id,
        type: "info",
        message: `Message ${id}`,
        $refs: { stops: { items: [] }, lines: { items: [] } },
    }) as any;

function mountList(count: number, displayed: number) {
    const pinia = createPinia();
    setActivePinia(pinia);
    useMessagesStore().messages = Array.from({ length: count }, (_, i) => message(String(i + 1)));
    useMessagesSettingsStore().update({ displayedEntriesCount: displayed });

    return mount(MessagesList, {
        global: {
            plugins: [pinia],
            stubs: {
                UiIcon: true,
                MessagesMessage: { props: ["message"], template: '<li class="message">{{ message }}</li>' },
            },
        },
    });
}

const shown = (wrapper: ReturnType<typeof mountList>) => wrapper.findAll(".message").map(item => item.text());

describe("MessagesList", () => {
    it("shows only the configured number of messages", () => {
        const wrapper = mountList(5, 2);

        expect(shown(wrapper)).toEqual(["Message 1", "Message 2"]);
        expect(wrapper.find("button").text()).toBe("3 więcej");
    });

    it("reveals the rest of the messages and hides them again", async () => {
        const wrapper = mountList(5, 2);

        await wrapper.find("button").trigger("click");
        expect(shown(wrapper)).toHaveLength(5);
        expect(wrapper.find("button").text()).toBe("3 mniej");

        await wrapper.find("button").trigger("click");
        expect(shown(wrapper)).toHaveLength(2);
    });

    it("has nothing to reveal when every message is shown", () => {
        const wrapper = mountList(2, 3);

        expect(shown(wrapper)).toHaveLength(2);
        expect(wrapper.find("button").exists()).toBe(false);
    });
});
