import { Identity } from "./identity.js";
import { NDS } from "../net/nds.js";
import { NodeConnection } from "../net/websocket.js";
import { UIState, setUIState } from "./state.js";

export function showMainUI() {
    const currentNetwork = localStorage.getItem("cartographica_network_id");

    if (!currentNetwork) {
        showNetworkSelectionUI();
    } else {
        showMainMenu(currentNetwork);
    }
}

// ------------------------------------------------------------
// Network selection UI
// ------------------------------------------------------------
async function showNetworkSelectionUI() {
    const root = document.getElementById("menu-root");
    root.innerHTML = `
        <div class="menu-panel">
            <div class="menu-title">Select Network</div>
            <div id="network-list">Loading...</div>
        </div>
    `;

    try {
        const networks = await NDS.listNetworks();
        const list = document.getElementById("network-list");

        if (!networks.length) {
            list.innerHTML = `
                <div>No networks found.</div>
                <div>You may need to start a node server first.</div>
            `;
            return;
        }

        list.innerHTML = networks.map(n => `
            <div class="menu-item">
                <button class="menu-button network-button" data-id="${n.id}">
                    ${n.name}
                </button>
            </div>
        `).join("");

        document.querySelectorAll(".network-button").forEach(btn => {
            btn.onclick = () => {
                const id = btn.getAttribute("data-id");
                localStorage.setItem("cartographica_network_id", id);
                showMainMenu(id);
            };
        });

    } catch (err) {
        console.error("Failed to load networks:", err);
        document.getElementById("network-list").innerText = "Failed to load networks.";
    }
}

// ------------------------------------------------------------
// Main menu for a specific network
// ------------------------------------------------------------
function showMainMenu(networkId) {
    const root = document.getElementById("menu-root");

    root.innerHTML = `
        <div class="menu-panel">
            <div class="menu-title">Main Menu</div>
            <div class="menu-subtitle">Network: ${networkId}</div>

            <button class="menu-button" id="btn-play">Play</button>
            <button class="menu-button" id="btn-change-network">Change Network</button>
            <button class="menu-button" id="btn-logout">Logout</button>
        </div>
    `;

    document.getElementById("btn-play").onclick = () => {
        startGame(networkId);
    };

    document.getElementById("btn-change-network").onclick = () => {
        localStorage.removeItem("cartographica_network_id");
        showNetworkSelectionUI();
    };

    document.getElementById("btn-logout").onclick = () => {
        localStorage.removeItem("cartographica_device_token");
        localStorage.removeItem("cartographica_identity_payload");
        localStorage.removeItem("cartographica_identity_signature");
        localStorage.removeItem("cartographica_network_id");
        setUIState(UIState.LOGIN);
    };
}

// ------------------------------------------------------------
// Game start: pick a node and connect
// ------------------------------------------------------------
async function startGame(networkId) {
    const identity = Identity.getIdentity();
    if (!identity) {
        alert("You are not logged in.");
        setUIState(UIState.LOGIN);
        return;
    }

    let nodes;
    try {
        nodes = await NDS.listNodes(networkId);
    } catch (err) {
        console.error("Failed to load nodes:", err);
        alert("Failed to load nodes for this network.");
        return;
    }

    if (!nodes.length) {
        alert("No nodes available in this network.");
        return;
    }

    // For now, just pick the first node
    const node = nodes[0];

    const conn = new NodeConnection(identity, node.ws_url);
    conn.connect((tiles, player) => {
        showWorldUI(tiles, player, networkId, node.node_id);
    });
}

// ------------------------------------------------------------
// Basic world view (placeholder)
// ------------------------------------------------------------
function showWorldUI(tiles, player, networkId, nodeId) {
    const root = document.getElementById("menu-root");

    let html = `<div class="menu-panel">
        <div class="menu-title">World View</div>
        <div class="menu-subtitle">Network: ${networkId} | Node: ${nodeId}</div>
        <pre>Player: ${player.player_id}\n\n`;

    for (let row of tiles) {
        html += row.join(" ") + "\n";
    }

    html += `</pre>
        <button class="menu-button" id="btn-back-main">Back to Main Menu</button>
    </div>`;

    root.innerHTML = html;

    document.getElementById("btn-back-main").onclick = () => {
        showMainMenu(networkId);
    };
}





















