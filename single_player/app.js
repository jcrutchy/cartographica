import { wsConnection } from "./lib/websocket.js";
import { showError, clearError } from "./lib/error.js";

import {
    showMainMenu,
    showLoadWorldMenu
} from "./menu.js";

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
