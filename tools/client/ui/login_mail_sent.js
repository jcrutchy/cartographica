import { UIState } from "./state.js";
import { setUIState } from "./state.js";


export function showEmailSentUI(email) {
    const root = document.getElementById("menu-root");

    root.innerHTML = `
        <div class="menu-panel">
            <div class="menu-title">Check your email</div>
            <div class="menu-text">
                <p>A link has been sent to <b>${email}</b></p>
                <p>It expires in 10 minutes.</p>
                <p>You can now safely close this tab.</p>
                <p>Clicking the link in your email will open Cartographica in a new web browser tab and automatically sign in for you.</p>
            </div>
        </div>
    `;

}
