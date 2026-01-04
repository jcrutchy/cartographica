export class Renderer {
    constructor(canvas, camera) {
        this.ctx = canvas.getContext("2d");
        this.camera = camera;
        this.tileSize = 32;
    }

    render(world) {
        const ctx = this.ctx;

        ctx.fillStyle = "black";
        ctx.fillRect(0, 0, ctx.canvas.width, ctx.canvas.height);

        // Draw all islands
        for (const island of world.islands) {
            this.drawIsland(island);
        }

        // Draw players
        this.drawPlayers(world.players, world.islands);
    }

    worldToScreen(wx, wy) {
        return {
            sx: wx * this.tileSize * this.camera.zoom - this.camera.x,
            sy: wy * this.tileSize * this.camera.zoom - this.camera.y
        };
    }

    islandToWorld(island, x, y) {
        return {
            wx: island.originX + x,
            wy: island.originY + y
        };
    }

    drawIsland(island) {
        const ctx = this.ctx;
        const size = this.tileSize * this.camera.zoom;

        for (let y = 0; y < island.tilemap.length; y++) {
            for (let x = 0; x < island.tilemap[y].length; x++) {
                const tile = island.tilemap[y][x];
                const { wx, wy } = this.islandToWorld(island, x, y);
                const { sx, sy } = this.worldToScreen(wx, wy);

                ctx.fillStyle = "#228B22";
                ctx.fillRect(sx, sy, size, size);
            }
        }
    }

    drawPlayers(players, islands) {
        const ctx = this.ctx;

        for (const id in players) {
            const p = players[id];

            // Find the island the player is on
            const island = islands.find(i => i.id === p.islandId);
            if (!island) continue;

            const { wx, wy } = this.islandToWorld(island, p.x, p.y);
            const { sx, sy } = this.worldToScreen(wx, wy);

            ctx.fillStyle = "white";
            ctx.beginPath();
            ctx.arc(sx + 16 * this.camera.zoom, sy + 16 * this.camera.zoom, 10 * this.camera.zoom, 0, Math.PI * 2);
            ctx.fill();
        }
    }
}
