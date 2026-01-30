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

        const first = this.data.islands[0];

        console.log(first);

        const centerTileX = first.originX + first.width / 2;
        const centerTileY = first.originY + first.height / 2;
        
        const isoX = (centerTileX - centerTileY) * (TILE_WIDTH / 2);
        const isoY = (centerTileX + centerTileY) * (TILE_HEIGHT / 2);

        console.log("center on: "+isoX+", "+isoY);

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
