// net/ws.js

const NODE_WS_URL = "ws://localhost:8080";

export class NodeConnection {
    constructor(identity) {
        this.identity = identity;
        this.ws = null;
    }

    connect(onWorld) {
        this.ws = new WebSocket(NODE_WS_URL);

        this.ws.onopen = () => {
            console.log("Connected to node server");
        };

        this.ws.onmessage = (ev) => {
            const msg = JSON.parse(ev.data);

            if (msg.type === "HELLO") {
                this.ws.send(JSON.stringify({
                    type: "AUTH",
                    payload: this.identity.payload,
                    signature: this.identity.signature
                }));
            }

            if (msg.type === "AUTH_OK") {
                this.ws.send(JSON.stringify({ type: "REQUEST_WORLD" }));
            }

            if (msg.type === "WORLD") {
                onWorld(msg.tiles, msg.player);
            }
        };
    }
}
