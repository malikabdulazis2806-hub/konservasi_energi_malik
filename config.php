<?php
// ====== Konfigurasi database ======
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'konservasi_energi';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    die('Koneksi database gagal. Periksa pengaturan di config.php. (' . htmlspecialchars($ex->getMessage()) . ')');
}

const TAHAPAN = ['SEBELUM', 'SELAMA', 'SESUDAH', '5R'];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function q($sql, $types = '', $params = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($types) $st->bind_param($types, ...$params);
    $st->execute();
    return $st;
}
function rows($sql, $types = '', $params = []) {
    return q($sql, $types, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
}
function row($sql, $types = '', $params = []) {
    $r = rows($sql, $types, $params);
    return $r[0] ?? null;
}

function require_login() {
    if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
}
function require_level(array $levels) {
    require_login();
    if (!in_array($_SESSION['user']['level'], $levels, true)) {
        http_response_code(403);
        die('Akses ditolak untuk level akun Anda. <a href="index.php">Kembali</a>');
    }
}
function can(array $levels) {
    return !empty($_SESSION['user']) && in_array($_SESSION['user']['level'], $levels, true);
}

function csrf_field() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
            http_response_code(400);
            die('Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        }
    }
}
function flash($msg = null, $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function redirect($url) { header("Location: $url"); exit; }

// ====== Login tersimpan (maks. 5 akun per perangkat, berlaku 30 hari) ======
const REMEMBER_DAYS = 30;
const SAVED_MAX = 5;

function saved_cookie_set(array $list) {
    $json = json_encode(array_values($list));
    $_COOKIE['akun_simpan'] = $json;
    setcookie('akun_simpan', $json, [
        'expires' => time() + REMEMBER_DAYS * 86400, 'path' => '/', 'httponly' => true,
        'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']),
    ]);
}
function saved_list() {
    $a = json_decode($_COOKIE['akun_simpan'] ?? '', true);
    if (!is_array($a)) return [];
    return array_values(array_filter($a, fn($x) => is_string($x) && preg_match('/^[a-f0-9]{24}:[a-f0-9]{64}$/', $x)));
}
// Daftar akun tersimpan yang masih valid di perangkat ini
function saved_accounts() {
    $out = [];
    try {
        foreach (saved_list() as $x) {
            [$sel, $val] = explode(':', $x);
            $t = row('SELECT t.id_user,t.validator_hash,u.username,u.nama,u.level FROM remember_token t
                      JOIN users u ON u.id_user=t.id_user WHERE t.selector=? AND t.expires_at > NOW()', 's', [$sel]);
            if ($t && hash_equals($t['validator_hash'], hash('sha256', $val))) {
                $t['sel'] = $sel; $t['val'] = $val; $out[] = $t;
            }
        }
    } catch (Throwable $e) { /* tabel remember_token belum dibuat */ }
    return $out;
}
// Simpan login akun ini di perangkat (mengganti simpanan lama akun yang sama)
function remember_issue($id_user) {
    try {
        q('DELETE FROM remember_token WHERE expires_at < NOW()');
        $keep = [];
        foreach (saved_accounts() as $a) {
            if ((int)$a['id_user'] === (int)$id_user) q('DELETE FROM remember_token WHERE selector=?', 's', [$a['sel']]);
            else $keep[] = $a['sel'] . ':' . $a['val'];
        }
        $keep = array_slice($keep, -(SAVED_MAX - 1));
        $sel = bin2hex(random_bytes(12));
        $val = bin2hex(random_bytes(32));
        q('INSERT INTO remember_token (id_user,selector,validator_hash,expires_at) VALUES (?,?,?,DATE_ADD(NOW(), INTERVAL ? DAY))',
          'issi', [(int)$id_user, $sel, hash('sha256', $val), REMEMBER_DAYS]);
        $keep[] = $sel . ':' . $val;
        saved_cookie_set($keep);
    } catch (Throwable $e) {}
}
// Hapus satu akun dari daftar tersimpan
function remember_forget($sel) {
    try {
        q('DELETE FROM remember_token WHERE selector=?', 's', [$sel]);
        saved_cookie_set(array_filter(saved_list(), fn($x) => explode(':', $x)[0] !== $sel));
    } catch (Throwable $e) {}
}
