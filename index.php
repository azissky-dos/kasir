<?php
/**
* Project: Sistem Kasir AZRYA GOLD v4.9 - FULLY FIXED
* Perbaikan: Detail Transaksi, Tabel User, Logo Login
*/
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_kasir_final');

class AplikasiKasirPro {
    private $db;
    public $current_user;
    public $current_user_level;
    public $keranjang;
   
    public function __construct() {
        $this->hubungkan_db();
        $this->current_user = $_SESSION['current_user'] ?? null;
        $this->current_user_level = $_SESSION['current_user_level'] ?? null;
        $this->keranjang = $_SESSION['keranjang'] ?? [];
    }
   
    public function hubungkan_db() {
        try {
            $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS);
            if ($this->db->connect_error) throw new Exception($this->db->connect_error);
           
            $this->db->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
            $this->db->select_db(DB_NAME);
           
            $this->db->query("CREATE TABLE IF NOT EXISTS produk (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nama_produk VARCHAR(100),
                kategori_id INT,
                harga_beli INT DEFAULT 0,
                harga INT,
                stok INT,
                stok_minimal INT DEFAULT 5)");
           
            $this->db->query("CREATE TABLE IF NOT EXISTS kategori (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nama_kategori VARCHAR(50) UNIQUE,
                icon VARCHAR(10) DEFAULT '📦',
                is_active TINYINT DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
           
            $this->db->query("CREATE TABLE IF NOT EXISTS transaksi (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tanggal DATETIME,
                total INT,
                diskon INT DEFAULT 0,
                ongkir INT DEFAULT 0,
                bayar INT,
                kembalian INT,
                kasir VARCHAR(50),
                is_retur TINYINT DEFAULT 0,
                retur_dari INT DEFAULT NULL,
                retur_alasan TEXT)");
           
            $this->db->query("CREATE TABLE IF NOT EXISTS detail_transaksi (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_transaksi INT,
                nama_produk VARCHAR(100),
                kategori VARCHAR(50),
                qty INT,
                harga_beli_saat_itu INT,
                harga_jual_saat_itu INT,
                is_retur TINYINT DEFAULT 0)");
           
            $this->db->query("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE,
                password VARCHAR(50),
                level ENUM('admin', 'kasir') DEFAULT 'kasir',
                nama_lengkap VARCHAR(100),
                is_active TINYINT DEFAULT 1,
                last_login DATETIME)");
           
            $this->db->query("CREATE TABLE IF NOT EXISTS log_aktivitas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                username VARCHAR(50),
                aktivitas VARCHAR(200),
                waktu DATETIME DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(50))");
           
            // Tambah kolom jika belum ada
            $cekIsRetur = $this->db->query("SHOW COLUMNS FROM transaksi LIKE 'is_retur'");
            if ($cekIsRetur->num_rows == 0) {
                $this->db->query("ALTER TABLE transaksi ADD COLUMN is_retur TINYINT DEFAULT 0");
                $this->db->query("ALTER TABLE transaksi ADD COLUMN retur_dari INT DEFAULT NULL");
                $this->db->query("ALTER TABLE transaksi ADD COLUMN retur_alasan TEXT");
            }
           
            $cekIsReturDetail = $this->db->query("SHOW COLUMNS FROM detail_transaksi LIKE 'is_retur'");
            if ($cekIsReturDetail->num_rows == 0) {
                $this->db->query("ALTER TABLE detail_transaksi ADD COLUMN is_retur TINYINT DEFAULT 0");
            }
           
            $cekMinimal = $this->db->query("SHOW COLUMNS FROM produk LIKE 'stok_minimal'");
            if ($cekMinimal->num_rows == 0) {
                $this->db->query("ALTER TABLE produk ADD COLUMN stok_minimal INT DEFAULT 5 AFTER stok");
            }
           
            $cekKategoriId = $this->db->query("SHOW COLUMNS FROM produk LIKE 'kategori_id'");
            if ($cekKategoriId->num_rows == 0) {
                $this->db->query("ALTER TABLE produk ADD COLUMN kategori_id INT");
            }
           
            $cekKategoriDetail = $this->db->query("SHOW COLUMNS FROM detail_transaksi LIKE 'kategori'");
            if ($cekKategoriDetail->num_rows == 0) {
                $this->db->query("ALTER TABLE detail_transaksi ADD COLUMN kategori VARCHAR(50) AFTER nama_produk");
            }
           
            $cekBeli = $this->db->query("SHOW COLUMNS FROM produk LIKE 'harga_beli'");
            if ($cekBeli->num_rows == 0) {
                $this->db->query("ALTER TABLE produk ADD COLUMN harga_beli INT DEFAULT 0 AFTER kategori_id");
            }
           
            // Data kategori default
            $cekKategori = $this->db->query("SELECT COUNT(*) as total FROM kategori");
            $row = $cekKategori->fetch_assoc();
            if ($row['total'] == 0) {
                $this->db->query("INSERT INTO kategori (nama_kategori, icon) VALUES
                    ('Makanan', '🍔'), ('Minuman', '🥤'), ('Fashion', '👕'),
                    ('Elektronik', '📱'), ('Kesehatan', '💊'), ('Olahraga', '⚽'),
                    ('Buku & ATK', '📚'), ('Rumah Tangga', '🏠'), ('Umum', '📦')");
            }
           
            // Data user default
            $resUser = $this->db->query("SELECT * FROM users WHERE username='admin'");
            if ($resUser->num_rows == 0) {
                $this->db->query("INSERT INTO users (username, password, level, nama_lengkap) VALUES
                    ('admin', 'admin123', 'admin', 'Administrator'),
                    ('kasir1', 'kasir123', 'kasir', 'Kasir Satu'),
                    ('kasir2', 'kasir123', 'kasir', 'Kasir Dua')");
            }
           
            // Data contoh produk
            $cekProduk = $this->db->query("SELECT COUNT(*) as total FROM produk");
            $row = $cekProduk->fetch_assoc();
            if ($row['total'] == 0) {
                $this->db->query("INSERT INTO produk (nama_produk, kategori_id, harga_beli, harga, stok, stok_minimal) VALUES
                    ('Nasi Goreng', 1, 10000, 15000, 50, 10), ('Mie Ayam', 1, 8000, 12000, 40, 8),
                    ('Es Teh', 2, 2000, 5000, 100, 15), ('Es Jeruk', 2, 3000, 7000, 80, 10),
                    ('Kemeja Pria', 3, 50000, 75000, 30, 5), ('Charger HP', 4, 15000, 25000, 20, 3)");
            }
           
        } catch (Exception $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
   
    public function is_admin() {
        return $this->current_user_level == 'admin';
    }
   
    public function tambah_log($aktivitas) {
        $user_id = $_SESSION['current_user_id'] ?? 0;
        $username = $_SESSION['current_user'] ?? 'system';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $this->db->prepare("INSERT INTO log_aktivitas (user_id, username, aktivitas, ip_address, waktu) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $user_id, $username, $aktivitas, $ip);
        $stmt->execute();
    }
   
    public function backup_database() {
        if (!$this->is_admin()) return false;
        $tables = [];
        $result = $this->db->query("SHOW TABLES");
        while($row = $result->fetch_array()) $tables[] = $row[0];
       
        $backup = "-- Backup Database " . DB_NAME . " - " . date('Y-m-d H:i:s') . "\n\n";
       
        foreach($tables as $table) {
            $result = $this->db->query("SELECT * FROM $table");
            $backup .= "DROP TABLE IF EXISTS $table;\n";
            $row2 = $this->db->query("SHOW CREATE TABLE $table")->fetch_array();
            $backup .= $row2[1] . ";\n\n";
            while($row = $result->fetch_assoc()) {
                $values = [];
                foreach($row as $value) $values[] = is_null($value) ? "NULL" : "'" . $this->db->real_escape_string($value) . "'";
                $backup .= "INSERT INTO $table VALUES(" . implode(",", $values) . ");\n";
            }
            $backup .= "\n";
        }
       
        $filename = "backup_" . DB_NAME . "_" . date('Ymd_His') . ".sql";
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $backup;
        $this->tambah_log("Backup database");
        exit();
    }
   
    public function restore_database($file) {
        if (!$this->is_admin()) return false;
        $sql = file_get_contents($file['tmp_name']);
        $queries = explode(";\n", $sql);
        foreach($queries as $query) {
            $query = trim($query);
            if(!empty($query) && $query != "DROP TABLE IF EXISTS") {
                if(!$this->db->query($query)) return "Error: " . $this->db->error;
            }
        }
        $this->tambah_log("Restore database");
        return true;
    }
   
    public function retur_transaksi($id_transaksi, $alasan) {
        if (!$this->is_admin()) return "Hanya admin yang dapat melakukan retur!";
       
        $cek = $this->db->query("SELECT is_retur FROM transaksi WHERE id = $id_transaksi");
        $row = $cek->fetch_assoc();
        if ($row['is_retur'] == 1) return "Transaksi sudah diretur!";
       
        $detail = $this->get_detail_transaksi($id_transaksi);
        if (!$detail) return "Transaksi tidak ditemukan!";
       
        $this->db->query("UPDATE transaksi SET is_retur = 1, retur_alasan = '$alasan' WHERE id = $id_transaksi");
       
        foreach ($detail['items'] as $item) {
            $this->db->query("UPDATE produk SET stok = stok + {$item['qty']} WHERE nama_produk = '{$item['nama_produk']}'");
            $this->db->query("UPDATE detail_transaksi SET is_retur = 1 WHERE id_transaksi = $id_transaksi AND nama_produk = '{$item['nama_produk']}'");
        }
       
        $this->tambah_log("Retur transaksi #$id_transaksi - Alasan: $alasan");
        return true;
    }
   
    public function get_daftar_kasir() {
        return $this->db->query("SELECT DISTINCT kasir FROM transaksi WHERE kasir IS NOT NULL ORDER BY kasir");
    }
   
    public function get_laporan_per_kasir($tgl1, $tgl2, $kasir = null) {
        if (!$this->is_admin() && $kasir == 'Semua') $kasir = $this->current_user;
       
        if ($kasir && $kasir != 'Semua') {
            $sql = "SELECT t.id, t.tanggal, t.total, t.kasir, t.diskon, t.bayar, t.is_retur,
                    SUM((d.harga_jual_saat_itu - d.harga_beli_saat_itu) * d.qty) as p_kotor
                    FROM transaksi t
                    JOIN detail_transaksi d ON t.id = d.id_transaksi
                    WHERE DATE(t.tanggal) BETWEEN '$tgl1' AND '$tgl2' AND t.kasir = '$kasir'
                    GROUP BY t.id ORDER BY t.tanggal DESC";
        } else {
            $sql = "SELECT t.id, t.tanggal, t.total, t.kasir, t.diskon, t.bayar, t.is_retur,
                    SUM((d.harga_jual_saat_itu - d.harga_beli_saat_itu) * d.qty) as p_kotor
                    FROM transaksi t
                    JOIN detail_transaksi d ON t.id = d.id_transaksi
                    WHERE DATE(t.tanggal) BETWEEN '$tgl1' AND '$tgl2'
                    GROUP BY t.id ORDER BY t.tanggal DESC";
        }
        return $this->db->query($sql);
    }
   
    public function get_laporan_laba_rugi($tgl1, $tgl2) {
        $sql = "SELECT
                    SUM(d.harga_jual_saat_itu * d.qty) as total_penjualan,
                    SUM(d.harga_beli_saat_itu * d.qty) as total_modal,
                    SUM((d.harga_jual_saat_itu - d.harga_beli_saat_itu) * d.qty) as total_profit_kotor,
                    SUM(t.diskon * d.harga_jual_saat_itu * d.qty / 100) as total_diskon,
                    SUM(t.ongkir) as total_ongkir
                FROM transaksi t
                JOIN detail_transaksi d ON t.id = d.id_transaksi
                WHERE DATE(t.tanggal) BETWEEN '$tgl1' AND '$tgl2' AND t.is_retur = 0";
        $res = $this->db->query($sql);
        return $res->fetch_assoc();
    }
   
    public function get_laporan_laba_rugi_per_kategori($tgl1, $tgl2) {
        $sql = "SELECT d.kategori,
                    SUM(d.qty) as terjual,
                    SUM(d.harga_jual_saat_itu * d.qty) as omzet,
                    SUM(d.harga_beli_saat_itu * d.qty) as modal,
                    SUM((d.harga_jual_saat_itu - d.harga_beli_saat_itu) * d.qty) as profit
                FROM transaksi t
                JOIN detail_transaksi d ON t.id = d.id_transaksi
                WHERE DATE(t.tanggal) BETWEEN '$tgl1' AND '$tgl2' AND t.is_retur = 0
                GROUP BY d.kategori ORDER BY profit DESC";
        return $this->db->query($sql);
    }
   
    public function get_all_kategori() {
        return $this->db->query("SELECT * FROM kategori ORDER BY nama_kategori");
    }
   
    public function get_jumlah_produk_by_kategori($kategori_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM produk WHERE kategori_id = ?");
        $stmt->bind_param("i", $kategori_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res['total'];
    }
   
    public function tambah_kategori($nama, $icon = '📦') {
        if (!$this->is_admin()) return false;
        $stmt = $this->db->prepare("INSERT INTO kategori (nama_kategori, icon) VALUES (?, ?)");
        $stmt->bind_param("ss", $nama, $icon);
        $result = $stmt->execute();
        if($result) $this->tambah_log("Tambah kategori: $nama");
        return $result;
    }
   
    public function edit_kategori($id, $nama, $icon, $is_active) {
        if (!$this->is_admin()) return false;
        $stmt = $this->db->prepare("UPDATE kategori SET nama_kategori=?, icon=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssii", $nama, $icon, $is_active, $id);
        $result = $stmt->execute();
        if($result) $this->tambah_log("Edit kategori ID: $id");
        return $result;
    }
   
    public function hapus_kategori($id) {
        if (!$this->is_admin()) return false;
        $jumlah = $this->get_jumlah_produk_by_kategori($id);
        if ($jumlah > 0) return false;
        $stmt = $this->db->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        if($result) $this->tambah_log("Hapus kategori ID: $id");
        return $result;
    }
   
    public function proses_login($u, $p) {
        $stmt = $this->db->prepare("SELECT id, username, level, nama_lengkap FROM users WHERE username=? AND password=? AND is_active=1");
        $stmt->bind_param("ss", $u, $p);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($user = $res->fetch_assoc()) {
            $_SESSION['current_user'] = $user['username'];
            $_SESSION['current_user_level'] = $user['level'];
            $_SESSION['current_user_id'] = $user['id'];
            $_SESSION['current_user_nama'] = $user['nama_lengkap'];
           
            $update = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update->bind_param("i", $user['id']);
            $update->execute();
           
            $this->tambah_log("Login sebagai " . $user['level']);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            return "Username/Password Salah atau Akun Nonaktif!";
        }
    }
   
    public function get_jumlah_stok_menipis() {
        $res = $this->db->query("SELECT COUNT(*) as total FROM produk WHERE stok <= stok_minimal");
        $row = $res->fetch_assoc();
        return $row['total'];
    }
   
    public function simpan_produk_baru($nama, $kategori_id, $beli, $harga, $stok, $stok_minimal = 5) {
        if (!$this->is_admin()) return false;
        $stmt = $this->db->prepare("INSERT INTO produk (nama_produk, kategori_id, harga_beli, harga, stok, stok_minimal) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiiii", $nama, $kategori_id, $beli, $harga, $stok, $stok_minimal);
        $result = $stmt->execute();
        if($result) $this->tambah_log("Tambah produk: $nama");
        return $result;
    }
   
    public function edit_produk($id, $nama, $kategori_id, $beli, $harga, $stok, $stok_minimal = 5) {
        if (!$this->is_admin()) return false;
        $stmt = $this->db->prepare("UPDATE produk SET nama_produk=?, kategori_id=?, harga_beli=?, harga=?, stok=?, stok_minimal=? WHERE id=?");
        $stmt->bind_param("siiiiii", $nama, $kategori_id, $beli, $harga, $stok, $stok_minimal, $id);
        $result = $stmt->execute();
        if($result) $this->tambah_log("Edit produk ID: $id");
        return $result;
    }
   
    public function hapus_produk($id) {
        if (!$this->is_admin()) return false;
        $stmt = $this->db->prepare("DELETE FROM produk WHERE id=?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        if($result) $this->tambah_log("Hapus produk ID: $id");
        return $result;
    }
   
    public function tambah_user($username, $password, $level, $nama_lengkap) {
        if (!$this->is_admin()) return false;
        $stmt = $this->db->prepare("INSERT INTO users (username, password, level, nama_lengkap) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $password, $level, $nama_lengkap);
        $result = $stmt->execute();
        if($result) $this->tambah_log("Tambah user: $username");
        return $result;
    }
   
    public function edit_user($id, $username, $level, $nama_lengkap, $is_active) {
        if (!$this->is_admin()) return false;
        $stmt = $this->db->prepare("UPDATE users SET username=?, level=?, nama_lengkap=?, is_active=? WHERE id=?");
        $stmt->bind_param("sssii", $username, $level, $nama_lengkap, $is_active, $id);
        $result = $stmt->execute();
        if($result) $this->tambah_log("Edit user: $username");
        return $result;
    }
   
    public function reset_password($id, $password_baru) {
        if (!$this->is_admin()) return false;
        $stmt = $this->db->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $password_baru, $id);
        $result = $stmt->execute();
        if($result) $this->tambah_log("Reset password user ID: $id");
        return $result;
    }
   
    public function hapus_user($id) {
        if (!$this->is_admin()) return false;
        if ($id == $_SESSION['current_user_id']) return false;
        $stmt = $this->db->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        if($result) $this->tambah_log("Hapus user ID: $id");
        return $result;
    }
   
    public function get_all_users() {
        return $this->db->query("SELECT id, username, level, nama_lengkap, is_active, last_login FROM users ORDER BY level, username");
    }
   
    public function tambah_keranjang($nama, $qty) {
        $stmt = $this->db->prepare("SELECT p.harga, p.stok, p.harga_beli, k.nama_kategori as kategori FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id WHERE p.nama_produk=?");
        $stmt->bind_param("s", $nama);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
       
        if ($res && $res['stok'] >= $qty) {
            $sub = $res['harga'] * $qty;
            $this->keranjang[] = [
                'nama' => $nama, 'kategori' => $res['kategori'], 'qty' => $qty,
                'harga' => $res['harga'], 'harga_beli' => $res['harga_beli'], 'subtotal' => $sub
            ];
            $_SESSION['keranjang'] = $this->keranjang;
            return true;
        }
        return false;
    }
   
    public function proses_transaksi($bayar, $diskon, $ongkir) {
        if (empty($this->keranjang)) return "Keranjang kosong!";
       
        $subtotal_brg = 0;
        foreach($this->keranjang as $item) $subtotal_brg += $item['subtotal'];
        $total = ($subtotal_brg - (int)($subtotal_brg * $diskon / 100)) + $ongkir;
        if ($bayar < $total) return "Uang kurang!";
       
        $kembalian = $bayar - $total;
        $waktu = date('Y-m-d H:i:s');
       
        $stmt = $this->db->prepare("INSERT INTO transaksi (tanggal, total, diskon, ongkir, bayar, kembalian, kasir) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiiiis", $waktu, $total, $diskon, $ongkir, $bayar, $kembalian, $this->current_user);
        $stmt->execute();
        $id_transaksi = $this->db->insert_id;
       
        foreach ($this->keranjang as $item) {
            $stmt_u = $this->db->prepare("UPDATE produk SET stok = stok - ? WHERE nama_produk = ?");
            $stmt_u->bind_param("is", $item['qty'], $item['nama']);
            $stmt_u->execute();
           
            $stmt_d = $this->db->prepare("INSERT INTO detail_transaksi (id_transaksi, nama_produk, kategori, qty, harga_beli_saat_itu, harga_jual_saat_itu) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_d->bind_param("issiii", $id_transaksi, $item['nama'], $item['kategori'], $item['qty'], $item['harga_beli'], $item['harga']);
            $stmt_d->execute();
        }
       
        $last_items = $this->keranjang;
        $_SESSION['keranjang'] = [];
        $_SESSION['last_transaksi'] = [
            'id' => $id_transaksi, 'total' => $total, 'bayar' => $bayar, 'kembalian' => $kembalian,
            'diskon' => $diskon, 'ongkir' => $ongkir, 'items' => $last_items, 'tanggal' => $waktu, 'kasir' => $this->current_user
        ];
       
        $this->tambah_log("Transaksi #$id_transaksi - Total: Rp " . number_format($total));
        return "SUCCESS";
    }
   
    public function get_produk() {
        return $this->db->query("SELECT p.*, k.nama_kategori, k.icon FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id ORDER BY p.nama_produk");
    }
   
    public function get_produk_for_dropdown() {
        return $this->db->query("SELECT p.nama_produk, p.harga, p.stok, k.nama_kategori as kategori FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id ORDER BY k.nama_kategori, p.nama_produk");
    }
   
    public function get_kategori_list() {
        $res = $this->db->query("SELECT id, nama_kategori, icon FROM kategori WHERE is_active = 1 ORDER BY nama_kategori");
        $kategori = [];
        while($row = $res->fetch_assoc()) $kategori[] = $row;
        return $kategori;
    }
   
    public function get_detail_transaksi($id_transaksi) {
        $stmt = $this->db->prepare("SELECT * FROM transaksi WHERE id = ?");
        $stmt->bind_param("i", $id_transaksi);
        $stmt->execute();
        $transaksi = $stmt->get_result()->fetch_assoc();
       
        if ($transaksi) {
            $stmt2 = $this->db->prepare("SELECT * FROM detail_transaksi WHERE id_transaksi = ?");
            $stmt2->bind_param("i", $id_transaksi);
            $stmt2->execute();
            $transaksi['items'] = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            $subtotal = 0;
            foreach($transaksi['items'] as $item) $subtotal += $item['qty'] * $item['harga_jual_saat_itu'];
            $transaksi['subtotal'] = $subtotal;
        }
        return $transaksi;
    }
   
    public function get_logo_path() {
        $logo_files = ['logo.png', 'logo.jpg', 'logo.jpeg', 'logo.gif', 'Logo.png', 'Logo.jpg', 'LOGO.png', 'azrya_logo.png', 'azrya_logo.jpg'];
        foreach ($logo_files as $logo) {
            if (file_exists($logo)) return $logo;
        }
        return null;
    }
   
    public function cetak_struk() {
    if (!file_exists('fpdf.php')) die("FPDF library tidak ditemukan.");
    require('fpdf.php');
    $s = $_SESSION['last_transaksi'];
    $lebar = 72;
    
    // HITUNG TINGGI DINAMIS (LEBIH BANYAK PADDING)
    $tinggi = 30; // padding awal
    $tinggi += 8; // logo + nama toko
    $tinggi += 10; // alamat + telp
    $tinggi += 10; // tanggal + kasir + garis
    $tinggi += 5; // header tabel
    $tinggi += count($s['items']) * 10; // setiap item 10mm (lebih besar agar tidak tumpuk)
    $tinggi += 5; // garis pemisah
    if($s['diskon'] > 0) $tinggi += 5;
    if($s['ongkir'] > 0) $tinggi += 5;
    $tinggi += 15; // total + bayar + kembali
    $tinggi += 10; // footer
    $tinggi += 10; // extra padding
    
    $pdf = new FPDF('P', 'mm', array($lebar, $tinggi));
    $pdf->AddPage();
    $pdf->SetMargins(4, 5, 4);
    $pdf->SetAutoPageBreak(false);
    
    // Gunakan lebar konten 64mm (72 - 8 margin)
    $lebar_konten = 64;
    
    // Logo di tengah
    $logo_path = $this->get_logo_path();
    if ($logo_path && file_exists($logo_path)) {
        $pdf->Image($logo_path, ($lebar - 15) / 2, 5, 15);
        $pdf->Ln(12);
    } else {
        $pdf->Ln(5);
    }
    
    // Header toko (center)
    $pdf->SetFont("Arial", 'B', 10);
    $pdf->Cell($lebar_konten, 6, "AZRYA GOLD", 0, 1, 'C');
    $pdf->SetFont("Arial", '', 7);
    $pdf->Cell($lebar_konten, 4, "Jl. Contoh No. 123, Kota", 0, 1, 'C');
    $pdf->Cell($lebar_konten, 4, "Telp: (021) 1234-5678", 0, 1, 'C');
    $pdf->Cell($lebar_konten, 4, str_repeat("-", 24), 0, 1, 'C');
    
    // Tanggal & Kasir
    $pdf->Cell($lebar_konten, 4, date('d/m/Y H:i:s', strtotime($s['tanggal'])), 0, 1, 'C');
    $pdf->Cell($lebar_konten, 4, "Kasir: " . $s['kasir'], 0, 1, 'C');
    $pdf->Cell($lebar_konten, 4, str_repeat("-", 24), 0, 1, 'C');
    $pdf->Ln(2);
    
    // Header tabel (LEFT, CENTER, RIGHT)
    $pdf->SetFont("Arial", 'B', 7);
    $pdf->Cell(30, 5, "Item", 0, 0, 'L');
    $pdf->Cell(10, 5, "Qty", 0, 0, 'C');
    $pdf->Cell(24, 5, "Total", 0, 1, 'R');
    
    // Item barang
    $pdf->SetFont("Arial", '', 7);
    foreach ($s['items'] as $item) {
        // Nama produk (maks 16 karakter)
        $nama = strlen($item['nama']) > 16 ? substr($item['nama'], 0, 14) . '..' : $item['nama'];
        $pdf->Cell(30, 5, $nama, 0, 0, 'L');
        $pdf->Cell(10, 5, $item['qty'], 0, 0, 'C');
        $pdf->Cell(24, 5, number_format($item['subtotal']), 0, 1, 'R');
        
        // Harga satuan (lebih kecil fontnya)
        $pdf->SetFont("Arial", '', 5);
        $pdf->Cell(30, 4, "  @" . number_format($item['harga']), 0, 0, 'L');
        $pdf->Cell(34, 4, "", 0, 1);
        $pdf->SetFont("Arial", '', 7);
    }
    
    // Garis pemisah
    $pdf->Cell($lebar_konten, 4, str_repeat("-", 24), 0, 1, 'C');
    
    // Diskon & Ongkir
    $subtotal_brg = 0;
    foreach($s['items'] as $item) $subtotal_brg += $item['subtotal'];
    $diskon_nominal = (int)($subtotal_brg * $s['diskon'] / 100);
    
    if($s['diskon'] > 0) {
        $pdf->Cell(50, 4, "Diskon (" . $s['diskon'] . "%)", 0, 0, 'L');
        $pdf->Cell(14, 4, "-" . number_format($diskon_nominal), 0, 1, 'R');
    }
    if($s['ongkir'] > 0) {
        $pdf->Cell(50, 4, "Ongkir", 0, 0, 'L');
        $pdf->Cell(14, 4, number_format($s['ongkir']), 0, 1, 'R');
    }
    
    // TOTAL (BOLD, lebih besar)
    $pdf->SetFont("Arial", 'B', 8);
    $pdf->Cell(50, 6, "TOTAL", 0, 0, 'L');
    $pdf->Cell(14, 6, number_format($s['total']), 0, 1, 'R');
    
    // BAYAR
    $pdf->SetFont("Arial", '', 7);
    $pdf->Cell(50, 4, "BAYAR", 0, 0, 'L');
    $pdf->Cell(14, 4, number_format($s['bayar']), 0, 1, 'R');
    
    // KEMBALI (BOLD)
    $pdf->SetFont("Arial", 'B', 8);
    $pdf->Cell(50, 6, "KEMBALI", 0, 0, 'L');
    $pdf->Cell(14, 6, number_format($s['kembalian']), 0, 1, 'R');
    
    // Footer
    $pdf->SetFont("Arial", 'I', 7);
    $pdf->Cell($lebar_konten, 4, str_repeat("=", 24), 0, 1, 'C');
    $pdf->Cell($lebar_konten, 4, "Terima kasih", 0, 1, 'C');
    $pdf->Cell($lebar_konten, 4, str_repeat("=", 24), 0, 1, 'C');
    
    $pdf->Output("D", "Struk_AzryaGold_" . date('Ymd_His') . ".pdf");
    exit();
}
   
    public function cetak_kwitansi() {
        if (!file_exists('fpdf.php')) die("FPDF library tidak ditemukan.");
        require('fpdf.php');
        $s = $_SESSION['last_transaksi'];
        $pdf = new FPDF('L', 'mm', 'A5');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);
       
        $logo_path = $this->get_logo_path();
        if ($logo_path && file_exists($logo_path)) $pdf->Image($logo_path, 10, 8, 15);
       
        $pdf->SetY(8);
        $pdf->SetFont("Arial", 'B', 12);
        $pdf->Cell(0, 6, "AZRYA GOLD", 0, 1, 'C');
        $pdf->SetFont("Arial", '', 7);
        $pdf->Cell(0, 3, "Jl. Contoh No. 123, Kota Contoh", 0, 1, 'C');
        $pdf->Cell(0, 3, "Telp: (021) 1234-5678", 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont("Arial", 'B', 10);
        $pdf->Cell(0, 6, "KUITANSI PEMBAYARAN", 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont("Arial", '', 8);
        $pdf->Cell(35, 5, "No. Transaksi", 0, 0);
        $pdf->Cell(3, 5, ":", 0, 0);
        $pdf->Cell(0, 5, "INV/" . date('Ymd') . "/" . $s['id'], 0, 1);
        $pdf->Cell(35, 5, "Tanggal", 0, 0);
        $pdf->Cell(3, 5, ":", 0, 0);
        $pdf->Cell(0, 5, date('d/m/Y H:i:s', strtotime($s['tanggal'])), 0, 1);
        $pdf->Cell(35, 5, "Kasir", 0, 0);
        $pdf->Cell(3, 5, ":", 0, 0);
        $pdf->Cell(0, 5, $s['kasir'], 0, 1);
        $pdf->Ln(3);
       
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont("Arial", 'B', 7);
        $pdf->Cell(60, 6, "Nama Barang", 1, 0, 'C', true);
        $pdf->Cell(30, 6, "Kategori", 1, 0, 'C', true);
        $pdf->Cell(15, 6, "Qty", 1, 0, 'C', true);
        $pdf->Cell(30, 6, "Harga", 1, 0, 'C', true);
        $pdf->Cell(30, 6, "Subtotal", 1, 1, 'C', true);
       
        $pdf->SetFont("Arial", '', 7);
        foreach ($s['items'] as $item) {
            $pdf->Cell(60, 5, $item['nama'], 1, 0, 'L');
            $pdf->Cell(30, 5, $item['kategori'], 1, 0, 'L');
            $pdf->Cell(15, 5, $item['qty'], 1, 0, 'C');
            $pdf->Cell(30, 5, number_format($item['harga']), 1, 0, 'R');
            $pdf->Cell(30, 5, number_format($item['subtotal']), 1, 1, 'R');
        }
       
        $pdf->Ln(3);
        $subtotal = array_sum(array_column($s['items'], 'subtotal'));
        $pdf->SetFont("Arial", '', 8);
        $pdf->SetX($pdf->GetX() + 80);
        $pdf->Cell(45, 5, "Subtotal", 0, 0, 'L');
        $pdf->Cell(25, 5, number_format($subtotal), 0, 1, 'R');
        if($s['diskon'] > 0) {
            $pdf->SetX($pdf->GetX() + 80);
            $pdf->Cell(45, 4, "Diskon (" . $s['diskon'] . "%)", 0, 0, 'L');
            $pdf->Cell(25, 4, "-" . number_format($s['total'] * $s['diskon'] / 100), 0, 1, 'R');
        }
        if($s['ongkir'] > 0) {
            $pdf->SetX($pdf->GetX() + 80);
            $pdf->Cell(45, 4, "Ongkos Kirim", 0, 0, 'L');
            $pdf->Cell(25, 4, number_format($s['ongkir']), 0, 1, 'R');
        }
        $pdf->SetX($pdf->GetX() + 80);
        $pdf->SetFont("Arial", 'B', 9);
        $pdf->Cell(45, 6, "TOTAL", 0, 0, 'L');
        $pdf->Cell(25, 6, number_format($s['total']), 0, 1, 'R');
        $pdf->SetX($pdf->GetX() + 80);
        $pdf->SetFont("Arial", '', 8);
        $pdf->Cell(45, 5, "Dibayar", 0, 0, 'L');
        $pdf->Cell(25, 5, number_format($s['bayar']), 0, 1, 'R');
        $pdf->SetX($pdf->GetX() + 80);
        $pdf->SetFont("Arial", 'B', 9);
        $pdf->Cell(45, 6, "Kembalian", 0, 0, 'L');
        $pdf->Cell(25, 6, number_format($s['kembalian']), 0, 1, 'R');
        $pdf->Ln(5);
        $pdf->SetFont("Arial", 'I', 7);
        $pdf->Cell(0, 4, "Terima kasih atas pembayaran Anda", 0, 1, 'C');
        $pdf->Cell(0, 4, "Barang yang sudah dibeli tidak dapat dikembalikan", 0, 1, 'C');
       
        $pdf->Output("D", "Kwitansi_AzryaGold_" . date('Ymd_His') . ".pdf");
        exit();
    }
   
    public function cetak_faktur() {
        if (!file_exists('fpdf.php')) die("FPDF library tidak ditemukan.");
        require('fpdf.php');
        $s = $_SESSION['last_transaksi'];
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(15, 15, 15);
       
        $logo_path = $this->get_logo_path();
        if ($logo_path && file_exists($logo_path)) {
            $pdf->Image($logo_path, 15, 10, 25);
            $pdf->SetY(20);
        }
       
        $pdf->SetFont("Arial", 'B', 16);
        $pdf->Cell(0, 8, "AZRYA GOLD", 0, 1, 'C');
        $pdf->SetFont("Arial", '', 9);
        $pdf->Cell(0, 5, "Jl. Contoh No. 123, Kota Contoh", 0, 1, 'C');
        $pdf->Cell(0, 5, "Telp: (021) 1234-5678 | Email: azrya@example.com", 0, 1, 'C');
        $pdf->Ln(3);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(8);
        $pdf->SetFont("Arial", 'B', 14);
        $pdf->Cell(0, 8, "FAKTUR PENJUALAN", 0, 1, 'C');
        $pdf->Ln(5);
        $pdf->SetFont("Arial", '', 10);
        $pdf->Cell(40, 7, "No. Faktur", 0, 0);
        $pdf->Cell(5, 7, ":", 0, 0);
        $pdf->SetFont("Arial", 'B', 10);
        $pdf->Cell(0, 7, "INV/" . date('Ymd') . "/" . $s['id'], 0, 1);
        $pdf->SetFont("Arial", '', 10);
        $pdf->Cell(40, 7, "Tanggal Transaksi", 0, 0);
        $pdf->Cell(5, 7, ":", 0, 0);
        $pdf->Cell(0, 7, date('d/m/Y H:i:s', strtotime($s['tanggal'])), 0, 1);
        $pdf->Cell(40, 7, "Kasir", 0, 0);
        $pdf->Cell(5, 7, ":", 0, 0);
        $pdf->Cell(0, 7, $s['kasir'], 0, 1);
        $pdf->Ln(8);
       
        $pdf->SetFillColor(50, 50, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont("Arial", 'B', 9);
        $pdf->Cell(70, 10, "Nama Barang", 1, 0, 'C', true);
        $pdf->Cell(35, 10, "Kategori", 1, 0, 'C', true);
        $pdf->Cell(20, 10, "Qty", 1, 0, 'C', true);
        $pdf->Cell(30, 10, "Harga Satuan", 1, 0, 'C', true);
        $pdf->Cell(30, 10, "Subtotal", 1, 1, 'C', true);
       
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont("Arial", '', 8);
        foreach ($s['items'] as $item) {
            $pdf->Cell(70, 7, $item['nama'], 1, 0, 'L');
            $pdf->Cell(35, 7, $item['kategori'], 1, 0, 'L');
            $pdf->Cell(20, 7, $item['qty'], 1, 0, 'C');
            $pdf->Cell(30, 7, "Rp " . number_format($item['harga']), 1, 0, 'R');
            $pdf->Cell(30, 7, "Rp " . number_format($item['subtotal']), 1, 1, 'R');
        }
       
        $pdf->Ln(5);
        $pdf->SetFont("Arial", 'B', 10);
        $pdf->Cell(130, 7, "Subtotal", 0, 0, 'R');
        $pdf->Cell(35, 7, "Rp " . number_format(array_sum(array_column($s['items'], 'subtotal'))), 0, 1, 'R');
        if($s['diskon'] > 0) {
            $pdf->Cell(130, 6, "Diskon (" . $s['diskon'] . "%)", 0, 0, 'R');
            $pdf->Cell(35, 6, "- Rp " . number_format($s['total'] * $s['diskon'] / 100), 0, 1, 'R');
        }
        if($s['ongkir'] > 0) {
            $pdf->Cell(130, 6, "Ongkos Kirim", 0, 0, 'R');
            $pdf->Cell(35, 6, "Rp " . number_format($s['ongkir']), 0, 1, 'R');
        }
        $pdf->SetFont("Arial", 'B', 12);
        $pdf->Cell(130, 10, "TOTAL", 0, 0, 'R');
        $pdf->Cell(35, 10, "Rp " . number_format($s['total']), 0, 1, 'R');
        $pdf->SetFont("Arial", '', 10);
        $pdf->Cell(130, 7, "Dibayar", 0, 0, 'R');
        $pdf->Cell(35, 7, "Rp " . number_format($s['bayar']), 0, 1, 'R');
        $pdf->SetFont("Arial", 'B', 11);
        $pdf->Cell(130, 8, "Kembalian", 0, 0, 'R');
        $pdf->Cell(35, 8, "Rp " . number_format($s['kembalian']), 0, 1, 'R');
        $pdf->Ln(10);
        $pdf->SetFont("Arial", 'I', 8);
        $pdf->Cell(0, 5, "Terima kasih atas kepercayaan Anda kepada AZRYA GOLD", 0, 1, 'C');
        $pdf->Cell(0, 5, "Barang yang sudah dibeli tidak dapat dikembalikan kecuali ada kerusakan", 0, 1, 'C');
       
        $pdf->Output("D", "Faktur_AzryaGold_" . date('Ymd_His') . ".pdf");
        exit();
    }
}

$app = new AplikasiKasirPro();
$msg = "";
$menu = $_GET['tab'] ?? 'kasir';
$is_admin = $app->is_admin();

// HANDLER
if (isset($_POST['login'])) { $msg = $app->proses_login($_POST['user'], $_POST['pass']); }
if (isset($_GET['logout'])) { session_destroy(); header("Location: " . $_SERVER['PHP_SELF']); exit(); }

// Backup & Restore
if (isset($_GET['backup']) && $is_admin) { $app->backup_database(); }
if (isset($_POST['restore']) && $is_admin && isset($_FILES['backup_file'])) {
    $result = $app->restore_database($_FILES['backup_file']);
    $msg = ($result === true) ? "Restore database berhasil!" : "Restore gagal: " . $result;
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=backup");
    exit();
}

// Retur
if (isset($_POST['retur_transaksi']) && $is_admin) {
    $result = $app->retur_transaksi($_POST['id_transaksi'], $_POST['alasan']);
    $msg = ($result === true) ? "Transaksi berhasil diretur!" : $result;
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=laporan");
    exit();
}

// Produk
if (isset($_POST['tambah_produk']) && $is_admin) {
    $app->simpan_produk_baru($_POST['p_nama'], $_POST['p_kategori_id'], $_POST['p_beli'], $_POST['p_harga'], $_POST['p_stok'], $_POST['p_stok_minimal']);
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=produk"); exit();
}
if (isset($_POST['edit_produk']) && $is_admin) {
    $app->edit_produk($_POST['p_id'], $_POST['p_nama'], $_POST['p_kategori_id'], $_POST['p_beli'], $_POST['p_harga'], $_POST['p_stok'], $_POST['p_stok_minimal']);
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=produk"); exit();
}
if (isset($_GET['hapus_p']) && $is_admin) {
    $app->hapus_produk($_GET['hapus_p']);
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=produk"); exit();
}

// Kategori
if (isset($_POST['tambah_kategori']) && $is_admin) {
    $app->tambah_kategori($_POST['nama_kategori'], $_POST['icon']);
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=kategori"); exit();
}
if (isset($_POST['edit_kategori']) && $is_admin) {
    $app->edit_kategori($_POST['kat_id'], $_POST['nama_kategori'], $_POST['icon'], $_POST['is_active']);
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=kategori"); exit();
}
if (isset($_GET['hapus_kat']) && $is_admin) {
    $app->hapus_kategori($_GET['hapus_kat']);
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=kategori"); exit();
}

// User
if (isset($_POST['tambah_user']) && $is_admin) {
    $app->tambah_user($_POST['username'], $_POST['password'], $_POST['level'], $_POST['nama_lengkap']);
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=users"); exit();
}
if (isset($_POST['edit_user']) && $is_admin) {
    $app->edit_user($_POST['user_id'], $_POST['username'], $_POST['level'], $_POST['nama_lengkap'], $_POST['is_active']);
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=users"); exit();
}
if (isset($_POST['reset_password']) && $is_admin) {
    $app->reset_password($_POST['user_id'], $_POST['new_password']);
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=users"); exit();
}
if (isset($_GET['hapus_user']) && $is_admin) {
    $app->hapus_user($_GET['hapus_user']);
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=users"); exit();
}

// Transaksi
if (isset($_POST['tambah_keranjang'])) {
    if (!$app->tambah_keranjang($_POST['cb_brg'], $_POST['ent_qty'])) $msg = "Stok tidak cukup!";
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=kasir"); exit();
}
if (isset($_POST['proses_transaksi'])) {
    $res = $app->proses_transaksi($_POST['ent_bayar'], $_POST['ent_diskon'], $_POST['ent_ongkir']);
    $msg = $res === "SUCCESS" ? "Transaksi Berhasil!" : $res;
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=kasir"); exit();
}
if (isset($_GET['clear_k'])) { $_SESSION['keranjang'] = []; header("Location: " . $_SERVER['PHP_SELF'] . "?tab=kasir"); exit(); }

// AJAX Detail Transaksi
if (isset($_GET['ajax_detail']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode($app->get_detail_transaksi($_GET['id']));
    exit();
}

// Cetak
if (isset($_GET['cetak']) && isset($_SESSION['last_transaksi'])) {
    $jenis = $_GET['cetak'];
    if ($jenis == 'struk') $app->cetak_struk();
    elseif ($jenis == 'kwitansi') $app->cetak_kwitansi();
    elseif ($jenis == 'faktur') $app->cetak_faktur();
    exit();
}

// Cetak Laba Rugi PDF
if (isset($_GET['cetak_laba_rugi']) && $is_admin) {
    if (!file_exists('fpdf.php')) die("FPDF library tidak ditemukan.");
    require('fpdf.php');
    $tgl1 = $_GET['tgl1']; $tgl2 = $_GET['tgl2'];
    $data = $app->get_laporan_laba_rugi($tgl1, $tgl2);
    $per_kategori = $app->get_laporan_laba_rugi_per_kategori($tgl1, $tgl2);
   
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetMargins(15, 15, 15);
    $logo_path = $app->get_logo_path();
    if ($logo_path) $pdf->Image($logo_path, 15, 10, 20);
    $pdf->SetY(20);
    $pdf->SetFont("Arial", 'B', 16);
    $pdf->Cell(0, 8, "AZRYA GOLD", 0, 1, 'C');
    $pdf->SetFont("Arial", '', 9);
    $pdf->Cell(0, 5, "Jl. Contoh No. 123, Kota Contoh", 0, 1, 'C');
    $pdf->Cell(0, 5, "Telp: (021) 1234-5678", 0, 1, 'C');
    $pdf->Ln(3);
    $pdf->SetFont("Arial", 'B', 13);
    $pdf->Cell(0, 8, "LAPORAN LABA RUGI", 0, 1, 'C');
    $pdf->SetFont("Arial", '', 10);
    $pdf->Cell(0, 6, "Periode: " . date('d/m/Y', strtotime($tgl1)) . " s/d " . date('d/m/Y', strtotime($tgl2)), 0, 1, 'C');
    $pdf->Ln(5);
   
    $pdf->SetFont("Arial", 'B', 10);
    $pdf->Cell(90, 8, "KETERANGAN", 1, 0, 'C');
    $pdf->Cell(90, 8, "JUMLAH (Rp)", 1, 1, 'C');
    $pdf->SetFont("Arial", '', 9);
    $pdf->Cell(90, 7, "Total Penjualan", 1, 0, 'L');
    $pdf->Cell(90, 7, number_format($data['total_penjualan'] ?? 0), 1, 1, 'R');
    $pdf->Cell(90, 7, "Total Modal (HPP)", 1, 0, 'L');
    $pdf->Cell(90, 7, number_format($data['total_modal'] ?? 0), 1, 1, 'R');
    $pdf->SetFont("Arial", 'B', 9);
    $pdf->Cell(90, 7, "LABA KOTOR", 1, 0, 'L');
    $pdf->Cell(90, 7, number_format(($data['total_profit_kotor'] ?? 0) - ($data['total_diskon'] ?? 0)), 1, 1, 'R');
    $pdf->SetFont("Arial", '', 9);
    $pdf->Cell(90, 7, "Total Diskon", 1, 0, 'L');
    $pdf->Cell(90, 7, number_format($data['total_diskon'] ?? 0), 1, 1, 'R');
    $pdf->Cell(90, 7, "Total Ongkos Kirim", 1, 0, 'L');
    $pdf->Cell(90, 7, number_format($data['total_ongkir'] ?? 0), 1, 1, 'R');
    $pdf->SetFont("Arial", 'B', 9);
    $pdf->Cell(90, 7, "LABA BERSIH", 1, 0, 'L');
    $net_profit = (($data['total_profit_kotor'] ?? 0) - ($data['total_diskon'] ?? 0) - ($data['total_ongkir'] ?? 0));
    $pdf->Cell(90, 7, number_format($net_profit), 1, 1, 'R');
   
    $pdf->Ln(5);
    $pdf->SetFont("Arial", 'B', 10);
    $pdf->Cell(0, 6, "RINCIAN PER KATEGORI", 0, 1, 'L');
    $pdf->SetFont("Arial", 'B', 8);
    $pdf->Cell(50, 7, "Kategori", 1, 0, 'C');
    $pdf->Cell(30, 7, "Terjual", 1, 0, 'C');
    $pdf->Cell(40, 7, "Omzet", 1, 0, 'C');
    $pdf->Cell(35, 7, "Modal", 1, 0, 'C');
    $pdf->Cell(35, 7, "Profit", 1, 1, 'C');
    $pdf->SetFont("Arial", '', 7);
    while($k = $per_kategori->fetch_assoc()) {
        $pdf->Cell(50, 6, $k['kategori'], 1, 0, 'L');
        $pdf->Cell(30, 6, number_format($k['terjual']), 1, 0, 'C');
        $pdf->Cell(40, 6, number_format($k['omzet']), 1, 0, 'R');
        $pdf->Cell(35, 6, number_format($k['modal']), 1, 0, 'R');
        $pdf->Cell(35, 6, number_format($k['profit']), 1, 1, 'R');
    }
   
    $pdf->Output("D", "LabaRugi_AzryaGold_" . date('Ymd_His') . ".pdf");
    exit();
}

// Cetak Laporan PDF
if (isset($_GET['cetak_laporan'])) {
    if (!file_exists('fpdf.php')) die("FPDF library tidak ditemukan.");
    require('fpdf.php');
    $tgl1 = $_GET['tgl1']; $tgl2 = $_GET['tgl2'];
    $kasir_filter = $is_admin ? ($_GET['kasir_filter'] ?? 'Semua') : $app->current_user;
   
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetMargins(15, 15, 15);
    $logo_path = $app->get_logo_path();
    if ($logo_path) $pdf->Image($logo_path, 15, 10, 20);
    $pdf->SetY(20);
    $pdf->SetFont("Arial", 'B', 16);
    $pdf->Cell(0, 8, "AZRYA GOLD", 0, 1, 'C');
    $pdf->SetFont("Arial", '', 9);
    $pdf->Cell(0, 5, "Jl. Contoh No. 123, Kota Contoh", 0, 1, 'C');
    $pdf->Cell(0, 5, "Telp: (021) 1234-5678", 0, 1, 'C');
    $pdf->Ln(3);
    $pdf->SetFont("Arial", 'B', 13);
    $pdf->Cell(0, 8, "LAPORAN PENJUALAN", 0, 1, 'C');
    $pdf->SetFont("Arial", '', 10);
    $pdf->Cell(0, 6, "Periode: " . date('d/m/Y', strtotime($tgl1)) . " s/d " . date('d/m/Y', strtotime($tgl2)), 0, 1, 'C');
    if($kasir_filter != 'Semua') $pdf->Cell(0, 6, "Kasir: " . $kasir_filter, 0, 1, 'C');
    $pdf->Ln(5);
   
    $pdf->SetFont("Arial", 'B', 8);
    $pdf->Cell(20, 8, "ID", 1, 0, 'C');
    $pdf->Cell(45, 8, "Tanggal", 1, 0, 'C');
    $pdf->Cell(40, 8, "Total", 1, 0, 'C');
    $pdf->Cell(40, 8, "Profit", 1, 0, 'C');
    $pdf->Cell(35, 8, "Kasir", 1, 1, 'C');
   
    $data = $app->get_laporan_per_kasir($tgl1, $tgl2, $kasir_filter);
    $to = 0; $tp = 0;
    while($r = $data->fetch_assoc()) {
        $profit = $r['p_kotor'] - (int)($r['total'] * $r['diskon'] / 100);
        $pdf->SetFont("Arial", '', 7);
        $pdf->Cell(20, 6, $r['id'], 1, 0, 'C');
        $pdf->Cell(45, 6, date('d/m/Y H:i', strtotime($r['tanggal'])), 1, 0, 'L');
        $pdf->Cell(40, 6, number_format($r['total']), 1, 0, 'R');
        $pdf->Cell(40, 6, number_format($profit), 1, 0, 'R');
        $pdf->Cell(35, 6, $r['kasir'], 1, 1, 'L');
        $to += $r['total']; $tp += $profit;
    }
    $pdf->Ln(5);
    $pdf->SetFont("Arial", 'B', 9);
    $pdf->Cell(0, 6, "TOTAL OMSET: Rp " . number_format($to), 0, 1, 'R');
    $pdf->Cell(0, 6, "TOTAL PROFIT: Rp " . number_format($tp), 0, 1, 'R');
   
    $pdf->Output("D", "Laporan_AzryaGold_" . date('Ymd_His') . ".pdf");
    exit();
}
// Cetak transaksi lama (berdasarkan ID)
if (isset($_GET['cetak_transaksi']) && isset($_GET['jenis'])) {
    $id = intval($_GET['cetak_transaksi']);
    $jenis = $_GET['jenis'];
    
    // Ambil data transaksi dari database
    $transaksi = $app->get_detail_transaksi($id);
    if ($transaksi) {
        // Format items sesuai yang dibutuhkan fungsi cetak
        $items = [];
        foreach($transaksi['items'] as $item) {
            $items[] = [
                'nama' => $item['nama_produk'],
                'kategori' => $item['kategori'],
                'qty' => $item['qty'],
                'harga' => $item['harga_jual_saat_itu'],
                'harga_beli' => $item['harga_beli_saat_itu'],
                'subtotal' => $item['qty'] * $item['harga_jual_saat_itu']
            ];
        }
        
        $_SESSION['last_transaksi'] = [
            'id' => $transaksi['id'],
            'total' => $transaksi['total'],
            'bayar' => $transaksi['bayar'],
            'kembalian' => $transaksi['kembalian'],
            'diskon' => $transaksi['diskon'],
            'ongkir' => $transaksi['ongkir'],
            'items' => $items,
            'tanggal' => $transaksi['tanggal'],
            'kasir' => $transaksi['kasir']
        ];
        
        // Panggil fungsi cetak
        if ($jenis == 'struk') $app->cetak_struk();
        elseif ($jenis == 'kwitansi') $app->cetak_kwitansi();
        elseif ($jenis == 'faktur') $app->cetak_faktur();
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Kasir AZRYA GOLD v4.9</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; }
        .login-box { width: 400px; margin-top: 150px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .nav-menu { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px; flex-wrap: wrap; }
        .nav-menu a { padding: 10px 25px; text-decoration: none; background: #e9ecef; color: #333; border-radius: 8px 8px 0 0; font-weight: bold; transition: 0.2s; }
        .nav-menu a.active { background: #0d6efd; color: white; }
        .nav-menu a:hover:not(.active) { background: #dee2e6; }
        .total-display { font-size: 2rem; font-weight: bold; color: #e74c3c; }
        .logo-login { max-height: 80px; margin-bottom: 15px; }
        .btn-group-cetak { gap: 10px; }
        .user-badge { font-size: 12px; padding: 3px 8px; border-radius: 20px; }
        .user-admin { background: #dc3545; color: white; }
        .user-kasir { background: #28a745; color: white; }
        .status-retur { background: #dc3545; color: white; padding: 2px 8px; border-radius: 20px; font-size: 10px; }
        .table-user { font-size: 13px; }
        .table-user td, .table-user th { vertical-align: middle; padding: 8px; }
    </style>
</head>
<body>
<div class="container">
    <?php if (!$app->current_user): ?>
        <div class="d-flex justify-content-center">
            <div class="login-box text-center">
                <?php $logo = $app->get_logo_path(); if($logo && file_exists($logo)): ?>
                    <img src="<?php echo $logo; ?>" class="logo-login" alt="Logo">
                <?php else: ?>
                    <div style="font-size: 60px;">🏪</div>
                <?php endif; ?>
                <h2 class="mb-4">AZRYA GOLD KASIR</h2>
                <p class="text-muted">v4.9 - Hak Akses Admin & Kasir</p>
                <?php if($msg) echo "<div class='alert alert-danger'>$msg</div>"; ?>
                <form method="POST">
                    <div class="mb-3 text-start"><label>Username</label><input type="text" name="user" class="form-control" required></div>
                    <div class="mb-3 text-start"><label>Password</label><input type="password" name="pass" class="form-control" required></div>
                    <button type="submit" name="login" class="btn btn-primary w-100 py-2">LOGIN</button>
                </form>
                <hr><small class="text-muted">Demo: admin/admin123 (Admin) | kasir1/kasir123 (Kasir)</small>
            </div>
        </div>
    <?php else: ?>
        <div class="mt-4 bg-white p-4 rounded shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h4>🏪 AZRYA GOLD KASIR <span class="badge bg-info">v4.9</span></h4>
                    <div class="mt-1">
                        <span class="user-badge <?php echo $is_admin ? 'user-admin' : 'user-kasir'; ?>">
                            <?php echo $is_admin ? '👑 Admin' : '🛒 Kasir'; ?>
                        </span>
                        <span class="ms-2">👤 <?php echo $_SESSION['current_user_nama'] ?? $app->current_user; ?></span>
                    </div>
                </div>
                <a href="?logout=1" class="btn btn-sm btn-outline-danger">🚪 Logout</a>
            </div>
           
            <?php $stok_menipis = $app->get_jumlah_stok_menipis(); if($stok_menipis > 0 && $is_admin): ?>
            <div class="alert alert-warning alert-dismissible fade show mb-3"><strong>⚠️ Peringatan Stok!</strong> Terdapat <strong><?php echo $stok_menipis; ?></strong> produk dengan stok menipis.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
           
            <?php if($msg) echo "<script>alert('$msg');</script>"; ?>
           
            <div class="nav-menu">
                <a href="?tab=kasir" class="<?php echo $menu == 'kasir' ? 'active' : ''; ?>">🛒 Transaksi Kasir</a>
                <?php if($is_admin): ?>
                <a href="?tab=produk" class="<?php echo $menu == 'produk' ? 'active' : ''; ?>">📦 Stok Produk</a>
                <?php endif; ?>
                <a href="?tab=laporan" class="<?php echo $menu == 'laporan' ? 'active' : ''; ?>">📊 Laporan Penjualan</a>
                <?php if($is_admin): ?>
                <a href="?tab=laba_rugi" class="<?php echo $menu == 'laba_rugi' ? 'active' : ''; ?>">💰 Laba Rugi</a>
                <a href="?tab=kategori" class="<?php echo $menu == 'kategori' ? 'active' : ''; ?>">🏷️ Kategori</a>
                <a href="?tab=users" class="<?php echo $menu == 'users' ? 'active' : ''; ?>">👥 User</a>
                <a href="?tab=backup" class="<?php echo $menu == 'backup' ? 'active' : ''; ?>">💾 Backup</a>
                <?php endif; ?>
            </div>
           
            <!-- MENU KASIR -->
            <?php if($menu == 'kasir'): ?>
            <div>
                <form method="POST" class="row g-3 mb-4">
                    <div class="col-md-5"><label>📂 Pilih Kategori</label><select name="cb_kategori" id="cb_kategori" class="form-select"><option value="">-- Pilih Kategori --</option><?php foreach($app->get_kategori_list() as $kat): ?><option value="<?php echo $kat['nama_kategori']; ?>"><?php echo $kat['icon']; ?> <?php echo $kat['nama_kategori']; ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-5"><label>🛍️ Pilih Produk</label><select name="cb_brg" id="cb_brg" class="form-select" disabled><option value="">-- Pilih Kategori Dulu --</option></select></div>
                    <div class="col-md-2"><label>🔢 Qty</label><input type="number" name="ent_qty" id="ent_qty" class="form-control" value="1" min="1"></div>
                    <div class="col-md-12"><button type="submit" name="tambah_keranjang" id="btnTambah" class="btn btn-warning w-100" disabled>➕ TAMBAH</button></div>
                </form>
               
                <div class="table-responsive">
                    <table class="table table-bordered"><thead class="table-light"></td><th>Nama</th><th>Kategori</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead><tbody>
                        <?php $st_total = 0; foreach($app->keranjang as $k): $st_total += $k['subtotal']; ?>
                        <tr><td><?php echo htmlspecialchars($k['nama']); ?></td><td><span class="badge bg-secondary"><?php echo htmlspecialchars($k['kategori']); ?></span></td><td><?php echo $k['qty']; ?></td><td>Rp <?php echo number_format($k['harga']); ?></td><td>Rp <?php echo number_format($k['subtotal']); ?></td></tr>
                        <?php endforeach; if(empty($app->keranjang)): ?>
                        <tr><td colspan="5" class="text-center text-muted">🛒 Keranjang kosong</td></tr><?php endif; ?>
                    </tbody><tfoot><tr class="table-active"><td colspan="4" class="text-end fw-bold">TOTAL:</td><td class="fw-bold">Rp <?php echo number_format($st_total); ?></td></tr></tfoot></table>
                </div>
                <div class="text-end mb-3"><a href="?clear_k=1&tab=kasir" class="btn btn-sm btn-link text-danger">🗑️ Kosongkan Keranjang</a></div>
               
                <form method="POST">
                    <div class="row g-3 align-items-center mb-4">
                        <div class="col-auto"><label>Diskon (%)</label></div>
                        <div class="col-auto"><input type="number" name="ent_diskon" id="dsk" class="form-control" value="0" style="width:80px" min="0" max="100"></div>
                        <div class="col-auto"><label>Ongkir (Rp)</label></div>
                        <div class="col-auto"><input type="number" name="ent_ongkir" id="ong" class="form-control" value="0" style="width:120px" min="0"></div>
                        <div class="col text-end"><span class="total-display" id="total_lbl">TOTAL: Rp <?php echo number_format($st_total); ?></span></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 offset-md-6">
                            <div class="input-group input-group-lg mb-3"><span class="input-group-text">BAYAR (Rp)</span><input type="number" name="ent_bayar" class="form-control" required min="0"></div>
                            <button type="submit" name="proses_transaksi" class="btn btn-primary btn-lg w-100">✅ PROSES TRANSAKSI</button>
                            <?php if(isset($_SESSION['last_transaksi'])): ?>
                                <div class="mt-3"><label class="fw-bold mb-2">📄 Cetak Dokumen:</label>
                                <div class="d-flex btn-group-cetak"><a href="?cetak=struk&tab=kasir" class="btn btn-sm btn-secondary flex-fill">🧾 Struk</a><a href="?cetak=kwitansi&tab=kasir" class="btn btn-sm btn-info flex-fill">📄 Kwitansi</a><a href="?cetak=faktur&tab=kasir" class="btn btn-sm btn-primary flex-fill">📑 Faktur</a></div></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>
           
            <!-- MENU STOK PRODUK (HANYA ADMIN) -->
            <?php if($menu == 'produk' && $is_admin): ?>
            <div>
                <div class="card mb-4"><div class="card-header bg-success text-white">➕ TAMBAH PRODUK BARU</div><div class="card-body"><form method="POST" class="row g-3"><div class="col-md-3"><input type="text" name="p_nama" placeholder="Nama Produk" class="form-control" required></div><div class="col-md-2"><select name="p_kategori_id" class="form-select" required><option value="">-- Pilih Kategori --</option><?php $kats = $app->get_all_kategori(); while($kat = $kats->fetch_assoc()): ?><option value="<?php echo $kat['id']; ?>"><?php echo $kat['icon']; ?> <?php echo $kat['nama_kategori']; ?></option><?php endwhile; ?></select></div><div class="col-md-2"><input type="number" name="p_beli" placeholder="Harga Beli" class="form-control" required min="0"></div><div class="col-md-2"><input type="number" name="p_harga" placeholder="Harga Jual" class="form-control" required min="0"></div><div class="col-md-1"><input type="number" name="p_stok" placeholder="Stok" class="form-control" required min="0"></div><div class="col-md-2"><input type="number" name="p_stok_minimal" placeholder="Min Stok" class="form-control" value="5" min="0"></div><div class="col-md-12"><button type="submit" name="tambah_produk" class="btn btn-success">➕ Tambah Produk</button></div></form></div></div>
               
                <div class="table-responsive"><table class="table table-bordered"><thead class="table-light"><tr><th>ID</th><th>Nama Produk</th><th>Kategori</th><th>Beli</th><th>Jual</th><th>Stok</th><th>Min</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                <?php $res_p = $app->get_produk(); while($p = $res_p->fetch_assoc()): $is_stok_menipis = $p['stok'] <= $p['stok_minimal']; ?>
                <tr style="<?php echo $is_stok_menipis ? 'background:#fff3cd' : ''; ?>"><td><?php echo $p['id']; ?></td><td><?php echo htmlspecialchars($p['nama_produk']); ?></td><td><span class="badge bg-secondary"><?php echo $p['icon'] ?? '📦'; ?> <?php echo htmlspecialchars($p['nama_kategori'] ?? 'Umum'); ?></span></td><td>Rp <?php echo number_format($p['harga_beli']); ?></td><td>Rp <?php echo number_format($p['harga']); ?></td><td><?php echo $p['stok']; ?></td><td><?php echo $p['stok_minimal']; ?></td>
                <td><?php if($p['stok'] <= 0): ?><span class="badge bg-danger">Habis</span><?php elseif($is_stok_menipis): ?><span class="badge bg-warning text-dark">Menipis</span><?php else: ?><span class="badge bg-success">Aman</span><?php endif; ?></span></td>
                <td><button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditProduk<?php echo $p['id']; ?>">✏️ Edit</button><a href="?hapus_p=<?php echo $p['id']; ?>&tab=produk" class="btn btn-danger btn-sm ms-1" onclick="return confirm('Hapus produk ini?')">🗑️ Hapus</a></td></tr>
                <div class="modal fade" id="modalEditProduk<?php echo $p['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header bg-warning"><h5 class="modal-title">✏️ EDIT PRODUK</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="p_id" value="<?php echo $p['id']; ?>"><div class="mb-3"><label>Nama Produk</label><input type="text" name="p_nama" class="form-control" value="<?php echo htmlspecialchars($p['nama_produk']); ?>" required></div><div class="mb-3"><label>Kategori</label><select name="p_kategori_id" class="form-select"><?php $kats2 = $app->get_all_kategori(); while($kat2 = $kats2->fetch_assoc()): ?><option value="<?php echo $kat2['id']; ?>" <?php echo ($kat2['id'] == $p['kategori_id']) ? 'selected' : ''; ?>><?php echo $kat2['icon']; ?> <?php echo $kat2['nama_kategori']; ?></option><?php endwhile; ?></select></div><div class="mb-3"><label>Harga Beli</label><input type="number" name="p_beli" class="form-control" value="<?php echo $p['harga_beli']; ?>" required min="0"></div><div class="mb-3"><label>Harga Jual</label><input type="number" name="p_harga" class="form-control" value="<?php echo $p['harga']; ?>" required min="0"></div><div class="mb-3"><label>Stok</label><input type="number" name="p_stok" class="form-control" value="<?php echo $p['stok']; ?>" required min="0"></div><div class="mb-3"><label>Minimal Stok</label><input type="number" name="p_stok_minimal" class="form-control" value="<?php echo $p['stok_minimal']; ?>" min="0"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="edit_produk" class="btn btn-primary">Simpan</button></div></form></div></div></div>
                <?php endwhile; ?></tbody></table></div>
            </div>
            <?php endif; ?>
           
<!-- MENU LAPORAN PENJUALAN -->
<?php if($menu == 'laporan'): ?>
<div>
    <form method="GET" class="row g-3 mb-4">
        <input type="hidden" name="tab" value="laporan">
        <div class="col-auto">📅 Mulai: <input type="date" name="tgl1" class="form-control w-auto d-inline-block" value="<?php echo $_GET['tgl1'] ?? date('Y-m-d'); ?>"></div>
        <div class="col-auto">📅 Sampai: <input type="date" name="tgl2" class="form-control w-auto d-inline-block" value="<?php echo $_GET['tgl2'] ?? date('Y-m-d'); ?>"></div>
        <?php if($is_admin): ?>
        <div class="col-auto">
            <select name="kasir_filter" class="form-select w-auto d-inline-block">
                <option value="Semua">Semua Kasir</option>
                <?php $kasir_list = $app->get_daftar_kasir(); while($k = $kasir_list->fetch_assoc()): ?>
                    <option value="<?php echo $k['kasir']; ?>" <?php echo ($_GET['kasir_filter'] ?? '') == $k['kasir'] ? 'selected' : ''; ?>><?php echo $k['kasir']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-auto"><button type="submit" class="btn btn-info text-white">🔍 FILTER</button><button type="submit" name="cetak_laporan" value="1" class="btn btn-warning text-white ms-2">📄 CETAK PDF</button></div>
    </form>
    
    <div class="table-responsive">
        <table class="table table-bordered" id="tableLaporan">
            <thead class="table-light">
                <tr><th>ID</th><th>Tanggal</th><th>Total</th><th>Profit</th><th>Kasir</th><th>Status</th><th>Aksi</th>比
            </thead>
            <tbody>
                <?php 
                $t1 = $_GET['tgl1'] ?? date('Y-m-d'); 
                $t2 = $_GET['tgl2'] ?? date('Y-m-d'); 
                $kasir_filter = $is_admin ? ($_GET['kasir_filter'] ?? 'Semua') : $app->current_user;
                $res_l = $app->get_laporan_per_kasir($t1, $t2, $kasir_filter); 
                $to = 0; $tp = 0; 
                while($r = $res_l->fetch_assoc()): 
                    $pb = $r['p_kotor'] - (int)($r['total'] * $r['diskon'] / 100); 
                    $to += $r['total']; $tp += $pb;
                ?>
                <tr>
                    <td><?php echo $r['id']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($r['tanggal'])); ?></td>
                    <td>Rp <?php echo number_format($r['total']); ?></td>
                    <td>Rp <?php echo number_format($pb); ?></td>
                    <td><?php echo $r['kasir']; ?></td>
                    <td><?php echo $r['is_retur'] ? '<span class="status-retur">Retur</span>' : '<span class="badge bg-success">Normal</span>'; ?></td>
                    <td>
                        <button class="btn btn-sm btn-info btn-detail" data-id="<?php echo $r['id']; ?>" data-bs-toggle="modal" data-bs-target="#modalDetail<?php echo $r['id']; ?>">📋 Detail</button>
                        <button class="btn btn-sm btn-secondary" onclick="window.location.href='?cetak_transaksi=<?php echo $r['id']; ?>&jenis=struk&tab=laporan'">🧾 Struk</button>
                        <button class="btn btn-sm btn-info" onclick="window.location.href='?cetak_transaksi=<?php echo $r['id']; ?>&jenis=kwitansi&tab=laporan'">📄 Kwitansi</button>
                        <button class="btn btn-sm btn-primary" onclick="window.location.href='?cetak_transaksi=<?php echo $r['id']; ?>&jenis=faktur&tab=laporan'">📑 Faktur</button>
                        <?php if($is_admin && !$r['is_retur']): ?>
                        <button class="btn btn-sm btn-danger btn-retur" data-id="<?php echo $r['id']; ?>" data-bs-toggle="modal" data-bs-target="#modalRetur">🔄 Retur</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; if($to == 0): ?>
                <tr><td colspan="7" class="text-center">📭 Tidak ada数据</tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="alert alert-secondary fw-bold">🏦 Total Omset: Rp <?php echo number_format($to); ?> | 📈 Total Profit: Rp <?php echo number_format($tp); ?></div>
</div>

<!-- SEMUA MODAL DETAIL TRANSAKSI DILETAKKAN DI SINI (DI LUAR LOOP TABLE) -->
<?php 
// Reset pointer query untuk mengambil data detail lagi
$res_l2 = $app->get_laporan_per_kasir($t1, $t2, $kasir_filter);
while($r2 = $res_l2->fetch_assoc()): 
    $detail_modal = $app->get_detail_transaksi($r2['id']);
?>
<div class="modal fade" id="modalDetail<?php echo $r2['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">📋 DETAIL TRANSAKSI #<?php echo $r2['id']; ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if($detail_modal): ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light fw-bold">📄 INFORMASI TRANSAKSI</div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tr><td width="120"><strong>No. Transaksi</strong></td><td>: INV/<?php echo date('Ymd', strtotime($detail_modal['tanggal'])); ?>/<?php echo $detail_modal['id']; ?></td></tr>
                                    <tr><td><strong>Tanggal</strong></td><td>: <?php echo date('d/m/Y H:i:s', strtotime($detail_modal['tanggal'])); ?></td></tr>
                                    <tr><td><strong>Kasir</strong></td><td>: <?php echo htmlspecialchars($detail_modal['kasir']); ?></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light fw-bold">💰 RINGKASAN</div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tr><td width="120"><strong>Diskon</strong></td><td>: <?php echo $detail_modal['diskon']; ?>% (Rp <?php echo number_format($detail_modal['subtotal'] * $detail_modal['diskon'] / 100); ?>)</td></tr>
                                    <tr><td><strong>Ongkir</strong></td><td>: Rp <?php echo number_format($detail_modal['ongkir']); ?></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-light fw-bold">📦 DAFTAR PRODUK</div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr><th>Nama Produk</th><th>Kategori</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th>比
                            </thead>
                            <tbody>
                                <?php foreach($detail_modal['items'] as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['nama_produk']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($item['kategori']); ?></span></td>
                                    <td class="text-center"><?php echo $item['qty']; ?>x</td>
                                    <td class="text-end">Rp <?php echo number_format($item['harga_jual_saat_itu']); ?></td>
                                    <td class="text-end fw-bold">Rp <?php echo number_format($item['qty'] * $item['harga_jual_saat_itu']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-active">
                                <tr><td colspan="4" class="text-end fw-bold">TOTAL BARANG:</td><td class="text-end fw-bold">Rp <?php echo number_format($detail_modal['subtotal']); ?></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-light fw-bold">💳 RINGKASAN PEMBAYARAN</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr><td width="200"><strong>Subtotal</strong></td><td class="text-end">Rp <?php echo number_format($detail_modal['subtotal']); ?></td></tr>
                            <?php if($detail_modal['diskon'] > 0): ?>
                            <tr><td><strong>Diskon (<?php echo $detail_modal['diskon']; ?>%)</strong></td><td class="text-end text-danger">- Rp <?php echo number_format($detail_modal['subtotal'] * $detail_modal['diskon'] / 100); ?></td></tr>
                            <?php endif; ?>
                            <?php if($detail_modal['ongkir'] > 0): ?>
                            <tr><td><strong>Ongkos Kirim</strong></td><td class="text-end">+ Rp <?php echo number_format($detail_modal['ongkir']); ?></td></tr>
                            <?php endif; ?>
                            <tr class="border-top"><td><strong>TOTAL</strong></td><td class="text-end fw-bold fs-5 text-primary">Rp <?php echo number_format($detail_modal['total']); ?></td></tr>
                            <tr><td><strong>Dibayar</strong></td><td class="text-end">Rp <?php echo number_format($detail_modal['bayar']); ?></td></tr>
                            <tr><td><strong>Kembalian</strong></td><td class="text-end text-success fw-bold">Rp <?php echo number_format($detail_modal['kembalian']); ?></td></tr>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-danger">Data tidak ditemukan</div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endwhile; ?>

<!-- MODAL RETUR -->
<?php if($is_admin): ?>
<div class="modal fade" id="modalRetur" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">🔄 RETUR TRANSAKSI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_transaksi" id="retur_id">
                    <div class="mb-3">
                        <label>Alasan Retur</label>
                        <textarea name="alasan" class="form-control" rows="3" required placeholder="Masukkan alasan retur..."></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <strong>⚠️ Peringatan!</strong> Retur akan mengembalikan stok barang dan transaksi akan ditandai sebagai retur. Tindakan ini tidak dapat dibatalkan.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="retur_transaksi" class="btn btn-danger">Proses Retur</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>
           
            <!-- MENU LABA RUGI (HANYA ADMIN) -->
            <?php if($menu == 'laba_rugi' && $is_admin): ?>
            <div>
                <form method="GET" class="row g-3 mb-4">
                    <input type="hidden" name="tab" value="laba_rugi">
                    <div class="col-auto">📅 Mulai: <input type="date" name="tgl1" class="form-control w-auto d-inline-block" value="<?php echo $_GET['tgl1'] ?? date('Y-m-01'); ?>"></div>
                    <div class="col-auto">📅 Sampai: <input type="date" name="tgl2" class="form-control w-auto d-inline-block" value="<?php echo $_GET['tgl2'] ?? date('Y-m-t'); ?>"></div>
                    <div class="col-auto"><button type="submit" class="btn btn-info text-white">🔍 TAMPILKAN</button><button type="submit" name="cetak_laba_rugi" value="1" class="btn btn-warning text-white ms-2">📄 CETAK PDF</button></div>
                </form>
               
                <?php $t1 = $_GET['tgl1'] ?? date('Y-m-01'); $t2 = $_GET['tgl2'] ?? date('Y-m-t'); $lr = $app->get_laporan_laba_rugi($t1, $t2); ?>
                <div class="card"><div class="card-header bg-primary text-white">💰 LAPORAN LABA RUGI</div><div class="card-body">
                    <table class="table table-bordered">
                        <tr><td width="200"><strong>Total Penjualan</strong></td><td align="right"><strong>Rp <?php echo number_format($lr['total_penjualan'] ?? 0); ?></strong></td></tr>
                        <tr><td>Total Modal (HPP)</td><td align="right">Rp <?php echo number_format($lr['total_modal'] ?? 0); ?></td></tr>
                        <tr class="table-warning"><td><strong>LABA KOTOR</strong></td><td align="right"><strong>Rp <?php echo number_format(($lr['total_profit_kotor'] ?? 0) - ($lr['total_diskon'] ?? 0)); ?></strong></td></tr>
                        <tr><td>Total Diskon</td><td align="right">Rp <?php echo number_format($lr['total_diskon'] ?? 0); ?></td></tr>
                        <tr><td>Total Ongkos Kirim</td><td align="right">Rp <?php echo number_format($lr['total_ongkir'] ?? 0); ?></td></tr>
                        <tr class="table-success"><td><strong>LABA BERSIH</strong></td><td align="right"><strong>Rp <?php echo number_format((($lr['total_profit_kotor'] ?? 0) - ($lr['total_diskon'] ?? 0) - ($lr['total_ongkir'] ?? 0))); ?></strong></td></tr>
                    </table>
                </div></div>
               
                <div class="mt-4"><h6>Rincian per Kategori</h6><div class="table-responsive"><table class="table table-bordered"><thead class="table-light"><tr><th>Kategori</th><th>Terjual</th><th>Omzet</th><th>Modal</th><th>Profit</th></tr></thead><tbody>
                <?php $per_kat = $app->get_laporan_laba_rugi_per_kategori($t1, $t2); while($pk = $per_kat->fetch_assoc()): ?>
                <tr><td><strong><?php echo $pk['kategori']; ?></strong></td><td><?php echo number_format($pk['terjual']); ?> pcs</td><td>Rp <?php echo number_format($pk['omzet']); ?></td><td>Rp <?php echo number_format($pk['modal']); ?></td><td class="text-success fw-bold">Rp <?php echo number_format($pk['profit']); ?></td></tr>
                <?php endwhile; ?></tbody></table></div></div>
            </div>
            <?php endif; ?>
           
            <!-- MENU KATEGORI (HANYA ADMIN) -->
            <?php if($menu == 'kategori' && $is_admin): ?>
            <div>
                <div class="card mb-4"><div class="card-header bg-primary text-white">➕ Tambah Kategori Baru</div><div class="card-body"><form method="POST" class="row g-3"><div class="col-md-6"><input type="text" name="nama_kategori" class="form-control" placeholder="Nama Kategori" required></div><div class="col-md-4"><input type="text" name="icon" class="form-control" placeholder="Icon (emoji)" value="📦"></div><div class="col-md-2"><button type="submit" name="tambah_kategori" class="btn btn-success w-100">➕ Tambah</button></div></form></div></div>
                <div class="table-responsive"><table class="table table-bordered"><thead class="table-light"><tr><th>ID</th><th>Icon</th><th>Nama Kategori</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr></thead><tbody>
                <?php $kats = $app->get_all_kategori(); while($kat = $kats->fetch_assoc()): $produk_count = $app->get_jumlah_produk_by_kategori($kat['id']); ?>
                <tr><td><?php echo $kat['id']; ?></td><td style="font-size:18px;"><?php echo $kat['icon']; ?></td><td><?php echo htmlspecialchars($kat['nama_kategori']); ?> <?php if($produk_count > 0): ?><small class="text-muted">(<?php echo $produk_count; ?> produk)</small><?php endif; ?></td>
                <td><?php echo $kat['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>'; ?></td>
                <td><?php echo date('d/m/Y', strtotime($kat['created_at'])); ?></td>
                <td><button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditKategori<?php echo $kat['id']; ?>">✏️ Edit</button><?php if($produk_count == 0): ?><a href="?hapus_kat=<?php echo $kat['id']; ?>&tab=kategori" class="btn btn-sm btn-danger ms-1" onclick="return confirm('Hapus kategori ini?')">🗑️ Hapus</a><?php else: ?><button class="btn btn-sm btn-secondary ms-1" disabled>🔒 Terpakai</button><?php endif; ?></td></tr>
                <div class="modal fade" id="modalEditKategori<?php echo $kat['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header"><h5 class="modal-title">Edit Kategori</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="kat_id" value="<?php echo $kat['id']; ?>"><div class="mb-3"><label>Nama Kategori</label><input type="text" name="nama_kategori" class="form-control" value="<?php echo htmlspecialchars($kat['nama_kategori']); ?>" required></div><div class="mb-3"><label>Icon (Emoji)</label><input type="text" name="icon" class="form-control" value="<?php echo $kat['icon']; ?>"></div><div class="mb-3"><label>Status</label><select name="is_active" class="form-select"><option value="1" <?php echo $kat['is_active'] ? 'selected' : ''; ?>>Aktif</option><option value="0" <?php echo !$kat['is_active'] ? 'selected' : ''; ?>>Nonaktif</option></select></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="edit_kategori" class="btn btn-primary">Simpan</button></div></form></div></div></div>
                <?php endwhile; ?></tbody></table></div>
            </div>
            <?php endif; ?>
           
            <!-- MENU USER (HANYA ADMIN) -->
            <?php if($menu == 'users' && $is_admin): ?>
            <div>
                <div class="card mb-4"><div class="card-header bg-primary text-white">➕ Tambah User Baru</div><div class="card-body"><form method="POST" class="row g-3"><div class="col-md-3"><input type="text" name="username" class="form-control" placeholder="Username" required></div><div class="col-md-3"><input type="text" name="password" class="form-control" value="kasir123" required></div><div class="col-md-2"><select name="level" class="form-select"><option value="kasir">Kasir</option><option value="admin">Admin</option></select></div><div class="col-md-4"><input type="text" name="nama_lengkap" class="form-control" placeholder="Nama Lengkap" required></div><div class="col-md-12"><button type="submit" name="tambah_user" class="btn btn-success">➕ Tambah User</button></div></form></div></div>
               
                <div class="table-responsive">
                    <table class="table table-bordered table-user">
                        <thead class="table-light">
                            <tr><th>ID</th><th>Username</th><th>Nama Lengkap</th><th>Level</th><th>Status</th><th>Last Login</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php $users = $app->get_all_users(); while($u = $users->fetch_assoc()): $is_current = ($u['id'] == $_SESSION['current_user_id']); ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?> <?php echo $is_current ? '<span class="badge bg-info">Anda</span>' : ''; ?></td>
                                <td><?php echo htmlspecialchars($u['nama_lengkap']); ?></td>
                                <td><?php echo $u['level'] == 'admin' ? '<span class="badge bg-danger">Admin</span>' : '<span class="badge bg-success">Kasir</span>'; ?></td>
                                <td><?php echo $u['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>'; ?></td>
                                <td><?php echo $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : '-'; ?></td>
                                <td>
                                    <?php if(!$is_current): ?>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditUser<?php echo $u['id']; ?>">✏️ Edit</button>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalResetPass<?php echo $u['id']; ?>">🔑 Reset</button>
                                        <a href="?hapus_user=<?php echo $u['id']; ?>&tab=users" class="btn btn-sm btn-danger" onclick="return confirm('Hapus user ini?')">🗑️ Hapus</a>
                                    <?php else: ?>
                                        <span class="text-muted">(Anda sendiri)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <!-- Modal Edit User -->
                            <div class="modal fade" id="modalEditUser<?php echo $u['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header"><h5 class="modal-title">Edit User: <?php echo htmlspecialchars($u['username']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><div class="mb-3"><label>Username</label><input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($u['username']); ?>" required></div><div class="mb-3"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-control" value="<?php echo htmlspecialchars($u['nama_lengkap']); ?>" required></div><div class="mb-3"><label>Level</label><select name="level" class="form-select"><option value="kasir" <?php echo $u['level'] == 'kasir' ? 'selected' : ''; ?>>Kasir</option><option value="admin" <?php echo $u['level'] == 'admin' ? 'selected' : ''; ?>>Admin</option></select></div><div class="mb-3"><label>Status</label><select name="is_active" class="form-select"><option value="1" <?php echo $u['is_active'] ? 'selected' : ''; ?>>Aktif</option><option value="0" <?php echo !$u['is_active'] ? 'selected' : ''; ?>>Nonaktif</option></select></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="edit_user" class="btn btn-primary">Simpan</button></div></form></div></div></div>
                            <!-- Modal Reset Password -->
                            <div class="modal fade" id="modalResetPass<?php echo $u['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header"><h5 class="modal-title">Reset Password</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><div class="mb-3"><label>Password Baru</label><input type="text" name="new_password" class="form-control" value="kasir123" required><small class="text-muted">Default: kasir123</small></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="reset_password" class="btn btn-warning">Reset</button></div></form></div></div></div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
           
            <!-- MENU BACKUP (HANYA ADMIN) -->
            <?php if($menu == 'backup' && $is_admin): ?>
            <div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card"><div class="card-header bg-primary text-white">💾 BACKUP DATABASE</div><div class="card-body"><p>Backup database akan mendownload semua data (produk, transaksi, user, dll) ke file SQL.</p><a href="?backup=1&tab=backup" class="btn btn-primary" onclick="return confirm('Lakukan backup database sekarang?')">📥 Download Backup</a></div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="card"><div class="card-header bg-warning text-dark">🔄 RESTORE DATABASE</div><div class="card-body"><p class="text-danger"><strong>⚠️ Peringatan!</strong> Restore akan menghapus semua data saat ini dan menggantinya dengan data dari file backup.</p><form method="POST" enctype="multipart/form-data"><div class="mb-3"><label>Pilih File Backup (.sql)</label><input type="file" name="backup_file" class="form-control" accept=".sql" required></div><button type="submit" name="restore" class="btn btn-warning" onclick="return confirm('Yakin ingin merestorasi database? Data saat ini akan hilang!')">🔄 Restore Database</button></form></div></div>
                    </div>
                </div>
                <div class="alert alert-info mt-3">💡 <strong>Tips:</strong> Backup database secara rutin untuk mengamankan data bisnis Anda. Simpan file backup di tempat yang aman.</div>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL DETAIL TRANSAKSI (per transaksi, seperti edit produk) -->
<div class="modal fade" id="modalDetail<?php echo $r['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">📋 DETAIL TRANSAKSI #<?php echo $r['id']; ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php 
                // Ambil detail transaksi langsung dari database untuk modal ini
                $detail_transaksi = $app->get_detail_transaksi($r['id']);
                ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr><td width="110"><strong>🆔 No. Transaksi</strong></td><td>: INV/<?php echo date('Ymd', strtotime($detail_transaksi['tanggal'])); ?>/<?php echo $detail_transaksi['id']; ?></td></tr>
                            <tr><td width="110"><strong>📅 Tanggal</strong></td><td>: <?php echo date('d/m/Y H:i:s', strtotime($detail_transaksi['tanggal'])); ?></td></tr>
                            <tr><td width="110"><strong>👤 Kasir</strong></td><td>: <?php echo htmlspecialchars($detail_transaksi['kasir']); ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr><td width="110"><strong>🏷️ Diskon</strong></td><td>: <?php echo $detail_transaksi['diskon']; ?>% (Rp <?php echo number_format($detail_transaksi['subtotal'] * $detail_transaksi['diskon'] / 100); ?>)</td></tr>
                            <tr><td width="110"><strong>🚚 Ongkir</strong></td><td>: Rp <?php echo number_format($detail_transaksi['ongkir']); ?></td></tr>
                        </table>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-header bg-light fw-bold">📦 DAFTAR PRODUK</div>
                    <div class="card-body p-2">
                        <?php foreach($detail_transaksi['items'] as $item): ?>
                        <div class="row border-bottom py-2">
                            <div class="col-5"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                            <div class="col-2"><span class="badge bg-secondary"><?php echo htmlspecialchars($item['kategori']); ?></span></div>
                            <div class="col-1 text-center"><?php echo $item['qty']; ?>x</div>
                            <div class="col-2 text-end">Rp <?php echo number_format($item['harga_jual_saat_itu']); ?></div>
                            <div class="col-2 text-end fw-bold">Rp <?php echo number_format($item['qty'] * $item['harga_jual_saat_itu']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header bg-light fw-bold">💰 RINGKASAN PEMBAYARAN</div>
                    <div class="card-body">
                        <div class="row mb-2"><div class="col-8 text-end">Subtotal:</div><div class="col-4 text-end">Rp <?php echo number_format($detail_transaksi['subtotal']); ?></div></div>
                        <?php if($detail_transaksi['diskon'] > 0): ?>
                        <div class="row mb-2"><div class="col-8 text-end">Diskon (<?php echo $detail_transaksi['diskon']; ?>%):</div><div class="col-4 text-end text-danger">- Rp <?php echo number_format($detail_transaksi['subtotal'] * $detail_transaksi['diskon'] / 100); ?></div></div>
                        <?php endif; ?>
                        <?php if($detail_transaksi['ongkir'] > 0): ?>
                        <div class="row mb-2"><div class="col-8 text-end">Ongkir:</div><div class="col-4 text-end">+ Rp <?php echo number_format($detail_transaksi['ongkir']); ?></div></div>
                        <?php endif; ?>
                        <hr>
                        <div class="row mb-2"><div class="col-8 text-end"><strong>TOTAL:</strong></div><div class="col-4 text-end text-primary fw-bold fs-5">Rp <?php echo number_format($detail_transaksi['total']); ?></div></div>
                        <div class="row mb-2"><div class="col-8 text-end">Dibayar:</div><div class="col-4 text-end">Rp <?php echo number_format($detail_transaksi['bayar']); ?></div></div>
                        <div class="row"><div class="col-8 text-end"><strong>Kembalian:</strong></div><div class="col-4 text-end text-success fw-bold">Rp <?php echo number_format($detail_transaksi['kembalian']); ?></div></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// DATA PRODUK UNTUK CASCADING
const produkData = {};
<?php $allProduk = $app->get_produk_for_dropdown(); while($prod = $allProduk->fetch_assoc()) { $kat = $prod['kategori']; echo "if(!produkData['$kat']) produkData['$kat'] = [];\n"; echo "produkData['$kat'].push({nama: '" . addslashes($prod['nama_produk']) . "', harga: " . $prod['harga'] . ", stok: " . $prod['stok'] . "});\n"; } ?>

const kategoriSelect = document.getElementById('cb_kategori');
const produkSelect = document.getElementById('cb_brg');
const btnTambah = document.getElementById('btnTambah');

function updateProdukDropdown() {
    if(!kategoriSelect) return;
    const kat = kategoriSelect.value;
    if (!kat) { produkSelect.innerHTML = '<option value="">-- Pilih Kategori Dulu --</option>'; produkSelect.disabled = true; btnTambah.disabled = true; return; }
    const items = produkData[kat] || [];
    if (items.length === 0) { produkSelect.innerHTML = '<option value="">-- Tidak ada produk --</option>'; produkSelect.disabled = true; btnTambah.disabled = true; return; }
    let html = '';
    for (let p of items) html += `<option value="${p.nama}" data-stok="${p.stok}">${p.nama} - Rp ${p.harga.toLocaleString('id-ID')} (Stok: ${p.stok})</option>`;
    produkSelect.innerHTML = html; produkSelect.disabled = false; btnTambah.disabled = false;
}
if(kategoriSelect) kategoriSelect.addEventListener('change', updateProdukDropdown);

const qtyInput = document.getElementById('ent_qty');
function cekStok() {
    const opt = produkSelect?.options[produkSelect.selectedIndex];
    if (opt && opt.value) {
        const stok = parseInt(opt.dataset.stok) || 0;
        const qty = parseInt(qtyInput?.value) || 0;
        if (qty > stok && stok > 0) { alert(`⚠️ Stok tidak cukup! Tersedia: ${stok}`); qtyInput.value = stok; return false; }
        if (stok === 0) { alert("⚠️ Stok produk ini habis!"); btnTambah.disabled = true; return false; }
    }
    return true;
}
if(qtyInput) qtyInput.addEventListener('change', cekStok);

const st = <?php echo $st_total; ?>; const dsk = document.getElementById('dsk'); const ong = document.getElementById('ong'); const lbl = document.getElementById('total_lbl');
function updateT() { let d = parseInt(dsk?.value) || 0; let o = parseInt(ong?.value) || 0; let res = st - Math.floor(st * d / 100) + o; if(lbl) lbl.innerText = "TOTAL: Rp " + res.toLocaleString(); }
if(dsk) { dsk.addEventListener('input', updateT); if(ong) ong.addEventListener('input', updateT); }


// ========== CETAK TRANSAKSI LAMA ==========
function cetakTransaksi(id, jenis) {
    // Langsung redirect ke handler PHP tanpa fetch
    // Karena handler PHP akan mengambil data sendiri dari database
    window.open(`?cetak_transaksi=${id}&jenis=${jenis}`, '_blank');
}

// ============ FUNGSI DETAIL TRANSAKSI (PAKAI ALERT DULU UNTUK TEST) ============
function showDetail(id) {
    // Test: cek apakah fungsi terpanggil
    alert('Tombol Detail diklik untuk transaksi ID: ' + id);
    
    // Ambil data via AJAX
    fetch('?ajax_detail=1&id=' + id)
        .then(response => response.json())
        .then(data => {
            alert('Data diterima: ' + JSON.stringify(data).substring(0, 200));
            
            if (!data || !data.id) {
                alert('Data tidak ditemukan!');
                return;
            }
            
            // Buat modal secara manual
            let modalHtml = `
            <div class="modal fade" id="manualModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">📋 DETAIL TRANSAKSI #${data.id}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light fw-bold">📄 INFORMASI TRANSAKSI</div>
                                        <div class="card-body">
                                            <table class="table table-sm">
                                                <tr><td width="120">No. Transaksi</td><td>: INV/${data.tanggal.split(' ')[0].replace(/-/g, '')}/${data.id}</td></tr>
                                                <tr><td>Tanggal</td><td>: ${new Date(data.tanggal).toLocaleString('id-ID')}</td></tr>
                                                <tr><td>Kasir</td><td>: ${escapeHtml(data.kasir)}</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light fw-bold">💰 RINGKASAN</div>
                                        <div class="card-body">
                                            <table class="table table-sm">
                                                <tr><td width="120">Diskon</td><td>: ${data.diskon}% (Rp ${(data.subtotal * data.diskon / 100).toLocaleString('id-ID')})</td></tr>
                                                <tr><td>Ongkir</td><td>: Rp ${data.ongkir.toLocaleString('id-ID')}</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-3">
                                <div class="card-header bg-light fw-bold">📦 DAFTAR PRODUK</div>
                                <div class="card-body p-0">
                                    <table class="table table-bordered mb-0">
                                        <thead class="table-light"><tr><th>Nama Produk</th><th>Kategori</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr></thead>
                                        <tbody>
                                            ${data.items.map(item => `
                                            <tr>
                                                <td>${escapeHtml(item.nama_produk)}</td>
                                                <td><span class="badge bg-secondary">${escapeHtml(item.kategori)}</span></td>
                                                <td class="text-center">${item.qty}x</td>
                                                <td class="text-end">Rp ${item.harga_jual_saat_itu.toLocaleString('id-ID')}</td>
                                                <td class="text-end fw-bold">Rp ${(item.qty * item.harga_jual_saat_itu).toLocaleString('id-ID')}</td>
                                            </tr>
                                            `).join('')}
                                        </tbody>
                                        <tfoot class="table-active"><tr><td colspan="4" class="text-end fw-bold">TOTAL:</td><td class="text-end fw-bold">Rp ${data.subtotal.toLocaleString('id-ID')}</td></tr></tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header bg-light fw-bold">💳 RINGKASAN PEMBAYARAN</div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr><td width="200">Subtotal</td><td class="text-end">Rp ${data.subtotal.toLocaleString('id-ID')}</td></tr>
                                        ${data.diskon > 0 ? `<tr><td>Diskon (${data.diskon}%)</td><td class="text-end text-danger">- Rp ${(data.subtotal * data.diskon / 100).toLocaleString('id-ID')}</td></tr>` : ''}
                                        ${data.ongkir > 0 ? `<tr><td>Ongkos Kirim</td><td class="text-end">+ Rp ${data.ongkir.toLocaleString('id-ID')}</td></tr>` : ''}
                                        <tr class="border-top"><td><strong>TOTAL</strong></td><td class="text-end fw-bold fs-5 text-primary">Rp ${data.total.toLocaleString('id-ID')}</td></tr>
                                        <tr><td>Dibayar</td><td class="text-end">Rp ${data.bayar.toLocaleString('id-ID')}</td></tr>
                                        <tr><td><strong>Kembalian</strong></td><td class="text-end text-success fw-bold">Rp ${data.kembalian.toLocaleString('id-ID')}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>`;
            
            // Hapus modal lama jika ada
            let oldModal = document.getElementById('manualModal');
            if (oldModal) oldModal.remove();
            
            // Tambahkan modal ke body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Tampilkan modal
            let newModal = new bootstrap.Modal(document.getElementById('manualModal'));
            newModal.show();
            
            // Hapus modal setelah ditutup
            document.getElementById('manualModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Event listener untuk tombol detail (menggunakan event delegation on document)
document.addEventListener('click', function(e) {
    const detailBtn = e.target.closest('.btn-detail');
    if (detailBtn) {
        e.preventDefault();
        const id = detailBtn.getAttribute('data-id');
        if (id) showDetailTransaksi(id);
    }
});

// Event listener untuk tombol retur
document.querySelectorAll('.btn-retur').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        if (id) document.getElementById('retur_id').value = id;
    });
});

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}
</script>
</body>
</html>