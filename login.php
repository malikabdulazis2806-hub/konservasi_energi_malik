<?php
require 'config.php';
if (!empty($_SESSION['user'])) redirect('index.php');
$error = '';
$saved = saved_accounts();

function login_as($id, $nama, $level, $username) {
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => $id, 'nama' => $nama, 'level' => $level, 'username' => $username];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (isset($_POST['forget'])) {                       // hapus satu akun tersimpan
        remember_forget((string)$_POST['forget']);
        redirect('login.php#tersimpan');
    } elseif (isset($_POST['quick'])) {                  // klik akun tersimpan: langsung masuk
        foreach ($saved as $a) {
            if (hash_equals($a['sel'], (string)$_POST['quick'])) {
                login_as($a['id_user'], $a['nama'], $a['level'], $a['username']);
                remember_issue((int)$a['id_user']);      // ganti dengan kode baru
                redirect('index.php');
            }
        }
        $error = 'Login tersimpan sudah kedaluwarsa. Masuk dengan username dan password.';
    } else {                                             // masuk biasa
        $u = row('SELECT * FROM users WHERE username=?', 's', [trim($_POST['username'] ?? '')]);
        if ($u && ($_POST['password'] ?? '') === $u['password']) {
            login_as($u['id_user'], $u['nama'], $u['level'], $u['username']);
            if (!empty($_POST['simpan'])) remember_issue((int)$u['id_user']);
            redirect('index.php');
        }
        $error = 'Username atau password salah. Periksa lagi atau minta admin mengatur ulang.';
    }
}
$title = 'Masuk';
$chips = ['Lampu', 'AC', 'Kipas', 'Proyektor', 'Komputer', 'Stop kontak'];
include 'includes/header.php';
?>
<div class="rings" aria-hidden="true"><i class="r1"></i><i class="r2"></i></div>
<div class="gate">
  <div class="gate-badge"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M5 27C5 14 13 5 27 5c0 14-8 22-22 22z" fill="currentColor"/><path d="M9 23L20 12" stroke="#051F20" stroke-width="2" stroke-linecap="round"/></svg>Konservasi Energi</div>
  <h1 class="gate-title">Selamat datang!</h1>

  <section class="card gate-card">
    <h2>Masuk ke akun kamu</h2>
    <p class="sub">Catat lampu, AC, dan perangkat lain sebelum, selama, dan sesudah dipakai.</p>

    <label class="saved-toggle" for="sw">
      <span>Login tersimpan<small><?= $saved ? count($saved) . ' akun di perangkat ini' : 'Belum ada akun tersimpan' ?></small></span>
      <span class="switch">
        <input type="checkbox" id="sw" role="switch" aria-controls="saved-panel" aria-expanded="false">
        <span class="track"></span>
      </span>
    </label>
    <div class="saved-panel" id="saved-panel">
      <div class="saved-inner">
        <?php if (!$saved): ?>
          <p class="saved-empty">Centang “Simpan login ini” saat masuk, lalu akunmu muncul di sini dan bisa dipakai masuk dengan satu klik.</p>
        <?php else: ?>
        <div class="saved-list">
          <?php foreach ($saved as $a): ?>
          <div class="saved-row">
            <form method="post"><?= csrf_field() ?>
              <input type="hidden" name="quick" value="<?= e($a['sel']) ?>">
              <button class="saved-item" type="submit">
                <span class="av"><?= e(function_exists('mb_substr') ? mb_strtoupper(mb_substr($a['nama'], 0, 1)) : strtoupper(substr($a['nama'], 0, 1))) ?></span>
                <span><b><?= e($a['nama']) ?></b><small><?= e($a['level']) ?> · <?= e($a['username']) ?></small></span>
                <svg class="go" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
              </button>
            </form>
            <form method="post" onsubmit="return confirm('Hapus login tersimpan ini dari perangkat?')"><?= csrf_field() ?>
              <input type="hidden" name="forget" value="<?= e($a['sel']) ?>">
              <button class="forget" type="submit" aria-label="Hapus login tersimpan <?= e($a['nama']) ?>">×</button>
            </form>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($error): ?><div class="alert err" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <label for="username">Username</label>
      <div class="field">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
        <input id="username" name="username" autocomplete="username" placeholder="Masukkan username" required value="<?= e($_POST['username'] ?? '') ?>">
      </div>
      <label for="password">Password</label>
      <div class="field">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="3"/><path d="M8 11V8a4 4 0 018 0v3"/></svg>
        <input id="password" type="password" name="password" autocomplete="current-password" placeholder="Masukkan password" required>
        <button type="button" class="eye" id="eye" aria-label="Tampilkan password" aria-pressed="false">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
      <label class="remember" for="simpan">
        <input type="checkbox" id="simpan" name="simpan" value="1" <?= !empty($_POST['simpan']) ? 'checked' : '' ?>>
        <span>Simpan login ini di perangkat<small>Jangan dicentang di komputer umum.</small></span>
      </label>
      <button class="btn block" type="submit">Masuk</button>
    </form>
    <p class="hint">Lupa password? Hubungi admin sekolah.</p>
  </section>

  <div class="gate-chips" aria-hidden="true">
    <?php foreach ($chips as $c): ?><span class="chip"><?= e($c) ?></span><?php endforeach; ?>
  </div>
</div>
<div class="gate-ghost" aria-hidden="true">Konservasi</div>
<p class="gate-foot">© <?= date('Y') ?> Konservasi Energi &nbsp;•&nbsp; Sekolah &nbsp;•&nbsp; Hemat energi mulai dari kelas</p>
<script>
const eye = document.getElementById('eye'), pw = document.getElementById('password');
eye.addEventListener('click', () => {
  const show = pw.type === 'password';
  pw.type = show ? 'text' : 'password';
  eye.setAttribute('aria-pressed', show);
  eye.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
  eye.classList.toggle('on', show);
});
const sw = document.getElementById('sw'), panel = document.getElementById('saved-panel');
function syncPanel() { panel.classList.toggle('open', sw.checked); sw.setAttribute('aria-expanded', sw.checked); }
sw.addEventListener('change', syncPanel);
if (location.hash === '#tersimpan') { sw.checked = true; syncPanel(); }
</script>
<?php include 'includes/footer.php'; ?>
