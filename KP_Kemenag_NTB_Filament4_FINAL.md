# Aplikasi Pengajuan Kenaikan Pangkat (KP) Kemenag NTB – Filament 4

Blueprint ini untuk aplikasi Pengajuan KP dengan scope **Provinsi NTB**.
Role utama: **Operator Kab/Kota**, **Operator Kanwil**, dan **Admin**.
Data pegawai diambil dari **API eksternal** (tanpa master pegawai lokal).

Fokus aturan FINAL:
- Operator Kab/Kota hanya mengajukan pegawai Kab/Kota-nya
- Operator Kanwil **boleh mengajukan hanya pegawai Kanwil**
- Operator Kanwil juga berperan sebagai verifikator tingkat provinsi

---

## 1. Tujuan Sistem
- Standardisasi proses pengajuan KP Kab/Kota dan Kanwil
- Mengurangi kesalahan kelengkapan dokumen
- Mempermudah verifikasi dan pengembalian berkas
- Export ZIP terstruktur sesuai standar instansi
- Rekap Excel siap laporan

---

## 2. Struktur Operator (FINAL)

Total operator: **11**

### 2.1 Operator Kab/Kota (10)
1. Kabupaten Bima
2. Kabupaten Dompu
3. Kabupaten Lombok Barat
4. Kabupaten Lombok Tengah
5. Kabupaten Lombok Timur
6. Kabupaten Lombok Utara
7. Kabupaten Sumbawa
8. Kabupaten Sumbawa Barat
9. Kota Bima
10. Kota Mataram

### 2.2 Operator Kanwil (1)
11. Kanwil Kemenag Provinsi NTB

---

## 3. Aturan Akses & Scope (WAJIB)

### 3.1 Operator Kab/Kota
- region.type = kabkota
- Boleh membuat, mengedit, dan submit pengajuan
- Pegawai yang dipilih dari API **harus**:
  - kab_kota == region.city_name
- Tidak bisa melihat pengajuan kab/kota lain
- Tidak bisa memilih pegawai Kanwil

### 3.2 Operator Kanwil
- region.type = kanwil
- Boleh membuat, mengedit, dan submit pengajuan
- Pegawai yang dipilih dari API **harus**:
  - kab_kota == "Kanwil Kemenag Provinsi NTB" atau kode satker kanwil
- Boleh melihat dan memverifikasi seluruh pengajuan Kab/Kota NTB
- Boleh export ZIP dan rekap tingkat provinsi

### 3.3 Admin
- Full akses
- Konfigurasi master data
- Monitoring lintas provinsi (jika dikembangkan)

---

## 4. Master Regions

Tabel `regions` (fixed):

Field:
- id
- province_code = 52
- province_name = Nusa Tenggara Barat
- city_name
- type = kabkota | kanwil

Contoh data:
- city_name: Kabupaten Lombok Timur, type: kabkota
- city_name: Kanwil Kemenag Provinsi NTB, type: kanwil

---

## 5. Data Pegawai (API Eksternal)

Sistem **tidak menyimpan master pegawai**.

### 5.1 Flow Pemilihan Pegawai
1. Operator input NIP / Nama
2. Sistem call API eksternal
3. Hasil ditampilkan dalam tabel
4. Sistem validasi wilayah pegawai
5. Operator pilih pegawai
6. Snapshot data disimpan ke pengajuan

Jika tidak sesuai wilayah:
- Tombol Pilih dinonaktifkan
- Tampilkan pesan penolakan yang jelas

---

## 6. Snapshot Data Pegawai

Disimpan di tabel `submissions`:

Wajib:
- employee_external_id (NIP_BARU)
- nip
- applicant_name
- satuan_kerja
- jabatan
- pangkat_sekarang
- tmt_pangkat

Opsional:
- masa_kerja
- pendidikan
- tanggal_lahir

Snapshot:
- Hanya bisa di-refresh saat status = draft
- Terkunci setelah diajukan

---

## 7. Pengajuan KP

Field utama:
- kp_type_id
- pangkat_target
- golongan_target
- region_id (asal pegawai)
- status

Status:
- draft
- diajukan
- dikembalikan
- disetujui
- ditolak

---

## 8. Dokumen & Validasi

- Persyaratan per tipe KP
- Max size per dokumen
- Validasi mime
- Checklist otomatis
- Submit terkunci jika belum lengkap

---

## 9. Workflow

1. Operator membuat draft
2. Upload dokumen
3. Submit
4. Verifikasi Kanwil
5. Disetujui atau Dikembalikan

---

## 10. Export ZIP

### 10.1 Per Pegawai
```
Nama Pegawai/
  Jenis Dokumen/
    file.ext
```

### 10.2 Per Kab/Kota
```
Kabupaten Lombok Timur/
  Nama Pegawai/
```

### 10.3 Per Provinsi (Kanwil)
```
NTB - Periode/
  Kabupaten Lombok Timur/
    Nama Pegawai/
```

---

## 11. Audit Trail

Dicatat:
- Pengajuan
- Pengembalian
- Persetujuan
- Download ZIP

---

## 12. Keamanan
- Storage private
- Sanitasi nama file
- Logging aktivitas
- Validasi wilayah pegawai

---

## 13. Catatan Implementasi Filament 4

- Policy berbasis role + region.type
- Scope query otomatis
- Action Filament untuk verifikasi & export
- Service layer untuk API pegawai

---

Dokumen ini adalah **VERSI FINAL** aturan KP Kemenag NTB dan siap dijadikan acuan implementasi.
