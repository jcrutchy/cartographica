import { showMainMenu } from "./mainmenu.js";


export function showLoginModal() {
    const root = document.getElementById("menu-root");

    root.innerHTML = `
        <div class="menu-panel">

            <div class="menu-title">Login</div>

            <input id="login-user" class="menu-input" placeholder="Username">
            <input id="login-email" class="menu-input" placeholder="Email">

            <label class="menu-checkbox">
                <input type="checkbox" id="login-remember" checked>
                Remember me on this device
            </label>

            <button class="menu-button" id="btn-send-link">Send Login Link</button>
        </div>
    `;

    document.getElementById("btn-send-link").onclick = async () => {
        const username = document.getElementById("login-user").value.trim();
        const email = document.getElementById("login-email").value.trim();
        const remember = document.getElementById("login-remember").checked;

        const ok = await requestMagicLink(username, email, remember);
        if (ok) showLinkSentModal(email);
    };
}



const API_BASE = "http://localhost:8000";

async function requestMagicLink(username, email, remember) {
    const res = await fetch(`${API_BASE}/api/send-login-link`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, email, remember })
    });
    return res.ok;
}






export function showLinkSentModal(email) {
    const root = document.getElementById("menu-root");

    root.innerHTML = `
        <div class="menu-panel">
            <div class="menu-title">Check Your Email</div>
            <div class="menu-text">
                A login link has been sent to <b>${email}</b>.<br>
                It expires in 10 minutes.
            </div>
            <button class="menu-button" id="btn-close">Close</button>
        </div>
    `;

    document.getElementById("btn-close").onclick = () => {
        root.innerHTML = "";
    };
}
