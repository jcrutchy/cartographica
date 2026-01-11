import { Identity } from "./identity.js";
import { Atlas } from "../net/atlas.js";
import { IslandConnection } from "../net/websocket.js";
import { UIState, setUIState } from "./state.js";
import { showError, clearError } from "../error.js";
import { World } from "../world/world.js";

let world = null;

function startWorld(worldData, connection) {
    console.log("Starting world...");

    const root = document.getElementById("menu-root");
    root.innerHTML = "";

    world = new World(worldData, connection);
    world.start();
}

export function showMainUI() {
    const currentNetwork = localStorage.getItem("cartographica_selected_network");

    if (!currentNetwork) {
        showNetworkSelectionUI();
    } else {
        showMainMenu();
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
        const networks = await Atlas.listNetworks();
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
                const name = btn.innerHTML.trim();
                localStorage.setItem("cartographica_selected_network", JSON.stringify({ network_id: id, network_name: name }));
                showMainMenu();
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
function showMainMenu() {

    let selected_network=localStorage.getItem("cartographica_selected_network");
    if (!selected_network)
    {
      showNetworkSelectionUI();
      return;
    };
    selected_network=JSON.parse(selected_network);

    const root = document.getElementById("menu-root");

    root.innerHTML = `
        <div class="menu-panel">
            <div class="menu-title">Main Menu</div>
            <div class="menu-subtitle">Network: ${selected_network.network_name}</div>

            <button class="menu-button" id="btn-play">Play</button>
            <button class="menu-button" id="btn-change-network">Change Network</button>
            <button class="menu-button" id="btn-logout">Logout</button>
        </div>
    `;

    document.getElementById("btn-play").onclick = () => {
        startGame(selected_network);
    };

    document.getElementById("btn-change-network").onclick = () => {
        localStorage.removeItem("cartographica_selected_network");
        showNetworkSelectionUI();
    };

    document.getElementById("btn-logout").onclick = () => {
        localStorage.removeItem("cartographica_session_token");
        localStorage.removeItem("cartographica_selected_network");
        setUIState(UIState.LOGIN);
    };
}

// ------------------------------------------------------------
// Game start: pick an island and connect
// ------------------------------------------------------------
async function startGame(selected_network) {
    clearError();
    if (!Identity.isAuthenticated()) {
        showError("You are not logged in.");
        setUIState(UIState.MAIN);
        return;
    }

    const identity = Identity.getIdentity();
    if (!identity) {
        showError("You are not logged in.");
        setUIState(UIState.LOGIN);
        return;
    }

    let islands;
    try {
        islands = await Atlas.listIslands(selected_network.network_id);
    } catch (err) {
        console.error("Failed to load islands:", err);
        showError("Failed to load islands for this network.");
        return;
    }

    if (!islands.length) {
        showError("No islands available in this network.");
        return;
    }

    // For now, just pick the first node
    const island = islands[0];

    //console.log(island);
    //console.log(identity);
    
    let url = island.ws_url;
    if (!url.startsWith("ws://") && !url.startsWith("wss://")) {
        url = "ws://" + url;
    }
    const conn = new IslandConnection(identity, url, {
        onWorld: (msg) => {
            console.log(msg);
            startWorld({
                islands: [
                    {
                        id: msg.island_id || "island_01",
                        originX: 0,
                        originY: 0,
                        tilemap: msg.tilemap,
                        default_tileset: msg.default_tileset
                    }
                ],
                players: msg.players
            }, conn);
        },
    
        onPlayerMoved: (msg) => {
            if (world) {
                world.updatePlayer(msg);
            }
        }
    });

    try {
        await conn.connect();
    } catch (err) {
        showError("Error connecting to island server.");
        return;
    }
}
