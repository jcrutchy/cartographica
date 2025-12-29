import { UIState } from "./state.js";
import { setUIState } from "./state.js";

export function showProfileUI() {
    const root = document.getElementById("menu-root");

    root.innerHTML = `
        <div class="menu-panel">
            <div class="menu-title">Edit Profile</div>

            <input id="profile-username" class="menu-input" placeholder="New Username">
            <input id="profile-email" class="menu-input" placeholder="New Email">

            <button class="menu-button" id="btn-save-profile">Save</button>
            <button class="menu-button" id="btn-back">Back</button>
        </div>
    `;

    document.getElementById("btn-back").onclick = () => {
        setUIState(UIState.MAIN);
    };

    document.getElementById("btn-save-profile").onclick = () => {
        alert("Profile saving not implemented yet");
    };
}
