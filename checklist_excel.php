<?php
// Unduh checklist sebagai file Excel (.xlsx). Tanpa library tambahan, memakai ZipArchive bawaan PHP.
require 'config.php';
require_login();

// ---- filter: sama dengan halaman checklist, atau satu checklist lewat ?id= ----
$where = ' WHERE 1=1'; $t = ''; $p = [];
if (!empty($_GET['id']))                    { $where .= ' AND c.id_checklist=?'; $t .= 'i'; $p[] = (int)$_GET['id']; }
if (($_GET['tanggal'] ?? '') !== '')        { $where .= ' AND c.tanggal=?';      $t .= 's'; $p[] = $_GET['tanggal']; }
if (($_GET['nis'] ?? '') !== '')            { $where .= ' AND c.nis=?';          $t .= 'i'; $p[] = (int)$_GET['nis']; }
if (in_array($_GET['status'] ?? '', ['Menunggu', 'Diterima', 'Ditolak'], true)) { $where .= ' AND c.status_verifikasi=?'; $t .= 's'; $p[] = $_GET['status']; }

$sum = rows("SELECT c.id_checklist,c.tanggal,c.jam,c.status_verifikasi,s.nis,s.nama,s.kelas,
                    COUNT(d.id_detail) jml, COALESCE(SUM(d.status='Ya'),0) ya
             FROM checklist c JOIN siswa s ON s.nis=c.nis
             LEFT JOIN detail_checklist d ON d.id_checklist=c.id_checklist $where
             GROUP BY c.id_checklist,c.tanggal,c.jam,c.status_verifikasi,s.nis,s.nama,s.kelas
             ORDER BY c.tanggal DESC, c.jam DESC", $t, $p);
$det = rows("SELECT c.id_checklist,c.tanggal,c.jam,c.status_verifikasi,s.nis,s.nama,s.kelas,
                    pr.nama_perangkat,pr.jenis,d.tahapan,d.kondisi,d.status,d.keterangan
             FROM detail_checklist d
             JOIN checklist c ON c.id_checklist=d.id_checklist
             JOIN siswa s ON s.nis=c.nis
             JOIN perangkat pr ON pr.id_perangkat=d.id_perangkat $where
             ORDER BY c.tanggal DESC, c.jam DESC, c.id_checklist, FIELD(d.tahapan,'SEBELUM','SELAMA','SESUDAH','5R'), pr.nama_perangkat", $t, $p);

// ---- isi lembar kerja ----
$r1 = [['No', 'ID Checklist', 'Tanggal', 'Jam', 'NIS', 'Nama', 'Kelas', 'Status', 'Jumlah Item', 'Dijawab Ya', 'Dijawab Tidak', 'Kepatuhan (%)']];
foreach ($sum as $i => $r) {
    $jml = (int)$r['jml']; $ya = (int)$r['ya'];
    $r1[] = [$i + 1, (int)$r['id_checklist'], $r['tanggal'], substr($r['jam'], 0, 5), (string)$r['nis'], $r['nama'], $r['kelas'],
             $r['status_verifikasi'], $jml, $ya, $jml - $ya, $jml ? (int)round($ya / $jml * 100) : 0];
}
$r2 = [['No', 'Tanggal', 'Jam', 'NIS', 'Nama', 'Kelas', 'Status', 'Perangkat', 'Jenis', 'Tahapan', 'Kondisi', 'Dilakukan', 'Keterangan']];
foreach ($det as $i => $r) {
    $r2[] = [$i + 1, $r['tanggal'], substr($r['jam'], 0, 5), (string)$r['nis'], $r['nama'], $r['kelas'], $r['status_verifikasi'],
             $r['nama_perangkat'], $r['jenis'], $r['tahapan'], $r['kondisi'], $r['status'], (string)$r['keterangan']];
}
$sheets = [
    ['name' => 'Ringkasan', 'cols' => [6, 13, 12, 8, 12, 26, 10, 12, 12, 12, 14, 14], 'rows' => $r1],
    ['name' => 'Detail',    'cols' => [6, 12, 8, 12, 26, 10, 12, 24, 16, 12, 24, 12, 36], 'rows' => $r2],
];

$nama = !empty($_GET['id']) && $sum ? 'checklist_' . (int)$_GET['id'] . '_' . preg_replace('/[^A-Za-z0-9]+/', '-', $sum[0]['nama']) : 'checklist_' . date('Y-m-d_Hi');

// ---- pembuat file xlsx ----
function xl_col($i) { $s = ''; for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s; return $s; }
function xl_esc($v) {
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$v);
    return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
function xl_sheet(array $cols, array $rows) {
    $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
       . '<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
       . '<sheetFormatPr defaultRowHeight="18"/><cols>';
    foreach ($cols as $i => $w) $x .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
    $x .= '</cols><sheetData>';
    foreach ($rows as $r => $row) {
        $x .= '<row r="' . ($r + 1) . '"' . ($r === 0 ? ' ht="28" customHeight="1"' : '') . '>';
        foreach ($row as $c => $v) {
            $ref = xl_col($c) . ($r + 1);
            if (is_int($v) || is_float($v)) $x .= '<c r="' . $ref . '" s="3"><v>' . $v . '</v></c>';
            else $x .= '<c r="' . $ref . '" s="' . ($r === 0 ? 1 : 2) . '" t="inlineStr"><is><t xml:space="preserve">' . xl_esc($v) . '</t></is></c>';
        }
        $x .= '</row>';
    }
    $x .= '</sheetData>';
    if (count($rows) > 1) $x .= '<autoFilter ref="A1:' . xl_col(count($cols) - 1) . count($rows) . '"/>';
    return $x . '</worksheet>';
}

if (class_exists('ZipArchive')) {
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    $z = new ZipArchive();
    $z->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $z->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
    $z->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $z->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Ringkasan" sheetId="1" r:id="rId1"/><sheet name="Detail" sheetId="2" r:id="rId2"/></sheets></workbook>');
    $z->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $z->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF235347"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFBFD0B8"/></left><right style="thin"><color rgb="FFBFD0B8"/></right><top style="thin"><color rgb="FFBFD0B8"/></top><bottom style="thin"><color rgb="FFBFD0B8"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
    foreach ($sheets as $i => $s) $z->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', xl_sheet($s['cols'], $s['rows']));
    $z->close();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nama . '.xlsx"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: no-store');
    readfile($tmp);
    unlink($tmp);
    exit;
}

// Cadangan bila ekstensi zip PHP belum aktif: tabel HTML yang tetap bisa dibuka di Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nama . '.xls"');
echo "\xEF\xBB\xBF<html><head><meta charset=\"UTF-8\"></head><body>";
foreach ($sheets as $s) {
    echo '<h3>' . e($s['name']) . '</h3><table border="1">';
    foreach ($s['rows'] as $row) { echo '<tr>'; foreach ($row as $v) echo '<td>' . e($v) . '</td>'; echo '</tr>'; }
    echo '</table><br>';
}
echo '</body></html>';
