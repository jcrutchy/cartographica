
import { worldData } from "./worldData.js";

export function generateWorld()
{
    for (let i = 0; i < worldData.islands.length; i++)
    {
        worldData.islands[i] = generateIsland(worldData.islands[i]);
    }
    return worldData;
}

export function generateIsland(island)
{
    let width = island.width;
    let height = island.height;
    // ------------------------------------------------------------
    // Base arrays
    // ------------------------------------------------------------
    let elevation = make2D(width, height, 0);
    let moisture  = make2D(width, height, 0);
    let tiles     = make2D(width, height, "water");

    // ------------------------------------------------------------
    // 1. Elevation seeds (continents + mountains)
    // ------------------------------------------------------------
    const seeds = [];
    const seedCount = 10;

    for (let i = 0; i < seedCount; i++) {
        seeds.push({
            x: Math.floor(Math.random() * width),
            y: Math.floor(Math.random() * height),
            radius: 30 + Math.random() * 60,
            height: 0.6 + Math.random() * 0.4 // mountains vs plains
        });
    }

    // Compute elevation influence
    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            let e = 0;

            for (const s of seeds) {
                const dx = x - s.x;
                const dy = y - s.y;
                const d = Math.sqrt(dx*dx + dy*dy);

                const v = Math.max(0, 1 - d / s.radius);
                e += v * s.height;
            }

            elevation[y][x] = e;
        }
    }

    // ------------------------------------------------------------
    // 2. Moisture gradient (simple but effective)
    // ------------------------------------------------------------
    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            // left side wet, right side dry
            moisture[y][x] = 1 - x / width;
        }
    }

    // ------------------------------------------------------------
    // 3. Initial biome classification
    // ------------------------------------------------------------
    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            const e = elevation[y][x];
            const m = moisture[y][x];

            if (e < 0.25) {
                tiles[y][x] = "water";
            } else if (e < 0.28) {
                tiles[y][x] = "beach";
            } else if (e < 0.45) {
                tiles[y][x] = m < 0.3 ? "desert" : "grass";
            } else if (e < 0.65) {
                tiles[y][x] = m < 0.4 ? "dry_forest" : "forest";
            } else if (e < 0.8) {
                tiles[y][x] = "hill";
            } else {
                tiles[y][x] = "mountain";
            }
        }
    }

    // ------------------------------------------------------------
    // 4. Smooth coastlines
    // ------------------------------------------------------------
    tiles = smoothTiles(tiles, width, height);
    tiles = smoothTiles(tiles, width, height);

    // ------------------------------------------------------------
    // 5. Carve rivers from high elevation
    // ------------------------------------------------------------
    carveRivers(tiles, elevation, width, height);

    island.tiles = tiles;

    return island;
}

// ------------------------------------------------------------
// Helpers
// ------------------------------------------------------------
function make2D(w, h, val) {
    return Array.from({ length: h }, () => Array(w).fill(val));
}

// Simple smoothing pass
function smoothTiles(tiles, width, height) {
    const out = tiles.map(r => r.slice());

    for (let y = 1; y < height - 1; y++) {
        for (let x = 1; x < width - 1; x++) {
            let land = 0;

            for (let dy = -1; dy <= 1; dy++) {
                for (let dx = -1; dx <= 1; dx++) {
                    if (tiles[y+dy][x+dx] !== "water") land++;
                }
            }

            if (land >= 5 && tiles[y][x] === "water") out[y][x] = "grass";
            if (land <= 3 && tiles[y][x] !== "water") out[y][x] = "water";
        }
    }

    return out;
}

// River carving
function carveRivers(tiles, elevation, width, height) {
    const riverStarts = [];

    // pick high elevation points
    for (let i = 0; i < 20; i++) {
        riverStarts.push({
            x: Math.floor(Math.random() * width),
            y: Math.floor(Math.random() * height)
        });
    }

    for (const start of riverStarts) {
        let x = start.x;
        let y = start.y;

        for (let steps = 0; steps < 200; steps++) {
            tiles[y][x] = "river";

            // find lowest neighbor
            let bestX = x;
            let bestY = y;
            let bestE = elevation[y][x];

            for (let dy = -1; dy <= 1; dy++) {
                for (let dx = -1; dx <= 1; dx++) {
                    const nx = x + dx;
                    const ny = y + dy;

                    if (nx < 0 || ny < 0 || nx >= width || ny >= height) continue;

                    if (elevation[ny][nx] < bestE) {
                        bestE = elevation[ny][nx];
                        bestX = nx;
                        bestY = ny;
                    }
                }
            }

            // stop if no downhill path
            if (bestX === x && bestY === y) break;

            x = bestX;
            y = bestY;

            // stop if we hit water
            if (tiles[y][x] === "water") break;
        }
    }
}
