import { apiFetch } from "../rest.js";


export const API = {
    me: () =>
        apiFetch("auth.php/api/me"),

    sendLoginLink: (username, email, remember) =>
        apiFetch("auth.php/api/send-login-link", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username, email, remember })
        }),

    logout: () =>
        apiFetch("auth.php/api/logout", { method: "POST" })
};
