
export function showMainMenu() {
    const canvas = document.getElementById("game");
    canvas.style.display = "none";

    const root = document.getElementById("menu-root");
    root.style.display = "flex";
    root.innerHTML = "";

    const panel = createPanel();
    panel.appendChild(createTitle("Cartographica"));

    panel.appendChild(createButton("New World", () => {
        showNewWorldMenu();
    }));

    panel.appendChild(createButton("Load World", () => {
        if (window.conn) {
            window.conn.send(JSON.stringify({ type: "LIST_WORLDS" }));
        }
    }));

    panel.appendChild(createButton("Options", () => {
        showOptionsMenu();
    }));

    root.appendChild(panel);
}

// ---------------------------------------------------------------------------

export function showNewWorldMenu() {
    const canvas = document.getElementById("game");
    canvas.style.display = "none";

    const root = document.getElementById("menu-root");
    root.style.display = "flex";
    root.innerHTML = "";

    const panel = createPanel();
    panel.appendChild(createTitle("Create New World"));

    panel.appendChild(createLabel("Graph Type:"));

    const select = document.createElement("select");
    select.className = "menu-select";
    select.id = "graph-select";

    ["single", "grid", "hex"].forEach(type => {
        const opt = document.createElement("option");
        opt.value = type;
        opt.textContent = type;
        select.appendChild(opt);
    });

    panel.appendChild(select);

    panel.appendChild(createButton("Create World", () => {
        const graph = document.getElementById("graph-select").value;

        window.startNewWorld(graph);
    }));

    panel.appendChild(createButton("Back", () => {
        showMainMenu();
    }));

    root.appendChild(panel);
}

// ---------------------------------------------------------------------------

export function showLoadWorldMenu(worlds) {
    const canvas = document.getElementById("game");
    canvas.style.display = "none";

    const root = document.getElementById("menu-root");
    root.style.display = "flex";
    root.innerHTML = "";

    const panel = createPanel();
    panel.appendChild(createTitle("Load World"));

    const list = document.createElement("ul");
    list.className = "menu-list";

    worlds.forEach(w => {
        const li = document.createElement("li");
        li.className = "menu-list-item";
        li.textContent = w.name;
        li.onclick = () => {
            if (window.conn) {
                window.conn.send(JSON.stringify({
                    type: "LOAD_WORLD",
                    world_id: w.id
                }));
            }
        };
        list.appendChild(li);
    });

    panel.appendChild(list);

    panel.appendChild(createButton("Back", () => {
        showMainMenu();
    }));

    root.appendChild(panel);
}

// ---------------------------------------------------------------------------

export function showOptionsMenu() {
    const canvas = document.getElementById("game");
    canvas.style.display = "none";

    const root = document.getElementById("menu-root");
    root.style.display = "flex";
    root.innerHTML = "";

    const panel = createPanel();
    panel.appendChild(createTitle("Options"));

    // Add options here later

    panel.appendChild(createButton("Back", () => {
        showMainMenu();
    }));

    root.appendChild(panel);
}

// ---------------------------------------------------------------------------
//  UI Helpers
// ---------------------------------------------------------------------------

function createPanel() {
    const div = document.createElement("div");
    div.className = "menu-panel";
    return div;
}

function createTitle(text) {
    const h = document.createElement("h2");
    h.textContent = text;
    return h;
}

function createLabel(text) {
    const div = document.createElement("div");
    div.className = "menu-label";
    div.textContent = text;
    return div;
}

function createButton(text, handler) {
    const btn = document.createElement("button");
    btn.className = "menu-button";
    btn.textContent = text;
    btn.onclick = handler;
    return btn;
}
