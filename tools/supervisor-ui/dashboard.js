const STATUS_URL = '/cartographica/supervisor/api/status.php';
const COMMAND_URL = '/cartographica/supervisor/api/command.php';
const LOG_URL = '/cartographica/supervisor/api/log.php';

async function fetchStatus() {
  const res = await fetch(STATUS_URL);
  const data = await res.json();
  if (!data.ok) {
    document.getElementById('status').textContent = 'Error: ' + data.error;
    return;
  }
  renderStatus(data.state);
}

function renderStatus(state) {
  const container = document.getElementById('status');
  container.innerHTML = '';

  const islandSelect = document.getElementById('log-island');
  islandSelect.innerHTML = '';

  state.worlds.forEach(world => {
    const h3 = document.createElement('h3');
    h3.textContent = `${world.name} (${world.id})`;
    container.appendChild(h3);

    const table = document.createElement('table');
    const thead = document.createElement('thead');
    thead.innerHTML = `
      <tr>
        <th>ID</th>
        <th>Port</th>
        <th>PID</th>
        <th>Status</th>
        <th>Uptime</th>
        <th>Position</th>
        <th>Actions</th>
      </tr>
    `;
    table.appendChild(thead);

    const tbody = document.createElement('tbody');

    world.islands.forEach(island => {
      const tr = document.createElement('tr');

      const statusClass = 'status-' + island.status;

      tr.innerHTML = `
        <td>${island.id}</td>
        <td>${island.port}</td>
        <td>${island.pid ?? ''}</td>
        <td class="${statusClass}">${island.status}</td>
        <td>${formatUptime(island.uptime)}</td>
        <td>${formatPosition(island.position)}</td>
        <td>
          <button data-action="start" data-id="${island.id}">Start</button>
          <button data-action="stop" data-id="${island.id}">Stop</button>
          <button data-action="restart" data-id="${island.id}">Restart</button>
        </td>
      `;

      tbody.appendChild(tr);

      const opt = document.createElement('option');
      opt.value = island.id;
      opt.textContent = island.id;
      islandSelect.appendChild(opt);
    });

    table.appendChild(tbody);
    container.appendChild(table);
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

function formatUptime(seconds) {
  if (seconds == null) return '';
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

function formatPosition(pos) {
  if (!pos) return '';
  return `(${pos.x}, ${pos.y})`;
}

async function sendCommand(action, id) {
  const url = `${COMMAND_URL}?action=${encodeURIComponent(action)}&id=${encodeURIComponent(id)}`;
  const res = await fetch(url, { method: 'POST' });
  const data = await res.json();
  if (!data.ok) {
    alert('Command failed: ' + (data.error || data.message));
  }
}

async function fetchLog() {
  const islandId = document.getElementById('log-island').value;
  if (!islandId) return;

  const url = `${LOG_URL}?id=${encodeURIComponent(islandId)}&lines=200`;
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
