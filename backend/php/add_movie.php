<?php
require '../../config/db.php';session_start();
if(!isset($_SESSION['user_id'])){header("Location: ./welcome.php");exit;}
$title=htmlspecialchars(trim($_POST['title']??''));$year=intval($_POST['year']??0);$poster=trim($_POST['poster']??'');$uid=$_SESSION['user_id'];$org_id=$_SESSION['organizer_id'];
if(empty($title)){header("Location: ./voting.php");exit;}
if(!empty($poster)&&str_starts_with($poster,'tmdb:')){
    $url=substr($poster,5);if(!is_dir('../../uploads/posters/'))mkdir('../../uploads/posters/',0755,true);
    $fn='poster_'.uniqid().'.jpg';$dest='../../uploads/posters/'.$fn;
    $opts=['http'=>['method'=>'GET','timeout'=>10,'header'=>"User-Agent: Mozilla/5.0\r\n"],'ssl'=>['verify_peer'=>false,'verify_peer_name'=>false]];
    $data=@file_get_contents($url,false,stream_context_create($opts));
    if($data!==false&&strlen($data)>1000){file_put_contents($dest,$data);$poster='uploads/posters/'.$fn;}else $poster='uploads/posters/default.jpg';
}elseif(empty($poster)) $poster='uploads/posters/default.jpg';
$pdo->prepare("INSERT INTO movie_suggestions(organizer_id,title,year,poster,suggested_by) VALUES(?,?,?,?,?)")->execute([$org_id,$title,$year?:null,$poster,$uid]);
header("Location: ./voting.php");exit;
?>
