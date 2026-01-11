export class IslandConnection

{
    constructor(identity, ws_url, handlers = {})
    {
        this.identity = identity;
        this.ws_url = ws_url;
        this.ws = null;
        this.handlers = handlers; // { onWorld, onPlayerMoved }
    }

    defaultTilesetFound()
    {
        return !!localStorage.getItem("cartographica_default_tileset");
    }

    connect()
    {
        return new Promise((resolve, reject) => {
        console.log(this.ws_url);
            this.ws = new WebSocket(this.ws_url);
    
            this.ws.onopen = () => {
                console.log("Connected to island server");
                resolve(true);
            };
    
            this.ws.onerror = (err) => {
                console.error("WebSocket error", err);
                reject(err);
            };
    
            this.ws.onmessage = (ev) => {
                const msg = JSON.parse(ev.data);
                console.log("WS MESSAGE:", msg);
    
                switch (msg.type)
                {
                    case "HELLO":
                        this.ws.send(JSON.stringify({
                            type: "AUTH",
                            payload: this.identity.payload,
                            signature: this.identity.signature
                        }));
                        break;
                    case "AUTH_OK":
                        this.ws.send(JSON.stringify({
                            type: "REQUEST_WORLD",
                            default_tileset: this.defaultTilesetFound()
                        }));
                        break;
                    case "WORLD":
                        if (this.handlers.onWorld) {
                            this.handlers.onWorld(msg);
                        }
                        break;
                    case "PLAYER_MOVED":
                        if (this.handlers.onPlayerMoved) {
                            this.handlers.onPlayerMoved(msg);
                        }
                        break;
                }

            };
        });
    }

}
