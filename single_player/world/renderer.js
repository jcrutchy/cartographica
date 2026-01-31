import { TILE_WIDTH, TILE_HEIGHT, CHUNK_SIZE, TILE_COLORS } from "./constants.js";

export class Renderer {
    constructor(canvas, camera, world) {
        this.canvas = canvas;
        this.ctx = canvas.getContext("2d");
        this.camera = camera;
        this.world = world;
      
        this.debugDrawChunkBoundaries = true;
        this.debugDrawChunkDiamonds = true;
        this.debugDrawChunkDiamonds = true;
        this.debugDrawIslandBounds = true;
        this.debugDrawGrids = false;

        this.chunkCache = new Map();

        // Precompute per-island chunk grids
        for (const island of this.world.islands) {
            island.chunksX = Math.ceil(island.width  / CHUNK_SIZE);
            island.chunksY = Math.ceil(island.height / CHUNK_SIZE);
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
        let canvas = this.chunkCache.get(key);
        if (canvas) return canvas;
    
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
    
        // For now: simple, explicit size – one tile grid worth.
        // (We can tighten this later once everything lines up.)
        const width = CHUNK_SIZE * tw;
        const height = CHUNK_SIZE * th;
    
        canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
    
        const ctx = canvas.getContext("2d");
    
        // Choose an origin inside the chunk bitmap.
        // For now: top-center-ish so we can see the whole diamond.
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
    
        this.chunkCache.set(key, canvas);
        return canvas;
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
        const chunkCanvas = this._getOrCreateChunkBitmap(island, cx, cy);
        const scale = this.camera.scale;
        const ctx = this.ctx;
    
        const tw = TILE_WIDTH;
        const th = TILE_HEIGHT;
    
        // World-space chunk origin (tile 0,0 of the chunk)
        const baseX = island.originX + cx * CHUNK_SIZE;
        const baseY = island.originY + cy * CHUNK_SIZE;
    
        // Use TILE CENTER iso transform
        const isoX = (baseX - baseY) * (tw / 2);
        const isoY = (baseX + baseY) * (th / 2);
    
        // Inside the bitmap, tile (0,0) CENTER is at (width/2, th/2)
        const originX = chunkCanvas.width / 2;
        const originY = th / 2;
    
        const screenX =
            (isoX - this.camera.x) * scale +
            this.canvas.width / 2 -
            originX * scale;
    
        const screenY =
            (isoY - this.camera.y) * scale +
            this.canvas.height / 2 -
            originY * scale;
    
        ctx.drawImage(
            chunkCanvas,
            0, 0, chunkCanvas.width, chunkCanvas.height,
            screenX, screenY,
            chunkCanvas.width * scale,
            chunkCanvas.height * scale
        );
    
        if (this.debugDrawChunkBoundaries) {
            this._debugDrawChunkBoundary(
                `${island.id}:${cx},${cy}`,
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
    
        ctx.strokeStyle = "rgba(255, 230, 120, 0.8)";
        ctx.lineWidth = 2;
    
        ctx.beginPath();
        ctx.moveTo(sx(leftX),        sy(isoCenterY));
        ctx.lineTo(sx(isoCenterX),   sy(topY));
        ctx.lineTo(sx(rightX),       sy(isoCenterY));
        ctx.lineTo(sx(isoCenterX),   sy(bottomY));
        ctx.closePath();
        ctx.stroke();
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

        panel.innerHTML =
            `Chunks loaded: ${chunkCount}<br>` +
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

        this._updateDebugPanel();
    }
}
