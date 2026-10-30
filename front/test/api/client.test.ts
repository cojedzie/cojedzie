// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

vi.mock("@/store", () => {
    throw new Error("store imported");
});

const get = vi.fn();

vi.mock("axios", async importOriginal => {
    const actual = await importOriginal<typeof import("axios")>();
    return {
        ...actual,
        default: {
            ...actual.default,
            create: () => ({ defaults: { baseURL: "http://fallback.test" }, get }),
        },
    };
});

const node = {
    id: "node-1",
    url: "http://node.test",
    endpoints: { v1_provider_list: { template: "/api/v1/providers", version: "1.0.0" } },
};

describe("API client", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.stubGlobal("CoJedzie", { api: { base: "http://localhost", hub: "http://localhost" } });
        get.mockReset();
        get.mockResolvedValue({ data: [] });
    });

    afterEach(async () => {
        const { resetClientContext } = await import("@/api/context");
        resetClientContext();
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    it("importing the client creates no store", async () => {
        await expect(import("@/api")).resolves.toBeDefined();
    });

    it("updates the nodes when a context is bound", async () => {
        const { bindClientContext } = await import("@/api/context");
        const update = vi.fn().mockResolvedValue(undefined);

        bindClientContext({
            network: { available: () => [], update, nodeFailed: async () => {} },
            provider: () => undefined,
        });

        expect(update).toHaveBeenCalledOnce();
    });

    it("uses the fallback API before a context is bound", async () => {
        const { default: client } = await import("@/api");

        await client.get("v1_provider_list", { version: "^1.0" });

        expect(get).toHaveBeenCalledOnce();
        expect(get.mock.calls[0][1].baseURL).toBe("http://fallback.test");
    });

    it("uses an available node from the bound context", async () => {
        const { default: client } = await import("@/api");
        const { bindClientContext } = await import("@/api/context");
        bindClientContext({
            network: { available: () => [node as never], update: async () => {}, nodeFailed: async () => {} },
            provider: () => undefined,
        });

        await client.get("v1_provider_list", { version: "^1.0" });

        expect(get.mock.calls[0][1].baseURL).toBe("http://node.test");
    });

    it("reports a failing node", async () => {
        const { default: client } = await import("@/api");
        const { bindClientContext } = await import("@/api/context");
        const nodeFailed = vi.fn().mockResolvedValue(undefined);
        bindClientContext({
            network: { available: () => [node as never], update: async () => {}, nodeFailed },
            provider: () => undefined,
        });
        get.mockRejectedValueOnce(new Error("boom"));

        const request = client.get("v1_provider_list", { version: "^1.0" });
        await vi.runAllTimersAsync();
        await request;

        expect(nodeFailed).toHaveBeenCalledOnce();
        expect(nodeFailed).toHaveBeenCalledWith("node-1");
    });
});
