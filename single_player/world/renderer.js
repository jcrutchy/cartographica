import { TILE_WIDTH, TILE_HEIGHT, CHUNK_SIZE, TILE_COLORS } from "./constants.js";

export class Renderer {
    constructor(canvas, camera, world) {
        this.canvas = canvas;
        this.ctx = canvas.getContext("2d");
        this.camera = camera;
        this.world = world;
      
        this.debugDrawChunkBoundaries = false;
        this.debugDrawChunkDiamonds = false;
        this.debugDrawIslandBounds = true;
        this.debugDrawGrids = false;

        this.debugChunksRendered = 0;
        this.debugChunksCached = 0;

        this.chunkCache = new Map();

        this.totalChunks = 0;
        // Precompute per-island chunk grids
        for (const island of this.world.islands) {
            island.chunksX = Math.ceil(island.width  / CHUNK_SIZE);
            island.chunksY = Math.ceil(island.height / CHUNK_SIZE);
            this.totalChunks += island.chunksX * island.chunksY;
        }

        this._setupResize();
        this._setupMouseMove();
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

    _setupMouseMove() {
        const mouseMove = (e) => {
            const rect = this.canvas.getBoundingClientRect();
            const sx = e.clientX - rect.left;
            const sy = e.clientY - rect.top;
    
            const world = this.screenToWorldTile(sx, sy);
    
            this.hoverTile = {
                x: Math.round(world.x),
                y: Math.round(world.y)
            };
        };
    
        window.addEventListener("mousemove", mouseMove);
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

    _getOrCreateChunkBitmap(island, cx, cy) {
        const key = `${island.id}:${cx},${cy}`;
        let entry = this.chunkCache.get(key);

        if (entry) {
            return entry.canvas;
        }
    
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
    
        const width = CHUNK_SIZE * tw;
        const height = CHUNK_SIZE * th;
    
        const canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
    
        const ctx = canvas.getContext("2d");
    
        const originX = width / 2;
        const originY = th / 2;
    
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
        this.chunkCache.set(key, {
            canvas,
            lastUsed: 0,
            screenX: 0,
            screenY: 0,
            screenW: canvas.width,
            screenH: canvas.height
        });
        return canvas;
    }

    _evictChunks() {
        const cache = this.chunkCache;
    
        const now = performance.now();
        const gracePeriod = 10000; // 10 seconds
        const maxEvictionsPerFrame = 1;
    
        // Proximity factor: how many screen-widths/heights define the "safe zone"
        const nrProxFact = 3;
    
        const entries = [...cache.entries()].map(([key, entry]) => ({
            key,
            entry,
            age: now - entry.lastUsed
        }));
    
        const candidates = entries.filter(e => {
    
            const { entry } = e;
    
            // --- 1. Never evict visible chunks ---
            const visible = this._rectsIntersect(
                entry.screenX, entry.screenY, entry.screenW, entry.screenH,
                0, 0, this.canvas.width, this.canvas.height
            );
            if (visible) return false;
    
            // --- 2. Proximity protection for ALL chunks (never-rendered or rendered) ---
            // Define a large "nearby" region around the viewport
            const near = this._rectsIntersect(
                entry.screenX, entry.screenY, entry.screenW, entry.screenH,
                -this.canvas.width * nrProxFact,
                -this.canvas.height * nrProxFact,
                this.canvas.width * (nrProxFact * 2 + 1),
                this.canvas.height * (nrProxFact * 2 + 1)
            );
            if (near) return false;
    
            // --- 3. Never-rendered chunks: allow eviction only if far away ---
            if (entry.lastUsed === 0) {
                // If it's far (not near), it's allowed to be evicted
                return true;
            }
    
            // --- 4. Must exceed grace period ---
            if (e.age <= gracePeriod) return false;
    
            // --- 5. Eligible for eviction ---
            return true;
        });
    
        // Oldest first
        candidates.sort((a, b) => a.age - b.age);
    
        // Evict gently
        const count = Math.min(maxEvictionsPerFrame, candidates.length);
        for (let i = 0; i < count; i++) {
            cache.delete(candidates[i].key);
        }
    
        this.debugChunksCached = cache.size;
    }

    screenToWorldTile(sx, sy) {
        const scale = this.camera.scale;
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
    
        const screenW = this.canvas.width;
        const screenH = this.canvas.height;
    
        // Convert screen → iso space
        const isoX = (sx - screenW / 2) / scale + this.camera.x;
        const isoY = (sy - screenH / 2) / scale + this.camera.y;
    
        // Convert iso → world tile coordinates
        const worldX = (isoY / th + isoX / tw);
        const worldY = (isoY / th - isoX / tw);
    
        return { x: worldX, y: worldY };
    }

    _renderChunk(island, cx, cy) {
        const scale = this.camera.scale;
        const ctx = this.ctx;
    
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
    
        // World-space chunk origin (tile 0,0 of the chunk)
        const baseX = island.originX + cx * CHUNK_SIZE;
        const baseY = island.originY + cy * CHUNK_SIZE;
    
        // TILE CENTER iso transform
        const isoX = (baseX - baseY) * (tw / 2);
        const isoY = (baseX + baseY) * (th / 2);
    
        // Precomputed chunk pixel footprint
        const CHUNK_PIXEL_WIDTH  = CHUNK_SIZE * tw;
        const CHUNK_PIXEL_HEIGHT = CHUNK_SIZE * th;
    
        // Inside the bitmap, tile (0,0) CENTER is at (width/2, th/2)
        const originX = CHUNK_PIXEL_WIDTH / 2;
        const originY = th / 2;
    
        // Screen-space placement
        const screenX =
            (isoX - this.camera.x) * scale +
            this.canvas.width / 2 -
            originX * scale;
    
        const screenY =
            (isoY - this.camera.y) * scale +
            this.canvas.height / 2 -
            originY * scale;
    
        const screenW = CHUNK_PIXEL_WIDTH * scale;
        const screenH = CHUNK_PIXEL_HEIGHT * scale;
    
        // Update rect in cache entry if it exists
        const key = `${island.id}:${cx},${cy}`;
        const entry = this.chunkCache.get(key);
        if (entry) {
            entry.screenX = screenX;
            entry.screenY = screenY;
            entry.screenW = screenW;
            entry.screenH = screenH;
        }

        // CULL BEFORE CREATING BITMAP
        const warmProxFactor = 3;
        if (!this._rectsIntersect(screenX, screenY, screenW, screenH,
                                  0, 0, this.canvas.width, this.canvas.height)) {
            // Warm nearby chunks
            const warm = this._rectsIntersect(
                screenX, screenY, screenW, screenH,
                -this.canvas.width * warmProxFactor,
                -this.canvas.height * warmProxFactor,
                this.canvas.width * (warmProxFactor * 2 + 1),
                this.canvas.height * (warmProxFactor * 2 + 1)
            );
            if (!warm) {
                return;
            }
        }
    
        // Only now create or fetch the bitmap
        const chunkCanvas = this._getOrCreateChunkBitmap(island, cx, cy);

        if (!this._rectsIntersect(
            screenX, screenY, screenW, screenH,
            0, 0, this.canvas.width, this.canvas.height
        )) {
            return;
        }

        // Update lastUsed now that we know it's visible
        const realEntry = this.chunkCache.get(key);
        if (realEntry) {
            realEntry.lastUsed = performance.now();
        }

        this.debugChunksRendered++;
    
        // Draw chunk
        ctx.drawImage(
            chunkCanvas,
            0, 0, chunkCanvas.width, chunkCanvas.height,
            screenX, screenY,
            chunkCanvas.width * scale,
            chunkCanvas.height * scale
        );
    
        // Debug overlays
        if (this.debugDrawChunkBoundaries) {
            this._debugDrawChunkBoundary(
                key,
                screenX,
                screenY,
                chunkCanvas.width * scale,
                chunkCanvas.height * scale
            );
        }
    
        if (this.debugDrawChunkDiamonds) {
            this._debugDrawChunkDiamondBoundary(island, cx, cy);
        }
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

    _debugDrawChunkDiamondBoundary(island, cx, cy) {
        const ctx = this.ctx;
        const scale = this.camera.scale;
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
    
        // Chunk world origin (tile 0,0 of this chunk)
        const baseX = island.originX + cx * CHUNK_SIZE;
        const baseY = island.originY + cy * CHUNK_SIZE;
    
        // Four corners of the chunk in tile space
        const TL = { x: baseX,                 y: baseY };
        const TR = { x: baseX + CHUNK_SIZE,    y: baseY };
        const BR = { x: baseX + CHUNK_SIZE,    y: baseY + CHUNK_SIZE };
        const BL = { x: baseX,                 y: baseY + CHUNK_SIZE };
    
        // Convert tile → iso → screen
        const toScreen = (tx, ty) => {
            const isoX = (tx - ty) * (tw / 2);
            const isoY = (tx + ty) * (th / 2) - (th / 2);
    
            return {
                x: (isoX - this.camera.x) * scale + this.canvas.width / 2,
                y: (isoY - this.camera.y) * scale + this.canvas.height / 2
            };
        };
    
        const pTL = toScreen(TL.x, TL.y);
        const pTR = toScreen(TR.x, TR.y);
        const pBR = toScreen(BR.x, BR.y);
        const pBL = toScreen(BL.x, BL.y);
    
        ctx.strokeStyle = "rgba(0, 255, 0, 0.5)"; // green
        ctx.lineWidth = 2;
    
        ctx.beginPath();
        ctx.moveTo(pTL.x, pTL.y);
        ctx.lineTo(pTR.x, pTR.y);
        ctx.lineTo(pBR.x, pBR.y);
        ctx.lineTo(pBL.x, pBL.y);
        ctx.closePath();
        ctx.stroke();
    }

    _debugDrawChunkBoundary(label, screenX, screenY, w, h) {
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
                // Canonical tile-corner iso transform
                const isoX = (pt.x - pt.y) * (tw / 2);
                const isoY = (pt.x + pt.y) * (th / 2) - (th / 2);
    
                return {
                    x: (isoX - this.camera.x) * scale + this.canvas.width / 2,
                    y: (isoY - this.camera.y) * scale + this.canvas.height / 2
                };
            });
    
            ctx.beginPath();
            ctx.moveTo(corners[0].x, corners[0].y);
            ctx.lineTo(corners[1].x, corners[1].y);
            ctx.lineTo(corners[2].x, corners[2].y);
            ctx.lineTo(corners[3].x, corners[3].y);
            ctx.closePath();
            ctx.stroke();

            ctx.fillStyle = "rgba(0, 200, 255, 0.7)";
            ctx.font = "14px monospace";
            ctx.fillText(`island:${island.id}`, corners[0].x + 4, corners[0].y - 3);
        }
    }

