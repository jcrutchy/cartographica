import { Renderer } from "./renderer.js";
import { Camera } from "./camera.js";
import { dummyWorld } from "../dummy/dummyWorld.js";
import { dummyUnits, updateDummyUnits } from "../dummy/dummyUnits.js";

let renderer, camera;

export function initWorld() {
    const canvas = document.getElementById("worldCanvas");
    resizeCanvas(canvas);

    camera = new Camera(canvas);
    renderer = new Renderer(canvas, camera);

    window.addEventListener("resize", () => resizeCanvas(canvas));

    function loop() {
        updateDummyUnits(dummyUnits, dummyWorld);
        renderer.render(dummyWorld, dummyUnits);
        requestAnimationFrame(loop);
    }
    loop();
}

function resizeCanvas(canvas) {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
