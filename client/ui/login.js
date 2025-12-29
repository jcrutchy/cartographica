import { showEmailSentUI } from "./login_mail_sent.js";
import { API } from "./auth.js";


export function showLoginUI() {
    const root = document.getElementById("menu-root");

    root.innerHTML = `
        <div class="menu-panel">
            <div class="menu-title">Login</div>

            <input id="username" class="menu-input" placeholder="Username">
            <input id="email" class="menu-input" placeholder="Email">

            <label class="menu-checkbox">
                <input type="checkbox" id="remember" checked>
                Remember me
            </label>

            <button class="menu-button" id="btn-login">Send Link</button>
        </div>
    `;

    document.getElementById("btn-login").onclick = async () => {
        const username = document.getElementById("username").value.trim();
        const email = document.getElementById("email").value.trim();
        const remember = document.getElementById("remember").checked;

        const ok = await API.sendLoginLink(username, email, remember);
        if (ok) showEmailSentUI(email);
    };
}






























