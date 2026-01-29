// world/renderer.js

import { TILE_WIDTH, TILE_HEIGHT } from "./constants.js";

export class Renderer {
    constructor(canvas, camera, world) {
        this.canvas = canvas;
        this.ctx = canvas.getContext("2d");
        this.camera = camera;
        this.world = world;

        // Handle resize once here
        this._setupResize();
    }

    _setupResize() {
        const resize = () => {
            const dpr = window.devicePixelRatio || 1;
            this.canvas.width = window.innerWidth * dpr;
            this.canvas.height = window.innerHeight * dpr;
            this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        };
        window.addEventListener("resize", resize);
        resize();
    }

    draw() {
        const ctx = this.ctx;
        ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;

        const width = this.world.width;
        const height = this.world.height;

        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                const tile = this.world.tiles[y][x];

                // Tile center in ISO world space
                const isoX = (x - y) * (tw / 2);
                const isoY = (x + y) * (th / 2);

                this._drawIsoTileWorld(tile, isoX, isoY, tw, th);
            }
        }
    }

    _drawIsoTileWorld(tile, isoCenterX, isoCenterY, w, h) {
        const ctx = this.ctx;

        // Diamond vertices in WORLD (iso) space
        const leftWorld  = { x: isoCenterX - w / 2, y: isoCenterY };
        const rightWorld = { x: isoCenterX + w / 2, y: isoCenterY };
        const topWorld   = { x: isoCenterX,         y: isoCenterY - h / 2 };
        const bottomWorld= { x: isoCenterX,         y: isoCenterY + h / 2 };

        // Transform each vertex via camera (world → screen)
        const left   = this.camera.worldToScreen(leftWorld.x,   leftWorld.y,   this.canvas);
        const right  = this.camera.worldToScreen(rightWorld.x,  rightWorld.y,  this.canvas);
        const top    = this.camera.worldToScreen(topWorld.x,    topWorld.y,    this.canvas);
        const bottom = this.camera.worldToScreen(bottomWorld.x, bottomWorld.y, this.canvas);

        ctx.fillStyle = tile === "land" ? "#4caf50" : "#3a6ea5";

        ctx.beginPath();
        ctx.moveTo(left.x,   left.y);
        ctx.lineTo(top.x,    top.y);
        ctx.lineTo(right.x,  right.y);
        ctx.lineTo(bottom.x, bottom.y);
        ctx.closePath();
        ctx.fill();
    }
}
