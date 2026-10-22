<?php
$page = basename($_SERVER['PHP_SELF']);
$u = $_SESSION['user'] ?? null;
$__win = (bool)$u;   // dipakai footer
const ICONS = [
  'index.php'     => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/>',
  'checklist.php' => '<rect x="5" y="3" width="14" height="18" rx="3"/><path d="M9 12l2 2 4-4"/>',
  'siswa.php'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17" cy="9" r="2.5"/><path d="M17 14c2.6 0 4.5 1.8 4.5 4.5"/>',
  'perangkat.php' => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
  'users.php'     => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
];
function nav($file, $label, $page) {
    $on = ($page === $file || ($file === 'checklist.php' && strpos($page, 'checklist_') === 0));
    echo '<a href="' . $file . '"' . ($on ? ' class="on" aria-current="page"' : '') . '><svg viewBox="0 0 24 24" aria-hidden="true">' . ICONS[$file] . '</svg><span>' . $label . '</span></a>';
}
$ini = $u ? strtoupper(function_exists('mb_substr') ? mb_substr($u['nama'], 0, 1) : substr($u['nama'], 0, 1)) : '';
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#051F20">
<title><?= e($title ?? 'Konservasi Energi') ?> | Konservasi Energi</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>">
</head>
<body class="<?= $u ? 'in-app' : 'is-login' ?>">
<div class="app">
<?php if ($u): ?>
<header class="topbar">
  <a class="brandmark" href="index.php" aria-label="Beranda"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M5 27C5 14 13 5 27 5c0 14-8 22-22 22z" fill="currentColor"/><path d="M9 23L20 12" stroke="#051F20" stroke-width="2" stroke-linecap="round"/></svg></a>
  <div class="pill-wrap">
    <button type="button" class="avatar" id="userbtn" aria-haspopup="true" aria-expanded="false" aria-label="Menu akun <?= e($u['nama']) ?>"><?= e($ini) ?></button>
    <nav class="menu-nav" aria-label="Menu utama">
      <?php
      nav('index.php', 'Beranda', $page);
      nav('checklist.php', 'Checklist', $page);
      if (can(['Admin','Guru'])) { nav('siswa.php', 'Siswa', $page); nav('perangkat.php', 'Perangkat', $page); nav('users.php', 'Pengguna', $page); }
      ?>
    </nav>
    <a class="btn cta" href="checklist_form.php">Isi checklist</a>
    <div class="menu" id="usermenu" role="menu">
      <div class="m-user"><b><?= e($u['nama']) ?></b><small><?= e($u['level']) ?> · <?= e($u['username']) ?></small></div>
      <a href="logout.php" role="menuitem">Keluar</a>
    </div>
  </div>
</header>
<?php endif; ?>
<main<?= $u ? '' : ' class="solo"' ?>>
<?php if ($f = flash()): ?><div class="alert toast <?= $f[1] ?>" role="status"><?= e($f[0]) ?></div><?php endif; ?>
<?php if ($u): ?>
<div class="hero" aria-hidden="true"><span class="ghost"><?= e($title ?? '') ?></span><span class="tag">Konservasi Energi</span></div>
<div class="content">
<?php endif; ?>
