# Server clock sync for S3-compatible uploads

## Summary

Aplikasi yang mengunggah file ke penyimpanan S3-compatible (termasuk non-AWS seperti `s3.bphn.go.id`) membutuhkan jam sistem server yang selaras dengan jam layanan S3. Spesifikasi ini mendefinisikan perilaku yang harus dijamin di setiap server aplikasi agar upload tidak gagal karena selisih waktu, dan agar operator dapat mendiagnosis serta memulihkan kondisi yang sama di aplikasi atau host lain.

## Problem

Upload file (contoh: pas foto profil) bisa gagal di lingkungan Docker/production dengan pesan generik di UI (`undefined: undefined`, “failed to upload”), sementara di mesin local berjalan normal. Akar masalah yang sering terlewat: jam host lebih maju atau mundur dari jam server S3, sehingga permintaan bertanda tangan ditolak dengan `RequestTimeTooSkewed`. Gejala ini bisa muncul di aplikasi lain di server lain yang memakai endpoint S3 yang sama atau sejenis.

## Goals / Non-goals

**Goals**

- Operator memahami gejala user-facing vs sinyal diagnosis yang benar.
- Setiap host yang berbicara ke S3-compatible menjaga jam dalam toleransi yang diterima layanan.
- Setelah perbaikan, jam tetap wajar setelah reboot (RTC selaras), bahkan jika NTP publik belum tersedia.
- Dokumen ini dapat dipakai ulang lintas aplikasi di infrastruktur BPHN.

**Non-goals**

- Mengganti penyedia S3 atau mengubah desain fitur upload di aplikasi.
- Menjamin sync NTP jika jaringan memblokir UDP 123 (itu tanggung jawab jaringan; dokumen hanya mendefinisikan perilaku yang diharapkan dan mitigasi).
- Menjelaskan implementasi internal SDK/Flysystem.

## Behavior

1. **Prasyarat waktu untuk upload sukses.** Setiap operasi tulis ke object storage S3-compatible (PutObject dan sejenisnya) berhasil hanya jika jam sistem host yang menjalankan aplikasi berada dalam toleransi waktu yang diterima layanan S3 (umumnya dalam orde menit; selisih jam penuh menyebabkan gagal). Toleransi ini berlaku untuk AWS dan S3-compatible non-AWS yang memakai signature berbasis waktu.

2. **Gejala yang dilihat pengguna akhir.** Ketika jam host di luar toleransi, pengguna melihat kegagalan upload file (pesan generik atau “failed to upload”), bukan pesan eksplisit “jam server salah”. Fitur lain yang tidak memanggil S3 dapat tetap tampak normal.

3. **Gejala yang dilihat operator.** Operator yang memaksa error eksplisit pada operasi tulis ke disk S3 melihat kegagalan bertipe “tidak dapat menulis file”, dengan penyebab inti berisi `RequestTimeTooSkewed` / “difference between the request time and the server's time is too large”. Operasi put yang menelan error dapat hanya mengembalikan gagal tanpa exception; operator harus dapat memaksa pesan penyebab agar terlihat.

4. **Sumber kebenaran waktu untuk diagnosis.** Operator membandingkan tiga sumber:
   - jam universal (UTC) host aplikasi,
   - jam UTC di dalam container aplikasi (jika workload di-container; container mengikuti jam kernel host),
   - nilai header `Date` dari respons HTTP ke endpoint S3.
   Upload ke S3 dianggap aman dari sisi waktu hanya jika ketiga sumber (atau setidaknya host UTC vs header `Date` S3) selisihnya dalam orde detik/menit, bukan jam.

5. **Kesalahan umum: timezone vs wall clock.** Menyetel “jam lokal” tanpa memperhitungkan zona waktu host (misalnya WIB, UTC+7) dapat membuat UTC host bergeser tepat 7 jam relatif ke S3, meskipun angka jam “terlihat” sama dengan header S3. Operator wajib memverifikasi dalam UTC, bukan hanya jam lokal.

6. **RTC vs system clock.** Status waktu host harus membedakan:
   - system clock (yang dipakai aplikasi/container),
   - RTC (hardware clock yang dipakai setelah reboot).
   Jika system clock sudah benar tetapi RTC meleset, upload bisa sukses sekarang namun gagal lagi setelah reboot. Setelah koreksi system clock, RTC harus diselaraskan ke system clock sebelum mengandalkan host untuk production berkelanjutan.

