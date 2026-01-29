export function generateIsland() {
    const width = 256;
    const height = 256;

    const tiles = new Array(height);

    for (let y = 0; y < height; y++) {
        tiles[y] = new Array(width);

        for (let x = 0; x < width; x++) {
            const isLand = (x % 8 === 0) || (y % 8 === 0);
            tiles[y][x] = isLand ? "land" : "water";
        }
    }

    return { width, height, tiles };
}
