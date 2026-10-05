<?php
require '../../config/db.php'; session_start();
if(!isset($_SESSION['user_id'])){header("Location: ./welcome.php");exit;}
$org_id=$_SESSION['organizer_id'];
$my_votes=[];
$s=$pdo->prepare("SELECT movie_id FROM votes WHERE user_id=?");$s->execute([$_SESSION['user_id']]);
$my_votes=$s->fetchAll(PDO::FETCH_COLUMN);

$movies=$pdo->prepare("SELECT ms.*,u.username AS sname,COUNT(v.id) AS nb 
FROM movie_suggestions ms 
LEFT JOIN votes v ON ms.id=v.movie_id 
LEFT JOIN users u ON ms.suggested_by=u.id 
WHERE ms.organizer_id=? 
GROUP BY ms.id 
ORDER BY nb DESC");
$movies->execute([$org_id]);$movies=$movies->fetchAll();

define('TMDB_KEY','1395bb1aef008f52fb48c0ed3de7f864');
$isOrg=$_SESSION['role']==='organizer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Voting - CineClub</title>
<link rel="stylesheet" href="../../frontend/css/style.css">
</head>
<body>

<?php include '../includes/navbar.php';?>

<div class="page-body">
<div class="container">

<div class="page-header">
    <div>
        <h1 class="page-title">MOVIE <span>VOTING</span></h1>
        <p class="subtitle">Suggest movies and vote for the next session</p>
    </div>
    <button class="btn-red" onclick="openSuggest()">+ Suggest Movie</button>
</div>

<input type="text" id="searchInput" placeholder="Search movies..." class="search-bar" oninput="filterMovies()">

<?php if(empty($movies)):?>
<div class="empty-state">
    <div class="empty-icon">🎬</div>
    <h3>No movies yet</h3>
    <p>Be the first to suggest a movie!</p>
</div>
<?php else:?>

<div class="voting-grid" id="votingGrid">
<?php foreach($movies as $m): $voted=in_array($m['id'],$my_votes);?>
<div class="vcard" data-title="<?=strtolower(htmlspecialchars($m['title']))?>">
    <img src="/projet_web_cineclub/<?=htmlspecialchars($m['poster'])?>" 
        onerror="this.src='../../uploads/posters/default.png'" alt="">
    
    <div style="position:absolute;top:7px;left:7px">
        <span class="mcard-badge">⭐ <?=$m['nb']?></span>
    </div>

    <div class="vcard-body">
        <h3><?=htmlspecialchars($m['title'])?></h3>
        <p><?=$m['year']?> · <?=htmlspecialchars($m['sname']??'?')?></p>

        <div class="vcard-actions">

            <form method="POST" action="./vote.php">
                <input type="hidden" name="movie_id" value="<?=$m['id']?>">
                <button class="vbtn <?=$voted?'voted':''?>">
                    <?=$voted?'✓ Voted':'👍 Vote'?>
                </button>
            </form>

            <?php if($isOrg):?>
            <form method="POST" action="./delete_movie.php">
                <input type="hidden" name="movie_id" value="<?=$m['id']?>">
                <button class="vbtn del" type="submit">🗑 Delete</button>
            </form>
            <?php endif;?>

        </div>
    </div>
</div>
<?php endforeach;?>
</div>

<?php endif;?>
</div>
</div>

<!-- MODAL -->
<div id="m-suggest" class="modal-bg" style="display:none" onclick="if(event.target===this)closeSuggest()">
<div class="modal">

    <h2>🎬 Suggest a Movie</h2>

    <div class="form-field">
        <label>Auto search</label>
        <div class="tmdb-wrap">
            <input type="text" id="tmdb-q"
                   placeholder="Type movie title..."
                   oninput="searchTMDB(this.value)"
                   autocomplete="off"
                   style="width:100%;padding:11px 13px;background:var(--bg3);border:1px solid var(--border);border-radius:4px;color:#fff;font-size:13px">
            <div class="tmdb-drop" id="tmdb-drop"></div>
        </div>
    </div>

    <form method="POST" action="./add_movie.php" id="suggest-form">

        <div class="form-field">
            <label>Title *</label>
            <input type="text" name="title" id="f-title" required>
        </div>

        <div class="form-field">
            <label>Year</label>
            <input type="number" name="year" id="f-year">
        </div>

        <div id="poster-preview" style="display:none;margin-top:10px;align-items:center;gap:10px">
            <img id="poster-img" src="" style="width:60px;height:88px;object-fit:cover;border-radius:3px;">
            <span style="font-size:12px;color:var(--text3)">Poster fetched automatically</span>
        </div>

        <input type="hidden" name="poster" id="f-poster" value="uploads/posters/default.png">

        <div class="modal-btns">
            <button type="submit" class="btn-red">Add Movie</button>
            <button type="button" class="btn-dark" onclick="closeSuggest()">Cancel</button>
        </div>
    </form>

</div>
</div>

<script>

function filterMovies(){
    const v=document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.vcard').forEach(c=>{
        c.style.display=c.dataset.title.includes(v)?'':'none';
    });
}

