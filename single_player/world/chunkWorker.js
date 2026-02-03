let TILE_COLORS = {};

self.onmessage = async (e) => {
    if (e.data.type === "init") {
        TILE_COLORS = e.data.TILE_COLORS;
        return;
    }
    const { islandId, cx, cy, lod, lodTiles, tileData, tw, th } = e.data;
    const canvas = renderChunkBitmap(tileData, lodTiles, tw, th);
    const bitmap = await createImageBitmap(canvas);
    self.postMessage({ islandId, cx, cy, lod, bitmap }, [bitmap]);
};

function renderChunkBitmap(tileData, lodTiles, tw, th) {
    const width = lodTiles * tw;
    const height = lodTiles * th;

    const canvas = new OffscreenCanvas(width, height);
    const ctx = canvas.getContext("2d");

    const originX = width / 2;
    const originY = th / 2;

    let i = 0;
    for (let y = 0; y < lodTiles; y++) {
        for (let x = 0; x < lodTiles; x++) {
            const tile = tileData[i++];
            if (!tile) continue;

            const isoX = originX + (x - y) * (tw / 2);
            const isoY = originY + (x + y) * (th / 2);

            drawTile(ctx, tile, isoX, isoY, tw, th);
        }
    }

    return canvas;
}

function drawTile(ctx, tile, isoCenterX, isoCenterY, w, h) {
    const leftX   = isoCenterX - w / 2;
    const rightX  = isoCenterX + w / 2;
    const topY    = isoCenterY - h / 2;
    const bottomY = isoCenterY + h / 2;

    ctx.fillStyle = TILE_COLORS[tile] || "#ff00ff";

    ctx.beginPath();
    ctx.moveTo(leftX,        isoCenterY);
    ctx.lineTo(isoCenterX,   topY);
    ctx.lineTo(rightX,       isoCenterY);
    ctx.lineTo(isoCenterX,   bottomY);
    ctx.closePath();
    ctx.fill();
}
