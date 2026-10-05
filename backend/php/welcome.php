<?php
require '../../config/db.php';
session_start();
if (isset($_SESSION['user_id'])) { header("Location: ./index.php"); exit; }

$error = $_GET['error'] ?? '';
$tab   = $_GET['tab']   ?? 'login';
$role  = $_GET['role']  ?? 'organizer';

$msgs = [
    'wrong'         => 'Incorrect email or password.',
    'empty'         => 'Please fill in all fields.',
    'short_pass'    => 'Password too short (min 6 characters).',
    'email_taken'   => 'This email is already in use.',
    'invalid_code'  => 'Invalid invitation code.',
    'wrong_role'    => 'This account does not exist with this role. Check the selected tab.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>CineClub — Organize your movie nights</title>
    <link rel="stylesheet" href="../../frontend/css/style.css">
</head>
<body class="welcome-page">

<!-- HERO -->
<section class="welcome-hero">
    <div class="welcome-hero-bg"></div>

    <div class="welcome-hero-nav">
        <div class="welcome-hero-nav-logo">CINECLUB</div>
    </div>

    <div class="welcome-hero-content">
        <h1>Your movie nights,<br>organized together</h1>
        <h2>Vote · Plan · Snack</h2>
        <p>Invite your friends, choose the movie, manage snacks.<br>All in one place. Free.</p>

        <!-- LOGIN BOX -->
        <div class="welcome-login-box">

            <?php if ($error): ?>
            <div class="wl-error"><?= htmlspecialchars($msgs[$error] ?? 'Error.') ?></div>
            <?php endif; ?>

            <!-- Main Tabs -->
            <div class="wl-tabs">
                <button class="wl-tab <?= $tab==='login'?'active':'' ?>"
                        onclick="setTab('login')">Log in</button>
                <button class="wl-tab <?= $tab==='register'?'active':'' ?>"
                        onclick="setTab('register')">Create an account</button>
            </div>

            <!-- REGISTER -->
            <div id="form-register" <?= $tab!=='register'?'style="display:none"':'' ?>>
                <p style="font-size:12px;color:rgba(255,255,255,.5);margin-bottom:14px;line-height:1.5">
                    Create your account. Then you can log in as
                    <strong>organizer</strong> (your own interface) or as
                    <strong>member</strong> (join a friend's interface).
                </p>
                <form method="POST" action="./register_action.php">
                    <input class="wl-input" type="text"  name="username"
                           placeholder="Your name" required>
                    <input class="wl-input" type="email" name="email"
                           placeholder="Email" required>
                    <div class="wl-pwd">
                        <input class="wl-input" type="password" name="password"
                               id="rp" placeholder="Password (min 6 characters)"
                               required style="margin-bottom:0">
                        <button type="button" class="wl-pwd-toggle" onclick="tog('rp')">👁</button>
                    </div>
                    <button type="submit" class="wl-submit" style="margin-top:14px">
                        Create my account →
                    </button>
                </form>
                <p class="wl-footer">
                    Already have an account?
                    <a href="#" onclick="setTab('login');return false">Log in</a>
                </p>
            </div>

            <!-- LOGIN -->
            <div id="form-login" <?= $tab!=='login'?'style="display:none"':'' ?>>

                <!-- Role Tabs -->
                <div class="wl-tabs" style="margin-bottom:16px">
                    <button class="wl-tab <?= $role==='organizer'?'active':'' ?>"
                            id="rt-org" onclick="setRole('organizer')">
                        👑 Organizer
                    </button>
                    <button class="wl-tab <?= $role==='member'?'active':'' ?>"
                            id="rt-mem" onclick="setRole('member')">
                        👤 Member
                    </button>
                </div>

                <!-- Description -->
                <p id="desc-org" style="font-size:12px;color:rgba(255,255,255,.45);margin-bottom:12px;<?= $role==='member'?'display:none':'' ?>">
                    You access your <strong>personal interface</strong>. If it's your first login, your space will be empty and ready to configure.
                </p>
                <p id="desc-mem" style="font-size:12px;color:rgba(255,255,255,.45);margin-bottom:12px;<?= $role==='organizer'?'display:none':'' ?>">
                    You access an <strong>organizer’s interface</strong> using the code they shared with you.
                </p>

                <form method="POST" action="./login_action.php">
                    <input type="hidden" name="login_type" id="login_type"
                           value="<?= $role ?>">

                    <input class="wl-input" type="email" name="email"
                           placeholder="Email" required>

                    <div class="wl-pwd">
                        <input class="wl-input" type="password" name="password"
                               id="lp" placeholder="Password"
                               required style="margin-bottom:0">
                        <button type="button" class="wl-pwd-toggle" onclick="tog('lp')">👁</button>
                    </div>

                    <!-- Invitation Code -->
                    <div id="code-field" style="<?= $role==='organizer'?'display:none':'' ?>">
                        <input class="wl-input" type="text" name="invite_code"
                               id="invite_code"
                               placeholder="Invitation code (e.g. ABCD-XYZ9)"
                               style="text-transform:uppercase;letter-spacing:4px;
                                      text-align:center;font-size:16px;
                                      font-weight:700;margin-top:4px">
                    </div>

                    <button type="submit" class="wl-submit" id="submit-btn">
                        <?= $role==='organizer' ? 'Log in as organizer' : 'Join the interface' ?>
                    </button>
                </form>

                <p class="wl-footer">
                    Don’t have an account yet?
                    <a href="#" onclick="setTab('register');return false">Create an account</a>
                </p>
            </div>

        </div>
    </div>
</section>

<div class="welcome-divider"></div>

<!-- FEATURES -->
<section class="welcome-features">
    <div class="wf-card">
        <h3>Vote for the next movie</h3>
        <p>Suggest movies and vote with your friends. The most voted one is selected.</p>
        <div class="wf-icon">🗳</div>
    </div>
    <div class="wf-card">
        <h3>Organize snacks</h3>
        <p>Each member volunteers for a snack. The organizer confirms.</p>
        <div class="wf-icon">🍿</div>
    </div>
    <div class="wf-card">
        <h3>Unique invitation code</h3>
        <p>Each organizer has a fixed and permanent code. Your friends use it to join you.</p>
        <div class="wf-icon">🎟</div>
    </div>
    <div class="wf-card">
        <h3>Multiple organizers</h3>
        <p>Everyone can create their own interface and invite their own members.</p>
        <div class="wf-icon">🎬</div>
    </div>
</section>

<div class="welcome-divider"></div>

<!-- FAQ -->
<section class="welcome-faq">
    <h2>Frequently Asked Questions</h2>
    <?php foreach ([
        ["Can I be both organizer AND member?",
         "Yes! With the same account, log in as organizer to manage your events, or as member to join a friend’s event using their invitation code."],
        ["How can I invite friends?",
         "Log in as organizer. From the Participants page, you will find your permanent invitation code. Share it via WhatsApp."],
        ["Does the invitation code change?",
         "No. Each organizer has ONE fixed and unique code. It never changes. Your friends can use it anytime."],
        ["What happens on my first login as organizer?",
         "Your interface is completely empty. You can customize it: add movies, create a session, invite members."],
    ] as $i => $f): ?>
    <div class="faq-item">
        <div class="faq-q" onclick="tFaq(<?=$i?>)">
            <span><?=$f[0]?></span>
            <span class="faq-icon" id="fi-<?=$i?>">+</span>
        </div>
        <div class="faq-a" id="fa-<?=$i?>"><?=$f[1]?></div>
    </div>
    <?php endforeach; ?>
    <div class="faq-end"></div>
</section>

<!-- CTA -->
<div class="welcome-cta">
    <div>
        <p>Ready for your next movie night?</p>
        <span>Join CineClub for free now.</span>
    </div>
    <div style="display:flex;gap:10px">
        <a href="#" onclick="setTab('register');scrollUp();return false" class="btn-red">
            Create an account
        </a>
        <a href="#" onclick="setTab('login');scrollUp();return false" class="btn-dark">
            Log in
        </a>
    </div>
</div>

<footer style="background:#000;padding:20px 60px;border-top:1px solid #222;">
    <p style="color:#555;font-size:13px;text-align:center;">© 2026 CineClub</p>
</footer>

<script>
function setTab(t) {
    document.getElementById('form-login').style.display    = t==='login'    ? '' : 'none';
    document.getElementById('form-register').style.display = t==='register' ? '' : 'none';
    document.querySelectorAll('.wl-tab').forEach((b,i) =>
        b.classList.toggle('active',
            (t==='login'&&i===0)||(t==='register'&&i===1)));
}

function setRole(r) {
    document.getElementById('login_type').value = r;
    document.getElementById('rt-org').classList.toggle('active', r==='organizer');
    document.getElementById('rt-mem').classList.toggle('active', r==='member');
    document.getElementById('desc-org').style.display   = r==='organizer' ? '' : 'none';
    document.getElementById('desc-mem').style.display   = r==='member'    ? '' : 'none';
    document.getElementById('code-field').style.display = r==='member'    ? '' : 'none';
    const code = document.getElementById('invite_code');
    if (r==='member') code.setAttribute('required','');
    else code.removeAttribute('required');
    document.getElementById('submit-btn').textContent =
        r==='organizer' ? 'Log in as organizer' : 'Join the interface';
}

function tog(id) {
    const i = document.getElementById(id);
    i.type = i.type==='password' ? 'text' : 'password';
}

function tFaq(i) {
    const a=document.getElementById('fa-'+i), ic=document.getElementById('fi-'+i);
    const o=a.classList.contains('open');
    a.classList.toggle('open',!o); ic.classList.toggle('open',!o);
    ic.textContent = o ? '+' : '×';
}

function scrollUp() {
    document.querySelector('.welcome-hero-content').scrollIntoView({behavior:'smooth'});
}

// Restore state
<?php if ($tab==='register'): ?>setTab('register');<?php endif; ?>
<?php if ($tab==='login' && $role==='member'): ?>setRole('member');<?php endif; ?>
</script>
</body>
</html>