function openSuggest(){
    resetForm();
    document.getElementById('m-suggest').style.display='flex';
}

function closeSuggest(){
    document.getElementById('m-suggest').style.display='none';
    resetForm();
}

function resetForm(){
    document.getElementById('suggest-form').reset();
    document.getElementById('f-poster').value='uploads/posters/default.png';
    document.getElementById('tmdb-q').value='';
    document.getElementById('tmdb-drop').innerHTML='';
    document.getElementById('tmdb-drop').style.display='none';
    document.getElementById('poster-preview').style.display='none';
    document.getElementById('poster-img').src='';
}

let tmdbTimer;

function searchTMDB(q){
    clearTimeout(tmdbTimer);
    const drop=document.getElementById('tmdb-drop');

    if(q.length<2){drop.style.display='none';return;}

    tmdbTimer=setTimeout(async()=>{
        const KEY='<?=TMDB_KEY?>';

        try{
            const r=await fetch(`https://api.themoviedb.org/3/search/movie?api_key=${KEY}&query=${encodeURIComponent(q)}&language=en-US`);
            const d=await r.json();
            showDrop(d.results?.slice(0,6)||[]);
        }catch(e){
            drop.style.display='none';
        }
    },350);
}

function showDrop(results){
    const drop=document.getElementById('tmdb-drop');
    if(!results.length){drop.style.display='none';return;}

    drop.innerHTML=results.map(m=>`
        <div class="tmdb-item" onclick='selectMovie(${JSON.stringify(m).replace(/'/g,"&#39;")})'>
            <img src="${m.poster_path?'https://image.tmdb.org/t/p/w92'+m.poster_path:'../../uploads/posters/default.png'}">
            <div class="tmdb-item-info">
                <strong>${m.title}</strong>
                <span>${m.release_date?m.release_date.substring(0,4):''}</span>
            </div>
        </div>
    `).join('');

    drop.style.display='block';
}

function selectMovie(m){
    document.getElementById('f-title').value=m.title;
    document.getElementById('f-year').value=m.release_date?m.release_date.substring(0,4):'';
    document.getElementById('tmdb-q').value=m.title;
    document.getElementById('tmdb-drop').style.display='none';

    if(m.poster_path){
        const url='https://image.tmdb.org/t/p/w500'+m.poster_path;
        document.getElementById('poster-img').src=url;
        document.getElementById('poster-preview').style.display='flex';
        document.getElementById('f-poster').value='tmdb:'+url;
    }
}

document.addEventListener('click',e=>{
    if(!e.target.closest('#tmdb-q') && !e.target.closest('#tmdb-drop'))
        document.getElementById('tmdb-drop').style.display='none';
});

</script>
</body>
</html>