const STATUS_URL = '/services/supervisor/rest-api/handlers/status.php';

async function fetchStatus() {
  const res = await fetch(STATUS_URL);
  const data = await res.json();
  if (!data.ok) {
    document.getElementById('status').textContent = 'Error: ' + JSON.stringify(data);
    return;
  }
  // Data format: { ok: true, islands: [ { id, name, port, running, pid, config } ] }
  renderStatusFromIslands(data.islands);
}

function renderStatusFromIslands(islands) {
  const container = document.getElementById('status');
  container.innerHTML = '';

  const islandSelect = document.getElementById('log-island');
  islandSelect.innerHTML = '';

  if (!Array.isArray(islands)) {
    container.textContent = 'No islands found';
    return;
  }

  const header = document.createElement('div');
  header.style.marginBottom = '8px';
  header.textContent = `Islands directory: ${islands.length ? islands[0].config && islands[0].config.islands_dir ? islands[0].config.islands_dir : '' : ''}`;
  container.appendChild(header);

  islands.forEach(island => {
    const div = document.createElement('div');
    div.className = 'island';
    div.innerHTML = `<strong>${island.id}</strong> — ${island.name || ''} — PID: ${island.pid||'—'} — Running: ${island.running}`;

    const controls = document.createElement('div');
    controls.style.marginTop = '6px';
    controls.innerHTML = `
      <button data-action="start" data-id="${island.id}">Start</button>
      <button data-action="stop" data-id="${island.id}">Stop</button>
      <button data-action="restart" data-id="${island.id}">Restart</button>
    `;
    div.appendChild(controls);
    container.appendChild(div);

    const opt = document.createElement('option');
    opt.value = island.id;
    opt.textContent = island.id;
    islandSelect.appendChild(opt);
  });

  container.querySelectorAll('button[data-action]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const action = e.target.getAttribute('data-action');
      const id = e.target.getAttribute('data-id');
      await sendCommand(action, id);
      await fetchStatus();
    });
  });
}

async function sendCommand(action, id) {
  let url;
  switch(action) {
    case 'start': url = `/services/supervisor/rest-api/handlers/start.php?id=${encodeURIComponent(id)}`; break;
    case 'stop': url = `/services/supervisor/rest-api/handlers/stop.php?id=${encodeURIComponent(id)}`; break;
    case 'restart': url = `/services/supervisor/rest-api/handlers/restart.php?id=${encodeURIComponent(id)}`; break;
    default: return;
  }
  const res = await fetch(url, { method: 'GET' });
  const data = await res.json();
  if (!data.ok) {
    alert('Command failed: ' + (data.error || data.message || JSON.stringify(data)));
  }
}

async function fetchLog() {
  const islandId = document.getElementById('log-island').value;
  if (!islandId) return;

  const url = `/services/supervisor/rest-api/handlers/log.php?id=${encodeURIComponent(islandId)}&lines=200`;
  const res = await fetch(url);
  const data = await res.json();
  if (!data.ok) {
    document.getElementById('log-output').textContent = 'Error: ' + data.error;
    return;
  }

  document.getElementById('log-output').textContent = data.log.join('\n');
}

document.getElementById('refresh-log').addEventListener('click', fetchLog);

// Poll status every 2 seconds
fetchStatus();
setInterval(fetchStatus, 2000);
