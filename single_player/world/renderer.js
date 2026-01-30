import { TILE_WIDTH, TILE_HEIGHT, CHUNK_SIZE, TILE_COLORS } from "./constants.js";

export class Renderer {
    constructor(canvas, camera, world) {
        this.canvas = canvas;
        this.ctx = canvas.getContext("2d");
        this.camera = camera;
        this.world = world;
        
        this.debugDrawChunkBoundaries = true;

        this.chunkCache = new Map();

        let minX = Infinity, minY = Infinity;
        let maxX = -Infinity, maxY = -Infinity;
        
        for (const island of this.world.islands) {
            minX = Math.min(minX, island.originX);
            minY = Math.min(minY, island.originY);
            maxX = Math.max(maxX, island.originX + island.width);
            maxY = Math.max(maxY, island.originY + island.height);
        }
        
        this.minWorldX = minX;
        this.minWorldY = minY;
        this.maxWorldX = maxX;
        this.maxWorldY = maxY;
        
        this.chunksX = Math.ceil((maxX - minX) / CHUNK_SIZE);
        this.chunksY = Math.ceil((maxY - minY) / CHUNK_SIZE);

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

    _renderChunk(cx, cy) {
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;

        // Padding to avoid clipped diamonds
        const PAD_X = tw / 2;
        const PAD_Y = th / 2;

        const chunkPixelW = CHUNK_SIZE * tw + tw;
        const chunkPixelH = CHUNK_SIZE * th + th;

        const canvas = document.createElement("canvas");
        canvas.width = chunkPixelW;
        canvas.height = chunkPixelH;

        const ctx = canvas.getContext("2d");

        // Chunk-local origin (center of tile 0,0 inside chunk)
        const originX = PAD_X + (CHUNK_SIZE - 1) * (tw / 2);
        const originY = PAD_Y;

        const startX = this.minWorldX + cx * CHUNK_SIZE;
        const startY = this.minWorldY + cy * CHUNK_SIZE;

        for (let y = 0; y < CHUNK_SIZE; y++) {
            for (let x = 0; x < CHUNK_SIZE; x++) {
                const worldX = startX + x;
                const worldY = startY + y;

                if (worldY < 0 || worldY >= this.world.height) continue;
                if (worldX < 0 || worldX >= this.world.width) continue;

                const tile = this._getTile(worldX, worldY);
                if (!tile) continue;

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

    _updateDebugPanel() {
        const panel = document.getElementById("debug-panel");
        if (!panel) return;
    
        const chunkCount = this.chunkCache.size;
        const zoom = this.camera.scale.toFixed(3);
    
        // Estimate memory: width * height * 4 bytes per chunk
        let memory = 0;
        for (const canvas of this.chunkCache.values()) {
            memory += canvas.width * canvas.height * 4;
        }
        const memoryMB = (memory / (1024 * 1024)).toFixed(2);
    
        panel.textContent =
            `Chunks loaded: ${chunkCount}\n` +
            `Zoom: ${zoom}\n` +
            `Chunk memory: ${memoryMB} MB`;
    }

    _drawChunkBoundary(cx, cy, screenX, screenY, w, h) {
        const ctx = this.ctx;
    
        // Rectangle outline around the chunk bitmap
        ctx.strokeStyle = "rgba(255, 0, 0, 0.6)";
        ctx.lineWidth = 1;
        ctx.strokeRect(screenX, screenY, w, h);
    
        // Optional: chunk coordinate label
        ctx.fillStyle = "rgba(255,255,255,0.85)";
        ctx.font = "12px monospace";
        ctx.fillText(`(${cx},${cy})`, screenX + 4, screenY + 14);
    }

    _drawIslandConnections() {
        const ctx = this.ctx;
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
        const scale = this.camera.scale;
    
        const centers = this.world.islands.map(island => {
            const centerTileX = island.originX + island.width / 2;
            const centerTileY = island.originY + island.height / 2;
    
            const isoX = (centerTileX - centerTileY) * (tw / 2);
            const isoY = (centerTileX + centerTileY) * (th / 2);
    
            const screenX = (isoX - this.camera.x) * scale + this.canvas.width / 2;
            const screenY = (isoY - this.camera.y) * scale + this.canvas.height / 2;
    
            return { x: screenX, y: screenY };
        });
    
        ctx.strokeStyle = "#f1c40f";
        ctx.lineWidth = 2;
    
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
                if (this.debugDrawChunkBoundaries) {
                    this._drawChunkBoundary(
                        cx,
                        cy,
                        screen.x,
                        screen.y,
                        chunkPixelW * this.camera.scale,
                        chunkPixelH * this.camera.scale
                    );
                }
            }
        }
        
        this._drawIslandConnections();
        this._updateDebugPanel();
    }
}
