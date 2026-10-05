<?php
require '../../config/db.php'; session_start();
if(!isset($_SESSION['user_id'])){header("Location: ./welcome.php");exit;}

$org_id=$_SESSION['organizer_id'];
$uid=$_SESSION['user_id'];
$isOrg=$_SESSION['role']==='organizer';

$sess=$pdo->prepare("SELECT * FROM sessions WHERE organizer_id=? AND status='upcoming' ORDER BY session_date ASC LIMIT 1");
$sess->execute([$org_id]);
$session=$sess->fetch();

$ptcs=[];
$ac=0;
$nc=0;
$my=null;

if($session){
    $s=$pdo->prepare("SELECT u.id,u.username,sp.status 
    FROM session_participants sp 
    JOIN users u ON u.id=sp.user_id 
    WHERE sp.session_id=? 
    ORDER BY u.username ASC");
    $s->execute([$session['id']]);
    $ptcs=$s->fetchAll();

    foreach($ptcs as $p){
        if($p['status']==='attending') $ac++;
        else $nc++;
    }

    $s2=$pdo->prepare("SELECT status FROM session_participants WHERE session_id=? AND user_id=?");
    $s2->execute([$session['id'],$uid]);
    $row=$s2->fetch();
    $my=$row?$row['status']:null;
}

$code=null;
if($isOrg) $code=getOrgCode($pdo,$uid);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Participants - CineClub</title>
<link rel="stylesheet" href="../../frontend/css/style.css">
</head>

<body>
<?php include '../includes/navbar.php';?>

<div class="page-body">
<div class="container">

<div class="page-header">
    <div>
        <h1 class="page-title">PARTI<span>CIPANTS</span></h1>
        <p class="subtitle">
        <?=$session
            ? 'Movie night: <strong>'.htmlspecialchars($session['movie_title']).'</strong> — '.date('d/m/Y',strtotime($session['session_date']))
            : 'See who\'s coming to the next movie night'?>
        </p>
    </div>

    <?php if($isOrg):?>
    <button class="btn-red" onclick="document.getElementById('m-invite').style.display='flex'">
        🔗 Invitation Code
    </button>
    <?php endif;?>
</div>

<?php if(!$session && $isOrg):?>
<div class="empty-state">
    <div class="empty-icon">🎬</div>
    <h3>No session yet</h3>
    <p>Create a session from Planning!</p>
    <a href="./planning.php" class="btn-red" style="margin-top:14px">Go to Planning</a>
</div>

<?php elseif(!$session):?>
<div class="empty-state">
    <div class="empty-icon">🎬</div>
    <h3>No session yet</h3>
    <p>The organizer hasn't scheduled a movie night yet.</p>
</div>

<?php elseif(empty($ptcs)):?>
<div class="empty-state">
    <div class="empty-icon">👥</div>
    <h3>No participants yet</h3>

    <?php if($isOrg):?>
        <p>Share your invitation code!</p>
        <button class="btn-red" style="margin-top:14px"
                onclick="document.getElementById('m-invite').style.display='flex'">
            🔗 Show Code
        </button>
    <?php else:?>
        <p>The organizer hasn't invited members yet.</p>
    <?php endif;?>
</div>

<?php else:?>

<div class="status-row">
    <span class="badge badge-green">✓ <?=$ac?> attending</span>
    <span class="badge badge-gray">✗ <?=$nc?> can't make it</span>
</div>

<div class="ptc-grid">
<?php foreach($ptcs as $p):?>
<div class="card ptc-card <?=$p['status']==='not_attending'?'absent':''?>">

    <div class="ptc-av <?=$p['status']==='not_attending'?'gray':'red'?>">
        <?=strtoupper(mb_substr($p['username'],0,1))?>
    </div>

    <div class="ptc-info">
        <strong>
            <?=htmlspecialchars($p['username'])?>
            <?=$p['id']==$org_id?' 👑':''?>
        </strong>
        <span><?=$p['id']==$org_id?'Organizer':'Member'?></span>
    </div>

    <span class="ptc-check <?=$p['status']==='attending'?'ok':'no'?>">
        <?=$p['status']==='attending'?'✓':'✗'?>
    </span>

</div>
<?php endforeach;?>
</div>

<div class="card rsvp-box" style="margin-top:20px">
    <h3>Your RSVP :</h3>

    <div class="btns">
        <form method="POST" action="./update_attendance.php">
            <input type="hidden" name="session_id" value="<?=$session['id']?>">
            <button name="status" value="attending"
                    class="btn-red <?=$my==='attending'?'btn-active':''?>">
                ✓ I'm attending
            </button>
        </form>

        <form method="POST" action="./update_attendance.php">
            <input type="hidden" name="session_id" value="<?=$session['id']?>">
            <button name="status" value="not_attending"
                    class="btn-dark <?=$my==='not_attending'?'btn-active':''?>">
                ✗ Can't make it
            </button>
        </form>
    </div>
</div>

<?php endif;?>
</div>
</div>

<?php if($isOrg && $code):?>
<div id="m-invite" class="modal-bg" style="display:none"
     onclick="if(event.target===this)this.style.display='none'">

<div class="modal">

    <h2>🔗 Invitation Code</h2>

    <p style="font-size:13px;color:var(--text2);margin-bottom:14px">
        This code is <strong>unique and permanent</strong>. Share it with your friends.
        They use it to log in as members.
    </p>

    <div class="code-box">
        <span class="code-val"><?=$code?></span>

        <button class="btn-copy"
        onclick="navigator.clipboard.writeText('<?=$code?>').then(()=>{
            this.textContent='✓ Copied!';
            this.style.color='var(--green)';
            setTimeout(()=>{
                this.textContent='📋 Copy';
                this.style.color='';
            },2000)
        })">
            📋 Copy
        </button>
    </div>

    <p class="code-hint">
        Your friends create an account on CineClub, then log in as
        <strong>Member</strong> using this code.
    </p>

    <button class="btn-dark"
            style="width:100%;margin-top:14px;justify-content:center"
            onclick="document.getElementById('m-invite').style.display='none'">
        Close
    </button>

</div>
</div>
<?php endif;?>

</body>
</html>