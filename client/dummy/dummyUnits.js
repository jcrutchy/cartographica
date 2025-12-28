import { dummyWorld } from "./dummyWorld.js";
import { revealTile } from "./revealTile.js";

export const dummyUnits = [
    { id: 1, islandId: 1, x: 0, y: 0, facingAngle: 0, trail: [], visibleRadius: 4 },
    { id: 2, islandId: 2, x: 0, y: 0, facingAngle: 0, trail: [], visibleRadius: 4 },
    { id: 3, islandId: 3, x: 0, y: 0, facingAngle: 0, trail: [], visibleRadius: 4 }
];

export function updateDummyUnits(units) {
    for (const u of units) {
        const island = dummyWorld.islands.find(i => i.id === u.islandId);

        // slow rotation
        u.facingAngle += (Math.random() - 0.5) * 0.03;

        // slow movement
        u.x += Math.cos(u.facingAngle) * 0.02;
        u.y += Math.sin(u.facingAngle) * 0.02;

        // reveal tiles in radius
        for (let dy = -u.visibleRadius; dy <= u.visibleRadius; dy++) {
            for (let dx = -u.visibleRadius; dx <= u.visibleRadius; dx++) {
                const tx = Math.floor(u.x + dx);
                const ty = Math.floor(u.y + dy);
                revealTile(island, tx, ty);
            }
        }

        // trail
        u.trail.push({ x: u.x, y: u.y, islandId: u.islandId });
        if (u.trail.length > 200) u.trail.shift();
    }
}
