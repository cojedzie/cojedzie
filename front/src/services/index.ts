import { ApiClient } from "@/api/client";
import { Endpoints } from "@/api/endpoints";
import ProviderRepository from "@/services/ProviderRepository";
import TrackRepository from "@/services/TrackRepository";
import createServiceContainer, { ServiceContainer } from "@/utils/container";
import { App, inject, InjectionKey } from "vue";
import { ApiClientKey } from "@/api";

export type Services = {
    api: ApiClient<Endpoints, "provider">;
    [ProviderRepository.service]: ProviderRepository;
    [TrackRepository.service]: TrackRepository;
};

export const ContainerKey: InjectionKey<ServiceContainer<Services>> = Symbol();

export default function install(app: App) {
    const container = createServiceContainer<Services>({
        api: () => inject(ApiClientKey),
        [ProviderRepository.service]: container => new ProviderRepository(container.get("api")),
        [TrackRepository.service]: container => new TrackRepository(container.get("api")),
    });

    app.provide(ContainerKey, container);
}
