<?php
require '../../config/db.php';session_start();
if(!isset($_SESSION['role'])||$_SESSION['role']!=='organizer'){header("Location: ./snacks.php");exit;}
$sid=intval($_POST['snack_id']??0);if($sid>0)$pdo->prepare("DELETE FROM snacks WHERE id=?")->execute([$sid]);
header("Location: ./snacks.php");exit;
?>
