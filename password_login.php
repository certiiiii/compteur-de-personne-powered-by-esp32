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
  <title>Connexion</title>
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
    .card h1 { margin-bottom: 24px; color: #333; }
    .card label { display: block; text-align: left; font-size: 14px; color: #555; margin-bottom: 4px; }
    .card input {
      width: 100%; padding: 10px; border: 1px solid #ddd;
      border-radius: 8px; font-size: 15px; margin-bottom: 16px;
      box-sizing: border-box;
    }
    .card button {
      width: 100%; padding: 12px;
      background: #667eea; color: white;
      border: none; border-radius: 10px;
      font-size: 16px; cursor: pointer;
    }
    .card button:hover { opacity: 0.88; }
    #msg { margin-top: 14px; font-size: 14px; color: #c62828; }
  </style>
</head>
<body>
<div class="card">
  <h1>Connexion</h1>
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
        window.location.href = "admin3.php";   // ← JS redirect, not PHP header()
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