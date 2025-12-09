<?php
session_start();

// --- 1. CONFIGURARE ---
$SITE_PASSWORD_HASH = '21232f297a57a5a743894a0e4a801fc3'; // MD5 'admin'
$ROWS_LIMIT = 300;

// --- 2. LOGICA LIMBA ---
if (isset($_GET['lang'])) {
    $_SESSION['site_lang'] = $_GET['lang'];
}
$LANG = $_SESSION['site_lang'] ?? 'ro';

$TRANS = [
    'ro' => [
        'gate_title' => 'ACCES SECURIZAT',
        'gate_ph' => 'Parola de acces...',
        'unlock' => 'DEBLOCARE',
        'pass_err' => 'Parola incorecta',
        'login_title' => 'CONECTARE SQL',
        'host' => 'HOST', 'user' => 'UTILIZATOR', 'pass' => 'PAROLA', 'db' => 'NUME BAZA DATE',
        'connect' => 'CONECTARE', 'db_err' => 'Eroare conexiune',
        'logout_site' => 'IESIRE SITE', 'logout_db' => 'DECONECTARE',
        'dashboard' => 'DASHBOARD', 'location' => 'LOCATIE', 'table' => 'TABEL',
        'rows' => 'RANDURI', 'no_data' => 'FARA DATE', 'limit_msg' => 'DATA LIMITA', 'db_label' => 'BAZA DE DATE',
        'credits' => 'Creat cu <span class="heart">&lt;3</span> de <strong>Giovanni</strong>'
    ],
    'en' => [
        'gate_title' => 'SECURE ACCESS',
        'gate_ph' => 'Access password...',
        'unlock' => 'UNLOCK',
        'pass_err' => 'Wrong password',
        'login_title' => 'SQL LOGIN',
        'host' => 'HOST', 'user' => 'USER', 'pass' => 'PASSWORD', 'db' => 'DATABASE NAME',
        'connect' => 'CONNECT', 'db_err' => 'Connection error',
        'logout_site' => 'EXIT SITE', 'logout_db' => 'DISCONNECT',
        'dashboard' => 'DASHBOARD', 'location' => 'LOCATION', 'table' => 'TABLE',
        'rows' => 'ROWS', 'no_data' => 'NO DATA', 'limit_msg' => 'DATA LIMIT', 'db_label' => 'DATABASE',
        'credits' => 'Created with <span class="heart">&lt;3</span> by <strong>Giovanni</strong>'
    ]
];
function T($key) { global $TRANS, $LANG; return $TRANS[$LANG][$key] ?? $key; }
function langLink($l) { $p = $_GET; $p['lang'] = $l; return '?' . http_build_query($p); }

// --- 3. LOGICA PRINCIPALA ---
if (isset($_GET['action']) && $_GET['action'] === 'global_logout') { session_destroy(); header("Location: ?"); exit; }

