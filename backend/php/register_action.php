<?php
require '../../config/db.php';
session_start();
if ($_SERVER['REQUEST_METHOD']!=='POST'){header("Location: ./welcome.php");exit;}

$username = htmlspecialchars(trim($_POST['username']??''));
$email    = htmlspecialchars(trim($_POST['email']??''));
$password = $_POST['password']??'';

if(empty($username)||empty($email)||empty($password)){
    header("Location: ./welcome.php?error=empty&tab=register");exit;}
if(strlen($password)<6){
    header("Location: ./welcome.php?error=short_pass&tab=register");exit;}

$c=$pdo->prepare("SELECT id FROM users WHERE email=?");$c->execute([$email]);
if($c->fetch()){header("Location: ./welcome.php?error=email_taken&tab=register");exit;}

$hash=password_hash($password,PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO users(username,email,password) VALUES(?,?,?)")
    ->execute([$username,$email,$hash]);

// Rediriger vers login avec message succès
header("Location: ./welcome.php?tab=login&success=1");exit;
?>
