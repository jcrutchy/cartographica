// net/nds.js

import { apiFetch } from "../rest.js";

const NDS_URL = "/cartographica/nds/index.php";

export const NDS = {
    async listNetworks() {
        const json = await apiFetch(`${NDS_URL}?action=list_networks`);
        return json.data; // array of networks
    },

    async listNodes(networkId) {
        const json = await apiFetch(`${NDS_URL}?action=list_nodes&network_id=${encodeURIComponent(networkId)}`);
        return json.data; // array of nodes
    }
};
