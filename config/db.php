<?php
$host   = 'localhost';
$dbname = 'cineclub';
$user   = 'root';
$pass   = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:40px;color:#ff6b6b;background:#141414'>
        <h2>Erreur BDD</h2><p>".$e->getMessage()."</p>
        <small style='color:#888'>Vérifie que MySQL est démarré dans XAMPP</small>
    </div>");
}

// Génère un code unique XXXX-XXXX
function generateCode(PDO $pdo): string {
    $c = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $p1 = $p2 = '';
        for ($i=0;$i<4;$i++) $p1 .= $c[random_int(0,strlen($c)-1)];
        for ($i=0;$i<4;$i++) $p2 .= $c[random_int(0,strlen($c)-1)];
        $code = $p1.'-'.$p2;
        $s = $pdo->prepare("SELECT id FROM invitations WHERE code=?");
        $s->execute([$code]);
    } while ($s->fetch());
    return $code;
}

// Retourne ou crée le code fixe de l'organisateur
function getOrgCode(PDO $pdo, int $orgId): string {
    $s = $pdo->prepare("SELECT code FROM invitations WHERE organizer_id=?");
    $s->execute([$orgId]);
    $row = $s->fetch();
    if ($row) return $row['code'];
    $code = generateCode($pdo);
    $pdo->prepare("INSERT INTO invitations(organizer_id,code) VALUES(?,?)")->execute([$orgId,$code]);
    return $code;
}

// Retourne l'organisateur par code
function getOrgByCode(PDO $pdo, string $code): ?array {
    $s = $pdo->prepare("SELECT u.* FROM invitations i JOIN users u ON u.id=i.organizer_id WHERE i.code=?");
    $s->execute([strtoupper(trim($code))]);
    return $s->fetch() ?: null;
}
?>
