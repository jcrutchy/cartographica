import { wsConnection } from "./lib/websocket.js";
import { showError, clearError } from "./lib/error.js";

import {
    showMainMenu,
    showLoadWorldMenu
} from "./menu.js";

import { generateIsland } from "./world/generate.js";
import { World } from "./world/world.js";

let world = null;
let conn = null;

window.addEventListener("load", onWindowLoad);

// ---------------------------------------------------------------------------
//  Startup
// ---------------------------------------------------------------------------

function onWindowLoad() {
    initConnection();
    showMainMenu();
}

// ---------------------------------------------------------------------------
//  WebSocket Connection
// ---------------------------------------------------------------------------

function initConnection() {
    conn = new wsConnection({
        onWorldList: handleWorldList,
        onWorldCreated: handleWorldCreated,
        onWorldData: handleWorldData,
        onError: handleSocketError
    });

    window.conn = conn;

    conn.connect().catch(err => {
        showError("Unable to connect to server.");
    });
}

// ---------------------------------------------------------------------------
//  Server Message Handlers
// ---------------------------------------------------------------------------

function handleWorldList(msg) {
    // msg.worlds = [ { id, name }, ... ]
    showLoadWorldMenu(msg.worlds);
}

function handleWorldCreated(msg) {
    // msg.world_id
    const id = msg.world_id;

    conn.send(JSON.stringify({
        type: "LOAD_WORLD",
        world_id: id
    }));
}

function handleWorldData(msg) {
    // msg contains full world JSON
    startWorld(msg);
}

function handleSocketError(err) {
    showError("Connection error.");
}

// ---------------------------------------------------------------------------
//  World Startup
// ---------------------------------------------------------------------------

/*async function startWorld(worldData) {
    const root = document.getElementById("menu-root");

    clearError();

    world = new World(worldData);
    world.start();
}

window.startNewWorld = function(graphType)
{
    const seed = Date.now() & 0xffffffff;

    const root = document.getElementById("menu-root");
    root.style.display = "flex";
    root.innerHTML = "<div class='loading'>Generating world...</div>";

    worldgenWorker.onmessage = function(ev) {
        const root = document.getElementById("menu-root");
        root.style.display = "none";
        const { worldData } = ev.data;
        startWorld(worldData);
    };

    worldgenWorker.postMessage({ seed, graphType });
};*/


window.startNewWorld = function (graphType) {
    // Hide menu
    const root = document.getElementById("menu-root");
    root.style.display = "none";

    // Generate new tilemap island
    const worldData = generateIsland();

    // Start the world
    world = new World(worldData);
    world.start();
};
