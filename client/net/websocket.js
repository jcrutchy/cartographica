export class WSClient {
    constructor(url) {
        this.url = url;
        this.ws = null;
        this.handlers = {};
    }

    connect() {
        this.ws = new WebSocket(this.url);

        this.ws.onopen = () => this.emit("open");
        this.ws.onclose = e => this.emit("close", e);
        this.ws.onerror = e => this.emit("error", e);
        this.ws.onmessage = e => {
            let data = e.data;
            try { data = JSON.parse(e.data); } catch {}
            this.emit("message", data);
        };
    }

    send(type, payload = {}) {
        const msg = JSON.stringify({ type, ...payload });
        this.ws.send(msg);
    }

    on(event, fn) {
        if (!this.handlers[event]) this.handlers[event] = [];
        this.handlers[event].push(fn);
    }

    emit(event, data) {
        if (this.handlers[event]) {
            for (const fn of this.handlers[event]) fn(data);
        }
    }
}
