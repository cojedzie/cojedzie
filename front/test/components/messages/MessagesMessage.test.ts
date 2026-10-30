// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import moment from "moment";
import MessagesMessage from "@/components/messages/MessagesMessage.vue";

function mountMessage(props = {}) {
    return mount(MessagesMessage, {
        props: { message: "Tram detour", ...props },
        global: {
            stubs: {
                UiIcon: { props: ["icon"], template: '<i :class="icon" />' },
                StopLabel: { props: ["stop"], template: '<span class="stop">{{ stop.name }}</span>' },
                LineSymbol: { props: ["line"], template: '<span class="line">{{ line.symbol }}</span>' },
            },
        },
    });
}

describe("MessagesMessage", () => {
    it("renders the lines and the stops it concerns", () => {
        const wrapper = mountMessage({
            lines: [{ id: "6", symbol: "6" }],
            stops: [
                { id: "1", name: "Brama" },
                { id: "2", name: "Oliwa" },
            ],
        });

        expect(wrapper.findAll(".line").map(line => line.text())).toEqual(["6"]);
        expect(wrapper.findAll(".stop").map(stop => stop.text())).toEqual(["Brama", "Oliwa"]);
    });

    it("renders neither lines nor stops when there are none", () => {
        const wrapper = mountMessage();

        expect(wrapper.find(".message__lines").exists()).toBe(false);
        expect(wrapper.find(".stop-list").exists()).toBe(false);
    });

    it("renders the validity and the given tag", () => {
        const wrapper = mountMessage({
            tag: "li",
            validFrom: moment("2020-01-02T10:00:00"),
            validTo: moment("2020-01-03T10:00:00"),
        });

        expect(wrapper.element.tagName).toBe("LI");
        expect(wrapper.find(".message__validity").text().replace(/\s+/g, " ")).toBe(
            `ważne od ${moment("2020-01-02").format("LL")} do ${moment("2020-01-03").format("LL")}`,
        );
    });
});
