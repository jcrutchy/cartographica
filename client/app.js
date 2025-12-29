//import { initWorld } from "./world/world.js";
//import { dummyInit } from "./dummy/dummyServer.js";

import { API } from "./ui/auth.js";
import { UIState } from "./ui/state.js";
import { setUIState } from "./ui/state.js";


let uiState = UIState.LOGIN;



window.addEventListener("load", async () => {
    const me = await API.me();

    if (me.authenticated) {
        setUIState(UIState.MAIN);
    } else {
        setUIState(UIState.LOGIN);
    }

});


window.addEventListener("load", async () => {
    try {
        const me = await API.me();
        if (me.authenticated) {
            setUIState(UIState.MAIN);
        } else {
            setUIState(UIState.LOGIN);
        }
    } catch (err) {
        // Don't show an error box for this one
        setUIState(UIState.LOGIN);
    }
    //dummyInit(); // simulate server tick loop
    //initWorld(); // create canvas + renderer
    //showLoginUI(); // start at login screen
});



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




