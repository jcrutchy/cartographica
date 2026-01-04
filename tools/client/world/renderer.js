export class Renderer {
    constructor(canvas, camera) {
        this.ctx = canvas.getContext("2d");
        this.camera = camera;
        this.tileSize = 32; // world units per tile
    }

    render(world) {
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

    drawIsland(island) {
        const ctx = this.ctx;
        const size = this.tileSize / this.camera.scale;

        for (let y = 0; y < island.tilemap.length; y++) {
            for (let x = 0; x < island.tilemap[y].length; x++) {
                const { wx, wy } = this.islandToWorld(island, x, y);
                const { x: sx, y: sy } = this.worldToScreen(wx, wy);

                ctx.fillStyle = "#228B22";
                ctx.fillRect(sx, sy, size, size);
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
