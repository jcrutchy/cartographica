export function createUnitInfoPanel(ui) {
    const el = document.createElement("div");
    el.id = "ui-bottom-left";
    el.className = "ui-panel";
    el.innerHTML = `
        <div class="ui-title">Unit Info</div>
        <div class="ui-content">No unit selected</div>
    `;
    ui.registerPanel("unit", el);
}
