<?php
// ── Connexion ──────────────────────────────────────────────────────────────
$pdo = new PDO("mysql:host=localhost;dbname=compteur_piece;charset=utf8", "root", "", [
    PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
// ── Helpers ────────────────────────────────────────────────────────────────
function query(PDO $pdo, string $sql, array $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// ── API AJAX ───────────────────────────────────────────────────────────────
if (isset($_GET["action"])) {
    $action = $_GET["action"];
    $id     = intval($_GET["id"] ?? 1);

    switch ($action) {

        case "get":
            $val = query($pdo, "SELECT nb_personnes FROM presence WHERE id = ?", [$id])->fetchColumn();
            echo $val === false ? 0 : $val;
            break;

        /* case "plus": */
        /* case "moins": */
        /*     $sql = $action === "plus" */
        /*         ? "UPDATE presence SET nb_personnes = nb_personnes + 1 WHERE id = ?" */
        /*         : "UPDATE presence SET nb_personnes = GREATEST(nb_personnes - 1, 0) WHERE id = ?"; */
        /*     query($pdo, $sql, [$id]); */
        /*     $valeur = query($pdo, "SELECT nb_personnes FROM presence WHERE id = ?", [$id])->fetchColumn(); */
        /*     query($pdo, "INSERT INTO historique (action, valeur, salle_id) VALUES (?,?,?)", [$action, $valeur, $id]); */
        /*     echo $valeur; */
        /*     break; */

        case "log":
            try {
                $rows = query($pdo, "SELECT action, valeur, date_action FROM historique WHERE salle_id = ? ORDER BY date_action DESC LIMIT 20", [$id])->fetchAll();
            } catch (Exception $e) {
                $rows = query($pdo, "SELECT action, valeur, date_action FROM historique ORDER BY date_action DESC LIMIT 20")->fetchAll();
            }
            if (!$rows) { echo "<p class='empty'>Aucun historique</p>"; break; }
            foreach ($rows as $r) {
                $color = $r["action"] === "plus" ? "#4CAF50" : "#f44336";
                echo "<div class='log-row'>
                        <span class='log-date'>{$r['date_action']}</span>
                        <span style='color:{$color}'>" . ($r["action"] === "plus" ? "▲" : "▼") . " {$r['action']}</span>
                        <span class='log-val'>{$r['valeur']}</span>
                        </div>";
            }
            break;

        case "salles":
            header("Content-Type: application/json");
            $rows = query($pdo, "SELECT id, `presence-name` AS name FROM presence ORDER BY id")->fetchAll();
            echo json_encode($rows);
            break;

        case "save":
            $name = trim($_GET["name"] ?? "");
            if ($id <= 0 || $name === "") { http_response_code(400); echo "ID ou nom invalide."; break; }
            $exists = query($pdo, "SELECT COUNT(*) FROM presence WHERE id = ?", [$id])->fetchColumn();
            if ($exists) { http_response_code(409); echo "L'ID $id existe déjà."; break; }
            query($pdo, "INSERT INTO presence (id, nb_personnes, `presence-name`) VALUES (?, 0, ?)", [$id, $name]);
            echo "ok";
            break;

        default:
            http_response_code(400);
            echo "Action inconnue.";
    }
    exit;
}

// ── Données initiales ──────────────────────────────────────────────────────
$salles = query($pdo, "SELECT id, `presence-name` AS name, nb_personnes FROM presence ORDER BY id")->fetchAll();
$first  = $salles[0] ?? ["id" => 1, "nb_personnes" => 0, "name" => "—"];
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

  /* ── Carte ── */
  .card {
    background: #fff;
    border-radius: 16px;
    padding: 32px 28px;
    width: 320px;
    box-shadow: 0 12px 36px rgba(0,0,0,.2);
    text-align: center;
  }
  .card h2 { font-size: 18px; color: #555; margin-bottom: 20px; letter-spacing: .3px; }

  /* ── Compteur ── */
  #count { font-size: 88px; font-weight: 700; color: #667eea; line-height: 1; margin: 16px 0; }

  /* ── Boutons ── */
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

  /* ── Select ── */
  select {
    width: 100%; padding: 9px 12px; border-radius: 8px;
    border: 1px solid #ddd; font-size: 15px; cursor: pointer;
    color: #333; background: #fafafa;
  }

  /* ── Formulaire ajout ── */
  #form-add { margin-top: 16px; text-align: left; }
  #form-add label { display: block; font-size: 13px; color: #666; margin-bottom: 4px; }
  #form-add input {
    width: 100%; padding: 9px 10px; border: 1px solid #ddd;
    border-radius: 8px; font-size: 14px; margin-bottom: 12px;
  }
  #form-msg { margin-top: 10px; font-size: 13px; }
  .msg-ok  { color: #2e7d32; }
  .msg-err { color: #c62828; }

  /* ── Log ── */
  #log-wrap { margin-top: 16px; max-height: 200px; overflow-y: auto; text-align: left; border-top: 1px solid #eee; padding-top: 10px; }
  .log-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
  .log-date { color: #aaa; font-size: 11px; flex: 0 0 auto; margin-right: 8px; }
  .log-val  { font-weight: 700; color: #667eea; }
  .empty    { color: #bbb; text-align: center; padding: 10px 0; font-size: 13px; }
</style>
</head>
<body>

<!-- ── Carte : Nouvelle salle ───────────────────────────── -->
<!--
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
-->

<!-- ── Carte : Compteur ─────────────────────────────────── -->
<div class="card">
  <h2>Présence</h2>

  <select id="salle-select" onchange="onSalleChange()">
    <?php foreach ($salles as $s): ?>
      <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['id'] ?>)</option>
    <?php endforeach; ?>
  </select>

  <div id="count"><?= $first['nb_personnes'] ?></div>

  <!--
  <div class="btn-row">
    <button class="btn-moins" onclick="update('moins')">−</button>
    <button class="btn-plus"  onclick="update('plus')">+</button>
  </div>
  -->

  <div class="btn-row" style="margin-top:14px;">
    <button class="btn-ghost" id="log-btn" onclick="toggleLog()">📊 Historique</button>
  </div>
  <div id="log-wrap" style="display:none"></div>
</div>

<script>
const api = path => fetch("admin3.php?" + path).then(r => r.text());
const getId = () => document.getElementById("salle-select").value;
let logOpen = false;

// ── Compteur ──────────────────────────────────────────────
function update(action) {
  api("action=" + action + "&id=" + getId()).then(val => {
    document.getElementById("count").textContent = val;
    if (logOpen) loadLog();
  });
}

function refresh() {
  api("action=get&id=" + getId()).then(val => {
    document.getElementById("count").textContent = val;
  });
}

function onSalleChange() {
  refresh();
  if (logOpen) loadLog();
}

// ── Log ───────────────────────────────────────────────────
function toggleLog() {
  logOpen = !logOpen;
  const wrap = document.getElementById("log-wrap");
  const btn  = document.getElementById("log-btn");
  wrap.style.display = logOpen ? "block" : "none";
  btn.textContent = logOpen ? "✕ Fermer" : "📊 Historique";
  if (logOpen) loadLog();
}

function loadLog() {
  api("action=log&id=" + getId()).then(html => {
    document.getElementById("log-wrap").innerHTML = html;
  });
}

// ── Formulaire ajout ──────────────────────────────────────
function toggleForm() {
  const form = document.getElementById("form-add");
  const btn  = document.getElementById("btn-add-wrap");
  const visible = form.style.display === "block";
  form.style.display = visible ? "none" : "block";
  btn.style.display  = visible ? "flex" : "none";
  document.getElementById("form-msg").innerHTML = "";
}

function save() {
  const id   = document.getElementById("f-id").value.trim();
  const name = document.getElementById("f-name").value.trim();
  const msg  = document.getElementById("form-msg");

  if (!id || !name) { msg.innerHTML = "<span class='msg-err'>Veuillez remplir tous les champs.</span>"; return; }

  fetch("admin3.php?action=save&id=" + encodeURIComponent(id) + "&name=" + encodeURIComponent(name))
    .then(r => r.ok ? r.text() : r.text().then(t => { throw new Error(t); }))
    .then(() => {
      const sel = document.getElementById("salle-select");
      const opt = new Option(name + " (" + id + ")", id);
      sel.add(opt);
      sel.value = id;
      toggleForm();
      msg.innerHTML = "<span class='msg-ok'>✔ Salle « " + name + " » créée.</span>";
      setTimeout(() => msg.innerHTML = "", 3000);
      refresh();
    })
    .catch(err => { msg.innerHTML = "<span class='msg-err'>❌ " + err.message + "</span>"; });
}

// ── Rafraîchissement automatique ──────────────────────────
setInterval(refresh,  2000);
setInterval(() => { if (logOpen) loadLog(); }, 3000);
</script>
</body>
</html>
