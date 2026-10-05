<?php if (session_status()===PHP_SESSION_NONE) session_start(); ?>
<nav class="navbar" id="mainNav">
    <a href="../php/index.php" class="nav-logo">
        <svg viewBox="0 0 24 24"><path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/></svg>
        CINE<span style="color:#fff">CLUB</span>
    </a>
    <ul class="nav-links">
        <li><a href="../php/index.php" <?=basename($_SERVER['PHP_SELF'])=='index.php'?'class="active"':''?>>
            <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>Home</a></li>
        <li><a href="../php/voting.php" <?=basename($_SERVER['PHP_SELF'])=='voting.php'?'class="active"':''?>>
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>Voting</a></li>
        <li><a href="../php/planning.php" <?=basename($_SERVER['PHP_SELF'])=='planning.php'?'class="active"':''?>>
            <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5C3.89 3 3 3.9 3 5v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>Planning</a></li>
        <li><a href="../php/participants.php" <?=basename($_SERVER['PHP_SELF'])=='participants.php'?'class="active"':''?>>
            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>Participants</a></li>
        <li><a href="../php/snacks.php" <?=basename($_SERVER['PHP_SELF'])=='snacks.php'?'class="active"':''?>>
            <svg viewBox="0 0 24 24"><path d="M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-8-15.03-8-15.03 0h15.03zM1.02 17h15v2H1z"/></svg>Snacks</a></li>
        <li><a href="../php/archives.php" <?=basename($_SERVER['PHP_SELF'])=='archives.php'?'class="active"':''?>>
            <svg viewBox="0 0 24 24"><path d="M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 17.5L6.5 12H10v-2h4v2h3.5L12 17.5z"/></svg>Archives</a></li>
        <li><a href="../php/gallery.php" <?=basename($_SERVER['PHP_SELF'])=='gallery.php'?'class="active"':''?>>
            <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>Gallery</a></li>
    </ul>
    <div class="nav-right">
        <?php if(isset($_SESSION['user_id'])): ?>
        <div class="nav-user">
            <div class="nav-avatar"><?=strtoupper(mb_substr($_SESSION['username'],0,1))?></div>
            <span class="nav-name"><?=htmlspecialchars($_SESSION['username'])?> <?=$_SESSION['role']==='organizer'?'👑':''?></span>
        </div>
        <a href="../php/logout.php" class="btn-nav">Logout</a>
        <?php else: ?>
        <a href="../php/welcome.php" class="btn-red" style="padding:7px 16px;font-size:13px">Sign In</a>
        <?php endif; ?>
    </div>
</nav>
<script>
window.addEventListener('scroll',function(){
    document.getElementById('mainNav').classList.toggle('solid',window.scrollY>50);
});
</script>
