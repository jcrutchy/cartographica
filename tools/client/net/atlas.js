// net/atlas.js

import { apiFetch } from "../rest.js";

const ATLAS_URL = "/cartographica/services/atlas/index.php";

export const Atlas = {

    async listNetworks() {
        const json = await apiFetch(`${ATLAS_URL}?action=list_networks`);
        return json.network_list;
    },

    async listIslands(networkId) {
        const json = await apiFetch(`${ATLAS_URL}?action=list_islands&network_id=${encodeURIComponent(networkId)}`);
        return json.island_list;
    }
};
