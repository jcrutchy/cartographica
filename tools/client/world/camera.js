export class Camera {
    constructor(canvas) {
        this.x = 0;        // world coordinate at screen center
        this.y = 0;        // world coordinate at screen center
        this.scale = 1.0;  // world units per pixel

        this.dragging = false;
        this.lastX = 0;
        this.lastY = 0;
        
        this.invertZoomDirection  = true;

        this.MIN_SCALE = 0.01;
        this.ZOOM_SENSITIVITY = -0.0015;

        // -----------------------------
        // DRAG PANNING (world space)
        // -----------------------------
        canvas.addEventListener("mousedown", e => {
            if (e.button !== 0) return;
            this.dragging = true;
            this.lastX = e.clientX;
            this.lastY = e.clientY;
        });

        window.addEventListener("mouseup", () => this.dragging = false);

        window.addEventListener("mousemove", e => {
            if (!this.dragging) return;

            const dx = e.clientX - this.lastX;
            const dy = e.clientY - this.lastY;

            this.x -= dx * this.scale;
            this.y -= dy * this.scale;

            this.lastX = e.clientX;
            this.lastY = e.clientY;
        });

        // -----------------------------
        // CAD-STYLE MOUSE-CENTRED ZOOM
        // -----------------------------
        canvas.addEventListener("wheel", e => {
            e.preventDefault();

            this.dragging = false;

            const rect = canvas.getBoundingClientRect();
            const mx = e.clientX - rect.left;
            const my = e.clientY - rect.top;

            const oldScale = this.scale;
            //const zoomFactor = Math.exp(e.deltaY * this.ZOOM_SENSITIVITY);

const direction = this.invertZoomDirection ? -1 : 1;
const zoomFactor = Math.exp(e.deltaY * this.ZOOM_SENSITIVITY * direction);

            let newScale = oldScale * zoomFactor;
            newScale = Math.max(newScale, this.MIN_SCALE);

            const wx = (mx - canvas.width / 2) * oldScale + this.x;
            const wy = (my - canvas.height / 2) * oldScale + this.y;

            this.scale = newScale;

            this.x = wx - (mx - canvas.width / 2) * newScale;
            this.y = wy - (my - canvas.height / 2) * newScale;
        }, { passive: false });
    }

    // Center camera on a world coordinate (tile coords or world coords)
    centerOn(wx, wy) {
        this.x = wx;
        this.y = wy;
    }
}
