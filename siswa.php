<?php
require 'config.php';
require_level(['Admin','Guru']);
$title = 'Data Siswa';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_level(['Admin']);
    check_csrf();
    $act = $_POST['act'] ?? '';
    try {
        if ($act === 'add') {
            q('INSERT INTO siswa (nis,nama,kelas) VALUES (?,?,?)', 'iss', [(int)$_POST['nis'], trim($_POST['nama']), trim($_POST['kelas'])]);
            flash('Siswa ditambahkan.');
        } elseif ($act === 'edit') {
            q('UPDATE siswa SET nama=?, kelas=? WHERE nis=?', 'ssi', [trim($_POST['nama']), trim($_POST['kelas']), (int)$_POST['nis']]);
            flash('Data siswa disimpan.');
        } elseif ($act === 'del') {
            q('DELETE FROM siswa WHERE nis=?', 'i', [(int)$_POST['nis']]);
            flash('Siswa dihapus.');
        }
    } catch (mysqli_sql_exception $ex) {
        flash($ex->getCode() == 1062 ? 'NIS sudah terdaftar.' : 'Gagal: siswa masih punya data checklist atau data tidak valid.', 'err');
    }
    redirect('siswa.php');
}
$edit = (can(['Admin']) && isset($_GET['edit'])) ? row('SELECT * FROM siswa WHERE nis=?', 'i', [(int)$_GET['edit']]) : null;
$list = rows('SELECT * FROM siswa ORDER BY kelas, nama');
include 'includes/header.php';
?>
<h1>Data Siswa</h1>
<?php if (!can(['Admin'])): ?><p class="sub">Mode pantau: kamu bisa melihat data, tetapi tidak bisa mengubahnya.</p><?php endif; ?>
<?php if (can(['Admin'])): ?>
<div class="card">
  <h2><?= $edit ? 'Ubah siswa' : 'Tambah siswa' ?></h2>
  <form method="post"><?= csrf_field() ?>
    <input type="hidden" name="act" value="<?= $edit ? 'edit' : 'add' ?>">
    <div class="grid">
      <div><label for="nis">NIS</label><input id="nis" type="number" name="nis" max="9999999999" required value="<?= e($edit['nis'] ?? '') ?>" <?= $edit ? 'readonly' : '' ?>></div>
      <div><label for="nama">Nama</label><input id="nama" name="nama" maxlength="100" required value="<?= e($edit['nama'] ?? '') ?>"></div>
      <div><label for="kelas">Kelas</label><input id="kelas" name="kelas" maxlength="10" required value="<?= e($edit['kelas'] ?? '') ?>"></div>
    </div>
    <p><button class="btn" type="submit">Simpan siswa</button>
    <?php if ($edit): ?><a class="btn sec" href="siswa.php">Batal</a><?php endif; ?></p>
  </form>
</div>
<?php endif; ?>
<div class="card scroll">
  <?php if (!$list): ?><p class="muted">Belum ada siswa. Tambahkan lewat formulir di atas.</p><?php else: ?>
  <table><tr><th>NIS</th><th>Nama</th><th>Kelas</th><?php if (can(['Admin'])): ?><th></th><?php endif; ?></tr>
  <?php foreach ($list as $s): ?>
    <tr><td data-label="NIS"><?= e($s['nis']) ?></td><td data-label="Nama"><?= e($s['nama']) ?></td><td data-label="Kelas"><?= e($s['kelas']) ?></td>
    <?php if (can(['Admin'])): ?><td data-label=""><a class="btn sm sec" href="siswa.php?edit=<?= $s['nis'] ?>">Ubah</a>
      <form class="inline" method="post" onsubmit="return confirm('Hapus siswa ini?')"><?= csrf_field() ?>
        <input type="hidden" name="act" value="del"><input type="hidden" name="nis" value="<?= $s['nis'] ?>">
        <button class="btn sm del" type="submit">Hapus</button></form></td><?php endif; ?></tr>
  <?php endforeach; ?></table>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
