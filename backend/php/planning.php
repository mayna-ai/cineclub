<?php
require '../../config/db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ./welcome.php"); exit; }

$org_id = $_SESSION['organizer_id'];
$isOrg  = $_SESSION['role'] === 'organizer';

// Auto-archivage
$pdo->prepare("
    UPDATE sessions SET status='past'
    WHERE organizer_id=? AND status='upcoming'
    AND TIMESTAMP(session_date,session_time) < NOW()
")->execute([$org_id]);

$errors = []; $success = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && $isOrg) {
    $movie_id = intval($_POST['movie_id']??0);
    $d = $_POST['session_date']??'';
    $h = $_POST['session_time']??'20:00';
    $loc = htmlspecialchars(trim($_POST['location']??''));
    if(!$movie_id) $errors[]='Sélectionne un film.';
    if(empty($d))  $errors[]='La date est obligatoire.';
    elseif(strtotime($d)<strtotime('today')) $errors[]='La date doit être dans le futur.';
    if(empty($errors)){
        $sm=$pdo->prepare("SELECT title,poster FROM movie_suggestions WHERE id=? AND organizer_id=?");
        $sm->execute([$movie_id,$org_id]); $mv=$sm->fetch();
        if($mv){
            $pdo->prepare("INSERT INTO sessions(organizer_id,movie_title,movie_poster,session_date,session_time,location,status) VALUES(?,?,?,?,?,?,'upcoming')")
                ->execute([$org_id,$mv['title'],$mv['poster'],$d,$h,$loc]);
            $sid=$pdo->lastInsertId();
            $pdo->prepare("INSERT IGNORE INTO session_participants(session_id,user_id,status) VALUES(?,?,'attending')")->execute([$sid,$_SESSION['user_id']]);
            $success='Session ajoutée !';
        }
    }
}

