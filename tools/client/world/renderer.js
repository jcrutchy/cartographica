export class Renderer {
    constructor(canvas, camera) {
        this.ctx = canvas.getContext("2d");
        this.ctx.imageSmoothingEnabled = false;
        this.camera = camera;
        this.tileSize = 32;
        this.tilesetCache = new Map(); // base64 → { tiles, config }
    }

    render(world) {
        const ctx = this.ctx;

        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.fillStyle = "black";
        ctx.fillRect(0, 0, ctx.canvas.width, ctx.canvas.height);

        ctx.setTransform(
            this.camera.scale, 0,
            0, this.camera.scale,
            ctx.canvas.width / 2 - this.camera.x * this.camera.scale,
            ctx.canvas.height / 2 - this.camera.y * this.camera.scale
        );

        for (const island of world.islands) {
            this.drawIsland(island);
        }

        this.drawPlayers(world.players, world.islands);

        ctx.setTransform(1, 0, 0, 1, 0, 0);
    }

    islandToWorld(island, tx, ty) {
        return {
            wx: island.originX + tx * this.tileSize,
            wy: island.originY + ty * this.tileSize
        };
    }

    async loadTilesetImage(base64, config) {
        if (this.tilesetCache.has(base64)) {
            return this.tilesetCache.get(base64);
        }
    
        const img = await new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = 'data:image/png;base64,' + base64;
        });
    
        const transparent = this.makeTransparent(img, [
            [127, 0, 127],
            [255, 0, 255]
        ]);
    
        // ✅ Wait for transparent image to load
        await new Promise((resolve, reject) => {
            transparent.onload = () => resolve();
            transparent.onerror = reject;
        });
    
        const tiles = this.extractTiles(transparent, config);
        const cached = { tiles, config };
        this.tilesetCache.set(base64, cached);
        return cached;
    }

    makeTransparent(img, colors) {
        const canvas = document.createElement("canvas");
        canvas.width = img.width;
        canvas.height = img.height;

        const ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0);

        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;

        for (let i = 0; i < data.length; i += 4) {
            const r = data[i], g = data[i + 1], b = data[i + 2];
            for (const [tr, tg, tb] of colors) {
                if (r === tr && g === tg && b === tb) {
                    data[i + 3] = 0;
                    break;
                }
            }
        }

        ctx.putImageData(imageData, 0, 0);

        const newImg = new Image();
        newImg.src = canvas.toDataURL();
        return newImg;
    }

    extractTiles(img, config) {
        const tiles = [];
        const tileW = config.tile_size[0];
        const tileH = config.tile_size[1];
        const spacingX = config.tile_padding?.[0] || 1;
        const spacingY = config.tile_padding?.[1] || 1;
        const cols = config.grid_columns;

        const rows = Math.floor((img.height - spacingY) / (tileH + spacingY));

        for (let ty = 0; ty < rows; ty++) {
            for (let tx = 0; tx < cols; tx++) {
                const sx = 1 + tx * (tileW + spacingX);
                const sy = 1 + ty * (tileH + spacingY);

                if (sx + tileW > img.width || sy + tileH > img.height) continue;

                const tileCanvas = document.createElement("canvas");
                tileCanvas.width = tileW;
                tileCanvas.height = tileH;

                const tileCtx = tileCanvas.getContext("2d");
                tileCtx.drawImage(img, sx, sy, tileW, tileH, 0, 0, tileW, tileH);

                tiles.push(tileCanvas);
            }
        }

        return tiles;
    }

    drawIsland(island) {

        const ctx = this.ctx;
        const tileset = island.default_tileset;
        const config = tileset.cfg;

        config.palette_map = Object.fromEntries(
            Object.entries(config.map_palette).map(([name, id]) => [id, name])
        );

        const tileW = config.tile_size[0];
        const tileH = config.tile_size[1];
        const terrainH = config.terrain_height / 2;

        const cached = this.tilesetCache.get(tileset.img);

        if (!cached) return;

        const tiles = cached.tiles;

        for (let y = 0; y < island.tilemap.length; y++) {
            for (let x = 0; x < island.tilemap[y].length; x++)
            {

                const terrainId = island.tilemap[y][x];
                const terrain = config.palette_map?.[terrainId] ?? config.default_tile;
                const [tx, ty] = config.terrain_index[terrain] || config.terrain_index[config.default_tile];

                let wx = island.originX + x * tileW;
                let wy = island.originY + y * terrainH;
                if (y % 2 === 1) wx += tileW / 2;

                const drawX = wx - tileW / 2;
                const drawY = wy - tileH;

                const tileIndex = ty * config.grid_columns + tx;
                const tileImage = tiles[tileIndex];

                if (!tileImage) continue;

                ctx.drawImage(tileImage, drawX, drawY);
            }
        }
    }

    drawPlayers(players, islands) {
        const ctx = this.ctx;

        for (const id in players) {
            const p = players[id];
            const island = islands.find(i => i.id === p.islandId);
            if (!island) continue;

            const { wx, wy } = this.islandToWorld(island, p.x, p.y);

            ctx.fillStyle = "white";
            ctx.beginPath();
            ctx.arc(wx, wy, 10, 0, Math.PI * 2);
            ctx.fill();
        }
    }
}
