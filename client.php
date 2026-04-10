<?php
// =======================
// CONNEXION BDD
// =======================
$pdo = new PDO(
    "mysql:host=localhost;dbname=compteur_piece;charset=utf8",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
if (isset($_GET["action"]) && $_GET["action"] === "get") {
    $result = $pdo->query("SELECT nb_personnes FROM presence WHERE id = 1");
    echo $result->fetchColumn();
    exit;
}

// =======================
// TRAITEMENT AJAX (+ / -)
// =======================
// =======================
// REQUÊTES AJAX
// =======================
if (isset($_GET["action"])) {

    // 🔄 Rafraîchissement simple
    if ($_GET["action"] === "get") {
        $result = $pdo->query("SELECT nb_personnes FROM presence WHERE id = 1");
        echo $result->fetchColumn();
        exit;
    }

    // 📊 AFFICHAGE DU LOG
    if ($_GET["action"] === "log") {
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

    // ➕➖ MODIFICATION DU COMPTEUR
    /* if ($_GET["action"] === "plus") { */
    /*     $pdo->exec("UPDATE presence SET nb_personnes = nb_personnes + 1 WHERE id = 1"); */
    /* } */
    /**/
    /* if ($_GET["action"] === "moins") { */
    /*     $pdo->exec(" */
    /*         UPDATE presence  */
    /*         SET nb_personnes = GREATEST(nb_personnes - 1, 0)  */
    /*         WHERE id = 1 */
    /*     "); */
    /* } */

    // nouvelle valeur
    $result = $pdo->query("SELECT nb_personnes FROM presence WHERE id = 1");
    $valeur = $result->fetchColumn();

    // enregistrer dans l'historique
    $stmt = $pdo->prepare("INSERT INTO historique (action, valeur) VALUES (?, ?)");
    $stmt->execute([$_GET["action"], $valeur]);

    echo $valeur;
    exit;
}


// =======================
// AFFICHAGE INITIAL
// =======================
$result = $pdo->query("SELECT nb_personnes FROM presence WHERE id = 1");
$nbPersonnes = $result->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Compteur de présence</title>

<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    min-height: 100vh;
    background-color: #061a2f;
    background-image:
        linear-gradient(rgba(0,255,255,.12) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,255,255,.12) 1px, transparent 1px);
    background-size: 20px 20px;
    font-family: Arial, sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #eee;
    padding: 24px;
}
.wrapper {
    width: 100%;
    max-width: 420px;
}
.card {
    width: 100%;
    background: rgba(7, 24, 44, .88);
    border: 2px solid rgba(0,255,255,.35);
    box-shadow: 0 0 30px rgba(0,255,255,.18);
    border-radius: 24px;
    padding: 32px;
    text-align: center;
    backdrop-filter: blur(4px);
}
.card h1 {
    margin: 0 0 18px;
    color: #fff;
    font-size: 30px;
}
.count {
    font-size: 92px;
    font-weight: bold;
    margin: 20px 0 24px;
    color: #fff;
    text-shadow: 0 0 18px rgba(0,210,255,.22);
}
.buttons button {
    font-size: 24px;
    padding: 15px 25px;
    margin: 10px;
    border: none;
    border-radius: 14px;
    cursor: pointer;
}
select {
    width: 100%;
    margin-top: 16px;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,.18);
    font-size: 15px;
    background: rgba(255,255,255,.08);
    color: #fff;
}
#log {
    margin: 16px auto 0;
    padding: 10px 0 0;
    max-height: 220px;
    overflow-y: auto;
    width: 100%;
    text-align: left;
    font-size: 14px;
    border-top: 1px solid rgba(255,255,255,.1);
}
#log div {
    padding: 8px 0;
}
#log hr {
    border: 0;
    border-top: 1px solid rgba(255,255,255,.08);
}
.plus { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; }
.moins { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
.buttons button:hover { opacity: 0.92; }
</style>
</head>

<body>

<div class="wrapper">
<div class="card">
    <h1>👥 Présence</h1>

    <div id="count" class="count">
        <?= $nbPersonnes ?>
    </div>

    <!--
    <div class="buttons">
        <button class="moins" onclick="updateCount('moins')">−</button>
        <button class="plus" onclick="updateCount('plus')">+</button>
    </div>
    -->
</div>
<select id="logMenu" onchange="toggleLog()">
    <option value="">📊 Log</option>
    <option value="show">Afficher l'historique</option>
</select>

<div id="log" style="display:none;"></div>
</div>



<script>
let logVisible = false;

function updateCount(action) {
    fetch("client.php?action=" + action)
        .then(r => r.text())
        .then(data => {
            document.getElementById("count").innerText = data;
            if (logVisible) loadLog();
        });
}

function autoRefresh() {
    fetch("client.php?action=get")
        .then(r => r.text())
        .then(data => {
            document.getElementById("count").innerText = data;
        });
}

function toggleLog() {
    logVisible = !logVisible;
    const logDiv = document.getElementById("log");

    if (logVisible) {
        loadLog();
        logDiv.style.display = "block";
    } else {
        logDiv.style.display = "none";
    }
}

function loadLog() {
    fetch("client.php?action=log")
        .then(r => r.text())
        .then(data => {
            document.getElementById("log").innerHTML = data;
        });
}

// rafraîchissement auto du compteur
setInterval(autoRefresh, 2000);

// rafraîchissement auto du log
setInterval(() => {
    if (logVisible) loadLog();
}, 3000);
</script>


</body>
</html>
