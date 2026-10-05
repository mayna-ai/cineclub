<?php
require '../../config/db.php';session_start();
if(!isset($_SESSION['role'])||$_SESSION['role']!=='organizer'){header("Location: ./planning.php");exit;}
$sid=intval($_POST['session_id']??0);
if($sid>0){
    $pdo->prepare("DELETE FROM session_participants WHERE session_id=?")->execute([$sid]);
    $pdo->prepare("DELETE FROM snacks WHERE session_id=?")->execute([$sid]);
    $pdo->prepare("DELETE FROM session_ratings WHERE session_id=?")->execute([$sid]);
    $pdo->prepare("UPDATE gallery SET session_id=NULL WHERE session_id=?")->execute([$sid]);
    $pdo->prepare("DELETE FROM sessions WHERE id=? AND organizer_id=?")->execute([$sid,$_SESSION['organizer_id']]);
}
header("Location: ./planning.php");exit;
?>