if (!isset($_SESSION['gatekeeper_access']) || $_SESSION['gatekeeper_access'] !== true) {
    $gate_error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['global_pass'])) {
        if (md5($_POST['global_pass']) === $SITE_PASSWORD_HASH) {
            $_SESSION['gatekeeper_access'] = true; header("Location: ?"); exit;
        } else { $gate_error = T('pass_err'); }
    }
    $view_mode = 'gate';
} elseif (!isset($_SESSION['db_user'])) {
    $db_error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connect'])) {
        $testConn = @new mysqli($_POST['host'], $_POST['user'], $_POST['pass'], $_POST['db']);
        if ($testConn->connect_error) { $db_error = T('db_err') . ": " . $testConn->connect_error; } 
        else {
            $_SESSION['db_host'] = $_POST['host']; $_SESSION['db_user'] = $_POST['user'];
            $_SESSION['db_pass'] = $_POST['pass']; $_SESSION['db_name'] = $_POST['db'];
            $testConn->close(); header("Location: ?"); exit;
        }
    }
    $view_mode = 'login';
} else {
    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        unset($_SESSION['db_host'], $_SESSION['db_user'], $_SESSION['db_pass'], $_SESSION['db_name']);
        header("Location: ?"); exit;
    }
    function getDbConnection() {
        $c = @new mysqli($_SESSION['db_host'], $_SESSION['db_user'], $_SESSION['db_pass'], $_SESSION['db_name']);
        if ($c->connect_error) return null;
        $c->set_charset("utf8mb4"); return $c;
    }
    function cleanStr($s) {
        if ($s === null) return null;
        if (function_exists('mb_convert_encoding')) return mb_convert_encoding($s, 'UTF-8', 'auto');
        return $s;
    }
    function formatValue($k, $v) {
        if ($v === null) return "<span class='v-null'>NULL</span>";
        $v = cleanStr($v); $k = strtolower($k); $vl = strtolower($v);
        if (in_array($k, ['status','stare','active','state','platit'])) {
            if (strpos($vl,'complet')!==false || strpos($vl,'activ')!==false || $vl=='1' || strpos($vl,'paid')!==false) return "<span class='bdg bdg-g'>$v</span>";
            if (strpos($vl,'pending')!==false || strpos($vl,'asteptare')!==false) return "<span class='bdg bdg-y'>$v</span>";
            if (strpos($vl,'anulat')!==false || $vl=='0') return "<span class='bdg bdg-r'>$v</span>";
            return "<span class='bdg'>$v</span>";
        }
        if (strlen($v) > 50) return "<span title='".htmlspecialchars($v)."'>".htmlspecialchars(substr($v,0,50))."...</span>";
        return htmlspecialchars($v);
    }
    $conn = getDbConnection();
    $view_mode = 'dashboard';
    $currentTable = $_GET['table'] ?? null;
    $dbTables = [];
    if($conn) {
        $r = $conn->query("SHOW TABLES");
        if($r) while($row = $r->fetch_row()) {
            $n = $row[0]; $cr = $conn->query("SELECT COUNT(*) FROM `$n`");
            $c = ($cr) ? $cr->fetch_row()[0] : 0;
            $dbTables[] = ['Name'=>$n, 'Rows'=>$c];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyView v1.0</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Space+Grotesk:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a; --bg-grad: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --glass-bg: rgba(30, 41, 59, 0.6); --glass-border: rgba(255, 255, 255, 0.08);
            --primary: #818cf8; --primary-glow: rgba(129, 140, 248, 0.4);
            --text: #f1f5f9; --mute: #94a3b8; --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body { margin:0; font-family: 'Space Grotesk', sans-serif; background: var(--bg-grad); color: var(--text); height: 100vh; overflow: hidden; display: flex; }
        
        /* CREDITS & ANIMATION */
        .credits-box { font-size: 0.75rem; color: var(--mute); margin-top: 15px; text-align: center; opacity: 0.7; }
        .credits-box strong { color: var(--text); }
        .heart { color: #f43f5e; display: inline-block; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }
        
        .fixed-credits { position: fixed; bottom: 15px; width: 100%; text-align: center; pointer-events: none; z-index: 5; }
        .fixed-credits span { pointer-events: auto; background: rgba(0,0,0,0.3); padding: 5px 12px; border-radius: 20px; backdrop-filter: blur(4px); }

        /* LANGUAGE SWITCHER */
        .lang-switch { position: fixed; top: 20px; right: 20px; z-index: 99999; display: flex; gap: 8px; background: rgba(0,0,0,0.4); padding: 6px; border-radius: 10px; backdrop-filter: blur(4px); }
        .lang-btn { text-decoration: none; color: #ccc; font-weight: 700; font-size: 0.8rem; padding: 6px 10px; border-radius: 6px; border: 1px solid transparent; transition: 0.2s; }
        .lang-btn:hover { color: white; background: rgba(255,255,255,0.1); }
        .lang-btn.active { background: var(--primary); color: white; box-shadow: 0 0 10px var(--primary-glow); }

        .fade-in { animation: fadeIn 0.5s ease-out forwards; opacity: 0; transform: translateY(10px); }
        @keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }

        .glass-panel { background: var(--glass-bg); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
        input { width: 100%; padding: 14px; margin-bottom: 15px; border: 1px solid var(--glass-border); border-radius: 10px; background: rgba(0,0,0,0.3); color: white; outline: none; font-family: 'Space Grotesk', sans-serif; transition: 0.3s; }
        input:focus { border-color: var(--primary); box-shadow: 0 0 15px var(--primary-glow); background: rgba(0,0,0,0.5); }
        button { width: 100%; padding: 14px; border-radius: 10px; border: none; background: var(--primary); color: white; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 1rem; letter-spacing: 1px; }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 20px var(--primary-glow); }

        .center-screen { width: 100%; height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; z-index: 10; }
        .auth-box { width: 100%; max-width: 380px; padding: 40px; text-align: center; }
        .err-msg { background: rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; border: 1px solid rgba(239, 68, 68, 0.3); }

        .sidebar { width: 280px; background: rgba(15, 23, 42, 0.5); border-right: 1px solid var(--glass-border); display: flex; flex-direction: column; flex-shrink: 0; backdrop-filter: blur(10px); }
        .brand { padding: 25px; font-size: 1.5rem; font-weight: 700; color: white; border-bottom: 1px solid var(--glass-border); }
        .brand span { color: var(--primary); }
        .nav-list { list-style: none; padding: 0 15px; margin: 0; overflow-y: auto; flex-grow: 1; }
        .nav-list li a { display: flex; justify-content: space-between; padding: 12px 15px; color: var(--mute); text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.2s; font-size: 0.9rem; }
        .nav-list li a:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-list li a.active { background: var(--primary); color: white; box-shadow: 0 0 15px var(--primary-glow); }
        .cnt { background: rgba(0,0,0,0.3); padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-family: 'JetBrains Mono', monospace; }

        .sidebar-footer { padding: 20px; border-top: 1px solid var(--glass-border); margin-top: auto; }
        .logout-btn { display: block; width: 100%; text-align: center; padding: 12px; background: rgba(239, 68, 68, 0.1); color: var(--danger); text-decoration: none; border-radius: 10px; font-weight: 700; border: 1px solid rgba(239, 68, 68, 0.2); transition: 0.2s; font-size: 0.85rem; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.2); box-shadow: 0 0 15px rgba(239, 68, 68, 0.2); color: #ffaaaa; }

        .main { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .card { text-decoration: none; color: white; padding: 25px; transition: 0.3s; display: flex; flex-direction: column; justify-content: space-between; height: 140px; }
        .card:hover { transform: translateY(-5px); border-color: var(--primary); background: rgba(30, 41, 59, 0.8); }
        .c-val { font-size: 2rem; font-weight: 700; font-family: 'JetBrains Mono', monospace; color: var(--primary); }

        .table-wrap { overflow: hidden; display: flex; flex-direction: column; max-height: calc(100vh - 150px); }
        .t-scroll { overflow: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: rgba(15, 23, 42, 0.95); padding: 18px; color: var(--mute); font-size: 0.75rem; text-transform: uppercase; position: sticky; top: 0; z-index: 5; border-bottom: 1px solid var(--glass-border); }
        td { padding: 14px 18px; border-bottom: 1px solid var(--glass-border); font-family: 'JetBrains Mono', monospace; font-size: 0.9rem; color: #e2e8f0; }
        tr:hover td { background: rgba(255,255,255,0.03); }

        .bdg { padding: 4px 10px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .bdg-g { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .bdg-y { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .bdg-r { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
        .v-null { color: var(--mute); font-style: italic; opacity: 0.5; }
        ::-webkit-scrollbar { width: 6px; height: 6px; } ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 3px; }
    </style>
</head>
<body>

<div class="lang-switch">
    <a href="<?= langLink('ro') ?>" class="lang-btn <?= $LANG=='ro'?'active':'' ?>">RO</a>
    <a href="<?= langLink('en') ?>" class="lang-btn <?= $LANG=='en'?'active':'' ?>">EN</a>
</div>

<?php if ($view_mode === 'gate'): ?>
    <div class="center-screen">
        <div class="glass-panel auth-box fade-in">
            <div style="font-size:3rem; margin-bottom:15px"></div>
            <h2 style="margin:0 0 20px 0;"><?= T('gate_title') ?></h2>
            <?php if($gate_error) echo "<div class='err-msg'>$gate_error</div>"; ?>
            <form method="POST">
                <input type="password" name="global_pass" placeholder="<?= T('gate_ph') ?>" autofocus required>
                <button type="submit"><?= T('unlock') ?></button>
            </form>
        </div>
        <div class="fixed-credits"><span class="credits-box"><?= T('credits') ?></span></div>
    </div>
<?php elseif ($view_mode === 'login'): ?>
    <div class="center-screen">
        <div class="glass-panel auth-box fade-in">
            <a href="?action=global_logout" style="position:absolute; top:15px; left:15px; color:var(--mute); text-decoration:none; font-size:0.8rem;"><?= T('logout_site') ?></a>
            <div style="font-size:3rem; margin-bottom:10px;"></div>
            <h2 style="margin:0 0 25px 0;"><?= T('login_title') ?></h2>
            <?php if(isset($db_error) && $db_error) echo "<div class='err-msg'>$db_error</div>"; ?>
            <form method="POST">
                <input type="text" name="host" placeholder="<?= T('host') ?>" value="localhost" required>
                <input type="text" name="user" placeholder="<?= T('user') ?>" required>
                <input type="password" name="pass" placeholder="<?= T('pass') ?>">
                <input type="text" name="db" placeholder="<?= T('db') ?>" required>
                <button type="submit" name="connect"><?= T('connect') ?></button>
            </form>
        </div>
        <div class="fixed-credits"><span class="credits-box"><?= T('credits') ?></span></div>
    </div>
<?php else: ?>
    <aside class="sidebar fade-in">
        <div class="brand">Easy <span>View v1.0</span></div>
        <div style="padding:15px 25px; font-size:0.8rem; color:var(--mute); font-weight:700"><?= T('db_label') ?>: <?= htmlspecialchars($_SESSION['db_name']) ?></div>
        <ul class="nav-list">
            <li><a href="?" class="<?= !$currentTable ? 'active' : '' ?>"><?= T('dashboard') ?></a></li>
            <?php foreach ($dbTables as $tbl): $act = ($currentTable === $tbl['Name']) ? 'active' : ''; ?>
                <li><a href="?table=<?= $tbl['Name'] ?>" class="<?= $act ?>"><?= $tbl['Name'] ?> <span class="cnt"><?= $tbl['Rows'] ?></span></a></li>
            <?php endforeach; ?>
        </ul>
        <div class="sidebar-footer">
            <a href="?action=logout" class="logout-btn"><?= T('logout_db') ?></a>
            <div class="credits-box"><?= T('credits') ?></div>
        </div>
    </aside>
    <main class="main fade-in">
        <div class="top-bar">
            <div style="font-size:1.2rem; color:var(--mute)"><?= T('location') ?> / <strong style="color:white"><?= $currentTable ? htmlspecialchars($currentTable) : T('dashboard') ?></strong></div>
        </div>
        <?php if (!$currentTable): ?>
            <div class="grid">
                <?php foreach ($dbTables as $tbl): ?>
                    <a href="?table=<?= $tbl['Name'] ?>" class="glass-panel card">
                        <span style="font-size:0.8rem; color:var(--mute); font-weight:700"><?= T('table') ?></span>
                        <span style="font-size:1.1rem; font-weight:700"><?= $tbl['Name'] ?></span>
                        <span class="c-val"><?= number_format($tbl['Rows']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="glass-panel table-wrap">
                <div class="t-scroll">
                    <?php
                    $safeTb = $conn->real_escape_string($currentTable);
                    $pkRes = $conn->query("SHOW KEYS FROM `$safeTb` WHERE Key_name = 'PRIMARY'");
                    $pk = ($pkRes && $pkRes->num_rows > 0) ? $pkRes->fetch_assoc()['Column_name'] : null;
                    $order = $pk ? "ORDER BY `$pk` DESC" : "";
                    $res = $conn->query("SELECT * FROM `$safeTb` $order LIMIT $ROWS_LIMIT");
                    if ($res && $res->num_rows > 0) {
                        echo "<table><thead><tr>";
                        foreach($res->fetch_fields() as $c) echo "<th>" . cleanStr($c->name) . "</th>";
                        echo "</tr></thead><tbody>";
                        while($row = $res->fetch_assoc()) {
                            echo "<tr>";
                            foreach($row as $k=>$v) echo "<td>" . formatValue($k, $v) . "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                    } else { echo "<div style='padding:50px; text-align:center; color:var(--mute);'>".T('no_data')."</div>"; }
                    ?>
                </div>
                <div style="padding:15px; border-top:1px solid var(--glass-border); font-size:0.8rem; text-align:right; color:var(--mute);"><?= T('limit_msg') ?>: <?= $ROWS_LIMIT ?></div>
            </div>
        <?php endif; ?>
    </main>
<?php endif; ?>
</body>
</html>