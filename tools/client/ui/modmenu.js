export function showModMenu() {
    const root = document.getElementById("menu-root");
    root.innerHTML = `
        <div class="menu">
            <h2>Mods & Themes</h2>
            <p>No mods installed.</p>
            <button id="back">Back</button>
        </div>
    `;

    document.getElementById("back").onclick = () => history.back();
}
