// ui/auth.js

import { apiFetch } from "../rest.js";

const IDENTITY_URL = "/cartographica/identity/index.php";

export const Identity = {
    async requestLoginLink(email) {
        const form = new FormData();
        form.append("email", email);

        const json = await apiFetch(`${IDENTITY_URL}?action=request_login`, {
            method: "POST",
            body: form
        });

        return json; // { ok: true, data: { message: ... } }
    },

    async redeemLoginToken(token) {
        const json = await apiFetch(
            `${IDENTITY_URL}?action=redeem&token=${encodeURIComponent(token)}`
        );

        const { token: deviceToken, payload, signature } = json.data;

        localStorage.setItem("cartographica_device_token", deviceToken);
        localStorage.setItem("cartographica_identity_payload", JSON.stringify(payload));
        localStorage.setItem("cartographica_identity_signature", signature);

        return payload;
    },

    isAuthenticated() {
        return !!localStorage.getItem("cartographica_identity_payload");
    },

    getIdentity() {
        const payload = localStorage.getItem("cartographica_identity_payload");
        const signature = localStorage.getItem("cartographica_identity_signature");
        if (!payload || !signature) return null;
        return { payload: JSON.parse(payload), signature };
    }
};
