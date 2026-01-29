// ---------------------------------------------------------------------------
//  IslandConnection
//  A simple WebSocket wrapper for Cartographica.
//  Handles connection, message routing, and clean error propagation.
// ---------------------------------------------------------------------------

export class wsConnection {

    constructor(handlers = {}) {
        this.ws_url = "ws://localhost:8081";
        this.ws = null;

        // Expected handlers:
        // onWorldList(msg)
        // onWorldCreated(msg)
        // onWorldData(msg)
        // onError(err)
        this.handlers = handlers;
    }

    // -----------------------------------------------------------------------
    //  Establish WebSocket connection
    // -----------------------------------------------------------------------

    connect() {
        return new Promise((resolve, reject) => {
            this.ws = new WebSocket(this.ws_url);

            let settled = false;

            this.ws.onopen = () => {
                if (!settled) {
                    settled = true;
                    resolve(true);
                }
            };

            this.ws.onerror = (err) => {
                if (!settled) {
                    settled = true;
                    reject(err);
                }
                if (this.handlers.onError) {
                    this.handlers.onError(err);
                }
            };

            this.ws.onclose = () => {
                if (!settled) {
                    settled = true;
                    reject(new Error("WebSocket closed before opening"));
                }
            };

            this.ws.onmessage = (ev) => {
                this._handleMessage(ev.data);
            };
        });
    }

    // -----------------------------------------------------------------------
    //  Send JSON message
    // -----------------------------------------------------------------------

    send(obj) {
        if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
            console.warn("Attempted to send while WebSocket not open:", obj);
            return;
        }
        this.ws.send(obj);
    }

    // -----------------------------------------------------------------------
    //  Internal message router
    // -----------------------------------------------------------------------

    _handleMessage(raw) {
        let msg = null;

        try {
            msg = JSON.parse(raw);
        } catch (err) {
            console.error("Invalid JSON from server:", raw);
            return;
        }

        switch (msg.type) {

            case "WORLD_LIST":
                if (this.handlers.onWorldList) {
                    this.handlers.onWorldList(msg);
                }
                break;

            case "WORLD_CREATED":
                if (this.handlers.onWorldCreated) {
                    this.handlers.onWorldCreated(msg);
                }
                break;

            case "WORLD_DATA":
                if (this.handlers.onWorldData) {
                    this.handlers.onWorldData(msg);
                }
                break;

            case "ERROR":
                console.error("Server error:", msg.message);
                if (this.handlers.onError) {
                    this.handlers.onError(msg.message);
                }
                break;

            default:
                console.warn("Unknown message type:", msg.type, msg);
                break;
        }
    }
}
