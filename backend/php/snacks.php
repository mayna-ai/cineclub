<?php
require '../../config/db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ./welcome.php"); exit; }

$org_id = $_SESSION['organizer_id'];
$isOrg  = $_SESSION['role'] === 'organizer';

// Prochaine session du club
$sess = $pdo->prepare("
    SELECT * FROM sessions
    WHERE organizer_id=? AND status='upcoming'
    ORDER BY session_date ASC LIMIT 1
");
$sess->execute([$org_id]);
$session = $sess->fetch();

$snacks = [];
if ($session) {
    $s = $pdo->prepare("
        SELECT s.*, u.username AS aname
        FROM snacks s
        LEFT JOIN users u ON u.id = s.assigned_to
        WHERE s.session_id = ?
        ORDER BY s.id ASC
    ");
    $s->execute([$session['id']]);
    $snacks = $s->fetchAll();
}

$nc = count(array_filter($snacks, fn($s) => $s['status']==='confirmed'));
$np = count(array_filter($snacks, fn($s) => $s['status']==='pending'));
$nu = count(array_filter($snacks, fn($s) => $s['status']==='unassigned'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Snacks - CineClub</title>
    <link rel="stylesheet" href="../../frontend/css/style.css">
    <style>
    .btn-del-snack {
        display: flex; align-items: center; justify-content: center;
        width: 100%; margin-top: 6px; padding: 7px; gap: 5px;
        background: transparent; color: rgba(255,100,100,.65);
        border: 1px solid rgba(229,9,20,.25); border-radius: 4px;
        font-size: 11px; font-weight: 600; cursor: pointer; transition: all .18s;
    }
    .btn-del-snack:hover { background: rgba(229,9,20,.12); color: #ff6b6b; border-color: rgba(229,9,20,.5); }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="page-body">
<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">SNACK <span>STATION</span></h1>
            <p class="subtitle">Organize who brings what for movie night</p>
        </div>
        <?php if ($isOrg): ?>
        <button class="btn-red" onclick="tryAddSnack()">+ Add Snack</button>
        <?php endif; ?>
    </div>

    <?php if (!empty($snacks)): ?>
    <div class="status-row">
        <span class="badge badge-green">✓ <?=$nc?> confirmed</span>
        <span class="badge badge-orange">⏳ <?=$np?> pending</span>
        <span class="badge badge-dark">⚠ <?=$nu?> unassigned</span>
    </div>
    <?php endif; ?>

    <?php if (!$session): ?>
    <!-- Pas de session : message info -->
    <div class="empty-state">
        <div class="empty-icon">🍿</div>
        <h3>No session yet</h3>
        <p>You have to create a session first to be able to add new snacks!.</p>
        <?php if ($isOrg): ?>
        <a href="./planning.php" class="btn-red" style="margin-top:14px">
            📅 Go to Planning
        </a>
        <?php endif; ?>
    </div>

    <?php elseif (empty($snacks)): ?>
    <div class="empty-state">
        <div class="empty-icon">🍿</div>
        <h3>No snacks yet</h3>
        <?php if ($isOrg): ?>
        <p>Add snacks for the next evening !</p>
        <button class="btn-red" style="margin-top:14px"
                onclick="document.getElementById('m-snack').style.display='flex'">
            + Add First Snack
        </button>
        <?php else: ?>
        <p>No snacks added by organizer.</p>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="snacks-grid">
        <?php foreach ($snacks as $sn): ?>
        <div class="card snack-card">
            <div class="snack-top">
                <span class="snack-emoji"><?= htmlspecialchars($sn['emoji']) ?></span>
                <?php if ($sn['status']==='confirmed'): ?>
                    <span class="badge badge-green">✓ Confirmed</span>
                <?php elseif ($sn['status']==='pending'): ?>
                    <span class="badge badge-orange">⏳ Pending</span>
                <?php else: ?>
                    <span class="badge badge-dark">⚠ Unassigned</span>
                <?php endif; ?>
            </div>
            <div class="snack-name"><?= htmlspecialchars($sn['name']) ?></div>
            <?php if ($sn['aname']): ?>
                <p class="snack-by">Brought by <strong><?= htmlspecialchars($sn['aname']) ?></strong></p>
                <?php if ($isOrg && $sn['status']==='pending'): ?>
                <form method="POST" action="./confirm_snack.php">
                    <input type="hidden" name="snack_id" value="<?= $sn['id'] ?>">
                    <button class="btn-conf">✓ Confirm</button>
                </form>
                <?php endif; ?>
            <?php else: ?>
                <p class="snack-none">No one assigned yet</p>
                <form method="POST" action="./volunteer_snack.php">
                    <input type="hidden" name="snack_id" value="<?= $sn['id'] ?>">
                    <button class="btn-vol">🙋 Volunteer</button>
                </form>
            <?php endif; ?>
            <?php if ($isOrg): ?>
            <form method="POST" action="./delete_snack.php">
                <input type="hidden" name="snack_id" value="<?= $sn['id'] ?>">
                <button type="submit" class="btn-del-snack">🗑 Delete snack</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</div>

<!-- Modal Add Snack -->
<div id="m-snack" class="modal-bg" style="display:none"
     onclick="if(event.target===this)this.style.display='none'">
    <div class="modal">
        <h2>🍿 Add a Snack</h2>
        <form method="POST" action="./add_snack.php">
            <input type="hidden" name="session_id" value="<?= $session['id'] ?? 0 ?>">
            <input type="hidden" name="emoji" id="e-val" value="🍿">
            <div class="form-field">
                <label>Nom du snack *</label>
                <input type="text" name="name" id="sn-name"
                       placeholder="Pizza, Popcorn, Nachos..."
                       oninput="suggestEmoji(this.value)" required>
            </div>
            <div class="emoji-sug" id="e-sug">
                <span class="sug-emoji" id="e-icon">🍿</span>
                <div class="sug-text">
                    <strong id="e-name">Popcorn</strong>Emoji suggéré
                </div>
                <button type="button" class="sug-ok" onclick="acceptEmoji()">✓ OK</button>
            </div>
            <div class="modal-btns">
                <button type="submit" class="btn-red">Add Snack</button>
                <button type="button" class="btn-dark"
                    onclick="document.getElementById('m-snack').style.display='none'">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast "Add session first" -->
<div id="toast-no-session"
     style="display:none;position:fixed;bottom:30px;left:50%;transform:translateX(-50%);
            background:#e50914;color:#fff;padding:12px 24px;border-radius:8px;
            font-size:14px;font-weight:600;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.5)">
    ⚠ Add session first! Go to Planning.
</div>

<script>
    function tryAddSnack() {
        <?php if ($session): ?>
            document.getElementById('m-snack').style.display = 'flex';
        <?php else: ?>
            const t = document.getElementById('toast-no-session');
            t.style.display = 'block';
            setTimeout(() => t.style.display = 'none', 3000);
        <?php endif; ?>
    }
    function openSnackModal()  { resetSnackForm(); document.getElementById('m-snack').style.display='flex'; }
    function closeSnackModal() { document.getElementById('m-snack').style.display='none'; resetSnackForm(); }

    function resetSnackForm() {
        document.getElementById('snack-form').reset();
        document.getElementById('sn-name').value = '';
        document.getElementById('e-val').value   = '🍿';
        document.getElementById('e-sug').classList.remove('show');
        document.getElementById('e-icon').textContent = '🍿';
        curE = '🍿';
    }

    const emap = {
        'pizza':'🍕','popcorn':'🍿','nachos':'🧀','chips':'🥔','frites':'🍟',
        'burger':'🍔','hotdog':'🌭','hot dog':'🌭','sandwich':'🥪','tacos':'🌮',
        'sushi':'🍣','ramen':'🍜','pates':'🍝','riz':'🍚','soupe':'🍲',
        'salade':'🥗','glace':'🍦','gateau':'🎂','cake':'🍰','cookie':'🍪',
        'chocolat':'🍫','bonbons':'🍬','donut':'🍩','croissant':'🥐',
        'fromage':'🧀','cheese':'🧀','coca':'🥤','soda':'🥤','jus':'🧃',
        'eau':'💧','cafe':'☕','coffee':'☕','the':'🍵','tea':'🍵',
        'biere':'🍺','beer':'🍺','vin':'🍷','cocktail':'🍹','smoothie':'🥤',
        'pomme':'🍎','banane':'🍌','raisin':'🍇','fraise':'🍓','orange':'🍊',
        'citron':'🍋','ananas':'🍍','mangue':'🥭','avocat':'🥑','carotte':'🥕',
        'mais':'🌽','cacahuete':'🥜','peanut':'🥜','nutella':'🍫',
        'muffin':'🧁','cupcake':'🧁','brownie':'🍫','pretzel':'🥨',
    };
    let curE = '🍿';
    function suggestEmoji(t) {
        const low = t.toLowerCase().trim();
        const box = document.getElementById('e-sug');
        if (!low) { box.classList.remove('show'); return; }
        let found = null;
        for (const [k, e] of Object.entries(emap)) {
            if (low.includes(k) || k.includes(low)) { found = {k, e}; break; }
        }
        if (found) {
            curE = found.e;
            document.getElementById('e-icon').textContent = found.e;
            document.getElementById('e-name').textContent = found.k.charAt(0).toUpperCase() + found.k.slice(1);
            document.getElementById('e-val').value = found.e;
            box.classList.add('show');
        } else {
            document.getElementById('e-val').value = '🍿';
            box.classList.remove('show');
        }
    }
    function acceptEmoji() {
        document.getElementById('e-val').value = curE;
        document.getElementById('e-sug').classList.remove('show');
    }
</script>
</body>
</html>