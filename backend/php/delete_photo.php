<?php
require '../../config/db.php';session_start();
if(!isset($_SESSION['user_id'])){header("Location: ./gallery.php");exit;}
$pid=intval($_POST['photo_id']??0);
if($pid>0){
    $s=$pdo->prepare("SELECT image_path FROM gallery WHERE id=?");$s->execute([$pid]);$p=$s->fetch();
    if($p){$f='../'.$p['image_path'];if(file_exists($f))@unlink($f);$pdo->prepare("DELETE FROM gallery WHERE id=?")->execute([$pid]);}
}
header("Location: ./gallery.php");exit;
?>
