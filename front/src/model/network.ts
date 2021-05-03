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
