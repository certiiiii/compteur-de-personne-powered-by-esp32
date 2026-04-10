<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=compteur_piece;charset=utf8",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$salles = $pdo->query("SELECT id, `presence-name` AS name, nb_personnes FROM presence ORDER BY id")->fetchAll();
$first = $salles[0] ?? ['id' => 1, 'nb_personnes' => 0, 'name' => 'Salle 1'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Présence</title>

<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    font-family: system-ui, Arial, sans-serif;
  }

  .card {
    background: #fff;
    border-radius: 16px;
    padding: 32px 28px;
    width: 320px;
    box-shadow: 0 12px 36px rgba(0,0,0,.2);
    text-align: center;
  }
  .card h2 { font-size: 18px; color: #555; margin-bottom: 20px; letter-spacing: .3px; }
  #count { font-size: 88px; font-weight: 700; color: #667eea; line-height: 1; margin: 16px 0; }
  .btn-row { display: flex; justify-content: center; gap: 12px; margin-top: 16px; }
  button {
    border: none; border-radius: 10px; cursor: pointer; font-size: 16px;
    padding: 11px 20px; transition: opacity .15s, transform .1s;
  }
  button:hover  { opacity: .88; }
  button:active { transform: scale(.96); }
  .btn-plus  { background: #4CAF50; color: #fff; font-size: 26px; padding: 12px 24px; }
  .btn-moins { background: #f44336; color: #fff; font-size: 26px; padding: 12px 24px; }
  .btn-main  { background: #667eea; color: #fff; }
  .btn-ghost { background: #f0f0f0; color: #444; }
  select {
    width: 100%; padding: 9px 12px; border-radius: 8px;
    border: 1px solid #ddd; font-size: 15px; cursor: pointer;
    color: #333; background: #fafafa;
  }
  #form-add { margin-top: 16px; text-align: left; }
  #form-add label { display: block; font-size: 13px; color: #666; margin-bottom: 4px; }
  #form-add input {
    width: 100%; padding: 9px 10px; border: 1px solid #ddd;
    border-radius: 8px; font-size: 14px; margin-bottom: 12px;
  }
  #form-msg, #form-msg-esp { margin-top: 10px; font-size: 13px; }
  .msg-ok  { color: #2e7d32; }
  .msg-err { color: #c62828; }
  #log-wrap { margin-top: 16px; max-height: 200px; overflow-y: auto; text-align: left; border-top: 1px solid #eee; padding-top: 10px; }
  .log-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
  .log-date { color: #aaa; font-size: 11px; flex: 0 0 auto; margin-right: 8px; }
  .log-val  { font-weight: 700; color: #667eea; }
  .empty    { color: #bbb; text-align: center; padding: 10px 0; font-size: 13px; }
  .log-src {
    font-size: 12px;
    color: #777;
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f2f2f2;
    text-align: left;
  }
  .input {
    width: 100%; padding: 9px 10px; border: 1px solid #ddd;
    border-radius: 8px; font-size: 14px; margin-bottom: 12px;
  }
  .lab {
    display: block; font-size: 13px; color: #666; margin-bottom: 4px; text-align: left;
  }
</style>
<style>
body {
    margin: 0;
    height: 100vh;
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #061a2f;
    background-image:
        linear-gradient(rgba(0,255,255,.12) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,255,255,.12) 1px, transparent 1px);
    background-size: 20px 20px;
    color: #eee;
}

.salle {
    position: relative;
    width: 700px;
    height: 480px;
    border: 3px solid rgba(0,255,255,.6);
    box-shadow: 0 0 40px rgba(0,255,255,.6);
}

.zone {
    position: absolute;
    background: rgba(45,0,80,.9);
    border: 2px solid #b388ff;
    border-radius: 14px;
    box-shadow: 0 0 18px rgba(179,136,255,.6);
    text-align: center;
    padding: 14px;
}

.entree { left: 40px; bottom: 60px; width: 130px; height: 100px; }
.sortie { right: 40px; bottom: 60px; width: 130px; height: 100px; }
.total { top: 60px; left: 50%; transform: translateX(-50%); width: 200px; height: 130px; }

.count {
    font-size: 56px;
    font-weight: bold;
}

button {
    font-size: 24px;
    padding: 8px 16px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    background: #b388ff;
}

.history {
    position: fixed;
    top: 20px;
    width: 250px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 15px;
    border-radius: 10px;
    background: rgba(0,150,255,0.25);
    border: 2px solid rgba(0,200,255,0.6);
}

.history-left { left: 20px; }
.history-right { right: 20px; }
</style>
</head>
<body>

<div class="card">
  <h2>Ajouter une salle</h2>
  <div id="form-add" style="display:none">
    <label>Nom</label>
    <input id="f-name" type="text" placeholder="Ex : Salle A">
    <label>ID</label>
    <input id="f-id" type="number" min="1" placeholder="Ex : 2">
    <div class="btn-row">
      <button class="btn-main" onclick="save()">Enregistrer</button>
      <button class="btn-ghost" onclick="toggleForm()">Annuler</button>
    </div>
  </div>
  <div class="btn-row" id="btn-add-wrap">
    <button class="btn-main" onclick="toggleForm()">+ Nouvelle salle</button>
  </div>
  <div id="form-msg"></div>
</div>

<div class="card">
  <h2>Ajouter un compteur</h2>
  <div id="form-add-esp" style="display:none">
    <label class="lab">ID du compteur</label>
    <input class="input" id="f-name-esp" type="text" placeholder="Ex : AA:BB:CC:DD:EE:FF">

    <label class="lab">ID de la salle</label>
    <input class="input" id="f-id-esp" type="number" min="1" placeholder="Ex : 1">

    <div class="btn-row">
      <button class="btn-main" onclick="saveEsp()">Enregistrer</button>
      <button class="btn-ghost" onclick="toggleFormEsp()">Annuler</button>
    </div>
  </div>
  <div class="btn-row" id="btn-add-wrap-esp">
    <button class="btn-main" onclick="toggleFormEsp()">+ Nouveau compteur</button>
  </div>
  <div id="form-msg-esp"></div>
</div>

<div class="card">
  <h2>Présence</h2>

  <select id="salle-select" onchange="onSalleChange()">
    <?php foreach ($salles as $s): ?>
      <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['id'] ?>)</option>
    <?php endforeach; ?>
  </select>

  <div id="count"><?= (int) $first['nb_personnes'] ?></div>

  <div class="btn-row">
    <button class="btn-moins" onclick="updateCount('moins')">−</button>
    <button class="btn-plus" onclick="updateCount('plus')">+</button>
  </div>

  <div class="btn-row" style="margin-top:14px;">
    <button class="btn-ghost" id="log-btn" onclick="toggleLog()">📊 Historique</button>
  </div>
  <div id="log-wrap" style="display:none"></div>
</div>

<script>
const apiFile = 'api.php';
const getId = () => document.getElementById('salle-select').value;
let logOpen = false;

function apiText(params) {
  return fetch(apiFile + '?' + params).then(async r => {
    const text = await r.text();
    if (!r.ok) throw new Error(text || 'Erreur');
    return text;
  });
}

function updateCount(action) {
  apiText('action=' + encodeURIComponent(action) + '&id=' + encodeURIComponent(getId()))
    .then(val => {
      document.getElementById('count').textContent = val;
      if (logOpen) loadLog();
    })
    .catch(err => alert(err.message));
}

function refresh() {
  apiText('action=get&id=' + encodeURIComponent(getId()))
    .then(val => {
      document.getElementById('count').textContent = val;
    })
    .catch(console.error);
}

function onSalleChange() {
  refresh();
  if (logOpen) loadLog();
}

function toggleLog() {
  logOpen = !logOpen;
  const wrap = document.getElementById('log-wrap');
  const btn = document.getElementById('log-btn');
  wrap.style.display = logOpen ? 'block' : 'none';
  btn.textContent = logOpen ? '✕ Fermer' : '📊 Historique';
  if (logOpen) loadLog();
}

function loadLog() {
  apiText('action=log&id=' + encodeURIComponent(getId()))
    .then(html => {
      document.getElementById('log-wrap').innerHTML = html;
    })
    .catch(console.error);
}

function toggleForm() {
  const form = document.getElementById('form-add');
  const btn = document.getElementById('btn-add-wrap');
  const visible = form.style.display === 'block';
  form.style.display = visible ? 'none' : 'block';
  btn.style.display = visible ? 'flex' : 'none';
  document.getElementById('form-msg').innerHTML = '';
}

function save() {
  const id = document.getElementById('f-id').value.trim();
  const name = document.getElementById('f-name').value.trim();
  const msg = document.getElementById('form-msg');

  if (!id || !name) {
    msg.innerHTML = "<span class='msg-err'>Veuillez remplir tous les champs.</span>";
    return;
  }

  fetch(apiFile + '?action=save&new_id=' + encodeURIComponent(id) + '&name=' + encodeURIComponent(name))
    .then(r => r.ok ? r.text() : r.text().then(t => { throw new Error(t); }))
    .then(() => {
      const sel = document.getElementById('salle-select');
      const opt = new Option(name + ' (' + id + ')', id);
      sel.add(opt);
      sel.value = id;
      document.getElementById('f-id').value = '';
      document.getElementById('f-name').value = '';
      toggleForm();
      msg.innerHTML = "<span class='msg-ok'>✔ Salle « " + name + " » créée.</span>";
      setTimeout(() => { msg.innerHTML = ''; }, 3000);
      refresh();
    })
    .catch(err => {
      msg.innerHTML = "<span class='msg-err'>❌ " + err.message + '</span>';
    });
}

function toggleFormEsp() {
  const form = document.getElementById('form-add-esp');
  const btn = document.getElementById('btn-add-wrap-esp');
  const visible = form.style.display === 'block';
  form.style.display = visible ? 'none' : 'block';
  btn.style.display = visible ? 'flex' : 'none';
  document.getElementById('form-msg-esp').innerHTML = '';
}

function saveEsp() {
  const salleId = document.getElementById('f-id-esp').value.trim();
  const espId = document.getElementById('f-name-esp').value.trim();
  const msg = document.getElementById('form-msg-esp');

  if (!salleId || !espId) {
    msg.innerHTML = "<span class='msg-err'>Veuillez remplir tous les champs.</span>";
    return;
  }

  fetch(apiFile + '?action=setesp&id=' + encodeURIComponent(espId) + '&idsalle=' + encodeURIComponent(salleId))
    .then(r => r.ok ? r.text() : r.text().then(t => { throw new Error(t); }))
    .then(() => {
      document.getElementById('f-id-esp').value = '';
      document.getElementById('f-name-esp').value = '';
      toggleFormEsp();
      msg.innerHTML = "<span class='msg-ok'>✔ Compteur « " + espId + " » enregistré.</span>";
      setTimeout(() => { msg.innerHTML = ''; }, 3000);
      refresh();
    })
    .catch(err => {
      msg.innerHTML = "<span class='msg-err'>❌ " + err.message + '</span>';
    });
}

setInterval(refresh, 2000);
setInterval(() => { if (logOpen) loadLog(); }, 3000);
</script>
</body>
</html>
