
const ws = new WebSocket("ws://localhost:8081");

const canvas = document.getElementById("world");
const ctx = canvas.getContext("2d");

let width = window.innerWidth;
let height = window.innerHeight;
canvas.width = width;
canvas.height = height;

window.addEventListener("resize", () => {
  width = window.innerWidth;
  height = window.innerHeight;
  canvas.width = width;
  canvas.height = height;
  render();
});

// world/grid settings
const GRID_SIZE = 100;   // logical grid size
const BASE_CELL = 20;    // base cell size in pixels

// camera
let zoom = 1.0;
let offsetX = 0;
let offsetY = 0;

// pan state
let isPanning = false;
let lastX = 0;
let lastY = 0;

// NPC logical position
let npc = { x: 20, y: 20 };

// ---------- camera helpers ----------

function worldToScreen(x, y) {
  return {
    x: x * BASE_CELL * zoom + offsetX,
    y: y * BASE_CELL * zoom + offsetY
  };
}

function screenToWorld(x, y) {
  return {
    x: (x - offsetX) / (BASE_CELL * zoom),
    y: (y - offsetY) / (BASE_CELL * zoom)
  };
}

// ---------- input: pan ----------

canvas.addEventListener("mousedown", e => {
  if (e.button === 0) {
    isPanning = true;
    lastX = e.clientX;
    lastY = e.clientY;
  }
});

window.addEventListener("mouseup", () => {
  isPanning = false;
});

window.addEventListener("mousemove", e => {
  if (!isPanning) return;
  const dx = e.clientX - lastX;
  const dy = e.clientY - lastY;
  lastX = e.clientX;
  lastY = e.clientY;

  offsetX += dx;
  offsetY += dy;

  render();
});

// ---------- input: zoom (CAD-like, to cursor) ----------

canvas.addEventListener("wheel", e => {
  e.preventDefault();

  const zoomFactor = 1.1; // step size
  const mouseX = e.clientX;
  const mouseY = e.clientY;

  const before = screenToWorld(mouseX, mouseY);

  if (e.deltaY < 0) {
    zoom *= zoomFactor;
  } else {
    zoom /= zoomFactor;
  }

  // clamp zoom
  zoom = Math.max(0.1, Math.min(10, zoom));

  const after = screenToWorld(mouseX, mouseY);

  // adjust offset so the world point under cursor stays fixed
  offsetX += (before.x - after.x) * BASE_CELL * zoom;
  offsetY += (before.y - after.y) * BASE_CELL * zoom;

  render();
}, { passive: false });

// ---------- drawing ----------

function drawGrid() {
  ctx.clearRect(0, 0, width, height);

  ctx.strokeStyle = "#222";
  ctx.lineWidth = 1;

  const cellSize = BASE_CELL * zoom;

  // find visible world bounds
  const topLeft = screenToWorld(0, 0);
  const bottomRight = screenToWorld(width, height);

  const startX = Math.floor(topLeft.x);
  const endX = Math.ceil(bottomRight.x);
  const startY = Math.floor(topLeft.y);
  const endY = Math.ceil(bottomRight.y);

  for (let x = startX; x <= endX; x++) {
    const sx = x * cellSize + offsetX;
    ctx.beginPath();
    ctx.moveTo(sx, 0);
    ctx.lineTo(sx, height);
    ctx.stroke();
  }

  for (let y = startY; y <= endY; y++) {
    const sy = y * cellSize + offsetY;
    ctx.beginPath();
    ctx.moveTo(0, sy);
    ctx.lineTo(width, sy);
    ctx.stroke();
  }
}

function drawNPC() {
  const cellSize = BASE_CELL * zoom;
  const s = worldToScreen(npc.x, npc.y);

  ctx.fillStyle = "#4af";
  ctx.fillRect(s.x, s.y, cellSize, cellSize);
}

function render() {
  drawGrid();
  drawNPC();
}

// ---------- Cortex integration ----------

ws.onopen = () => {
  console.log("Connected to Cortex WS");

  setInterval(() => {
    ws.send(JSON.stringify({
      npc_id: 1,
      role_id: 2,
      state: {
        "npc.x": npc.x,
        "npc.y": npc.y
      }
    }));
  }, 150);
};

ws.onmessage = evt => {
  const msg = JSON.parse(evt.data);
  const action = msg.action;

  if (action === 0) npc.y -= 1; // north
  if (action === 1) npc.y += 1; // south
  if (action === 2) npc.x += 1; // east
  if (action === 3) npc.x -= 1; // west
  if (action === 4) {}          // idle

  render();
};

// initial draw
render();
