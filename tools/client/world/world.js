export class World {
    constructor(tilemap, players) {
        this.tilemap = tilemap;
        this.players = players;

        this.canvas = document.getElementById("worldCanvas");
        if (!this.canvas) {
            this.canvas = document.createElement("canvas");
            this.canvas.id = "worldCanvas";
            document.body.appendChild(this.canvas);
        }

        this.resizeCanvas();

        this.camera = new Camera(this.canvas);
        this.renderer = new Renderer(this.canvas, this.camera);

        window.addEventListener("resize", () => this.resizeCanvas());
    }

    resizeCanvas() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }

    start() {
        this.bindInput();
        requestAnimationFrame(() => this.loop());
    }

    loop() {
        this.update();
        this.renderer.render(this.tilemap, this.players);
        requestAnimationFrame(() => this.loop());
    }

    update() {
        const me = Object.values(this.players)[0];
        if (me) {
            this.camera.centerOn(me.x, me.y);
        }
    }

    updatePlayer(msg) {
        const p = this.players[msg.id];
        if (!p) return;
        p.x = msg.x;
        p.y = msg.y;
    }

    bindInput() {
        window.addEventListener("keydown", (e) => {
            let dx = 0, dy = 0;

            if (e.key === "ArrowUp" || e.key === "w") dy = -1;
            if (e.key === "ArrowDown" || e.key === "s") dy = 1;
            if (e.key === "ArrowLeft" || e.key === "a") dx = -1;
            if (e.key === "ArrowRight" || e.key === "d") dx = 1;

            if (dx !== 0 || dy !== 0) {
                ws.send(JSON.stringify({
                    type: "MOVE",
                    dx,
                    dy
                }));
            }
        });
    }
}
