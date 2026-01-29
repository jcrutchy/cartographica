export class Camera {
    constructor(canvas) {
        this.x = 0;        // world coordinate at screen center
        this.y = 0;        // world coordinate at screen center
        this.scale = 1.0;  // pixels per world unit

        this.dragging = false;
        this.lastX = 0;
        this.lastY = 0;

        this.MIN_SCALE = 0.001;
        this.MAX_SCALE = 1000;
        this.ZOOM_SENSITIVITY = 0.0012;

        // --- PANNING ---
        canvas.addEventListener("mousedown", e => {
            if (e.button !== 0) return;
            this.dragging = true;
            this.lastX = e.clientX;
            this.lastY = e.clientY;
        });

        window.addEventListener("mouseup", () => {
            this.dragging = false;
        });

        window.addEventListener("mousemove", e => {
            if (!this.dragging) return;

            const dx = e.clientX - this.lastX;
            const dy = e.clientY - this.lastY;

            // convert pixel drag → world movement
            this.x -= dx / this.scale;
            this.y -= dy / this.scale;

            this.lastX = e.clientX;
            this.lastY = e.clientY;
        });

        // --- ZOOM (zoom to cursor) ---
        canvas.addEventListener("wheel", e => {
            e.preventDefault();

            const rect = canvas.getBoundingClientRect();
            const mx = e.clientX - rect.left;
            const my = e.clientY - rect.top;

            const oldScale = this.scale;
            const zoomFactor = Math.exp(-e.deltaY * this.ZOOM_SENSITIVITY);
            const newScale = Math.min(this.MAX_SCALE, Math.max(this.MIN_SCALE, oldScale * zoomFactor));

            // world coords under cursor BEFORE zoom
            const wx = (mx - canvas.width / 2) / oldScale + this.x;
            const wy = (my - canvas.height / 2) / oldScale + this.y;

            this.scale = newScale;

            // adjust camera so that world point stays under cursor
            this.x = wx - (mx - canvas.width / 2) / newScale;
            this.y = wy - (my - canvas.height / 2) / newScale;
        }, { passive: false });
    }

    // world → screen
    worldToScreen(wx, wy, canvas) {
        return {
            x: (wx - this.x) * this.scale + canvas.width / 2,
            y: (wy - this.y) * this.scale + canvas.height / 2
        };
    }

    // screen → world
    screenToWorld(sx, sy, canvas) {
        return {
            x: (sx - canvas.width / 2) / this.scale + this.x,
            y: (sy - canvas.height / 2) / this.scale + this.y
        };
    }

    centerOn(wx, wy) {
        this.x = wx;
        this.y = wy;
    }
}
