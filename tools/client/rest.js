import { showError } from "./error.js";

const FRIENDLY_HTTP_ERRORS = {
    401: "You are not logged in.",
    403: "You do not have permission to do that.",
    404: "The requested resource was not found.",
    500: "The server encountered an error."
};

export async function apiFetch(url, options = {}) {
    try {
        const res = await fetch(url, options);

        // Try to parse JSON
        const data = await res.json().catch(() => null);

        // Handle API-level errors
        if (data && data.ok === false) {
            const msg = data.error || "Unknown API error";
            showError(msg);
            throw new Error(msg);
        }

        // Handle HTTP-level errors
        if (!res.ok) {
            const friendly = FRIENDLY_HTTP_ERRORS[res.status];
            const msg = data?.error || friendly || `HTTP ${res.status}`;
            showError(msg);
            throw new Error(msg);
        }



        return data;

    } catch (err) {
        // Network errors, JSON parse errors, etc.
        showError(err.message);
        throw err;
    }
}
