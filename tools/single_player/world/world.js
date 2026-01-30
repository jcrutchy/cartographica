// world/world.js

import { Camera } from "./camera.js";
import { Renderer } from "./renderer.js";
import { TILE_WIDTH, TILE_HEIGHT } from "./constants.js";

export class World {
    constructor(worldData) {
        this.data = worldData;

        this.canvas = document.getElementById("game");
        this.camera = new Camera(this.canvas);

        this.renderer = new Renderer(
            this.canvas,
            this.camera,
            this.data
        );

        // Center camera on world middle in ISO space
        const midX = this.data.width / 2;
        const midY = this.data.height / 2;

        const isoX = (midX - midY) * (TILE_WIDTH / 2);
        const isoY = (midX + midY) * (TILE_HEIGHT / 2);

        this.camera.centerOn(isoX, isoY);
    }

    start() {
        const loop = () => {
            this.renderer.draw();
            requestAnimationFrame(loop);
        };
        loop();
    }
}
