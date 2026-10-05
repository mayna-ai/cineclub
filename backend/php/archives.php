<?php
require '../../config/db.php';session_start();
if(!isset($_SESSION['user_id'])){header("Location: ./welcome.php");exit;}
$org_id=$_SESSION['organizer_id'];
$sessions=$pdo->prepare("SELECT s.*,AVG(r.rating) AS avg_r,COUNT(DISTINCT sp.id) AS np,GROUP_CONCAT(DISTINCT r.comment SEPARATOR ' | ') AS comments FROM sessions s LEFT JOIN session_ratings r ON r.session_id=s.id LEFT JOIN session_participants sp ON sp.session_id=s.id AND sp.status='attending' WHERE s.status='past' AND s.organizer_id=? GROUP BY s.id ORDER BY s.session_date DESC");
$sessions->execute([$org_id]);$sessions=$sessions->fetchAll();
$total=count($sessions);$ratings=array_filter(array_column($sessions,'avg_r'));$avg=!empty($ratings)?round(array_sum($ratings)/count($ratings),1):0;
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Archives - CineClub</title>
<link rel="stylesheet" href="../../frontend/css/style.css">
</head><body>
<?php include '../includes/navbar.php';?>
<div class="page-body"><div class="container">
<div class="page-header"><div><h1 class="page-title">ARCH<span>IVES</span></h1><p class="subtitle">Relive past movie nights</p></div></div>
<div class="stat-row"><span class="stat-chip stat-red">🎬 <?=$total?> movie nights</span><span class="stat-chip stat-gray">⭐ Avg: <?=$avg?></span></div>
<?php if(empty($sessions)):?><div class="empty-state"><div class="empty-icon">🗂</div><h3>No archives yet</h3><p>Past movie nights will appear here.</p></div>
<?php else:?>
<div class="arch-grid">
<?php foreach($sessions as $s):?>
<div class="arch-card">
    <?php if($s['avg_r']):?><div class="arch-badge">⭐ <?=number_format($s['avg_r'],1)?></div><?php endif;?>
    <img src="/projet_web_cineclub/<?=htmlspecialchars($s['movie_poster'])?>" onerror="this.src='../../uploads/posters/default.png'" alt="<?=htmlspecialchars($s['movie_title'])?>">
    <div class="arch-info">
        <h3><?=htmlspecialchars($s['movie_title'])?></h3>
        <p>📅 <?=date('M j, Y',strtotime($s['session_date']))?> · 👥 <?=$s['np']?> participants</p>
        <?php if($s['comments']):?><p style="font-style:italic;color:var(--text2)">"<?=htmlspecialchars(mb_strimwidth($s['comments'],0,70,'...'))?>"</p><?php endif;?>
    </div>
</div>
<?php endforeach;?></div>
<?php endif;?>
</div></div>
</body></html>