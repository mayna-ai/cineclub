<?php
require '../../config/db.php';
session_start();
if($_SERVER['REQUEST_METHOD']!=='POST'){header("Location: ./welcome.php");exit;}

$email      = trim($_POST['email']??'');
$password   = $_POST['password']??'';
$login_type = $_POST['login_type']??'organizer'; // 'organizer' ou 'member'
$code       = strtoupper(trim($_POST['invite_code']??''));

if(empty($email)||empty($password)){
    header("Location: ./welcome.php?error=empty&tab=login");exit;}

// Vérifier email + password
$s=$pdo->prepare("SELECT * FROM users WHERE email=?");$s->execute([$email]);$user=$s->fetch();
if(!$user||!password_verify($password,$user['password'])){
    header("Location: ./welcome.php?error=wrong&tab=login&role=$login_type");exit;}

if($login_type==='organizer'){
    // Connexion organisateur → créer/récupérer son code fixe
    $invCode = getOrgCode($pdo,$user['id']);
    session_regenerate_id(true);
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['username']    = $user['username'];
    $_SESSION['role']        = 'organizer';
    $_SESSION['organizer_id']= $user['id']; // il voit SA propre interface
    header("Location: ./index.php");exit;

} else {
    // Connexion membre → vérifier le code
    if(empty($code)){
        header("Location: ./welcome.php?error=empty&tab=login&role=member");exit;}

    $org = getOrgByCode($pdo,$code);
    if(!$org){
        header("Location: ./welcome.php?error=invalid_code&tab=login&role=member");exit;}

    session_regenerate_id(true);
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['username']    = $user['username'];
    $_SESSION['role']        = 'member';
    $_SESSION['organizer_id']= $org['id']; // il voit l'interface DE CET organisateur
    header("Location: ./index.php");exit;
}
?>
