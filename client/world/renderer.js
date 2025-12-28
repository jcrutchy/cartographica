export class Renderer {
    constructor(canvas, camera) {
        this.ctx = canvas.getContext("2d");
        this.camera = camera;

        this.showGrid = true;
        this.showAxes = true;
        this.showUnitTrails = true;
        this.showBoundingBox = true;
        this.showConnections = true;
    }

    render(world, units) {
        const ctx = this.ctx;

        // black empty space
        ctx.fillStyle = "black";
        ctx.fillRect(0, 0, ctx.canvas.width, ctx.canvas.height);

        // draw islands
        for (const island of world.islands) {
            this.drawIsland(island);
        }

        // draw connections
        if (this.showConnections) {
            this.drawConnections(world);
        }

        // draw units
        this.drawUnits(units, world);
    }

    worldToScreen(wx, wy) {
        return {
            sx: wx * 32 * this.camera.zoom - this.camera.x,
            sy: wy * 32 * this.camera.zoom - this.camera.y
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
        const size = 32 * this.camera.zoom;

        // draw each tile in sparse map
        for (const [key, tile] of island.tiles) {
            const [x, y] = key.split(",").map(Number);
            const { wx, wy } = this.islandToWorld(island, x, y);
            const { sx, sy } = this.worldToScreen(wx, wy);

            ctx.fillStyle = tile.visible ? "#00aa00" :
                            tile.explored ? "#004400" : "#000000";

            ctx.fillRect(sx, sy, size, size);
        }

        if (this.showGrid) this.drawGrid(island);
        if (this.showAxes) this.drawAxes(island);
        if (this.showBoundingBox) this.drawBoundingBox(island);
    }

    drawGrid(island) {
        const ctx = this.ctx;
        const size = 32 * this.camera.zoom;

        ctx.strokeStyle = "rgba(255,255,255,0.1)";
        ctx.lineWidth = 1;

        for (let y = island.minY; y <= island.maxY + 1; y++) {
            const { wx, wy } = this.islandToWorld(island, island.minX, y);
            const { sx, sy } = this.worldToScreen(wx, wy);
            ctx.beginPath();
            ctx.moveTo(sx, sy);
            ctx.lineTo(sx + (island.maxX - island.minX + 1) * size, sy);
            ctx.stroke();
        }

        for (let x = island.minX; x <= island.maxX + 1; x++) {
            const { wx, wy } = this.islandToWorld(island, x, island.minY);
            const { sx, sy } = this.worldToScreen(wx, wy);
            ctx.beginPath();
            ctx.moveTo(sx, sy);
            ctx.lineTo(sx, sy + (island.maxY - island.minY + 1) * size);
            ctx.stroke();
        }
    }

    drawAxes(island) {
        const ctx = this.ctx;
        const origin = this.worldToScreen(island.originX, island.originY);

        ctx.strokeStyle = "red";
        ctx.beginPath();
        ctx.moveTo(origin.sx, origin.sy);
        ctx.lineTo(origin.sx + 200, origin.sy);
        ctx.stroke();

        ctx.strokeStyle = "blue";
        ctx.beginPath();
        ctx.moveTo(origin.sx, origin.sy);
        ctx.lineTo(origin.sx, origin.sy + 200);
        ctx.stroke();
    }

    drawBoundingBox(island) {
        const ctx = this.ctx;

        const topLeft = this.worldToScreen(
            island.originX + island.minX,
            island.originY + island.minY
        );

        const bottomRight = this.worldToScreen(
            island.originX + island.maxX + 1,
            island.originY + island.maxY + 1
        );

        ctx.strokeStyle = "magenta";
        ctx.lineWidth = 2;
        ctx.strokeRect(
            topLeft.sx,
            topLeft.sy,
            bottomRight.sx - topLeft.sx,
            bottomRight.sy - topLeft.sy
        );
    }

    drawConnections(world) {
        const ctx = this.ctx;
        ctx.strokeStyle = "cyan";
        ctx.lineWidth = 2;

        for (const c of world.connections) {
            const a = world.islands.find(i => i.id === c.from);
            const b = world.islands.find(i => i.id === c.to);

            const A = this.worldToScreen(a.originX, a.originY);
            const B = this.worldToScreen(b.originX, b.originY);

            ctx.beginPath();
            ctx.moveTo(A.sx, A.sy);
            ctx.lineTo(B.sx, B.sy);
            ctx.stroke();
        }
    }

    drawUnits(units, world) {
        const ctx = this.ctx;

        for (const u of units) {
            const island = world.islands.find(i => i.id === u.islandId);
            const { wx, wy } = this.islandToWorld(island, u.x, u.y);
            const { sx, sy } = this.worldToScreen(wx, wy);

            // trail
            if (this.showUnitTrails) {
                ctx.strokeStyle = "yellow";
                ctx.beginPath();
                for (let i = 0; i < u.trail.length - 1; i++) {
                    const p = u.trail[i];
                    const island2 = world.islands.find(i => i.id === p.islandId);
                    const { wx, wy } = this.islandToWorld(island2, p.x, p.y);
                    const { sx: sx2, sy: sy2 } = this.worldToScreen(wx, wy);

                    const p2 = u.trail[i + 1];
                    const island3 = world.islands.find(i => i.id === p2.islandId);
                    const { wx: wx3, wy: wy3 } = this.islandToWorld(island3, p2.x, p2.y);
                    const { sx: sx3, sy: sy3 } = this.worldToScreen(wx3, wy3);

                    ctx.moveTo(sx2, sy2);
                    ctx.lineTo(sx3, sy3);
                }
                ctx.stroke();
            }

            // unit body
            ctx.fillStyle = "white";
            ctx.beginPath();
            ctx.arc(sx, sy, 6 * this.camera.zoom, 0, Math.PI * 2);
            ctx.fill();

            // facing direction
            const dx = Math.cos(u.facingAngle) * 12 * this.camera.zoom;
            const dy = Math.sin(u.facingAngle) * 12 * this.camera.zoom;

            ctx.strokeStyle = "red";
            ctx.beginPath();
            ctx.moveTo(sx, sy);
            ctx.lineTo(sx + dx, sy + dy);
            ctx.stroke();
        }
    }
}
