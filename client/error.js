export function showError(msg) {
    const box = document.getElementById("error-box");
    if (!box) {
        console.warn("Error box not found in DOM");
        return;
    }
    box.textContent = msg;
    box.style.display = "block";
}

export function clearError() {
    const box = document.getElementById("error-box");
    if (box) box.style.display = "none";
}
