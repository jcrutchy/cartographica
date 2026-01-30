// world/renderer.js

import { TILE_WIDTH, TILE_HEIGHT, CHUNK_SIZE, TILE_COLORS } from "./constants.js";

export class Renderer {
    constructor(canvas, camera, world) {
        this.canvas = canvas;
        this.ctx = canvas.getContext("2d");
        this.camera = camera;
        this.world = world;

        this.chunkCache = new Map();

        this.chunksX = Math.ceil(this.world.width / CHUNK_SIZE);
        this.chunksY = Math.ceil(this.world.height / CHUNK_SIZE);

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

    _chunkKey(cx, cy) {
        return `${cx},${cy}`;
    }

    _getOrCreateChunkBitmap(cx, cy) {
        const key = this._chunkKey(cx, cy);
        if (this.chunkCache.has(key)) return this.chunkCache.get(key);

        const bitmap = this._renderChunk(cx, cy);
        this.chunkCache.set(key, bitmap);
        return bitmap;
    }

    _renderChunk(cx, cy) {
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;

        // Padding to avoid clipped diamonds
        const PAD_X = tw / 2;
        const PAD_Y = th / 2;

        const chunkPixelW = CHUNK_SIZE * tw + tw;
        const chunkPixelH = CHUNK_SIZE * th + th;

        const canvas =
            typeof OffscreenCanvas !== "undefined"
                ? new OffscreenCanvas(chunkPixelW, chunkPixelH)
                : (() => {
                      const c = document.createElement("canvas");
                      c.width = chunkPixelW;
                      c.height = chunkPixelH;
                      return c;
                  })();

        const ctx = canvas.getContext("2d");

        // Chunk-local origin (center of tile 0,0 inside chunk)
        const originX = PAD_X + (CHUNK_SIZE - 1) * (tw / 2);
        const originY = PAD_Y;

        const startX = cx * CHUNK_SIZE;
        const startY = cy * CHUNK_SIZE;

        for (let y = 0; y < CHUNK_SIZE; y++) {
            for (let x = 0; x < CHUNK_SIZE; x++) {
                const worldX = startX + x;
                const worldY = startY + y;

                if (worldY < 0 || worldY >= this.world.height) continue;
                if (worldX < 0 || worldX >= this.world.width) continue;

                const tile = this.world.tiles[worldY][worldX];

                // Tile center in chunk-local ISO space
                const isoX = originX + (x - y) * (tw / 2);
                const isoY = originY + (x + y) * (th / 2);

                this._drawIsoTileChunk(ctx, tile, isoX, isoY, tw, th);
            }
        }

        return canvas;
    }

    _drawIsoTileChunk(ctx, tile, isoCenterX, isoCenterY, w, h) {
        const leftX   = isoCenterX - w / 2;
        const rightX  = isoCenterX + w / 2;
        const topY    = isoCenterY - h / 2;
        const bottomY = isoCenterY + h / 2;

        ctx.fillStyle = TILE_COLORS[tile] || "#ff00ff"; // fallback magenta for debugging

        ctx.beginPath();
        ctx.moveTo(leftX,        isoCenterY); // left
        ctx.lineTo(isoCenterX,   topY);       // top
        ctx.lineTo(rightX,       isoCenterY); // right
        ctx.lineTo(isoCenterX,   bottomY);    // bottom
        ctx.closePath();
        ctx.fill();
    }

    draw() {
        const ctx = this.ctx;

        ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;

        const PAD_X = tw / 2;
        const PAD_Y = th / 2;

        const chunkPixelW = CHUNK_SIZE * tw + tw;
        const chunkPixelH = CHUNK_SIZE * th + th;

        const originX = PAD_X + (CHUNK_SIZE - 1) * (tw / 2);
        const originY = PAD_Y;

        for (let cy = 0; cy < this.chunksY; cy++) {
            for (let cx = 0; cx < this.chunksX; cx++) {
                const chunkCanvas = this._getOrCreateChunkBitmap(cx, cy);
                if (!chunkCanvas) continue;

                const baseX = cx * CHUNK_SIZE;
                const baseY = cy * CHUNK_SIZE;

                // Chunk center in ISO world space
                const chunkIsoX = (baseX - baseY) * (tw / 2);
                const chunkIsoY = (baseX + baseY) * (th / 2);

                // Top-left of chunk canvas in ISO world space
                const topLeftIsoX = chunkIsoX - originX;
                const topLeftIsoY = chunkIsoY - originY;

                // ISO world → screen via camera
                const screen = this.camera.worldToScreen(
                    topLeftIsoX,
                    topLeftIsoY,
                    this.canvas
                );

                ctx.drawImage(
                    chunkCanvas,
                    screen.x,
                    screen.y,
                    chunkPixelW * this.camera.scale,
                    chunkPixelH * this.camera.scale
                );
            }
        }
    }
}
