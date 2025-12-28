function makeIsland(id, originX, originY) {
    const island = {
        id,
        originX,
        originY,

        // sparse tile storage
        tiles: new Map(),

        // bounding box
        minX: 0,
        maxX: 0,
        minY: 0,
        maxY: 0
    };

    // initial tile at (0,0)
    island.tiles.set("0,0", {
        explored: true,
        visible: true,
        color: "#00aa00"
    });

    return island;
}

export const dummyWorld = {
    islands: [
        makeIsland(1, 0, 0),
        makeIsland(2, 60, -20),
        makeIsland(3, -50, 40)
    ],

    connections: [
        { from: 1, to: 2 },
        { from: 2, to: 3 }
    ]
};
