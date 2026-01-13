export class Renderer {
    constructor(canvas, camera) {
        this.ctx = canvas.getContext("2d");
        this.ctx.imageSmoothingEnabled = false;
        this.camera = camera;
        this.tileSize = 32;
        this.tilesetCache = new Map();

        // Debug toggle
        this.debugCoastlines = true;
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

    isCoastTile(island, x, y) {
        const cell = island.tilemap[y][x];
        if (!cell || cell.biome === "water") return false;
    
        const north = this.getBiome(island, x, y - 1);
        const south = this.getBiome(island, x, y + 1);
        const west  = this.getBiome(island, x - 1, y);
        const east  = this.getBiome(island, x + 1, y);
    
        return (
            north === "water" ||
            south === "water" ||
            west  === "water" ||
            east  === "water"
        );
    }

    // ---------------------------------------------------------
    // NEW: Helper to get biome safely
    // ---------------------------------------------------------
    getBiome(island, x, y) {
        if (y < 0 || y >= island.tilemap.length) return "water";
        if (x < 0 || x >= island.tilemap[0].length) return "water";
        return island.tilemap[y][x].biome;
    }

    isWater(biome) {
        return biome === "water" || biome === "shore";
    }

    // ---------------------------------------------------------
    // NEW: Draw island with coastline quarter tiles
    // ---------------------------------------------------------
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
            for (let x = 0; x < island.tilemap[y].length; x++) {

                const cell = island.tilemap[y][x];
                const terrain = config.palette_map[cell.tile] ?? config.default_tile;
                const [tx, ty] = config.terrain_index[terrain];

                let wx = island.originX + x * tileW;
                let wy = island.originY + y * terrainH;
                if (y % 2 === 1) wx += tileW / 2;

                const drawX = wx - tileW / 2;
                const drawY = wy - tileH;

                const baseIndex = ty * config.grid_columns + tx;
                const baseTile = tiles[baseIndex];
                if (baseTile) ctx.drawImage(baseTile, drawX, drawY);

                // ---------------------------------------------------------
                // Draw coastline quarter tiles
                // ---------------------------------------------------------
                if (cell.coast) {
                    const coastRow = cell.coast.row;
                    const coastCol = 0;

                    const quarterW = tileW / 2;
                    const quarterH = tileH / 2;

                    const drawQuarter = (corner, index) => {
                        const tileIndex = coastRow * config.grid_columns + (coastCol + index);
                        const tileImg = tiles[tileIndex];
                        if (!tileImg) return;

                        // Source crop
                        const sx = (corner === "tr" || corner === "br") ? quarterW : 0;
                        const sy = (corner === "bl" || corner === "br") ? quarterH : 0;

                        // Destination
                        const dx = drawX + sx;
                        const dy = drawY + sy;

                        ctx.drawImage(
                            tileImg,
                            sx, sy, quarterW, quarterH,
                            dx, dy, quarterW, quarterH
                        );
                    };

                    //drawQuarter("tl", cell.coast.tl);
                    //drawQuarter("tr", cell.coast.tr);
                    //drawQuarter("bl", cell.coast.bl);
                    //drawQuarter("br", cell.coast.br);
                }
            }
        }

        for (let y = 0; y < island.tilemap.length; y++)
        {
            for (let x = 0; x < island.tilemap[y].length; x++)
            {

                const cell = island.tilemap[y][x];
                const terrain = config.palette_map[cell.tile] ?? config.default_tile;
                const [tx, ty] = config.terrain_index[terrain];

                let wx = island.originX + x * tileW;
                let wy = island.originY + y * terrainH;
                if (y % 2 === 1) wx += tileW / 2;

                const drawX = wx - tileW / 2;
                const drawY = wy + tileH;

                const baseIndex = ty * config.grid_columns + tx;
                const baseTile = tiles[baseIndex];

                if (this.debugCoastlines) {
                    const cell = island.tilemap[y][x];
                    if (!cell || cell.biome === "water") {
                        // Skip overlay, but DO NOT return
                    } else {
                        const tileCenterX = wx;
                        const tileCenterY = wy;
                
                        const drawDiag = (dx, dy) => {
                            ctx.beginPath();
                            ctx.moveTo(tileCenterX, tileCenterY);
                            ctx.lineTo(tileCenterX + dx, tileCenterY + dy);
                            ctx.stroke();
                        };
                
                        ctx.strokeStyle = "rgba(255, 0, 0, 0.7)";
                        ctx.lineWidth = 2;

                        const isWater = (biome) => biome === "water" || biome === "shore";

                        if (isWater(this.getBiome(island, x - 1, y - 1))) drawDiag(-tileW / 2, -terrainH); // NW
                        if (isWater(this.getBiome(island, x + 1, y - 1))) drawDiag(+tileW / 2, -terrainH); // NE
                        if (isWater(this.getBiome(island, x - 1, y + 1))) drawDiag(-tileW / 2, +terrainH); // SW
                        if (isWater(this.getBiome(island, x + 1, y + 1))) drawDiag(+tileW / 2, +terrainH); // SE
                    }
                }

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
