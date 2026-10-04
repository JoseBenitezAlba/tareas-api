<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tareas API</title>
<style>
:root{--bg:#0f172a;--card:#1e293b;--line:#334155;--txt:#e2e8f0;--mut:#94a3b8;--acc:#10b981;--acc2:#34d399}
*{box-sizing:border-box}
body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--bg);color:var(--txt);line-height:1.5}
.wrap{max-width:820px;margin:auto;padding:28px 20px 60px}
h1{margin:0;font-size:1.8rem}
.sub{color:var(--mut);margin:.3rem 0 20px}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:18px;margin-bottom:16px}
label{display:block;font-size:.85rem;color:var(--mut);margin:10px 0 4px}
input,select{width:100%;padding:10px;border-radius:8px;border:1px solid var(--line);background:#0b1220;color:var(--txt);font:inherit}
button{cursor:pointer;border:0;border-radius:8px;padding:10px 16px;background:var(--acc);color:#04231a;font:inherit;font-weight:700}
button:hover{background:var(--acc2)}
button:disabled{opacity:.6;cursor:wait}
button.sec{background:transparent;border:1px solid var(--line);color:var(--txt);padding:6px 12px;font-weight:500}
button.sec:hover{background:#334155}
.row{display:flex;gap:10px;flex-wrap:wrap}.row>*{flex:1;min-width:130px}
.top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.hint{background:#0b1220;border-radius:8px;padding:10px 12px;font-size:.9rem;color:var(--mut);margin-bottom:10px}
.hint code{color:var(--acc2)}
.task{display:flex;gap:12px;align-items:center;justify-content:space-between;padding:12px 0;border-top:1px solid var(--line)}
.task:first-child{border-top:0}
.task h3{margin:0;font-size:1rem}.task.done h3{text-decoration:line-through;color:var(--mut)}
.meta{font-size:.8rem;color:var(--mut)}
.badge{display:inline-block;padding:1px 9px;border-radius:999px;font-size:.72rem;font-weight:600;margin-right:6px;background:#334155;color:var(--txt)}
.high{background:#b91c1c}.medium{background:#b45309}.low{background:#15803d}
.actions{display:flex;gap:6px;flex-shrink:0}
.msg{margin-top:10px;font-size:.9rem}.err{color:#fca5a5}.ok{color:#86efac}
.empty{color:var(--mut);text-align:center;padding:20px}
.hidden{display:none}
</style>
</head>
<body>
<div class="wrap">
  <h1>✅ Tareas API</h1>
  <p class="sub">Pantalla de prueba de una API REST en Laravel con autenticación por token (Sanctum).</p>

  <section id="login" class="card">
    <h2 style="margin:0 0 8px;font-size:1.1rem">Iniciar sesión</h2>
    <div class="hint">Usuario demo: <code>demo@example.com</code> · contraseña: <code>password</code></div>
    <form id="loginForm">
      <label for="email">Correo</label>
      <input id="email" type="email" value="demo@example.com" required>
      <label for="pass">Contraseña</label>
      <input id="pass" type="password" value="password" required>
      <button style="margin-top:14px" id="loginBtn">Entrar</button>
      <div id="loginMsg" class="msg"></div>
    </form>
  </section>

  <div id="app" class="hidden">
    <div class="top card">
      <div>Sesión: <b id="who"></b></div>
      <button class="sec" id="logout">Cerrar sesión</button>
    </div>

    <section class="card">
      <h2 style="margin:0 0 4px;font-size:1.1rem">Nueva tarea</h2>
      <form id="newForm">
        <label for="title">Título</label>
        <input id="title" required maxlength="255" placeholder="Comprar pan">
        <div class="row">
          <div><label for="prio">Prioridad</label>
            <select id="prio"><option value="low">Baja</option><option value="medium" selected>Media</option><option value="high">Alta</option></select></div>
          <div><label for="due">Fecha límite</label><input id="due" type="date"></div>
        </div>
        <button style="margin-top:14px">Añadir</button>
        <div id="newMsg" class="msg"></div>
      </form>
    </section>

    <section class="card">
      <div class="row" style="margin-bottom:8px">
        <input id="search" placeholder="Buscar por título...">
        <select id="fStatus"><option value="">Todos los estados</option><option value="pending">Pendiente</option><option value="in_progress">En curso</option><option value="done">Hecha</option></select>
        <select id="fPrio"><option value="">Todas las prioridades</option><option value="high">Alta</option><option value="medium">Media</option><option value="low">Baja</option></select>
      </div>
      <div id="list"></div>
    </section>
  </div>
</div>
<script>
const $ = s => document.querySelector(s);
const el = (tag, cls, text) => { const e = document.createElement(tag); if (cls) e.className = cls; if (text !== undefined) e.textContent = text; return e; };
const ST = {pending: 'Pendiente', in_progress: 'En curso', done: 'Hecha'};
const PR = {low: 'Baja', medium: 'Media', high: 'Alta'};
let token = sessionStorage.getItem('token');

async function api(url, opts = {}) {
  const headers = {'Accept': 'application/json', 'Content-Type': 'application/json'};
  if (token) headers.Authorization = 'Bearer ' + token;
  const r = await fetch(url, {...opts, headers});
  if (r.status === 204) return null;
  const j = await r.json().catch(() => ({}));
  if (r.status === 401 && token) { logout(); throw new Error('Sesión caducada.'); }
  if (!r.ok) throw new Error(j.errors ? Object.values(j.errors)[0][0] : (j.message || 'Error ' + r.status));
  return j;
}

function show(user) {
  $('#login').classList.toggle('hidden', !!user);
  $('#app').classList.toggle('hidden', !user);
  if (user) { $('#who').textContent = user.name + ' (' + user.email + ')'; load(); }
}
function logout() { token = null; sessionStorage.removeItem('token'); show(null); }

$('#loginForm').onsubmit = async ev => {
  ev.preventDefault();
  const b = $('#loginBtn'), m = $('#loginMsg');
  b.disabled = true; m.textContent = '';
  try {
    const r = await api('/api/login', {method: 'POST', body: JSON.stringify({email: $('#email').value, password: $('#pass').value})});
    token = r.token; sessionStorage.setItem('token', token); show(r.user);
  } catch (e) { m.className = 'msg err'; m.textContent = e.message; }
  b.disabled = false;
};
$('#logout').onclick = async () => { try { await api('/api/logout', {method: 'POST'}); } catch (e) {} logout(); };

function row(t) {
  const d = el('div', 'task' + (t.status === 'done' ? ' done' : ''));
  const info = el('div');
  info.append(el('h3', '', t.title));
  const meta = el('div', 'meta');
  meta.append(el('span', 'badge', ST[t.status]), el('span', 'badge ' + t.priority, PR[t.priority]));
  if (t.due_date) meta.append(document.createTextNode('vence ' + t.due_date));
  info.append(meta);
  const act = el('div', 'actions');
  if (t.status !== 'done') {
    const ok = el('button', 'sec', '✓ Completar');
    ok.onclick = () => change(() => api('/api/tasks/' + t.id, {method: 'PUT', body: JSON.stringify({status: 'done'})}));
    act.append(ok);
  }
  const del = el('button', 'sec', 'Borrar');
  del.onclick = () => change(() => api('/api/tasks/' + t.id, {method: 'DELETE'}));
  act.append(del);
  d.append(info, act);
  return d;
}
async function change(fn) { try { await fn(); } catch (e) { alert(e.message); } load(); }

async function load() {
  const q = new URLSearchParams({per_page: 50});
  if ($('#search').value) q.set('search', $('#search').value);
  if ($('#fStatus').value) q.set('status', $('#fStatus').value);
  if ($('#fPrio').value) q.set('priority', $('#fPrio').value);
  try {
    const r = await api('/api/tasks?' + q);
    $('#list').replaceChildren(...(r.data.length ? r.data.map(row) : [el('div', 'empty', 'No hay tareas con esos filtros.')]));
  } catch (e) { $('#list').replaceChildren(el('div', 'empty err', e.message)); }
}
let timer;
$('#search').oninput = () => { clearTimeout(timer); timer = setTimeout(load, 300); };
$('#fStatus').onchange = $('#fPrio').onchange = load;

$('#newForm').onsubmit = async ev => {
  ev.preventDefault();
  const m = $('#newMsg'); m.textContent = '';
  try {
    const body = {title: $('#title').value, priority: $('#prio').value};
    if ($('#due').value) body.due_date = $('#due').value;
    await api('/api/tasks', {method: 'POST', body: JSON.stringify(body)});
    ev.target.reset(); m.className = 'msg ok'; m.textContent = 'Tarea creada.'; load();
  } catch (e) { m.className = 'msg err'; m.textContent = e.message; }
};

if (token) api('/api/me').then(show, () => show(null)); else show(null);
</script>
</body>
</html>