7. **NTP aktif vs NTP tersinkron.** Layanan NTP/chrony yang “active” tidak cukup sebagai bukti jam aman. Operator hanya menganggap sync NTP berhasil jika status menunjukkan jam tersinkron (misalnya synchronized yes / leap status synchronised) dan sumber NTP reachable. Jika semua sumber NTP unreachable (reach 0), jam tidak dijaga oleh NTP meskipun service berjalan.

8. **Jaringan yang memblokir NTP.** Di lingkungan yang memblokir outbound NTP (UDP 123), operator mengharapkan `Not synchronised` sebagai kondisi yang diketahui. Dalam kondisi itu:
   - upload tetap boleh sukses selama system clock (dan setelah perbaikan, RTC) selaras dengan S3,
   - mitigasi wajib adalah menyimpan jam yang benar ke RTC,
   - resolusi jangka panjang adalah NTP internal organisasi atau pembukaan akses NTP, bukan menonaktifkan permanen koreksi jam.

9. **Alur pemulihan yang diterima.** Ketika `RequestTimeTooSkewed` terkonfirmasi, operator:
   1. membandingkan host UTC dengan header `Date` S3,
   2. mengkoreksi system clock ke waktu yang selaras dengan S3 (dengan metode yang menghormati zona waktu host),
   3. memverifikasi ulang host UTC ≈ `Date` S3,
   4. menyimpan jam ke RTC,
   5. menguji ulang operasi tulis ke bucket (harus sukses),
   6. baru menguji ulang upload dari UI aplikasi.
   Restart aplikasi/container tanpa mengkoreksi jam tidak mengubah hasil.

10. **Setelah reboot.** Setelah host di-reboot, operator mengharapkan system clock dan RTC tetap dalam toleransi terhadap `Date` S3 tanpa harus mengulang koreksi manual, selama RTC telah diselaraskan sebelumnya. Jika setelah reboot selisih kembali berorde jam, RTC belum tersimpan benar atau sumber waktu boot salah.

11. **Cakupan lintas aplikasi.** Invarian (1)–(10) berlaku untuk setiap aplikasi di host manapun yang memanggil endpoint S3-compatible dengan signature waktu—bukan hanya FungsionalPro. Gejala UI bisa berbeda per aplikasi; sinyal diagnosis `RequestTimeTooSkewed` dan perbandingan jam vs header `Date` S3 tetap sama.

12. **Yang bukan akar masalah tipikal untuk gejala ini.** Ketika penyebab terkonfirmasi `RequestTimeTooSkewed`, operator tidak menghabiskan waktu utama pada permission folder upload lokal, healthcheck worker yang “unhealthy”, atau perbedaan “pakai AWS vs hanya S3”, selama konektivitas TLS ke endpoint S3 sudah berhasil dan kredensial terisi. Masalah-masalah itu dapat ada secara paralel, tetapi tidak menjelaskan error skew waktu.

13. **Keadaan sehat yang dijamin untuk production.** Untuk host production yang bergantung pada S3-compatible storage, kondisi sehat yang diharapkan adalah:
    - put object uji ke bucket sukses,
    - host UTC ≈ header `Date` S3 (orde detik/menit),
    - RTC ≈ Universal time,
    - layanan NTP aktif; dan bila jaringan mengizinkan, system clock synchronized.
    **Open question:** apakah organisasi menetapkan NTP internal resmi (hostname) yang wajib dipakai semua host aplikasi, agar `synchronized: yes` menjadi standar wajib—bukan hanya mitigasi RTC?

14. **Dokumentasi untuk operator berikutnya.** Saat kejadian serupa muncul di server/aplikasi lain, operator mengikuti diagnosis (4) dan pemulihan (9) tanpa mengasumsikan bug fitur upload, kecuali put uji ke S3 sukses sementara UI tetap gagal (baru saat itu fokus ke konfigurasi aplikasi/disk temp/upload path).

## Linux — perintah praktis (operator)

Ganti `S3_ENDPOINT` dengan URL S3-compatible Anda (contoh: `https://s3.bphn.go.id`). Perintah di bawah untuk Ubuntu/Debian; sesuaikan jika distro lain.

### 1) Diagnosis cepat — bandingkan jam

```bash
# Jam host (UTC), jam di container (jika ada), header Date dari S3
date -u
# sudo docker compose exec app date -u   # opsional, jika app di Docker
curl -sI "$S3_ENDPOINT" | grep -i '^date:'

timedatectl status
# Perhatikan: Universal time, RTC time, System clock synchronized, NTP service
```

