<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Esp Présence</title>
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
.wrapper {
    width: 100%;
    max-width: 980px;
    display: grid;
    grid-template-columns: 1.15fr .85fr;
    gap: 28px;
    align-items: center;
}
.panel {
    background: rgba(7, 24, 44, .88);
    border: 2px solid rgba(0,255,255,.35);
    box-shadow: 0 0 30px rgba(0,255,255,.18);
    border-radius: 24px;
    padding: 36px;
    backdrop-filter: blur(4px);
}
.title {
    font-size: 46px;
    line-height: 1.05;
    margin: 0 0 14px;
    color: #fff;
}
.subtitle {
    margin: 0 0 26px;
    font-size: 18px;
    line-height: 1.6;
    color: #c7d7e8;
}
.badges {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 18px;
}
.badge {
    padding: 10px 14px;
    border-radius: 999px;
    border: 1px solid rgba(0,255,255,.25);
    background: rgba(0,255,255,.08);
    color: #d9f9ff;
    font-size: 14px;
}
.logo-wrap {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}
.logo {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(0,255,255,.2);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.brand {
    font-size: 24px;
    font-weight: bold;
    color: #fff;
}
.card-actions h2 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #fff;
    font-size: 28px;
}
.card-actions p {
    margin: 0 0 24px;
    color: #c7d7e8;
    line-height: 1.6;
}
.actions {
    display: grid;
    gap: 14px;
}
.btn {
    display: block;
    width: 100%;
    text-align: center;
    text-decoration: none;
    padding: 14px 18px;
    border-radius: 14px;
    font-weight: bold;
    font-size: 16px;
    transition: transform .15s ease, opacity .15s ease, box-shadow .15s ease;
}
.btn:hover {
    transform: translateY(-1px);
    opacity: .95;
}
.btn-primary {
    background: linear-gradient(135deg, #00d2ff, #3a7bd5);
    color: white;
    box-shadow: 0 10px 25px rgba(58,123,213,.35);
}
.btn-secondary {
    background: rgba(255,255,255,.08);
    color: #fff;
    border: 1px solid rgba(255,255,255,.18);
}
.note {
    margin-top: 18px;
    font-size: 13px;
    color: #9fb6c9;
    text-align: center;
}
@media (max-width: 860px) {
    .wrapper {
        grid-template-columns: 1fr;
    }
    .title {
        font-size: 38px;
    }
}
</style>
</head>
<body>
    <div class="wrapper">
        <section class="panel">
            <div class="logo-wrap">
                <div class="logo">
                    <img src="logo.png" alt="Logo Esp Présence">
                </div>
                <div class="brand">Esp Présence</div>
            </div>

            <h1 class="title">Compteur de personnes avec ESP32</h1>
            <p class="subtitle">
                Suivi en temps réel des entrées et sorties avec capteurs infrarouges,
                ESP32 et interface web PHP/MySQL.
            </p>

            <div class="badges">
                <span class="badge">ESP32</span>
                <span class="badge">Capteurs infrarouges</span>
                <span class="badge">PHP / MySQL</span>
                <span class="badge">Temps réel</span>
            </div>
        </section>

        <aside class="panel card-actions">
            <h2>Accès au projet</h2>
            <p>
                Choisis l’accès que tu veux ouvrir. L’administration passe par une
                page de connexion dédiée.
            </p>

            <div class="actions">
                <a class="btn btn-primary" href="login.php">Connexion administrateur</a>
                <a class="btn btn-secondary" href="client.php">Voir l’affichage public</a>
            </div>

            <div class="note">
                Aucun lien direct vers la page admin n’est affiché ici.
            </div>
        </aside>
    </div>
</body>
</html>
