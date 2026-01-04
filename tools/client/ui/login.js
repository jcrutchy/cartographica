import { showEmailSentUI } from "./login_mail_sent.js";
import { Identity } from "./identity.js";
import { showError, clearError } from "../error.js";

export function showLoginUI() {
    const root = document.getElementById("menu-root");

    root.innerHTML = `
        <div class="menu-panel">
            <div class="menu-title">Register/Login</div>
            <p>Enter you email address: <input id="email" class="menu-input-email" placeholder="yourname@example.com"></p>
            <button class="menu-button" id="btn-login">Send Link</button>
        </div>
    `;

document.getElementById("btn-login").onclick = async () => {
    const btn = document.getElementById("btn-login");
    const email = document.getElementById("email").value.trim();

    if (!email) {
        showError("Please enter your email address.");
        return;
    }

    // Disable button to prevent double-clicks
    btn.disabled = true;
    btn.textContent = "Sending...";

    try {
        const res = await Identity.requestLoginLink(email);

        if (res.ok) {
            clearError();
            showEmailSentUI(email);
            return; // no need to re-enable, UI is changing
        }

        // If server returned ok:false, re-enable
        btn.disabled = false;
        btn.textContent = "Send Link";

    } catch (err) {
        showError("Login link request failed: " + err);

        // Re-enable button on error
        btn.disabled = false;
        btn.textContent = "Send Link";
    }
};

}