Selisih **orde jam** → kemungkinan besar `RequestTimeTooSkewed`. Selisih **detik/menit** → jam biasanya OK.

### 2) Konfirmasi error S3 (contoh Laravel)

```bash
# Put yang menelan error sering hanya "PUT_FALSE" — paksa throw:
sudo docker compose exec app php artisan tinker --execute='
config(["filesystems.disks.s3.throw" => true]);
try {
  \Illuminate\Support\Facades\Storage::disk("s3")->put("healthcheck/test.txt", "ok");
  echo "PUT_OK\n";
} catch (Throwable $e) {
  echo "MSG: " . $e->getMessage() . "\n";
}
'
```

Cari teks: `RequestTimeTooSkewed`.

### 3) Koreksi jam — set UTC dari header S3 (aman untuk host WIB)

`timedatectl set-time` mengisi **jam lokal**. Untuk menghindari geser 7 jam di WIB, set lewat **UTC**:

```bash
sudo timedatectl set-ntp false

S3_DATE=$(curl -sI "$S3_ENDPOINT" | awk -F': ' 'tolower($1)=="date"{print $2}' | tr -d '\r')
echo "S3 time: $S3_DATE"

# Set system clock dalam UTC (bukan local)
sudo date -u -s "$(date -u -d "$S3_DATE" '+%Y-%m-%d %H:%M:%S')"

# Verifikasi — harus hampir sama
date -u
curl -sI "$S3_ENDPOINT" | grep -i '^date:'
```

Alternatif jika ingin `timedatectl` (konversi ke lokal otomatis — **tanpa** `-u` di `date -d`):

```bash
sudo timedatectl set-time "$(date -d "$S3_DATE" '+%Y-%m-%d %H:%M:%S')"
```

### 4) Simpan ke RTC (supaya reboot tidak kacau)

Di Ubuntu 24.04 (`noble`), `hwclock` ada di paket terpisah:

```bash
sudo apt install -y util-linux-extra
sudo /usr/sbin/hwclock --systohc
sudo /usr/sbin/hwclock --show --utc
timedatectl status
# RTC time harus ≈ Universal time
```

### 5) Nyalakan NTP / chrony (jangan biarkan `set-ntp false`)

```bash
sudo apt install -y chrony
sudo systemctl enable --now chrony
sudo chronyc -a makestep
sudo timedatectl set-ntp true
sudo timedatectl status

# Cek apakah peer NTP benar-benar tercapai
sudo chronyc tracking
sudo chronyc sources -v
```

Interpretasi singkat:

| Output | Artinya |
|--------|---------|
| `System clock synchronized: yes` + sumber `^*` | NTP OK |
| `NTP service: active` tapi `synchronized: no`, semua `^?` / `Reach 0` | Chrony jalan, **UDP 123 diblok** atau tidak ada NTP reachable |
| `Leap status: Not synchronised` | Belum sync — jangan anggap aman hanya karena service active |

Pastikan `rtcsync` ada di config chrony:

```bash
grep -E '^(pool|server|rtcsync)' /etc/chrony/chrony.conf
# jika rtcsync belum ada:
echo 'rtcsync' | sudo tee -a /etc/chrony/chrony.conf
sudo systemctl restart chrony
```

### 6) Jika NTP publik tidak reachable (umum di jaringan instansi)

1. Minta tim jaringan: buka **outbound UDP 123**, atau beri hostname **NTP internal**.
2. Contoh pakai NTP internal:

```bash
sudo nano /etc/chrony/chrony.conf
# server ntp.internal.example iburst
# rtcsync

sudo systemctl restart chrony
sudo chronyc -a makestep
sudo chronyc sources -v
```

3. Mitigasi sementara tanpa NTP: pastikan langkah **(4) RTC** sudah dijalankan; pantau drift dengan membandingkan `date -u` vs `Date:` S3 secara berkala.

### 7) Setelah jam benar — uji ulang app

```bash
# Docker example
sudo docker compose restart app worker

# Ulangi put uji (langkah 2) → harus PUT_OK
# Baru uji upload dari UI
```

### 8) Checklist sehat (copy-paste)

```bash
date -u
curl -sI "$S3_ENDPOINT" | grep -i '^date:'
timedatectl status
sudo chronyc sources -v 2>/dev/null | head
```

OK jika: host UTC ≈ `Date` S3, RTC ≈ Universal time. Ideal jika: `synchronized: yes`.
