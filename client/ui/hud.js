export function showHUD() {
    const menuRoot = document.getElementById("menu-root");
    menuRoot.innerHTML = `
        <div class="hud">
            <span>Resources: (dummy)</span>
            <button id="menuBtn">Menu</button>
        </div>
    `;

    document.getElementById("menuBtn").onclick = () => {
        alert("Menu not implemented yet");
    };
}
