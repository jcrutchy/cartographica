export function showError(msg) {
    const box = document.getElementById("error-box");
    if (!box) return;

    box.textContent = msg;
    box.classList.add("show");
}

export function clearError() {
    const box = document.getElementById("error-box");
    if (!box) return;

    box.textContent = "";
    box.classList.remove("show");
}
