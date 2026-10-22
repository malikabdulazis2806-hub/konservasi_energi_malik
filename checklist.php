<?php
require 'config.php';
require_login();
$title = 'Checklist';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $act = $_POST['act'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($act === 'del') {
        require_level(['Admin']);
        q('DELETE FROM checklist WHERE id_checklist=?', 'i', [$id]);
        flash('Checklist dihapus.');
    } elseif ($act === 'terima' || $act === 'tolak' || $act === 'reset') {
        require_level(['Admin']);
        $st = ['terima' => 'Diterima', 'tolak' => 'Ditolak', 'reset' => 'Menunggu'][$act];
        q('UPDATE checklist SET status_verifikasi=? WHERE id_checklist=?', 'si', [$st, $id]);
        flash($act === 'terima' ? 'Checklist diterima.' : ($act === 'tolak' ? 'Checklist ditolak.' : 'Status dikembalikan ke menunggu.'));
    }
    redirect('checklist.php' . (!empty($_POST['back']) ? '?' . $_POST['back'] : ''));
}
$tgl = $_GET['tanggal'] ?? '';
$nis = $_GET['nis'] ?? '';
$stf = $_GET['status'] ?? '';
$sql = 'SELECT c.id_checklist,c.tanggal,c.jam,c.status_verifikasi,s.nis,s.nama,s.kelas,
        (SELECT COUNT(*) FROM detail_checklist d WHERE d.id_checklist=c.id_checklist) jml,
        (SELECT SUM(d.status="Ya") FROM detail_checklist d WHERE d.id_checklist=c.id_checklist) ya
        FROM checklist c JOIN siswa s ON s.nis=c.nis WHERE 1=1';
$t = ''; $p = [];
if ($tgl !== '') { $sql .= ' AND c.tanggal=?'; $t .= 's'; $p[] = $tgl; }
if ($nis !== '') { $sql .= ' AND c.nis=?'; $t .= 'i'; $p[] = (int)$nis; }
if (in_array($stf, ['Menunggu', 'Diterima', 'Ditolak'], true)) { $sql .= ' AND c.status_verifikasi=?'; $t .= 's'; $p[] = $stf; }
$sql .= ' ORDER BY (c.status_verifikasi="Menunggu") DESC, c.tanggal DESC, c.jam DESC';
$list = rows($sql, $t, $p);
$siswa = rows('SELECT nis,nama,kelas FROM siswa ORDER BY nama');
$back = $_SERVER['QUERY_STRING'] ?? '';
include 'includes/header.php';
?>
<div class="bar"><h1>Checklist</h1><span class="acts"><a class="btn sec" href="checklist_excel.php<?= $back !== '' ? '?' . e($back) : '' ?>" download>Unduh Excel</a><a class="btn" href="checklist_form.php">Isi checklist baru</a></span></div>
<form class="card filter grid" method="get">
  <div><label for="f1">Tanggal</label><input id="f1" type="date" name="tanggal" value="<?= e($tgl) ?>"></div>
  <div><label for="f2">Siswa</label><select id="f2" name="nis"><option value="">Semua siswa</option>
    <?php foreach ($siswa as $s): ?><option value="<?= $s['nis'] ?>" <?= $nis == $s['nis'] ? 'selected' : '' ?>><?= e($s['nama']) ?> (<?= e($s['kelas']) ?>)</option><?php endforeach; ?></select></div>
  <div><label for="f3">Status</label><select id="f3" name="status"><option value="">Semua status</option>
    <?php foreach (['Menunggu', 'Diterima', 'Ditolak'] as $x): ?><option <?= $stf === $x ? 'selected' : '' ?>><?= $x ?></option><?php endforeach; ?></select></div>
  <div style="align-self:end"><button class="btn" type="submit">Terapkan filter</button> <a class="btn sec" href="checklist.php">Reset</a></div>
</form>
<div class="card scroll">
<?php if (!$list): ?><p class="muted">Tidak ada checklist yang cocok.</p><?php else: ?>
<table><tr><th>Tanggal</th><th>Jam</th><th>Siswa</th><th>Kelas</th><th>Item</th><th>"Ya"</th><th>Status</th><th></th></tr>
<?php foreach ($list as $r): $sv = $r['status_verifikasi']; ?>
<tr><td data-label="Tanggal"><?= e($r['tanggal']) ?></td><td data-label="Jam"><?= e(substr($r['jam'], 0, 5)) ?></td><td data-label="Siswa"><?= e($r['nama']) ?></td><td data-label="Kelas"><?= e($r['kelas']) ?></td>
<td data-label="Item"><?= $r['jml'] ?></td><td data-label="&quot;Ya&quot;"><?= (int)$r['ya'] ?></td>
<td data-label="Status"><span class="badge <?= strtolower($sv) ?>"><?= e($sv) ?></span></td>
<td data-label=""><a class="btn sm sec" href="checklist_view.php?id=<?= $r['id_checklist'] ?>">Lihat</a>
<?php if (can(['Admin'])): ?>
  <?php if ($sv !== 'Diterima'): ?>
  <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="act" value="terima"><input type="hidden" name="id" value="<?= $r['id_checklist'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>"><button class="btn sm" type="submit">Terima</button></form>
  <?php endif; ?>
  <?php if ($sv === 'Menunggu'): ?>
  <form class="inline" method="post" onsubmit="return confirm('Tolak checklist ini?')"><?= csrf_field() ?><input type="hidden" name="act" value="tolak"><input type="hidden" name="id" value="<?= $r['id_checklist'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>"><button class="btn sm del" type="submit">Tolak</button></form>
  <?php endif; ?>
<?php endif; ?>
<?php if (can(['Admin'])): ?>
<form class="inline" method="post" onsubmit="return confirm('Hapus checklist beserta detailnya?')"><?= csrf_field() ?>
<input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= $r['id_checklist'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>"><button class="btn sm del" type="submit">Hapus</button></form>
<?php endif; ?></td></tr>
<?php endforeach; ?></table>
<?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
