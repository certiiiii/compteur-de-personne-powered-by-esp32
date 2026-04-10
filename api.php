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

function textResponse(string $text, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $text;
    exit;
}

function htmlResponse(string $html, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getRoomIdFromMixedId(PDO $pdo, string $rawId): ?int {
    if ($rawId === '') {
        return null;
    }

    // 1) Essayer d'abord comme identifiant ESP (ex: adresse MAC)
    $stmt = $pdo->prepare("SELECT presence_id FROM esp WHERE esp_id = ? LIMIT 1");
    $stmt->execute([$rawId]);
    $roomId = $stmt->fetchColumn();
    if ($roomId !== false) {
        return (int) $roomId;
    }

    // 2) Sinon, si c'est un entier, l'utiliser comme id de salle (compatibilité boutons web)
    if (ctype_digit($rawId)) {
        return (int) $rawId;
    }

    return null;
}

$action = $_GET['action'] ?? null;
if ($action !== null) {
    $rawId = trim((string)($_GET['id'] ?? ''));
    $roomIdParam = trim((string)($_GET['room_id'] ?? ''));
    $roomId = $roomIdParam !== '' && ctype_digit($roomIdParam)
        ? (int) $roomIdParam
        : getRoomIdFromMixedId($pdo, $rawId);

    if ($action === 'get') {
        $roomId = $roomId ?: 1;
        $stmt = $pdo->prepare("SELECT nb_personnes FROM presence WHERE id = ?");
        $stmt->execute([$roomId]);
        $count = $stmt->fetchColumn();
        textResponse((string)($count === false ? 0 : $count));
    }

    if ($action === 'log') {
        $sql = "
            SELECT h.action, h.valeur, h.date_action, h.esp_id
            FROM historique h
            LEFT JOIN esp e ON e.esp_id = h.esp_id
        ";
        $params = [];

        if ($roomId !== null) {
            $sql .= " WHERE e.presence_id = ? OR h.esp_id = ? ";
            $params[] = $roomId;
            $params[] = (string)$roomId;
        }

        $sql .= " ORDER BY h.date_action DESC LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        if (!$logs) {
            htmlResponse("<p>Aucun historique.</p>");
        }

        $html = '';
        foreach ($logs as $log) {
            $actionLabel = $log['action'] === 'plus' ? 'plus' : 'moins';
            $espLabel = htmlspecialchars((string)($log['esp_id'] ?? 'web'), ENT_QUOTES, 'UTF-8');
            $dateLabel = htmlspecialchars((string)$log['date_action'], ENT_QUOTES, 'UTF-8');
            $valueLabel = htmlspecialchars((string)$log['valeur'], ENT_QUOTES, 'UTF-8');

            $html .= "<div>"
                . "<strong>{$dateLabel}</strong><br>"
                . "action : <b>{$actionLabel}</b> → valeur : <b>{$valueLabel}</b><br>"
                . "source : <code>{$espLabel}</code>"
                . "</div><hr>";
        }

        htmlResponse($html);
    }

    if ($action === 'plus' || $action === 'moins') {
        if ($roomId === null || $roomId <= 0) {
            textResponse('ESP ou salle introuvable.', 404);
        }

        $pdo->beginTransaction();
        try {
            if ($action === 'plus') {
                $stmt = $pdo->prepare("UPDATE presence SET nb_personnes = nb_personnes + 1 WHERE id = ?");
                $stmt->execute([$roomId]);
            } else {
                $stmt = $pdo->prepare("UPDATE presence SET nb_personnes = GREATEST(nb_personnes - 1, 0) WHERE id = ?");
                $stmt->execute([$roomId]);
            }

            $stmt = $pdo->prepare("SELECT nb_personnes FROM presence WHERE id = ?");
            $stmt->execute([$roomId]);
            $value = (int)$stmt->fetchColumn();

            $logEspId = $rawId !== '' ? $rawId : (string)$roomId;
            $stmt = $pdo->prepare("INSERT INTO historique (action, valeur, esp_id) VALUES (?, ?, ?)");
            $stmt->execute([$action, $value, $logEspId]);

            $pdo->commit();
            textResponse((string)$value);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            textResponse('Erreur serveur : ' . $e->getMessage(), 500);
        }
    }

    if ($action === 'salles') {
        $rows = $pdo->query("SELECT id, `presence-name` AS name, nb_personnes FROM presence ORDER BY id")->fetchAll();
        jsonResponse($rows);
    }

    if ($action === 'setesp') {
        $espId = $rawId;
        $roomIdToBind = isset($_GET['idsalle']) && ctype_digit((string)$_GET['idsalle']) ? (int)$_GET['idsalle'] : 0;

        if ($espId === '' || $roomIdToBind <= 0) {
            textResponse('Paramètres invalides.', 400);
        }

        $stmt = $pdo->prepare("DELETE FROM esp WHERE esp_id = ?");
        $stmt->execute([$espId]);

        $stmt = $pdo->prepare("INSERT INTO esp (esp_id, presence_id) VALUES (?, ?)");
        $stmt->execute([$espId, $roomIdToBind]);

        textResponse('ok');
    }

    textResponse('Action inconnue.', 400);
}

// Affichage minimal de secours
$stmt = $pdo->prepare("SELECT nb_personnes FROM presence WHERE id = ?");
$stmt->execute([1]);
$nbPersonnes = (int)($stmt->fetchColumn() ?: 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Compteur de présence</title>
</head>
<body>
    <h1>Compteur de présence</h1>
    <p>Valeur actuelle : <strong><?= $nbPersonnes ?></strong></p>
    <p>Cette page sert surtout d'API. Utilise de préférence l'interface admin/client.</p>
</body>
</html>
