export class Camera {
    constructor(canvas) {
        this.x = 0;
        this.y = 0;
        this.zoom = 1;

        this.dragging = false;
        this.lastMouse = { x: 0, y: 0 };

        // Mouse drag panning
        canvas.addEventListener("mousedown", e => {
            this.dragging = true;
            this.lastMouse = { x: e.clientX, y: e.clientY };
        });

        canvas.addEventListener("mouseup", () => this.dragging = false);
        canvas.addEventListener("mouseleave", () => this.dragging = false);

        canvas.addEventListener("mousemove", e => {
            if (this.dragging) {
                this.x -= (e.clientX - this.lastMouse.x);
                this.y -= (e.clientY - this.lastMouse.y);
                this.lastMouse = { x: e.clientX, y: e.clientY };
            }
        });

        // Scroll wheel zoom
        canvas.addEventListener("wheel", e => {
            const zoomFactor = e.deltaY < 0 ? 1.1 : 0.9;
            this.zoom *= zoomFactor;
            this.zoom = Math.max(0.01, Math.min(1000, this.zoom));
        });

        // Keyboard panning
        window.addEventListener("keydown", e => {
            const speed = 50 / this.zoom;
            if (e.key === "ArrowUp" || e.key === "w") this.y -= speed;
            if (e.key === "ArrowDown" || e.key === "s") this.y += speed;
            if (e.key === "ArrowLeft" || e.key === "a") this.x -= speed;
            if (e.key === "ArrowRight" || e.key === "d") this.x += speed;

            // Reset camera
            if (e.key === "0") {
                this.x = 0;
                this.y = 0;
                this.zoom = 1;
            }
        });
    }

    // NEW: Center camera on a world coordinate
    centerOn(wx, wy) {
        const tileSize = 32;
        this.x = wx * tileSize * this.zoom - window.innerWidth / 2;
        this.y = wy * tileSize * this.zoom - window.innerHeight / 2;
    }
}
