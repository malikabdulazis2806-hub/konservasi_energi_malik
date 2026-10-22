# Aplikasi Konservasi Energi (PHP + MySQL)

## Cara pasang (XAMPP / Laragon)
1. Salin folder ini ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka phpMyAdmin, klik tab **Impor**, pilih **`database.sql`** (satu-satunya file database), lalu **Kirim**.
   File ini menghapus database `konservasi_energi` lama (jika ada) dan membuat yang baru lengkap dengan data contoh.
3. Cek `config.php` (bawaan: host `localhost`, user `root`, password kosong).
4. Buka `http://localhost/konservasi_energi_malik/`

## Akun contoh (password disimpan tanpa hash)
| Username | Password | Level |
|---|---|---|
| admin | admin123 | Admin |
| guru  | guru123  | Guru |
| siswa | siswa123 | Siswa |

## Hak akses
- Admin: semua menu, dan satu-satunya yang bisa menerima/menolak checklist serta mengubah data.
- Guru: mengisi checklist seperti siswa dan memantau semua data tanpa bisa mengubah apa pun.
- Siswa: mengisi dan melihat checklist.

## Tampilan
Tema kaca gelap hijau dengan palet #051F20 #0B2B26 #163832 #235347 #8EB69B #DAF1DE (diatur di `:root` pada `assets/style.css`). Menu berupa pil melayang di atas (di HP pindah ke bawah), judul besar samar di belakang kartu, kartu kaca dengan sudut bulat, dan tombol pil. Halaman login bergaya "Coming soon" dengan kartu kaca di tengah. Font: Poppins (Google Fonts, butuh internet).

## Login tersimpan
Centang "Simpan login ini di perangkat" saat masuk. Lain kali geser tombol **Login tersimpan** lalu klik akunmu untuk langsung masuk (maks. 5 akun, 30 hari). Jangan dipakai di komputer umum.

## Animasi
Riak saat menekan, halaman memudar saat pindah, putaran memuat saat mengirim formulir. Otomatis mati jika perangkat memakai pengaturan "kurangi gerakan".

## Unduh Excel
Di halaman Checklist ada tombol **Unduh Excel** (mengikuti filter tanggal, siswa, dan status yang sedang dipilih). Di halaman detail checklist ada **Simpan PDF** dan **Unduh Excel** untuk satu checklist. File .xlsx berisi dua lembar: Ringkasan dan Detail. Butuh ekstensi `zip` PHP (aktif bawaan di XAMPP).
