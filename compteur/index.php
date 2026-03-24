<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=compteur_piece;charset=utf8",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// =======================
// API (AJAX + ESP32)
// =======================
if (isset($_GET["action"])) {

    $action = $_GET["action"];
    $esp_id = intval($_GET["id"] ?? 1); // id=1 par défaut pour les boutons du site

    // 🔄 Rafraîchissement compteur
    if ($action === "get") {
        echo $pdo->query("SELECT nb_personnes FROM presence WHERE id=1")->fetchColumn();
        exit;
    }

    // 📊 Affichage log
    if ($action === "log") {
        $logs = $pdo->query("
            SELECT action, valeur, date_action 
            FROM historique 
            ORDER BY date_action DESC 
            LIMIT 20
        ");

        foreach ($logs as $log) {
            echo "<div>
                    <strong>{$log['date_action']}</strong><br>
                    action : <b>{$log['action']}</b> → 
                    valeur : <b>{$log['valeur']}</b>
                  </div><hr>";
        }
        exit;
    }

    // ➕➖ Modification
    if (($action === "plus" || $action === "moins") && $esp_id >= 1) {

        // Trouver la salle liée à cet ESP
        $salle_id = $pdo->query("SELECT presence_id FROM esp WHERE esp_id = $esp_id")->fetchColumn();

        if ($salle_id) {

            if ($action === "plus") {
                $pdo->exec("UPDATE presence SET nb_personnes = nb_personnes + 1 WHERE id = $salle_id");
            } else {
                $pdo->exec("UPDATE presence SET nb_personnes = GREATEST(nb_personnes - 1,0) WHERE id = $salle_id");
            }

            // récupérer nouvelle valeur
            $valeur = $pdo->query("SELECT nb_personnes FROM presence WHERE id = $salle_id")->fetchColumn();

            // enregistrer historique
            $stmt = $pdo->prepare("INSERT INTO historique (action, valeur, esp_id) VALUES (?, ?, ?)");
            $stmt->execute([$action, $valeur, $esp_id]);

            echo $valeur;
            exit;
        }
    }
}

// =======================
// Affichage initial
// =======================
$nbPersonnes = $pdo->query("SELECT nb_personnes FROM presence WHERE id=1")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Compteur de présence</title>

<style>
body {
    margin:0;
    height:100vh;
    background: linear-gradient(135deg,#667eea,#764ba2);
    font-family: Arial, sans-serif;
    display:flex;
    align-items:center;
    justify-content:center;
}

.card {
    background:white;
    padding:40px;
    border-radius:20px;
    text-align:center;
    width:340px;
    box-shadow:0 15px 40px rgba(0,0,0,0.25);
}

.count {
    font-size:80px;
    font-weight:bold;
    margin:20px 0;
    color:#667eea;
}

button {
    font-size:22px;
    padding:12px 20px;
    margin:10px;
    border:none;
    border-radius:10px;
    cursor:pointer;
}

.plus { background:#4CAF50; color:white; }
.moins { background:#f44336; color:white; }

#log {
    margin-top:20px;
    max-height:200px;
    overflow-y:auto;
    font-size:14px;
    text-align:left;
    display:none;
    border-top:1px solid #ddd;
    padding-top:10px;
}

select {
    margin-top:15px;
    padding:8px;
    border-radius:6px;
}
</style>
</head>

<body>

<div class="card">
    <h1>👥 Présence</h1>

    <div id="count" class="count">
        <?= $nbPersonnes ?>
    </div>

    <button class="moins" onclick="updateCount('moins')">−</button>
    <button class="plus" onclick="updateCount('plus')">+</button>

    <br>

    <select onchange="toggleLog()">
        <option>📊 Log</option>
        <option>Afficher / Masquer</option>
    </select>

    <div id="log"></div>
</div>

<script>
let logVisible = false;
let esp_id = 1; // utilisé par les boutons du site

function updateCount(action) {
    fetch("index.php?action=" + action + "&id=" + esp_id)
        .then(r => r.text())
        .then(data => {
            document.getElementById("count").innerText = data;
            if(logVisible) loadLog();
        });
}

function autoRefresh() {
    fetch("index.php?action=get")
        .then(r => r.text())
        .then(data => {
            document.getElementById("count").innerText = data;
        });
}

function toggleLog() {
    logVisible = !logVisible;
    const logDiv = document.getElementById("log");

    if(logVisible) {
        loadLog();
        logDiv.style.display = "block";
    } else {
        logDiv.style.display = "none";
    }
}

function loadLog() {
    fetch("index.php?action=log")
        .then(r => r.text())
        .then(data => {
            document.getElementById("log").innerHTML = data;
        });
}

setInterval(autoRefresh, 2000);
setInterval(() => { if(logVisible) loadLog(); }, 3000);
</script>

</body>
</html>
