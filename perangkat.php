<?php
require 'config.php';
require_level(['Admin','Guru']);
$title = 'Data Perangkat';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_level(['Admin']);
    check_csrf();
    $act = $_POST['act'] ?? '';
    try {
        if ($act === 'add') {
            q('INSERT INTO perangkat (nama_perangkat,jenis,keterangan) VALUES (?,?,?)', 'sss', [trim($_POST['nama_perangkat']), trim($_POST['jenis']), trim($_POST['keterangan'])]);
            flash('Perangkat ditambahkan.');
        } elseif ($act === 'edit') {
            q('UPDATE perangkat SET nama_perangkat=?, jenis=?, keterangan=? WHERE id_perangkat=?', 'sssi', [trim($_POST['nama_perangkat']), trim($_POST['jenis']), trim($_POST['keterangan']), (int)$_POST['id']]);
            flash('Perangkat disimpan.');
        } elseif ($act === 'del') {
            q('DELETE FROM perangkat WHERE id_perangkat=?', 'i', [(int)$_POST['id']]);
            flash('Perangkat dihapus.');
        }
    } catch (mysqli_sql_exception $ex) {
        flash('Gagal: perangkat masih dipakai di checklist atau data tidak valid.', 'err');
    }
    redirect('perangkat.php');
}
$edit = (can(['Admin']) && isset($_GET['edit'])) ? row('SELECT * FROM perangkat WHERE id_perangkat=?', 'i', [(int)$_GET['edit']]) : null;
$list = rows('SELECT * FROM perangkat ORDER BY jenis, nama_perangkat');
include 'includes/header.php';
?>
<h1>Data Perangkat</h1>
<?php if (!can(['Admin'])): ?><p class="sub">Mode pantau: kamu bisa melihat data, tetapi tidak bisa mengubahnya.</p><?php endif; ?>
<?php if (can(['Admin'])): ?>
<div class="card">
  <h2><?= $edit ? 'Ubah perangkat' : 'Tambah perangkat' ?></h2>
  <form method="post"><?= csrf_field() ?>
    <input type="hidden" name="act" value="<?= $edit ? 'edit' : 'add' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id_perangkat'] ?>"><?php endif; ?>
    <div class="grid">
      <div><label for="np">Nama perangkat</label><input id="np" name="nama_perangkat" maxlength="100" required value="<?= e($edit['nama_perangkat'] ?? '') ?>"></div>
      <div><label for="jn">Jenis</label><input id="jn" name="jenis" maxlength="50" required value="<?= e($edit['jenis'] ?? '') ?>"></div>
    </div>
    <label for="ket">Keterangan</label><textarea id="ket" name="keterangan" rows="2"><?= e($edit['keterangan'] ?? '') ?></textarea>
    <p><button class="btn" type="submit">Simpan perangkat</button>
    <?php if ($edit): ?><a class="btn sec" href="perangkat.php">Batal</a><?php endif; ?></p>
  </form>
</div>
<?php endif; ?>
<div class="card scroll">
  <?php if (!$list): ?><p class="muted">Belum ada perangkat. Tambahkan lewat formulir di atas.</p><?php else: ?>
  <table><tr><th>Nama</th><th>Jenis</th><th>Keterangan</th><?php if (can(['Admin'])): ?><th></th><?php endif; ?></tr>
  <?php foreach ($list as $p): ?>
    <tr><td data-label="Nama"><?= e($p['nama_perangkat']) ?></td><td data-label="Jenis"><?= e($p['jenis']) ?></td><td data-label="Keterangan"><?= e($p['keterangan']) ?></td>
    <?php if (can(['Admin'])): ?><td data-label=""><a class="btn sm sec" href="perangkat.php?edit=<?= $p['id_perangkat'] ?>">Ubah</a>
      <form class="inline" method="post" onsubmit="return confirm('Hapus perangkat ini?')"><?= csrf_field() ?>
        <input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= $p['id_perangkat'] ?>">
        <button class="btn sm del" type="submit">Hapus</button></form></td><?php endif; ?></tr>
  <?php endforeach; ?></table>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
