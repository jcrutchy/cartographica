export const worldData = {
    islands: [
        {
            id: "A",
            originX: 0,
            originY: 0,
            width: 200,
            height: 200,
            tiles: null,
            connections: ["B", "C"]
        },
        {
            id: "B",
            originX: 300,
            originY: 50,
            width: 180,
            height: 220,
            tiles: null,
            connections: ["A", "D"]
        },
        {
            id: "C",
            originX: 100,
            originY: 400,
            width: 250,
            height: 180,
            tiles: null,
            connections: ["A", "D"]
        },
        {
            id: "D",
            originX: 500,
            originY: 350,
            width: 300,
            height: 300,
            tiles: null,
            connections: ["B", "C"]
        },
        {
            id: "E",
            originX: 800,
            originY: 100,
            width: 220,
            height: 260,
            tiles: null,
            connections: ["A", "C"]
        },
        {
            id: "F",
            originX: 900,
            originY: 500,
            width: 280,
            height: 240,
            tiles: null,
            connections: ["E", "C"]
        }
    ]
};
