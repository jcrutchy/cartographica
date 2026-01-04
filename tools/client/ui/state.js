import { showLoginUI } from "./login.js";
import { showProfileUI } from "./edit_profile.js";
import { showMainUI } from "./mainmenu.js";

export const UIState = {
    LOGIN: "login",
    PROFILE: "profile",
    MAIN: "main"
};

export function setUIState(state) {
    switch (state) {
        case UIState.LOGIN:    showLoginUI(); break;
        case UIState.PROFILE:  showProfileUI(); break;
        case UIState.MAIN:     showMainUI(); break;
    }
}
