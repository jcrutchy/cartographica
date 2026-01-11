export class Renderer
{

    constructor(canvas, camera)
    {
        this.ctx = canvas.getContext("2d");
        this.camera = camera;
        this.tileSize = 32; // world units per tile
    }

    render(world)
    {
        const ctx = this.ctx;

        ctx.fillStyle = "black";
        ctx.fillRect(0, 0, ctx.canvas.width, ctx.canvas.height);

        for (const island of world.islands) {
            this.drawIsland(island);
        }

        this.drawPlayers(world.players, world.islands);
    }

    // WORLD → SCREEN (center-origin)
    worldToScreen(wx, wy) {
        return {
            x: (wx - this.camera.x) / this.camera.scale + this.ctx.canvas.width / 2,
            y: (wy - this.camera.y) / this.camera.scale + this.ctx.canvas.height / 2
        };
    }

    islandToWorld(island, tx, ty) {
        return {
            wx: island.originX + tx * this.tileSize,
            wy: island.originY + ty * this.tileSize
        };
    }

    loadTileset(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = src;
        });
    }

    loadTilesetImage(base64)
    {
        const img = new Image();
        img.src = 'data:image/png;base64,' + base64;
        return img;
    }

    drawIsland(island) // gets called every update. needs to be efficient
    {
        console.log(island);
        const ctx = this.ctx;
        const default_tileset = island.default_tileset;
        const config = default_tileset.cfg;
        console.log(default_tileset);
        const tileSize = config.tile_size; // [96, 72]
        const padding = config.tile_padding || [0, 0];
        const columns = config.grid_columns;

        const img= this.loadTilesetImage(default_tileset.img);
        console.log(img);
    
        for (let y = 0; y < island.tilemap.length; y++) {
            for (let x = 0; x < island.tilemap[y].length; x++) {
                const terrain = island.tilemap[y][x];
                const [tx, ty] = config.terrain_index[terrain] || config.terrain_index[config.default_tile];
    
                const { wx, wy } = this.islandToWorld(island, x, y);
                const { x: sx, y: sy } = this.worldToScreen(wx, wy);
    
                const sxTile = tx * (tileSize[0] + padding[0]);
                const syTile = ty * (tileSize[1] + padding[1]);
    
                ctx.drawImage(
                    img,
                    sxTile, syTile,
                    tileSize[0], tileSize[1],
                    sx, sy - (tileSize[1] - config.terrain_height), // align to bottom
                    tileSize[0], tileSize[1]
                );
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
            const { x: sx, y: sy } = this.worldToScreen(wx, wy);

            ctx.fillStyle = "white";
            ctx.beginPath();
            ctx.arc(sx, sy, 10 / this.camera.scale, 0, Math.PI * 2);
            ctx.fill();
        }
    }

}
