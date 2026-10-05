<?php
require '../../config/db.php';session_start();
if(!isset($_SESSION['user_id'])){header("Location: ./welcome.php");exit;}
$org_id=$_SESSION['organizer_id'];$uid=$_SESSION['user_id'];
if(isset($_FILES['photo'])&&$_FILES['photo']['error']===0){
    $allowed=['image/jpeg','image/jpg','image/png','image/webp','image/gif'];
    $ftype=mime_content_type($_FILES['photo']['tmp_name']);
    if(in_array($ftype,$allowed)){
        $ext=pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION);$fn='photo_'.uniqid().'.'.$ext;$dest='../../uploads/gallery/'.$fn;
        if(!is_dir('../../uploads/gallery/'))mkdir('../../uploads/gallery/',0755,true);
        if(move_uploaded_file($_FILES['photo']['tmp_name'],$dest)){
            $cap=htmlspecialchars(trim($_POST['caption']??''));
            $sid=intval($_POST['session_id']??0)?:null;
            $pdo->prepare("INSERT INTO gallery(organizer_id,session_id,image_path,caption,uploaded_by) VALUES(?,?,?,?,?)")->execute([$org_id,$sid,'uploads/gallery/'.$fn,$cap,$uid]);
        }
    }
}
header("Location: ./gallery.php");exit;
?>
