//import { initWorld } from "./world/world.js";

import { Identity } from "./ui/identity.js";
import { UIState, setUIState } from "./ui/state.js";

window.addEventListener("load", async () => {
    // 1. Check for ?token=... in URL (login link)
    const params = new URLSearchParams(window.location.search);
    if (params.has("token")) {
        try {
            await Identity.redeemLoginToken(params.get("token"));
            window.location.href="/cartographica/tools/client";
            return;
        } catch (err) {
            console.error(err);
            setUIState(UIState.LOGIN);
            return;
        }
    }

    // 2. Check if already authenticated
    if (Identity.isAuthenticated()) {
        setUIState(UIState.MAIN);
        return;
    }

    // 3. Otherwise show login UI
    setUIState(UIState.LOGIN);
});

    //dummyInit(); // simulate server tick loop
    //initWorld(); // create canvas + renderer
    //showLoginUI(); // start at login screen



//import { UIManager } from "./ui/ui.js";
//import { createWorldInfoPanel } from "./ui/panels/worldInfoPanel.js";
//import { createUnitInfoPanel } from "./ui/panels/unitInfoPanel.js";
//import { createDebugPanel } from "./ui/panels/debugPanel.js";
//import { createPlayerPanel } from "./ui/panels/playerPanel.js";

/*
const ui = new UIManager();

createWorldInfoPanel(ui);
createUnitInfoPanel(ui);
createDebugPanel(ui);
createPlayerPanel(ui);
*/

/*function hasSessionCookie() {
    return document.cookie.includes("session=");
}*/


