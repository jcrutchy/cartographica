import { initWorld } from "./world/world.js";
import { showLoginModal } from "./ui/login.js";
import { dummyInit } from "./dummy/dummyServer.js";


export const UIState = {
    BOOT: "boot",
    LOGIN: "login",
    MENU: "menu",
    GAME: "game"
};

let uiState = UIState.BOOT;


window.addEventListener("load", () => {
    if (hasSessionCookie()) {
        setUIState(UIState.MENU);
    } else {
        setUIState(UIState.LOGIN);
    }
      //dummyInit(); // simulate server tick loop
    //initWorld(); // create canvas + renderer
    //showLoginUI(); // start at login screen
});




import { UIManager } from "./ui/ui.js";
import { createWorldInfoPanel } from "./ui/panels/worldInfoPanel.js";
import { createUnitInfoPanel } from "./ui/panels/unitInfoPanel.js";
import { createDebugPanel } from "./ui/panels/debugPanel.js";
import { createPlayerPanel } from "./ui/panels/playerPanel.js";

/*
const ui = new UIManager();

createWorldInfoPanel(ui);
createUnitInfoPanel(ui);
createDebugPanel(ui);
createPlayerPanel(ui);
*/

function hasSessionCookie() {
    return document.cookie.includes("session=");
}





export function setUIState(state) {
    uiState = state;

    const uiRoot = document.getElementById("ui-root");
    const menuRoot = document.getElementById("menu-root");
    const blocker = document.getElementById("menu-blocker");

    switch (state) {
        case UIState.BOOT:
            uiRoot.classList.add("ui-hidden");
            menuRoot.innerHTML = "";
            blocker.classList.add("active");
            break;

        case UIState.LOGIN:
            uiRoot.classList.add("ui-hidden");
            blocker.classList.add("active");
            showLoginModal();
            break;

        case UIState.MENU:
            uiRoot.classList.add("ui-hidden");
            blocker.classList.add("active");
            showMainMenu();
            break;

        case UIState.GAME:
            menuRoot.innerHTML = "";
            uiRoot.classList.remove("ui-hidden");
            blocker.classList.remove("active");
            break;
    }
}
