import { Stop, Track } from "@/model";
import { ApiClient } from "@/api/client";
import { Endpoints } from "@/api/endpoints";

export default class TrackRepository {
    public constructor(private client: ApiClient<Endpoints, "provider">) {
    }

    async getTracksForDestination(stop: Pick<Stop, "id">, destination: Pick<Stop, "id">): Promise<Track[]> {
        const response = await this.client.get('v1_track_list', {
            version: "1.0",
            query: {
                stop: stop.id,
                destination: destination.id,
                embed: "stops"
            }
        })

        return response.data;
    }
}
