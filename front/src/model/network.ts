export type ApiNodeType = "hub" | "federated";

export interface ApiEndpoint {
    name: string,
    template: string,
    version: string,
    methods: string[],
}

export interface ApiNode {
    id: string,
    url: string,
    type: ApiNodeType,
    endpoints: ApiEndpoint[],
}

export type ApiNodeUpdateEventType = "node-joined" | "node-left" | "node-suspended" | "node-resumed";

export interface ApiNodeUpdate {
    event: ApiNodeUpdateEventType,
    node: ApiNode,
}
