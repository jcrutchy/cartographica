export function createWorldInfoPanel(ui) {
    const el = document.createElement("div");
    el.id = "ui-top-left";
    el.className = "ui-panel";
    el.innerHTML = `
        <div class="ui-title">World Info</div>
        <div class="ui-content">Loading…</div>
    `;
    ui.registerPanel("world", el);
}