$upcoming=$pdo->prepare("SELECT s.*,COUNT(sp.id) AS pcount FROM sessions s LEFT JOIN session_participants sp ON sp.session_id=s.id AND sp.status='attending' WHERE s.organizer_id=? AND s.status='upcoming' GROUP BY s.id ORDER BY s.session_date ASC,s.session_time ASC");
$upcoming->execute([$org_id]); $upcoming=$upcoming->fetchAll();
$dates=$pdo->prepare("SELECT session_date FROM sessions WHERE organizer_id=? AND status='upcoming'");
$dates->execute([$org_id]); $dates=$dates->fetchAll(PDO::FETCH_COLUMN);
$mlist=$pdo->prepare("SELECT id,title,poster,year FROM movie_suggestions WHERE organizer_id=? ORDER BY votes DESC,title ASC");
$mlist->execute([$org_id]); $mlist=$mlist->fetchAll();
$cy=intval($_GET['year']??date('Y')); $cm=intval($_GET['month']??date('m'));
if($cm<1){$cm=12;$cy--;} if($cm>12){$cm=1;$cy++;}
?>
<!DOCTYPE html><html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Planning - CineClub</title>
<link rel="stylesheet" href="../../frontend/css/style.css">
<style>
.mp-wrap{position:relative;}
.movie-picker-drop{position:absolute;top:100%;left:0;right:0;background:#1a1a1a;border:1px solid var(--border);border-radius:0 0 4px 4px;max-height:280px;overflow-y:auto;z-index:200;display:none;}
.movie-picker-drop.open{display:block;}
.mp-item{display:flex;align-items:center;gap:12px;padding:10px 12px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .12s;}
.mp-item:last-child{border-bottom:none;}
.mp-item:hover{background:var(--bg4);}
.mp-item.selected{background:rgba(229,9,20,.15);border-left:3px solid var(--red);}
.mp-img{width:36px;height:54px;object-fit:cover;border-radius:3px;flex-shrink:0;background:var(--bg4);}
.mp-info strong{font-size:13px;display:block;}
.mp-info span{font-size:11px;color:var(--text3);}
.mp-preview{display:none;align-items:center;gap:10px;margin-top:8px;padding:10px 12px;background:rgba(229,9,20,.1);border:1px solid rgba(229,9,20,.3);border-radius:4px;}
.mp-preview.show{display:flex;}
.mp-preview img{width:30px;height:44px;object-fit:cover;border-radius:3px;}
.mp-preview span{font-size:13px;font-weight:600;flex:1;}
.mp-preview button{background:none;border:none;color:var(--text3);cursor:pointer;font-size:16px;}
.mp-input{width:100%;padding:11px 13px;background:var(--bg3);border:1px solid var(--border);border-radius:4px;color:#fff;font-size:13px;transition:border-color .18s;}
.mp-input:focus{outline:none;border-color:var(--red);}
</style>
</head><body>
<?php include '../includes/navbar.php';?>
<div class="page-body"><div class="container">
<div class="page-header">
    <div><h1 class="page-title">PLANNING & <span>CALENDAR</span></h1><p class="subtitle">Schedule and view upcoming movie sessions</p></div>
    <?php if($isOrg):?><button class="btn-red" onclick="openPlanModal()">+ Add Session</button><?php endif;?>
</div>
<?php if($success):?><div class="alert alert-success">✓ <?=$success?></div><?php endif;?>
<?php foreach($errors as $e):?><div class="alert alert-error">⚠ <?=$e?></div><?php endforeach;?>
<div class="planning-grid">
    <div class="card cal-box">
        <div class="cal-top">
            <a href="?month=<?=$cm-1?>&year=<?=$cy?>" class="cal-nav-btn">‹</a>
            <h2><?=date('F Y',mktime(0,0,0,$cm,1,$cy))?></h2>
            <a href="?month=<?=$cm+1?>&year=<?=$cy?>" class="cal-nav-btn">›</a>
        </div>
        <div class="cal-grid7">
            <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d):?><div class="cal-dn"><?=$d?></div><?php endforeach;?>
            <?php $fd=date('w',mktime(0,0,0,$cm,1,$cy));$dm=date('t',mktime(0,0,0,$cm,1,$cy));$today=date('Y-m-d');
            for($i=0;$i<$fd;$i++) echo '<div></div>';
            for($d=1;$d<=$dm;$d++){$ds=sprintf('%04d-%02d-%02d',$cy,$cm,$d);$cls='cal-day';if($ds===$today)$cls.=' today';if(in_array($ds,$dates))$cls.=' has-s';echo "<div class='$cls'>$d";if(in_array($ds,$dates))echo "<div class='cal-dot'></div>";echo "</div>";}?>
        </div>
        <div class="cal-legend">
            <span><span style="background:var(--red);display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px"></span>Session</span>
            <span><span style="background:transparent;border:2px solid var(--red);display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px"></span>Today</span>
        </div>
    </div>
    <div>
        <h2 class="section-title" style="margin-bottom:14px">UPCOMING <span>SESSIONS</span></h2>
        <?php if(empty($upcoming)):?>
        <div class="empty-state"><div class="empty-icon">📅</div><h3>No sessions yet</h3><?php if($isOrg):?><p>Add your first session !</p><?php endif;?></div>
        <?php else:?><div class="sess-list">
        <?php foreach($upcoming as $i=>$s):?>
        <div class="card sess-card <?=$i===0?'next-s':''?>">
            <span class="badge <?=$i===0?'sess-badge-next':'sess-badge-planned'?>"><?=$i===0?'NEXT':'PLANNED'?></span>
            <div class="sess-title"><?=htmlspecialchars(strtoupper($s['movie_title']))?></div>
            <div class="sess-meta">
                <span><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5C3.89 3 3 3.9 3 5v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg><?=date('l, F j, Y',strtotime($s['session_date']))?></span>
                <span><svg viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm.5 5v5.3l4.5 2.7-.7 1.2L11 13V7z"/></svg><?=substr($s['session_time'],0,5)?></span>
                <?php if(!empty($s['location'])):?><span><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg><?=htmlspecialchars($s['location'])?></span><?php endif;?>
                <span><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg><?=$s['pcount']?> attending</span>
            </div>
            <div class="sess-actions">
                <a href="./participants.php" class="btn-ghost" style="font-size:12px;padding:6px 12px">Participants</a>
                <a href="./snacks.php" class="btn-ghost" style="font-size:12px;padding:6px 12px">Snacks</a>
                <?php if($isOrg):?><form method="POST" action="./delete_session.php" style="margin:0"><input type="hidden" name="session_id" value="<?=$s['id']?>"><button type="submit" class="btn-ghost" style="font-size:12px;padding:6px 12px;color:#ff6b6b;border-color:rgba(255,107,107,.3)">🗑</button></form><?php endif;?>
            </div>
        </div>
        <?php endforeach;?></div>
        <?php endif;?>
    </div>
</div>
</div></div>

<?php if($isOrg):?>
<div id="m-plan" class="modal-bg" style="display:none" onclick="if(event.target===this)closePlanModal()">
<div class="modal" style="max-width:520px">
    <h2>📅 Add Session</h2>
    <?php if(!empty($mlist)):?>
    <form method="POST" id="plan-form">
        <input type="hidden" name="movie_id" id="sel-mid" value="">
        <div class="form-field"><label>Films * (from Voting)</label>
        <div class="mp-wrap">
            <input type="text" id="mp-q" class="mp-input" placeholder="search for suggested movie..." oninput="filterMp(this.value)" onfocus="document.getElementById('mp-drop').classList.add('open')" autocomplete="off">
            <div class="movie-picker-drop" id="mp-drop">
            <?php foreach($mlist as $m):?>
            <div class="mp-item" data-id="<?=$m['id']?>" data-title="<?=htmlspecialchars($m['title'])?>" data-poster="<?=htmlspecialchars($m['poster'])?>" onclick="selectMp(this)">
                <img class="mp-img" src="/projet_web_cineclub/<?=htmlspecialchars($m['poster'])?>" onerror="this.src='../../uploads/posters/default.jpg'" alt="">
                <div class="mp-info"><strong><?=htmlspecialchars($m['title'])?></strong><span><?=$m['year']?></span></div>
            </div>
            <?php endforeach;?>
            </div>
        </div>
        <div class="mp-preview" id="mp-prev"><img id="mp-prev-img" src="" alt=""><span id="mp-prev-t"></span><button type="button" onclick="clearMp()">✕</button></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-field"><label>Date *</label><input type="date" name="session_date" id="plan-date" min="<?=date('Y-m-d')?>" required></div>
            <div class="form-field"><label>Time *</label><input type="time" name="session_time" id="plan-time" value="20:00" required></div>
        </div>
        <div class="form-field"><label>Place</label><input type="text" name="location" id="plan-loc" placeholder="Ex: at Alice, cinema..."></div>
        <p style="font-size:11px;color:var(--text3);margin-top:8px">ℹ The session will automatically move to Archives once the date and time have passed.</p>
        <div class="modal-btns">
            <button type="submit" class="btn-red" id="mp-submit" disabled style="opacity:.5;cursor:not-allowed">Add Session</button>
            <button type="button" class="btn-dark" onclick="closePlanModal()">Cancel</button>
        </div>
    </form>
    <?php else:?>
    <div style="text-align:center;padding:20px;color:var(--text3)"><p style="font-size:32px;margin-bottom:10px">🎬</p><p>No movies in voting.</p><p style="font-size:12px;margin-top:6px">Go add films from <a href="/cineclub/voting.php" style="color:var(--red)">Voting</a>.</p></div>
    <div class="modal-btns" style="justify-content:flex-end"><button class="btn-dark" onclick="closePlanModal()">close</button></div>
    <?php endif;?>
</div>
</div>

<script>
function openPlanModal()  { resetPlanForm(); document.getElementById('m-plan').style.display='flex'; }
function closePlanModal() { document.getElementById('m-plan').style.display='none'; resetPlanForm(); }

function resetPlanForm() {
    document.getElementById('sel-mid').value   = '';
    document.getElementById('mp-q').value      = '';
    document.getElementById('plan-date').value = '';
    document.getElementById('plan-time').value = '20:00';
    document.getElementById('plan-loc').value  = '';
    document.getElementById('mp-prev').classList.remove('show');
    document.getElementById('mp-drop').classList.remove('open');
    document.querySelectorAll('.mp-item').forEach(i => i.classList.remove('selected'));
    const btn = document.getElementById('mp-submit');
    btn.disabled = true; btn.style.opacity='0.5'; btn.style.cursor='not-allowed';
}

function filterMp(q){
    const drop=document.getElementById('mp-drop');drop.classList.add('open');
    drop.querySelectorAll('.mp-item').forEach(i=>{i.style.display=(!q||i.dataset.title.toLowerCase().includes(q.toLowerCase()))?'':'none';});
}
function selectMp(el){
    document.getElementById('sel-mid').value=el.dataset.id;
    document.getElementById('mp-q').value=el.dataset.title;
    document.getElementById('mp-drop').classList.remove('open');
    document.getElementById('mp-prev-img').src='/projet_web_cineclub/'+el.dataset.poster;
    document.getElementById('mp-prev-t').textContent=el.dataset.title;
    document.getElementById('mp-prev').classList.add('show');
    document.querySelectorAll('.mp-item').forEach(i=>i.classList.remove('selected'));
    el.classList.add('selected');
    const btn=document.getElementById('mp-submit');
    btn.disabled=false;btn.style.opacity='1';btn.style.cursor='pointer';
}
function clearMp(){
    document.getElementById('sel-mid').value='';
    document.getElementById('mp-q').value='';
    document.getElementById('mp-prev').classList.remove('show');
    document.querySelectorAll('.mp-item').forEach(i=>i.classList.remove('selected'));
    const btn=document.getElementById('mp-submit');
    btn.disabled=true;btn.style.opacity='0.5';btn.style.cursor='not-allowed';
}
document.addEventListener('click',e=>{if(!e.target.closest('.mp-wrap'))document.getElementById('mp-drop')?.classList.remove('open');});
</script>
<?php if(!empty($errors)):?><script>openPlanModal();</script><?php endif;?>
<?php endif;?>
</body></html>