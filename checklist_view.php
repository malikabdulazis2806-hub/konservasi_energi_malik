<?php
require 'config.php';
require_login();
$title = 'Detail Checklist';
$c = row('SELECT c.*, s.nama, s.kelas FROM checklist c JOIN siswa s ON s.nis=c.nis WHERE c.id_checklist=?', 'i', [(int)($_GET['id'] ?? 0)]);
if (!$c) { flash('Checklist tidak ditemukan.', 'err'); redirect('checklist.php'); }
$det = rows('SELECT d.*, p.nama_perangkat, p.jenis FROM detail_checklist d JOIN perangkat p ON p.id_perangkat=d.id_perangkat WHERE d.id_checklist=? ORDER BY FIELD(d.tahapan,"SEBELUM","SELAMA","SESUDAH","5R"), p.nama_perangkat', 'i', [$c['id_checklist']]);
include 'includes/header.php';
?>
<div class="bar"><h1>Detail Checklist</h1><span class="acts"><a class="btn sec" href="checklist.php">Kembali</a><button type="button" class="btn sec" onclick="window.print()">Simpan PDF</button><a class="btn" href="checklist_excel.php?id=<?= $c['id_checklist'] ?>" download>Unduh Excel</a></span></div>
<div class="card">
  <div class="grid">
    <div><span class="muted">Siswa</span><br><b><?= e($c['nama']) ?></b> (<?= e($c['kelas']) ?>, NIS <?= e($c['nis']) ?>)</div>
    <div><span class="muted">Tanggal</span><br><b><?= e($c['tanggal']) ?></b></div>
    <div><span class="muted">Jam</span><br><b><?= e(substr($c['jam'],0,5)) ?></b></div>
    <div><span class="muted">Status</span><br><span class="badge <?= strtolower($c['status_verifikasi']) ?>"><?= e($c['status_verifikasi']) ?></span></div>
  </div>
</div>
<?php if (can(['Admin'])): ?>
<div class="card">
  <h2>Keputusan admin</h2>
  <form method="post" action="checklist.php" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id_checklist'] ?>">
    <?php if ($c['status_verifikasi'] !== 'Diterima'): ?><button class="btn" name="act" value="terima" type="submit">Terima checklist</button><?php endif; ?>
    <?php if ($c['status_verifikasi'] !== 'Ditolak'): ?><button class="btn del" name="act" value="tolak" type="submit" onclick="return confirm('Tolak checklist ini?')">Tolak checklist</button><?php endif; ?>
    <?php if ($c['status_verifikasi'] !== 'Menunggu'): ?><button class="btn sec" name="act" value="reset" type="submit">Kembalikan ke menunggu</button><?php endif; ?>
  </form>
</div>
<?php endif; ?>
<div class="card scroll">
<?php if (!$det): ?><p class="muted">Checklist ini belum punya detail.</p><?php else: ?>
<table><tr><th>Tahapan</th><th>Perangkat</th><th>Jenis</th><th>Kondisi</th><th>Dilakukan</th><th>Keterangan</th></tr>
<?php foreach ($det as $d): ?>
<tr><td data-label="Tahapan"><?= e($d['tahapan']) ?></td><td data-label="Perangkat"><?= e($d['nama_perangkat']) ?></td><td data-label="Jenis"><?= e($d['jenis']) ?></td><td data-label="Kondisi"><?= e($d['kondisi']) ?></td>
<td data-label="Dilakukan"><span class="badge <?= strtolower($d['status']) ?>"><?= e($d['status']) ?></span></td><td data-label="Keterangan"><?= e($d['keterangan']) ?></td></tr>
<?php endforeach; ?></table>
<?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
