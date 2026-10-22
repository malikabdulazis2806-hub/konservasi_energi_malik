<?php
require 'config.php';
require_login();
$title = 'Isi Checklist';
$siswa = rows('SELECT nis,nama,kelas FROM siswa ORDER BY nama');
$perangkat = rows('SELECT id_perangkat,nama_perangkat,jenis FROM perangkat ORDER BY nama_perangkat');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $ids = $_POST['id_perangkat'] ?? [];
    $ok = false;
    try {
        if (!$ids) throw new Exception('Tambahkan minimal satu baris perangkat.');
        $db->begin_transaction();
        q('INSERT INTO checklist (nis,tanggal,jam) VALUES (?,?,?)', 'iss', [(int)$_POST['nis'], $_POST['tanggal'], $_POST['jam']]);
        $idc = $db->insert_id;
        foreach ($ids as $i => $idp) {
            $tah = in_array($_POST['tahapan'][$i] ?? '', TAHAPAN, true) ? $_POST['tahapan'][$i] : 'SEBELUM';
            $sta = ($_POST['status'][$i] ?? '') === 'Ya' ? 'Ya' : 'Tidak';
            q('INSERT INTO detail_checklist (id_checklist,id_perangkat,tahapan,kondisi,status,keterangan) VALUES (?,?,?,?,?,?)',
              'iissss', [$idc, (int)$idp, $tah, trim($_POST['kondisi'][$i] ?? ''), $sta, trim($_POST['keterangan'][$i] ?? '')]);
        }
        $db->commit();
        flash('Checklist tersimpan.');
        redirect('checklist_view.php?id=' . $idc);
    } catch (Throwable $ex) {
        if ($db->errno || true) { try { $db->rollback(); } catch (Throwable $x) {} }
        flash($ex instanceof mysqli_sql_exception ? 'Gagal menyimpan: data tidak valid.' : $ex->getMessage(), 'err');
        redirect('checklist_form.php');
    }
}
include 'includes/header.php';
?>
<h1>Isi Checklist</h1>
<?php if (!$siswa || !$perangkat): ?>
<div class="card"><p>Data belum lengkap. Tambahkan <?= !$siswa ? 'siswa' : 'perangkat' ?> terlebih dahulu<?= can(['Admin','Guru']) ? ' di menu ' . (!$siswa ? '<a href="siswa.php">Siswa</a>' : '<a href="perangkat.php">Perangkat</a>') : ', hubungi guru atau admin' ?>.</p></div>
<?php else: ?>
<form method="post" class="card"><?= csrf_field() ?>
  <div class="grid">
    <div><label for="nis">Siswa</label><select id="nis" name="nis" required>
      <?php foreach ($siswa as $s): ?><option value="<?= $s['nis'] ?>"><?= e($s['nama']) ?> (<?= e($s['kelas']) ?>, NIS <?= $s['nis'] ?>)</option><?php endforeach; ?></select></div>
    <div><label for="tgl">Tanggal</label><input id="tgl" type="date" name="tanggal" required value="<?= date('Y-m-d') ?>"></div>
    <div><label for="jam">Jam</label><input id="jam" type="time" name="jam" required value="<?= date('H:i') ?>"></div>
  </div>
  <h2 style="margin-top:20px">Detail perangkat</h2>
  <div class="row-detail head"><span>Perangkat</span><span>Tahapan</span><span>Kondisi</span><span>Dilakukan?</span><span>Keterangan</span><span></span></div>
  <div id="rows"></div>
  <p><button type="button" class="btn sec" id="add">Tambah baris</button></p>
  <p><button class="btn" type="submit">Simpan checklist</button> <a class="btn sec" href="checklist.php">Batal</a></p>
</form>
<template id="tpl">
  <div class="row-detail">
    <select name="id_perangkat[]" aria-label="Perangkat" required>
      <?php foreach ($perangkat as $p): ?><option value="<?= $p['id_perangkat'] ?>"><?= e($p['nama_perangkat']) ?> (<?= e($p['jenis']) ?>)</option><?php endforeach; ?></select>
    <select name="tahapan[]" aria-label="Tahapan"><?php foreach (TAHAPAN as $t): ?><option><?= $t ?></option><?php endforeach; ?></select>
    <input name="kondisi[]" maxlength="100" placeholder="Contoh: menyala, mati" aria-label="Kondisi" required>
    <select name="status[]" aria-label="Status"><option>Ya</option><option>Tidak</option></select>
    <input name="keterangan[]" placeholder="Catatan (opsional)" aria-label="Keterangan">
    <button type="button" class="btn sm del rm" aria-label="Hapus baris">Hapus</button>
  </div>
</template>
<script>
const rows = document.getElementById('rows'), tpl = document.getElementById('tpl');
function addRow(){ rows.appendChild(tpl.content.cloneNode(true)); }
document.getElementById('add').onclick = addRow;
rows.addEventListener('click', e => {
  if (e.target.classList.contains('rm')) {
    const r = e.target.closest('.row-detail');
    r.classList.add('out');
    setTimeout(() => r.remove(), 220);
  }
});
addRow();
</script>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
