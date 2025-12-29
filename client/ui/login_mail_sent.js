import { UIState } from "./state.js";
import { setUIState } from "./state.js";


export function showEmailSentUI(email) {
    const root = document.getElementById("menu-root");

    root.innerHTML = `
        <div class="menu-panel">
            <div class="menu-title">Check Your Email</div>
            <div class="menu-text">
                A login link has been sent to <b>${email}</b>.
                It expires in 10 minutes.
            </div>
            <button class="menu-button" id="btn-close">Close</button>
        </div>
    `;

    document.getElementById("btn-close").onclick = () => {
        setUIState(UIState.LOGIN);
    };
}
