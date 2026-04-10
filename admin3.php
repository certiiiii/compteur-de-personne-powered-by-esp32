<?php
// ── Connexion ──────────────────────────────────────────────────────────────
$pdo = new PDO("mysql:host=localhost;dbname=compteur_piece;charset=utf8", "root", "", [
    PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
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

        case "plus":
        case "moins":
            $sql = $action === "plus"
                ? "UPDATE presence SET nb_personnes = nb_personnes + 1 WHERE id = ?"
                : "UPDATE presence SET nb_personnes = GREATEST(nb_personnes - 1, 0) WHERE id = ?";
            query($pdo, $sql, [$id]);
            $valeur = query($pdo, "SELECT nb_personnes FROM presence WHERE id = ?", [$id])->fetchColumn();
            query($pdo, "INSERT INTO historique (action, valeur, salle_id) VALUES (?,?,?)", [$action, $valeur, $id]);
            echo $valeur;
            break;

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

        case "get_salle": // requete que fera l'arduino pour connaitre l'id de salle auquel il est assigne 
          $v = query($pdo, "SELECT presence_id FROM esp WHERE id=?", $_GET["id"])->fetchColumn();
          echo $v;
          break;
        case "setesp": // commande pour mise a jour via formulaire


          /* $response = query($pdo, "select * from esp;")->fetchAll(); */
          /* var_dump($response); */
          /* var_dump($_GET['id']); */
          $v = query($pdo, "DELETE FROM esp WHERE esp_id=?;", [$_GET['id']]);
          /* var_dump($v); */
          $v = query($pdo, "insert into esp (esp_id, presence_id) values (?, ?)",[$_GET['id'], $_GET['idsalle']]);
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Présence</title>

<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
    padding: 24px;
    background-color: #061a2f;
    background-image:
      linear-gradient(rgba(0,255,255,.12) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0,255,255,.12) 1px, transparent 1px);
    background-size: 20px 20px;
    font-family: Arial, sans-serif;
    color: #eee;
  }

  .card {
    width: 100%;
    max-width: 360px;
    background: rgba(7, 24, 44, .88);
    border: 2px solid rgba(0,255,255,.35);
    box-shadow: 0 0 30px rgba(0,255,255,.18);
    border-radius: 24px;
    padding: 32px;
    backdrop-filter: blur(4px);
    text-align: center;
  }
  .card h2 {
    font-size: 26px;
    color: #fff;
    margin-bottom: 20px;
    letter-spacing: .2px;
  }

  #count {
    font-size: 92px;
    font-weight: 700;
    color: #fff;
    line-height: 1;
    margin: 20px 0 24px;
    text-shadow: 0 0 18px rgba(0,210,255,.22);
  }

  .btn-row {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 16px;
    flex-wrap: wrap;
  }
  button {
    border: none;
    border-radius: 14px;
    cursor: pointer;
    font-size: 16px;
    padding: 12px 18px;
    transition: transform .15s ease, opacity .15s ease, box-shadow .15s ease;
  }
  button:hover  { transform: translateY(-1px); opacity: .95; }
  button:active { transform: scale(.98); }
  .btn-plus  {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    font-size: 26px;
    padding: 12px 24px;
    box-shadow: 0 10px 25px rgba(34,197,94,.25);
  }
  .btn-moins {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    font-size: 26px;
    padding: 12px 24px;
    box-shadow: 0 10px 25px rgba(239,68,68,.25);
  }
  .btn-main  {
    background: linear-gradient(135deg, #00d2ff, #3a7bd5);
    color: #fff;
    box-shadow: 0 10px 25px rgba(58,123,213,.35);
  }
  .btn-ghost {
    background: rgba(255,255,255,.08);
    color: #fff;
    border: 1px solid rgba(255,255,255,.18);
  }

  select,
  .input,
  #form-add input {
    width: 100%;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid rgba(0,255,255,.18);
    font-size: 15px;
    cursor: pointer;
    color: #fff;
    background: rgba(255,255,255,.06);
    margin-bottom: 10px;
  }

  #form-add, #form-add-esp { margin-top: 16px; text-align: left; }
  #form-add label,
  .lab {
    display: block;
    font-size: 13px;
    color: #d9f9ff;
    margin-bottom: 6px;
    text-align: left;
  }

  #form-msg,
  #form-msg-esp {
    margin-top: 10px;
    font-size: 13px;
    min-height: 20px;
  }
  .msg-ok  { color: #86efac; }
  .msg-err { color: #fca5a5; }

  #log-wrap {
    margin-top: 16px;
    max-height: 220px;
    overflow-y: auto;
    text-align: left;
    border-top: 1px solid rgba(255,255,255,.1);
    padding-top: 10px;
  }
  .log-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,.06);
    font-size: 13px;
  }
  .log-date { color: #8fb3c9; font-size: 11px; flex: 0 0 auto; margin-right: 8px; }
  .log-val  { font-weight: 700; color: #d9f9ff; }
  .empty    { color: #9fb6c9; text-align: center; padding: 10px 0; font-size: 13px; }

  @media (max-width: 980px) {
    body { gap: 18px; }
    .card { max-width: 420px; }
  }
</style>
</head>
<body>

<!-- ── Carte : Nouvelle salle ───────────────────────────── -->
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

<!-- ── Carte : Nouvel esp ───────────────────────────── -->
<div class="card">
  <h2>Ajouter un compteur</h2>
  <div id="form-add-esp" style="display:none">
    <label class="lab">id du compteur</label>
    <input class="input" id="f-name-esp" type="text" min="1" placeholder="Ex : AA:BB:CC:DD:EE:FF">
    <label class="lab">id de la salle</label>
    <input class="input" id="f-id-esp" type="number" min="1" placeholder="Ex : 1">

    <div class="btn-row">
      <button class="btn-main" onclick="saveEsp()">Enregistrer</button>
      <button class="btn-ghost" onclick="toggleFormEsp()">Annuler</button>
    </div>
  </div>
  <div class="btn-row" id="btn-add-wrap-esp">
    <button class="btn-main" onclick="toggleFormEsp()">+ Nouvelle salle</button>
  </div>
  <div id="form-msg-esp"></div>
</div>

<!-- ── Carte : Compteur ─────────────────────────────────── -->
<div class="card">
  <h2>Présence</h2>

  <select id="salle-select" onchange="onSalleChange()">
    <?php foreach ($salles as $s): ?>
      <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['id'] ?>)</option>
    <?php endforeach; ?>
  </select>

  <div id="count"><?= $first['nb_personnes'] ?></div>

  <div class="btn-row">
    <button class="btn-moins" onclick="update('moins')">−</button>
    <button class="btn-plus"  onclick="update('plus')">+</button>
  </div>

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

// ── Formulaire ajout d'un esp ──────────────────────────────────────
function toggleFormEsp() {
  const form = document.getElementById("form-add-esp");
  const btn  = document.getElementById("btn-add-wrap-esp");
  const visible = form.style.display === "block";
  form.style.display = visible ? "none" : "block";
  btn.style.display  = visible ? "flex" : "none";
  document.getElementById("form-msg-esp").innerHTML = "";
}

function saveEsp() {
  const id   = document.getElementById("f-id-esp").value.trim(); // id de la salle 
  const name = document.getElementById("f-name-esp").value.trim(); // id de l'esp
  const msg  = document.getElementById("form-msg-esp");

  if (!id || !name) { msg.innerHTML = "<span class='msg-err'>Veuillez remplir tous les champs.</span>"; return; }

  fetch("admin3.php?action=setesp&id=" + encodeURIComponent(name) + "&idsalle=" + encodeURIComponent(id)) // id c'est idesp
    .then(r => r.ok ? r.text() : r.text().then(t => { throw new Error(t); }))
    .then(r => {
      toggleForm();
      msg.innerHTML = "<span class='msg-ok'>✔ esp « " + r+ " » créée.</span>";
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
