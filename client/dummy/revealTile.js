export function revealTile(island, tx, ty) {
    const key = `${tx},${ty}`;

    if (!island.tiles.has(key)) {
        island.tiles.set(key, {
            explored: true,
            visible: true,
            color: "#00aa00"
        });

        // update bounding box
        island.minX = Math.min(island.minX, tx);
        island.maxX = Math.max(island.maxX, tx);
        island.minY = Math.min(island.minY, ty);
        island.maxY = Math.max(island.maxY, ty);
    } else {
        const tile = island.tiles.get(key);
        tile.explored = true;
        tile.visible = true;
    }
}
