# SI-PAJAK Terpadu (Sistem Basis Data & Administrasi Perpajakan)

Website Landing Page & Aplikasi CRUD Sistem Informasi Perpajakan berbasis PHP & MySQL relasional sesuai spesifikasi lembar kerja dokumen perpajakan.

---

## 📁 Lokasi Folder Proyek

Folder proyek telah dibuat di:
```text
C:\laragon\www\perpajakan
```

---

## 🌐 Cara Membuka Website di Laragon

1. Buka aplikasi **Laragon** di komputer Anda.
2. Pastikan tombol **Start All** sudah diklik (Apache dan MySQL menyala).
3. Buka browser (Google Chrome, Edge, dll.) dan akses alamat berikut:

| Halaman | URL Akses | Keterangan |
| :--- | :--- | :--- |
| **Landing Page** | [http://localhost/perpajakan](http://localhost/perpajakan) | Landing page bertema DJP, hero section, live tax calculator, cek status billing 15 digit, dan preview tabel |
| **Dashboard CRUD** | [http://localhost/perpajakan/dashboard.php](http://localhost/perpajakan/dashboard.php) | Overview penerimaan negara, KPI metrics, chart jenis pajak, dan shortcut transaksi |
| **Master Wajib Pajak** | [http://localhost/perpajakan/wajib_pajak.php](http://localhost/perpajakan/wajib_pajak.php) | CRUD Wajib Pajak (NPWP/NIK 16 digit, klasifikasi OP/Badan, KPP, AR, status EFIN) |
| **Transaksi & Billing** | [http://localhost/perpajakan/pembayaran.php](http://localhost/perpajakan/pembayaran.php) | CRUD Transaksi Billing (Generate 15 digit billing, input DPP & otomatis hitung pajak, konfirmasi bayar NTPN 16 digit) |
| **Pelaporan SPT** | [http://localhost/perpajakan/spt.php](http://localhost/perpajakan/spt.php) | CRUD Pelaporan SPT Masa & Tahunan (Generate nomor BPS otomatis, Kurang/Lebih Bayar/Nihil) |
| **Master Jenis Pajak** | [http://localhost/perpajakan/jenis_pajak.php](http://localhost/perpajakan/jenis_pajak.php) | CRUD Master KAP (PPN 11%, PPh 21, PPh 23, PBB, Bea Materai) |
| **Master KPP & AR** | [http://localhost/perpajakan/kpp.php](http://localhost/perpajakan/kpp.php) | CRUD Kantor Pelayanan Pajak (KPP) dan Petugas Pajak / Account Representative |
| **phpMyAdmin** | [http://localhost/phpmyadmin](http://localhost/phpmyadmin) | Database `db_perpajakan` sudah langsung dibuat dan terisi data |

> **Catatan Laragon Pretty URL**: Jika fitur Pretty URLs Laragon aktif, Anda juga dapat mengakses via `http://perpajakan.test`.

---

## 🗄️ Konfigurasi & Database MySQL

- **Nama Database**: `db_perpajakan`
- **Host**: `localhost` (Port 3306)
- **User**: `root`
- **Password**: *(kosong)*
- **File Dump SQL**: `C:\laragon\www\perpajakan\database.sql`

---

## 📊 Relasi 6 Tabel Relasional (Sesuai Bab 4 Dokumen)

1. **`kpp`** (Master Kantor Pelayanan Pajak)
   - `id` (PK), `kode_kpp` (UNIQUE), `nama_kpp`, `alamat`, `no_telepon`, `created_at`
2. **`petugas_pajak`** (Account Representative / Pemeriksa)
   - `id` (PK), `nip` (UNIQUE), `kpp_id` (FK `kpp`), `nama_petugas`, `jabatan` (ENUM), `email`, `status_aktif`
3. **`wajib_pajak`** (Master Data Wajib Pajak)
   - `id` (PK), `npwp` (UNIQUE), `nama_wp`, `jenis_wp` (ENUM), `nik`, `kpp_id` (FK `kpp`), `petugas_ar_id` (FK `petugas_pajak`), `alamat`, `email`, `status_efin`
4. **`jenis_pajak`** (Master Jenis Pajak & Tarif)
   - `id` (PK), `kode_akun_pajak` (UNIQUE), `nama_jenis_pajak`, `kategori_pajak` (ENUM), `tarif_standar` (%)
5. **`spt`** (Surat Pemberitahuan Pajak)
   - `id` (PK), `nomor_spt` / BPS (UNIQUE), `wajib_pajak_id` (FK `wajib_pajak`), `jenis_pajak_id` (FK `jenis_pajak`), `jenis_spt` (ENUM), `tahun_pajak`, `masa_pajak`, `pembetulan_ke`, `status_spt`, `nominal_pajak`, `tanggal_lapor`
6. **`pembayaran_pajak`** (Transaksi Pembayaran Billing)
   - `id` (PK), `kode_billing` (UNIQUE 15 digit), `ntpn` (UNIQUE 16 digit), `spt_id` (FK `spt`), `wajib_pajak_id` (FK `wajib_pajak`), `jenis_pajak_id` (FK `jenis_pajak`), `dpp`, `jumlah_bayar`, `bank_persepsi`, `status_pembayaran` (ENUM: 'Unpaid','Paid','Expired'), `tanggal_bayar`

---

## ✨ Fitur Unggulan Website

- **Cetak Bukti Setoran Resmi**: Terdapat tombol cetak **Bukti Penerimaan Negara (BPN)** lengkap dengan watermark LUNAS, validasi kode buku kas negara, dan format standar Direktorat Jenderal Pajak.
- **Kalkulator Pajak Interaktif**: Menghitung secara real-time kewajiban pajak terutang dari DPP berdasarkan tarif standar (PPN 11%, PPh 21, PPh 23, dll.).
- **Pengecekan Kode Billing Instan**: Pengguna dapat memasukkan 15 digit ID Billing langsung di halaman depan untuk mengetahui status bayar secara real-time.
- **Pencarian & Filter Canggih**: Pencarian multi-kategori berdasarkan NPWP, nama, nomor BPS, kode billing, atau status lunas.

