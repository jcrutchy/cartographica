import { apiFetch } from "../rest.js";

const IDENTITY_URL = "/cartographica/services/identity/index.php";

export const Identity = {
    async requestLoginLink(email) {
        const form = new FormData();
        form.append("email", email);

        const json = await apiFetch(`${IDENTITY_URL}?action=request_login`, {
            method: "POST",
            body: form
        });

        return json;
    },

    async redeemLoginToken(token) {
        const form = new FormData();
        form.append("email_token", token);
    
        const json = await apiFetch(`${IDENTITY_URL}?action=redeem`, {
            method: "POST",
            body: form
        });
        const session_token = json.session_token;
    
        localStorage.setItem("cartographica_session_token", session_token);
    
        return { ok: true };
    },

    isAuthenticated() {
        return !!localStorage.getItem("cartographica_session_token");
    },

    getIdentity() {
        const token = localStorage.getItem("cartographica_session_token");
        if (!token) return null;
    
        try {
            const decoded = JSON.parse(token); // { payload, signature }
            return decoded; // return BOTH
        } catch {
            return null;
        }
    }


};
