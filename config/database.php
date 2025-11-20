<?php
// Konfigurasi koneksi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventory_cookies');

// Membuat koneksi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set charset UTF-8
$conn->set_charset("utf8mb4");

// --- FUNGSI-FUNGSI BANTUAN (HELPER) ---

// 1. Mencegah SQL Injection
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// 2. Format Rupiah
if (!function_exists('format_rupiah')) {
    function format_rupiah($angka) {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}

// 3. Format Tanggal Indonesia
function format_tanggal($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $split = explode('-', $tanggal);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// 4. FUNGSI KONVERSI SATUAN (NEW! PENTING!)
// Fungsi ini mengubah jumlah di resep agar sesuai dengan satuan stok
function hitung_stok_pengurangan($jumlah_resep, $satuan_resep, $satuan_stok) {
    
    // Normalisasi teks (huruf kecil semua biar cocok)
    $s_resep = strtolower(trim($satuan_resep));
    $s_stok  = strtolower(trim($satuan_stok));

    // Jika satuan sama, tidak perlu konversi
    if ($s_resep == $s_stok) {
        return $jumlah_resep;
    }

    // Skenario 1: Stok KG, Resep GRAM
    if ($s_stok == 'kg' && ($s_resep == 'gram' || $s_resep == 'gr' || $s_resep == 'g')) {
        return $jumlah_resep / 1000; // 100 gram jadi 0.1 kg
    }

    // Skenario 2: Stok LITER, Resep ML
    if (($s_stok == 'liter' || $s_stok == 'l') && ($s_resep == 'ml' || $s_resep == 'mililiter')) {
        return $jumlah_resep / 1000; // 500 ml jadi 0.5 liter
    }

    // Skenario 3: Stok GRAM, Resep KG (Jarang, tapi mungkin)
    if (($s_stok == 'gram' || $s_stok == 'gr') && $s_resep == 'kg') {
        return $jumlah_resep * 1000; // 1 kg jadi 1000 gram
    }

    // Skenario 4: Stok PCS/BUTIR/PACK (Barang satuan)
    // Biasanya tidak dikonversi, kembalikan nilai asli
    return $jumlah_resep;
}
?>