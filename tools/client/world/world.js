import { Camera } from "./camera.js";
import { Renderer } from "./renderer.js";

export class World

{
    constructor(worldData, connection)
    {
        this.islands = worldData.islands;
        this.players = worldData.players;
        this.connection = connection;

        this.canvas = document.getElementById("worldCanvas");
        if (!this.canvas) {
            this.canvas = document.createElement("canvas");
            this.canvas.id = "worldCanvas";
            document.body.appendChild(this.canvas);
        }

        this.resizeCanvas();

        this.camera = new Camera(this.canvas);
        this.renderer = new Renderer(this.canvas, this.camera);

        this.autoFollow = true;

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
        this.renderer.render(this);
        requestAnimationFrame(() => this.loop());
    }

    update() {
        if (this.autoFollow) {
            const me = Object.values(this.players)[0];
            if (me) {
                const island = this.islands.find(i => i.id === me.islandId);
                if (island) {
                    const wx = island.originX + me.x * 32;
                    const wy = island.originY + me.y * 32;
                    this.camera.centerOn(wx, wy);
                }
            }
        }
    }

    updatePlayer(msg) {
        const p = this.players[msg.id];
        if (!p) return;
        p.x = msg.x;
        p.y = msg.y;
    }

    bindInput() {
        this.canvas.addEventListener("mousedown", () => {
            this.autoFollow = false;
        });

        this.canvas.addEventListener("wheel", () => {
            this.autoFollow = false;
        }, { passive: false });

        window.addEventListener("keydown", (e) => {
            if (e.key === "f") {
                this.autoFollow = true;
            }
        });
    }
}
