export function createDebugPanel(ui) {
    const el = document.createElement("div");
    el.id = "ui-bottom-right";
    el.className = "ui-panel";
    el.innerHTML = `
        <div class="ui-title">Debug</div>
        <div class="ui-content">Initializing…</div>
    `;
    ui.registerPanel("debug", el);
}