    _drawHoverDiamond(tx, ty) {
        const ctx = this.ctx;
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
        const scale = this.camera.scale;
    
        // Tile center in iso space
        const isoCenterX = (tx - ty) * (tw / 2);
        const isoCenterY = (tx + ty) * (th / 2);
    
        // Tile corners
        const leftX   = isoCenterX - tw / 2;
        const rightX  = isoCenterX + tw / 2;
        const topY    = isoCenterY - th / 2;
        const bottomY = isoCenterY + th / 2;
    
        // Convert to screen
        const sx = x => (x - this.camera.x) * scale + this.canvas.width / 2;
        const sy = y => (y - this.camera.y) * scale + this.canvas.height / 2;
    
        ctx.strokeStyle = "rgba(255, 210, 100, 0.8)";
        ctx.lineWidth = 3;
        ctx.shadowBlur = 8;
        ctx.shadowColor = "rgba(255, 255, 255, 0.8)";
    
        ctx.beginPath();
        ctx.moveTo(sx(leftX),        sy(isoCenterY));
        ctx.lineTo(sx(isoCenterX),   sy(topY));
        ctx.lineTo(sx(rightX),       sy(isoCenterY));
        ctx.lineTo(sx(isoCenterX),   sy(bottomY));
        ctx.closePath();
        ctx.stroke();
        ctx.shadowBlur = 0;
        ctx.shadowColor = "transparent";
    }

