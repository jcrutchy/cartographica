export function createPlayerPanel(ui) {
    const el = document.createElement("div");
    el.id = "ui-top-right";
    el.className = "ui-panel";
    el.innerHTML = `
        <div class="ui-title">Player</div>
        <div class="ui-content">Loading…</div>
    `;
    ui.registerPanel("player", el);
}
