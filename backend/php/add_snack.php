<?php
require '../../config/db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'organizer') {
    header("Location: ./snacks.php"); exit;
}

$sid  = intval($_POST['session_id'] ?? 0);
$name = htmlspecialchars(trim($_POST['name']  ?? ''));
$emoji= htmlspecialchars(trim($_POST['emoji'] ?? '🍿'));

// Sécurité : session_id = 0 ou inexistant → ne pas insérer
if ($sid <= 0 || empty($name)) {
    header("Location: ./snacks.php"); exit;
}

// Vérifier que la session existe vraiment
$check = $pdo->prepare("SELECT id FROM sessions WHERE id=?");
$check->execute([$sid]);
if (!$check->fetch()) {
    header("Location: ./snacks.php"); exit;
}

$pdo->prepare("INSERT INTO snacks(session_id,name,emoji,status) VALUES(?,?,?,'unassigned')")
    ->execute([$sid, $name, $emoji]);

header("Location: ./snacks.php"); exit;
?>