    _debugDrawIsoGrid() {
        const ctx = this.ctx;
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
        const scale = this.camera.scale;
    
        const screenW = this.canvas.width;
        const screenH = this.canvas.height;
    
        // Convert screen → world tile space
        const toWorld = (sx, sy) => {
            const isoX = (sx - screenW / 2) / scale + this.camera.x;
            const isoY = (sy - screenH / 2) / scale + this.camera.y;
    
            const x = (isoY / th + isoX / tw);
            const y = (isoY / th - isoX / tw);
            return { x, y };
        };
    
        const tl = toWorld(0, 0);
        const br = toWorld(screenW, screenH);
    
        const minX = Math.floor(Math.min(tl.x, br.x)) - 2;
        const maxX = Math.ceil(Math.max(tl.x, br.x)) + 2;
        const minY = Math.floor(Math.min(tl.y, br.y)) - 2;
        const maxY = Math.ceil(Math.max(tl.y, br.y)) + 2;
    
        ctx.strokeStyle = "rgba(0, 200, 0, 0.20)";
        ctx.lineWidth = 1;
    
        // Draw a diamond for each tile
        for (let ty = minY; ty <= maxY; ty++) {
            for (let tx = minX; tx <= maxX; tx++) {
    
                // Tile center in iso space
                const isoCenterX = (tx - ty) * (tw / 2);
                const isoCenterY = (tx + ty) * (th / 2);
    
                // Tile corners
                const leftX   = isoCenterX - tw / 2;
                const rightX  = isoCenterX + tw / 2;
                const topY    = isoCenterY - th / 2;
                const bottomY = isoCenterY + th / 2;
    
                // Convert to screen
                const sx = x => (x - this.camera.x) * scale + screenW / 2;
                const sy = y => (y - this.camera.y) * scale + screenH / 2;
    
                ctx.beginPath();
                ctx.moveTo(sx(leftX),        sy(isoCenterY));
                ctx.lineTo(sx(isoCenterX),   sy(topY));
                ctx.lineTo(sx(rightX),       sy(isoCenterY));
                ctx.lineTo(sx(isoCenterX),   sy(bottomY));
                ctx.closePath();
                ctx.stroke();
            }
        }
    }

