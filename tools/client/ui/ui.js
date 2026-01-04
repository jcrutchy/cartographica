export class UIManager {
    constructor() {
        this.root = document.getElementById("ui-root");
        this.panels = {};
    }

    registerPanel(id, element) {
        this.panels[id] = element;
        this.root.appendChild(element);
    }

    updatePanel(id, html) {
        if (this.panels[id]) {
            this.panels[id].querySelector(".ui-content").innerHTML = html;
        }
    }
}
