import { showHUD } from "./hud.js";
import { showModMenu } from "./modmenu.js";



export function showMainMenu() {
    const root = document.getElementById("menu-root");

    root.innerHTML = `
        <div class="menu-panel">
            <div class="menu-title">Main Menu</div>
            <button class="menu-button" id="btn-new">New Game</button>
            <button class="menu-button" id="btn-load">Load Game</button>
            <button class="menu-button" id="btn-options">Options</button>
            <button class="menu-button" id="btn-mods">Mods & Themes</button>
            <button class="menu-button" id="btn-logout">Logout</button>
        </div>
    `;

    document.getElementById("btn-new").onclick = () => setUIState(UIState.GAME);
    document.getElementById("btn-load").onclick = () => loadSavedGame();
    document.getElementById("btn-options").onclick = () => showOptions();
    document.getElementById("btn-mods").onclick = () => showMods();
    document.getElementById("btn-logout").onclick = () => {
        document.cookie = "session=; expires=Thu, 01 Jan 1970 00:00:00 UTC;";
        setUIState(UIState.LOGIN);
    };
}




