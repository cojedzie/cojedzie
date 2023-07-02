import { convertProviderDtoToProvider, Provider } from "@/model";
import { ApiClient, client } from "@/api/client";
import { Endpoints } from "@/api/endpoints";

export default class ProviderRepository {
    static readonly service = Symbol();

    public constructor(private client: ApiClient<Endpoints, "provider">) {}

    public async getAllProviders(): Promise<Provider[]> {
        return await client
            .get("v1_provider_list", {
                version: "1.0",
            })
            .then(res => res.data.map(convertProviderDtoToProvider));
    }
}
