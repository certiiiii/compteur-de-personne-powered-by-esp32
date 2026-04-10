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
<title>Compteur de présence</title>

<style>
body {
    margin: 0;
    height: 100vh;
    background: linear-gradient(135deg, #667eea, #764ba2);
    font-family: Arial, sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card {
    background: white;
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    width: 320px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.25);
}

.count {
    font-size: 80px;
    font-weight: bold;
    margin: 20px 0;
    color: #667eea;
}

.buttons button {
    font-size: 24px;
    padding: 15px 25px;
    margin: 10px;
    border: none;
    border-radius: 12px;
    cursor: pointer;
}
select {
    margin-top: 20px;
    padding: 10px;
    border-radius: 8px;
    font-size: 16px;
}
#log {
    margin: 20px auto;
    padding: 10px;
    max-height: 220px;
    overflow-y: auto;
    width: 100%;
    text-align: left;
    font-size: 14px;
    border-top: 1px solid #ddd;
}



.plus { background: #4CAF50; color: white; }
.moins { background: #f44336; color: white; }

.buttons button:hover {
    opacity: 0.85;
}
</style>
</head>

<body>

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

<div id="log" style="display:none; margin-top:20px; max-height:200px; overflow:auto; text-align:left;"></div>



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