    _rectsIntersect(ax, ay, aw, ah, bx, by, bw, bh) {
        return (
            ax < bx + bw &&
            ax + aw > bx &&
            ay < by + bh &&
            ay + ah > by
        );
    }

    _updateDebugPanel() {
        const panel = document.getElementById("debug-panel");
        if (!panel) return;

        const zoom = this.camera.scale.toFixed(3);

        let totalPixels = 0;
        for (const entry of this.chunkCache.values())
        {
            const c = entry.canvas;
            if (!c) continue; // safety
            totalPixels += c.width * c.height;
        }
        const megaPixels = (totalPixels / 1_000_000).toFixed(2);

        panel.innerHTML =
            `Chunks cached: ${this.chunkCache.size}<br>` +
            `Chunks rendered: ${this.debugChunksRendered}<br>` +
            `Total chunks: ${this.totalChunks}<br>` +
            `Zoom: ${zoom}<br>` +
            `Pixels: ${megaPixels} MP`;
    }

    _getIslandCenterIso(island) {
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
    
        const centerX = island.originX + island.width / 2;
        const centerY = island.originY + island.height / 2;
    
        const isoX = (centerX - centerY) * (tw / 2);
        const isoY = (centerX + centerY) * (th / 2);
    
        return { isoX, isoY };
    }

    _drawIslandConnections() {
        const ctx = this.ctx;
        const scale = this.camera.scale;
    
        ctx.lineWidth = 1;
        ctx.strokeStyle = "rgba(120, 80, 160, 0.6)";   // dark purple
        ctx.shadowBlur = 8;
        ctx.shadowColor = "rgba(120, 80, 160, 0.8)";
    
        for (const island of this.world.islands) {
            const a = this._getIslandCenterIso(island);
    
            for (const targetId of island.connections || []) {
                const target = this.world.islands.find(i => i.id === targetId);
                if (!target) continue;
    
                const b = this._getIslandCenterIso(target);
    
                // Convert to screen
                const ax = (a.isoX - this.camera.x) * scale + this.canvas.width / 2;
                const ay = (a.isoY - this.camera.y) * scale + this.canvas.height / 2;
                const bx = (b.isoX - this.camera.x) * scale + this.canvas.width / 2;
                const by = (b.isoY - this.camera.y) * scale + this.canvas.height / 2;
    
                ctx.beginPath();
                ctx.moveTo(ax, ay);
                ctx.lineTo(bx, by);
                ctx.stroke();
            }
        }
    
        ctx.shadowBlur = 0;
        ctx.shadowColor = "transparent";
    }

    draw()
    {
        this.debugChunksRendered = 0;
        const ctx = this.ctx;
        ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        //var exit_loops = false;
        for (const island of this.world.islands)
        {
            for (let cy = 0; cy < island.chunksY; cy++)
            {
                for (let cx = 0; cx < island.chunksX; cx++)
                {
                    this._renderChunk(island, cx, cy);
                    //exit_loops = true; break;
                }
                //if (exit_loops) break;
            }
            //if (exit_loops) break;
        }
        if (this.debugDrawIslandBounds) {
            this._drawIslandBounds();
        }
        if (this.debugDrawGrids) {
            this._debugDrawIsoGrid();
        }
        this._drawIslandConnections();

        if (this.hoverTile) {
            this._drawHoverDiamond(this.hoverTile.x, this.hoverTile.y);
        }
        this._evictChunks();
        this._updateDebugPanel();
    }
}
