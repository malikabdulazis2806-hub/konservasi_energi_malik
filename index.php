<?php
require 'config.php';
require_login();
$title = 'Beranda';
$n = [
  'Siswa'     => row('SELECT COUNT(*) c FROM siswa')['c'],
  'Perangkat' => row('SELECT COUNT(*) c FROM perangkat')['c'],
  'Checklist' => row('SELECT COUNT(*) c FROM checklist')['c'],
  'Menunggu diterima' => row("SELECT COUNT(*) c FROM checklist WHERE status_verifikasi='Menunggu'")['c'],
];
$tot = row("SELECT COUNT(*) t, SUM(status='Ya') y FROM detail_checklist");
$pct = $tot['t'] ? round($tot['y'] / $tot['t'] * 100) : 0;
$recent = rows('SELECT c.id_checklist,c.tanggal,c.jam,c.status_verifikasi,s.nama,s.kelas FROM checklist c JOIN siswa s ON s.nis=c.nis ORDER BY c.tanggal DESC,c.jam DESC LIMIT 8');
$per = rows("SELECT d.tahapan, COUNT(*) t, SUM(d.status='Ya') y FROM detail_checklist d GROUP BY d.tahapan");
include 'includes/header.php';
?>
<h1>Halo, <?= e($_SESSION['user']['nama']) ?></h1>
<p class="sub">Ringkasan checklist konservasi energi sekolah.</p>
<div class="stats">
  <?php foreach ($n as $k => $v): ?><div class="stat<?= $k === 'Menunggu diterima' ? ' hl' : '' ?>"><b><?= $v ?></b><span><?= $k ?></span></div><?php endforeach; ?>
  <div class="stat"><b><?= $pct ?>%</b><span>Item checklist dijawab "Ya"</span></div>
</div>
<div class="card">
  <div class="bar"><h2>Checklist terbaru</h2><a class="btn" href="checklist_form.php">Isi checklist baru</a></div>
  <?php if (!$recent): ?><p class="muted">Belum ada checklist. Klik "Isi checklist baru" untuk memulai.</p><?php else: ?>
  <div class="scroll"><table>
    <tr><th>Tanggal</th><th>Jam</th><th>Siswa</th><th>Kelas</th><th>Status</th><th></th></tr>
    <?php foreach ($recent as $r): ?>
    <tr><td data-label="Tanggal"><?= e($r['tanggal']) ?></td><td data-label="Jam"><?= e(substr($r['jam'],0,5)) ?></td><td data-label="Siswa"><?= e($r['nama']) ?></td><td data-label="Kelas"><?= e($r['kelas']) ?></td>
      <td data-label="Status"><span class="badge <?= strtolower($r['status_verifikasi']) ?>"><?= e($r['status_verifikasi']) ?></span></td>
      <td data-label=""><a href="checklist_view.php?id=<?= $r['id_checklist'] ?>">Lihat</a></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>
<?php if ($per): ?>
<div class="card"><h2>Kepatuhan per tahapan</h2>
  <table><tr><th>Tahapan</th><th>Item</th><th>Jawaban "Ya"</th></tr>
  <?php foreach ($per as $p): ?><tr><td data-label="Tahapan"><?= e($p['tahapan']) ?></td><td data-label="Item"><?= $p['t'] ?></td><td data-label="Jawaban &quot;Ya&quot;"><?= round($p['y']/$p['t']*100) ?>%</td></tr><?php endforeach; ?>
  </table></div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
