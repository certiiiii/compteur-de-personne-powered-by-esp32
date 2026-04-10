<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=compteur_piece;charset=utf8",
    "root", "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function query(PDO $pdo, string $sql, array $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// ── AJAX endpoint ──────────────────────────────────────────
if (isset($_GET["passwd"]) && isset($_GET["username"])) {
    $username = $_GET["username"];
    $passwd   = $_GET["passwd"];

    $val = query($pdo, "SELECT passwd FROM mot_de_passe WHERE username = ?", [$username])->fetchColumn();

    // Return "ok" or "fail" — JS handles the redirect
    echo ($val !== false && $val === $passwd) ? "ok" : "fail";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion</title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
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
      padding: 24px;
    }
    .panel {
      width: 100%;
      max-width: 440px;
      background: rgba(7, 24, 44, .88);
      border: 2px solid rgba(0,255,255,.35);
      box-shadow: 0 0 30px rgba(0,255,255,.18);
      border-radius: 24px;
      padding: 36px;
      backdrop-filter: blur(4px);
    }
    .brand {
      font-size: 24px;
      font-weight: bold;
      color: #fff;
      margin-bottom: 8px;
    }
    h1 {
      margin: 0 0 10px;
      color: #fff;
      font-size: 32px;
    }
    .subtitle {
      margin: 0 0 24px;
      color: #c7d7e8;
      line-height: 1.6;
      font-size: 15px;
    }
    label {
      display: block;
      text-align: left;
      font-size: 14px;
      color: #d9f9ff;
      margin-bottom: 6px;
    }
    input {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid rgba(0,255,255,.18);
      border-radius: 14px;
      font-size: 15px;
      margin-bottom: 16px;
      box-sizing: border-box;
      background: rgba(255,255,255,.06);
      color: #fff;
      outline: none;
    }
    input::placeholder { color: #8fb3c9; }
    input:focus {
      border-color: rgba(0,210,255,.65);
      box-shadow: 0 0 0 3px rgba(0,210,255,.14);
    }
    button {
      width: 100%;
      text-align: center;
      padding: 14px 18px;
      border-radius: 14px;
      font-weight: bold;
      font-size: 16px;
      transition: transform .15s ease, opacity .15s ease, box-shadow .15s ease;
      border: none;
      cursor: pointer;
      background: linear-gradient(135deg, #00d2ff, #3a7bd5);
      color: white;
      box-shadow: 0 10px 25px rgba(58,123,213,.35);
    }
    button:hover {
      transform: translateY(-1px);
      opacity: .95;
    }
    #msg {
      margin-top: 14px;
      font-size: 14px;
      color: #ff9f9f;
      min-height: 20px;
      text-align: center;
    }
  </style>
</head>
<body>
<div class="panel">
  <div class="brand">Esp Présence</div>
  <h1>Connexion</h1>
  <p class="subtitle">Accès à l’interface d’administration du compteur de présence.</p>

  <label>Identifiant</label>
  <input id="username" type="text" placeholder="Votre login">
  <label>Mot de passe</label>
  <input id="passwd" type="password" placeholder="Votre mot de passe">
  <button onclick="connection()">Se connecter</button>
  <div id="msg"></div>
</div>

<script>
function connection() {
  const username = document.getElementById("username").value.trim();
  const passwd   = document.getElementById("passwd").value;
  const msg      = document.getElementById("msg");

  if (!username || !passwd) {
    msg.textContent = "Veuillez remplir tous les champs.";
    return;
  }

  fetch("password_login.php?username=" + encodeURIComponent(username)
                          + "&passwd="  + encodeURIComponent(passwd))
    .then(r => r.text())
    .then(result => {
      if (result.trim() === "ok") {
        window.location.href = "admin3.php";
      } else {
        msg.textContent = "Identifiant ou mot de passe incorrect.";
      }
    })
    .catch(() => {
      msg.textContent = "Erreur réseau, réessayez.";
    });
}
</script>
</body>
</html>
