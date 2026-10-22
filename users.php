<?php
require 'config.php';
require_level(['Admin','Guru']);
$title = 'Pengguna';
$levels = ['Admin','Guru','Siswa'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_level(['Admin']);
    check_csrf();
    $act = $_POST['act'] ?? '';
    try {
        $lv = in_array($_POST['level'] ?? '', $levels, true) ? $_POST['level'] : 'Siswa';
        if ($act === 'add') {
            q('INSERT INTO users (username,password,nama,level) VALUES (?,?,?,?)', 'ssss',
              [trim($_POST['username']), $_POST['password'], trim($_POST['nama']), $lv]);
            flash('Pengguna ditambahkan.');
        } elseif ($act === 'edit') {
            q('UPDATE users SET username=?, nama=?, level=? WHERE id_user=?', 'sssi', [trim($_POST['username']), trim($_POST['nama']), $lv, (int)$_POST['id']]);
            if (!empty($_POST['password']))
                q('UPDATE users SET password=? WHERE id_user=?', 'si', [$_POST['password'], (int)$_POST['id']]);
            if (!empty($_POST['password'])) { try { q('DELETE FROM remember_token WHERE id_user=?', 'i', [(int)$_POST['id']]); } catch (Throwable $e) {} }
            flash('Pengguna disimpan.');
        } elseif ($act === 'del') {
            if ((int)$_POST['id'] === (int)$_SESSION['user']['id']) throw new Exception('self');
            q('DELETE FROM users WHERE id_user=?', 'i', [(int)$_POST['id']]);
            flash('Pengguna dihapus.');
        }
    } catch (Throwable $ex) {
        flash($ex->getMessage() === 'self' ? 'Anda tidak bisa menghapus akun yang sedang dipakai.' : 'Gagal: username sudah dipakai atau data tidak valid.', 'err');
    }
    redirect('users.php');
}
$edit = (can(['Admin']) && isset($_GET['edit'])) ? row('SELECT * FROM users WHERE id_user=?', 'i', [(int)$_GET['edit']]) : null;
$list = rows('SELECT id_user,username,nama,level FROM users ORDER BY level, nama');
include 'includes/header.php';
?>
<h1>Pengguna</h1>
<?php if (!can(['Admin'])): ?><p class="sub">Mode pantau: kamu bisa melihat data, tetapi tidak bisa mengubahnya.</p><?php endif; ?>
<?php if (can(['Admin'])): ?>
<div class="card">
  <h2><?= $edit ? 'Ubah pengguna' : 'Tambah pengguna' ?></h2>
  <form method="post"><?= csrf_field() ?>
    <input type="hidden" name="act" value="<?= $edit ? 'edit' : 'add' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id_user'] ?>"><?php endif; ?>
    <div class="grid">
      <div><label for="un">Username</label><input id="un" name="username" maxlength="50" required value="<?= e($edit['username'] ?? '') ?>"></div>
      <div><label for="pw">Password <?= $edit ? '(kosongkan jika tidak diubah)' : '' ?></label><input id="pw" type="password" name="password" <?= $edit ? '' : 'required' ?>></div>
      <div><label for="nm">Nama</label><input id="nm" name="nama" maxlength="100" required value="<?= e($edit['nama'] ?? '') ?>"></div>
      <div><label for="lv">Level</label><select id="lv" name="level">
        <?php foreach ($levels as $l): ?><option <?= ($edit['level'] ?? '') === $l ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    </div>
    <p><button class="btn" type="submit">Simpan pengguna</button>
    <?php if ($edit): ?><a class="btn sec" href="users.php">Batal</a><?php endif; ?></p>
  </form>
</div>
<?php endif; ?>
<div class="card scroll"><table>
  <tr><th>Username</th><th>Nama</th><th>Level</th><?php if (can(['Admin'])): ?><th></th><?php endif; ?></tr>
  <?php foreach ($list as $u): ?>
  <tr><td data-label="Username"><?= e($u['username']) ?></td><td data-label="Nama"><?= e($u['nama']) ?></td><td data-label="Level"><?= e($u['level']) ?></td>
  <?php if (can(['Admin'])): ?><td data-label=""><a class="btn sm sec" href="users.php?edit=<?= $u['id_user'] ?>">Ubah</a>
    <form class="inline" method="post" onsubmit="return confirm('Hapus pengguna ini?')"><?= csrf_field() ?>
      <input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= $u['id_user'] ?>">
      <button class="btn sm del" type="submit">Hapus</button></form></td><?php endif; ?></tr>
  <?php endforeach; ?></table></div>
<?php include 'includes/footer.php'; ?>
