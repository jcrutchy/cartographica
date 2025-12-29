import { showHUD } from "./hud.js";
import { showModMenu } from "./modmenu.js";
import { UIState } from "./state.js";
import { setUIState } from "./state.js";


export function showMainMenu() {
    const root = document.getElementById("menu-root");

    root.innerHTML = `
        <div class="menu-panel">
            <div class="menu-title">Cartographica</div>

            <button class="menu-button" id="btn-play">Play</button>
            <button class="menu-button" id="btn-profile">Edit Profile</button>
            <button class="menu-button" id="btn-logout">Logout</button>
        </div>
    `;

    document.getElementById("btn-profile").onclick = () => {
        setUIState(UIState.PROFILE);
    };

    document.getElementById("btn-logout").onclick = async () => {
        await API.logout();
        setUIState(UIState.LOGIN);
    };
}
