import { TILE_WIDTH, TILE_HEIGHT, CHUNK_SIZE, TILE_COLORS } from "./constants.js";

export class Renderer {
    constructor(canvas, camera, world) {
        this.canvas = canvas;
        this.ctx = canvas.getContext("2d");
        this.camera = camera;
        this.world = world;

        this.debugDrawChunkBoundaries = true;
        this.debugDrawIslandBounds = true;

        this.chunkCache = new Map();

        // Precompute per-island chunk grids
        for (const island of this.world.islands) {
            island.chunksX = Math.ceil(island.width  / CHUNK_SIZE);
            island.chunksY = Math.ceil(island.height / CHUNK_SIZE);
        }

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

    _chunkKey(island, cx, cy) {
        return `${island.id}:${cx},${cy}`;
    }

    _getOrCreateChunkBitmap(island, cx, cy) {
        const key = this._chunkKey(island, cx, cy);
        if (this.chunkCache.has(key)) return this.chunkCache.get(key);

        const bitmap = this._renderChunk(island, cx, cy);
        this.chunkCache.set(key, bitmap);
        return bitmap;
    }

    _getTile(worldX, worldY) {
        for (const island of this.world.islands) {
            const localX = worldX - island.originX;
            const localY = worldY - island.originY;

            if (
                localX >= 0 && localY >= 0 &&
                localX < island.width &&
                localY < island.height
            ) {
                return island.tiles[localY][localX];
            }
        }
        return null;
    }

    _renderChunk(island, cx, cy) {
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;

        const PAD_X = tw / 2;
        const PAD_Y = th / 2;

        const chunkPixelW = CHUNK_SIZE * tw + tw;
        const chunkPixelH = CHUNK_SIZE * th + th;

        const canvas = document.createElement("canvas");
        canvas.width = chunkPixelW;
        canvas.height = chunkPixelH;

        const ctx = canvas.getContext("2d");

        const originX = PAD_X + (CHUNK_SIZE - 1) * (tw / 2);
        const originY = PAD_Y;

        // Per-island world-space chunk origin
        const startX = island.originX + cx * CHUNK_SIZE;
        const startY = island.originY + cy * CHUNK_SIZE;

        for (let y = 0; y < CHUNK_SIZE; y++) {
            for (let x = 0; x < CHUNK_SIZE; x++) {
                const worldX = startX + x;
                const worldY = startY + y;

                const tile = this._getTile(worldX, worldY);
                if (!tile) continue;

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

        ctx.fillStyle = TILE_COLORS[tile] || "#ff00ff";

        ctx.beginPath();
        ctx.moveTo(leftX,        isoCenterY);
        ctx.lineTo(isoCenterX,   topY);
        ctx.lineTo(rightX,       isoCenterY);
        ctx.lineTo(isoCenterX,   bottomY);
        ctx.closePath();
        ctx.fill();
    }

    _drawChunkIsoDiamond(island, cx, cy) {
        const ctx = this.ctx;
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
        const scale = this.camera.scale;
    
        // World-space chunk origin (top-left tile of the chunk)
        const baseX = island.originX + cx * CHUNK_SIZE;
        const baseY = island.originY + cy * CHUNK_SIZE;
    
        // Compute the four tile-corner coordinates of the chunk
        const corners = [
            { x: baseX,                 y: baseY                 }, // top-left
            { x: baseX + CHUNK_SIZE,    y: baseY                 }, // top-right
            { x: baseX + CHUNK_SIZE,    y: baseY + CHUNK_SIZE    }, // bottom-right
            { x: baseX,                 y: baseY + CHUNK_SIZE    }, // bottom-left
        ].map(pt => {
            // Convert tile-corner → iso (top corner of tile)
            const isoX = (pt.x - pt.y) * (tw / 2);
            const isoY = (pt.x + pt.y) * (th / 2) - (th / 2);
    
            // iso → screen
            const screenX = (isoX - this.camera.x) * scale + this.canvas.width / 2;
            const screenY = (isoY - this.camera.y) * scale + this.canvas.height / 2;
    
            return { x: screenX, y: screenY };
        });
    
        // Draw the diamond
        ctx.strokeStyle = "rgba(0, 255, 0, 0.8)";
        ctx.lineWidth = 2;
    
        ctx.beginPath();
        ctx.moveTo(corners[0].x, corners[0].y);
        ctx.lineTo(corners[1].x, corners[1].y);
        ctx.lineTo(corners[2].x, corners[2].y);
        ctx.lineTo(corners[3].x, corners[3].y);
        ctx.closePath();
        ctx.stroke();
    }

    _drawChunkBoundary(label, screenX, screenY, w, h) {
        const ctx = this.ctx;

        ctx.strokeStyle = "rgba(255, 0, 0, 0.6)";
        ctx.lineWidth = 1;
        ctx.strokeRect(screenX, screenY, w, h);

        ctx.fillStyle = "rgba(255,255,255,0.85)";
        ctx.font = "12px monospace";
        ctx.fillText(label, screenX + 4, screenY + 14);
    }

    _drawIslandBounds() {
        const ctx = this.ctx;
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
        const scale = this.camera.scale;

        ctx.strokeStyle = "rgba(0, 200, 255, 0.7)";
        ctx.lineWidth = 2;

        for (const island of this.world.islands) {
            const x0 = island.originX;
            const y0 = island.originY;
            const x1 = island.originX + island.width;
            const y1 = island.originY + island.height;

            const corners = [
                { x: x0, y: y0 },
                { x: x1, y: y0 },
                { x: x1, y: y1 },
                { x: x0, y: y1 },
            ].map(pt => {
                const isoX = (pt.x - pt.y) * (tw / 2);
                const isoY = (pt.x + pt.y) * (th / 2) - (th / 2);

                const screenX = (isoX - this.camera.x) * scale + this.canvas.width / 2;
                const screenY = (isoY - this.camera.y) * scale + this.canvas.height / 2;

                return { x: screenX, y: screenY };
            });

            ctx.beginPath();
            ctx.moveTo(corners[0].x, corners[0].y);
            ctx.lineTo(corners[1].x, corners[1].y);
            ctx.lineTo(corners[2].x, corners[2].y);
            ctx.lineTo(corners[3].x, corners[3].y);
            ctx.closePath();
            ctx.stroke();

            ctx.fillStyle = "rgba(0, 200, 255, 0.9)";
            ctx.font = "12px monospace";
            ctx.fillText(island.id || "island", corners[0].x + 4, corners[0].y - 6);
        }
    }

    _updateDebugPanel() {
        const panel = document.getElementById("debug-panel");
        if (!panel) return;

        const chunkCount = this.chunkCache.size;
        const zoom = this.camera.scale.toFixed(3);

        let totalPixels = 0;
        for (const canvas of this.chunkCache.values()) {
            totalPixels += canvas.width * canvas.height;
        }
        const megaPixels = (totalPixels / 1_000_000).toFixed(2);

        panel.textContent =
            `Chunks loaded: ${chunkCount}\n` +
            `Zoom: ${zoom}\n` +
            `Pixels: ${megaPixels} MP`;
    }

    _drawIslandConnections() {
        const ctx = this.ctx;
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
        const scale = this.camera.scale;
    
        ctx.strokeStyle = "rgba(255, 215, 0, 0.9)"; // gold-ish
        ctx.lineWidth = 2;
    
        // Compute screen-space centers for each island
        const centers = this.world.islands.map(island => {
            const centerTileX = island.originX + island.width / 2;
            const centerTileY = island.originY + island.height / 2;
    
            // world → iso
            const isoX = (centerTileX - centerTileY) * (tw / 2);
            const isoY = (centerTileX + centerTileY) * (th / 2);
    
            // iso → screen
            const screenX = (isoX - this.camera.x) * scale + this.canvas.width / 2;
            const screenY = (isoY - this.camera.y) * scale + this.canvas.height / 2;
    
            return { x: screenX, y: screenY };
        });
    
        // Draw lines between every pair of islands
        for (let i = 0; i < centers.length; i++) {
            for (let j = i + 1; j < centers.length; j++) {
                ctx.beginPath();
                ctx.moveTo(centers[i].x, centers[i].y);
                ctx.lineTo(centers[j].x, centers[j].y);
                ctx.stroke();
            }
        }
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

        // Per-island chunk rendering
        for (const island of this.world.islands) {
            for (let cy = 0; cy < island.chunksY; cy++) {
                for (let cx = 0; cx < island.chunksX; cx++) {

                    const chunkCanvas = this._getOrCreateChunkBitmap(island, cx, cy);
                    if (!chunkCanvas) continue;

                    const baseX = island.originX + cx * CHUNK_SIZE;
                    const baseY = island.originY + cy * CHUNK_SIZE;

                    const chunkIsoX = (baseX - baseY) * (tw / 2);
                    const chunkIsoY = (baseX + baseY) * (th / 2);

                    const topLeftIsoX = chunkIsoX - originX;
                    const topLeftIsoY = chunkIsoY - originY;

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

                    if (this.debugDrawChunkBoundaries) {
                        this._drawChunkBoundary(
                            `${island.id}:${cx},${cy}`,
                            screen.x,
                            screen.y,
                            chunkPixelW * this.camera.scale,
                            chunkPixelH * this.camera.scale
                        );
                        this._drawChunkIsoDiamond(island, cx, cy);
                    }
                }
            }
        }

        if (this.debugDrawIslandBounds) {
            this._drawIslandBounds();
        }

        this._drawIslandConnections();
        this._updateDebugPanel();
    }
}
