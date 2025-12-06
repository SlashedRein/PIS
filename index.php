<?php
require_once 'config/database.php';

// --- FUNGSI DETEKSI GAMBAR (SUPER ROBUST) ---
function get_product_image($id) {
    // Daftar folder yang mungkin ada
    $folders = [
        "assets/images/foto_produk/kue_kering/",
        "assets/images/foto_produk/roti/"
    ];

    // Daftar ekstensi yang mungkin dipakai
    $extensions = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG'];

    // Cek kombinasi Folder + ID + Ekstensi
    foreach ($folders as $folder) {
        foreach ($extensions as $ext) {
            $path = $folder . $id . "." . $ext;
            if (file_exists($path)) {
                return $path; // Ketemu! Kembalikan path gambarnya
            }
        }
    }

    // Default jika benar-benar tidak ada
    // (Ganti text=No+Image dengan nama produk biar ketahuan ID berapa yang hilang)
    return "https://dummyimage.com/600x400/e0e0e0/8b4513&text=404+ID+$id";
}

// Ambil 6 Produk Unggulan
$querySlider = "SELECT * FROM produk WHERE stok > 0 ORDER BY harga_jual DESC LIMIT 6";
$resultSlider = $conn->query($querySlider);

// Ambil SEMUA Produk
$queryAll = "SELECT * FROM produk WHERE stok > 0 ORDER BY nama_produk ASC";
$resultAll = $conn->query($queryAll);

// Config WhatsApp
$wa_number = "6285287560800"; 
$wa_link_utama = "https://wa.me/$wa_number?text=" . urlencode("Halo Dewi Cookies, saya tertarik pesan kue.");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dewi Cookies - Premium Taste</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/custom.css">

    <style>
        /* --- STYLE KHUSUS INDEX --- */
        :root {
            --primary: #8B4513;
            --accent: #D7BCA2;
            --bg-light: #FFFBF2;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-light);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, .font-serif {
            font-family: 'Playfair Display', serif;
        }

        /* NAVBAR */
        .navbar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(139, 69, 19, 0.1);
            padding: 15px 0;
            transition: all 0.3s;
        }
        .nav-link {
            font-weight: 600;
            color: #5D4037 !important;
            margin: 0 10px;
        }

        /* HERO SECTION */
        .hero-section {
            padding: 120px 0 80px;
            background: radial-gradient(circle at top right, #FFF8DC 0%, transparent 40%);
        }
        .hero-img {
            border-radius: 30px;
            box-shadow: 20px 20px 60px rgba(139, 69, 19, 0.15);
            animation: float 6s ease-in-out infinite;
            max-height: 400px;
            object-fit: cover;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        /* HORIZONTAL SCROLL (Produk Unggulan) */
        .scrolling-wrapper {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 20px;
            padding-bottom: 20px;
            padding-left: 5px;
            padding-right: 20px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Sembunyikan scrollbar Firefox */
        }
        .scrolling-wrapper::-webkit-scrollbar { 
            display: none; /* Sembunyikan scrollbar Chrome */
        }
        
        .scroll-card {
            flex: 0 0 auto;
            width: 280px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border: 1px solid rgba(139, 69, 19, 0.05);
        }
        @media (max-width: 768px) {
            .scroll-card { width: 220px; }
        }
        .scroll-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(139, 69, 19, 0.15);
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
            background-color: #f8f9fa;
        }

        /* MODAL CATALOG (Grid System) */
        .modal-catalog .modal-content {
            border-radius: 20px;
            border: none;
        }
        .modal-catalog .modal-header {
            background-color: var(--bg-light);
            border-bottom: 1px solid rgba(139, 69, 19, 0.1);
        }
        .catalog-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr); /* Default HP: 2 Kolom */
            gap: 15px;
        }
        @media (min-width: 768px) {
            .catalog-grid { grid-template-columns: repeat(4, 1fr); /* Laptop: 4 Kolom */ }
        }

        /* FOOTER */
        footer {
            background: #2D1B18;
            color: #D7BCA2;
            padding: 60px 0 20px;
        }
        .hidden-login {
            color: #3E2723;
            opacity: 0.3;
            transition: 0.3s;
        }
        .hidden-login:hover {
            color: var(--accent);
            opacity: 1;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 font-serif fw-bold fs-3" href="#" style="color: #8B4513;">
                <i class="bi bi-cookie"></i> Dewi Cookies
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#home">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#produk">Produk</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimoni">Kata Mereka</a></li>
                    <li class="nav-item ms-2">
                        <a href="<?php echo $wa_link_utama; ?>" target="_blank" class="btn rounded-pill px-4 py-2 text-white fw-bold shadow-sm" style="background: linear-gradient(45deg, #8B4513, #A0522D);">
                            Pesan Sekarang
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center flex-column-reverse flex-lg-row">
                <div class="col-lg-6 mt-5 mt-lg-0 text-center text-lg-start">
                    <span class="badge bg-warning text-dark mb-3 px-3 py-2 rounded-pill fw-bold">✨ Resep Warisan Sejak 2015</span>
                    <h1 class="display-4 fw-bold mb-4" style="color: #3E2723;">
                        Kue Kering <br><span style="color: #D7BCA2;">Premium & Autentik</span>
                    </h1>
                    <p class="lead mb-5 text-muted">
                        Rasakan kelezatan resep turun-temurun khas Cikarang. Tekstur renyah, bahan premium, dan dikemas elegan untuk momen spesial Anda.
                    </p>
                    <div class="d-flex gap-3 justify-content-center justify-content-lg-start">
                        <a href="#produk" class="btn btn-lg rounded-pill px-5 text-white shadow" style="background-color: #8B4513;">Lihat Menu</a>
                        <a href="<?php echo $wa_link_utama; ?>" target="_blank" class="btn btn-lg btn-outline-dark rounded-pill px-4">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://images.unsplash.com/photo-1558961363-fa8fdf82db35?q=80&w=800" 
                         class="img-fluid hero-img w-75" alt="Cookies Hero">
                </div>
            </div>
        </div>
    </section>

    <section id="produk" class="py-5">
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h5 class="text-uppercase text-muted fw-bold small">Pilihan Favorit</h5>
                    <h2 class="font-serif fw-bold text-dark">Menu Andalan</h2>
                </div>
                <button type="button" class="btn btn-outline-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#catalogModal">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </button>
            </div>

            <div class="scrolling-wrapper">
                <?php if ($resultSlider->num_rows > 0): ?>
                    <?php while($row = $resultSlider->fetch_assoc()): 
                        // Deteksi Gambar (Kue Kering / Roti)
                        $img_src = get_product_image($row['id_produk']);
                        
                        // Link WA per Produk
                        $wa_prod = "https://wa.me/$wa_number?text=" . urlencode("Halo, saya mau pesan " . $row['nama_produk']);
                    ?>
                    <div class="scroll-card">
                        <img src="<?php echo $img_src; ?>" class="card-img-top" alt="<?php echo $row['nama_produk']; ?>">
                        <div class="p-3 text-center">
                            <h5 class="font-serif fw-bold text-dark mb-1 text-truncate"><?php echo $row['nama_produk']; ?></h5>
                            <p class="small text-muted mb-2 text-truncate"><?php echo ucfirst($row['satuan']); ?></p>
                            <h5 class="fw-bold text-primary mb-3">Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?></h5>
                            <a href="<?php echo $wa_prod; ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill w-100">Pesan</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center text-muted w-100 py-5">Belum ada produk unggulan.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="testimoni" class="py-5" style="background-color: #FFF3E0;">
        <div class="container py-5 text-center">
            <h2 class="font-serif fw-bold mb-5" style="color: #3E2723;">Kata Mereka</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="bg-white p-4 rounded-4 shadow-sm">
                        <div class="text-warning fs-4 mb-3">★★★★★</div>
                        <p class="fs-5 fst-italic text-muted">"Rasanya benar-benar premium! Beda banget sama kue kering pasaran. Packagingnya juga aman sampai luar kota."</p>
                        <h6 class="fw-bold mt-3">— Ibu Sarah, Jakarta</h6>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="row text-center text-md-start">
                <div class="col-md-4 mb-4">
                    <h4 class="font-serif fw-bold mb-3 text-white">Dewi Cookies</h4>
                    <p class="small opacity-75">Perum Telaga Murni Blok C8 No.18, Cikarang Barat, Bekasi.<br>WA: 0852-8756-0800</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold text-white mb-3">Navigasi</h5>
                    <ul class="list-unstyled">
                        <li><a href="#home" class="text-decoration-none text-white-50">Beranda</a></li>
                        <li><a href="#produk" class="text-decoration-none text-white-50">Produk</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4 text-center text-md-end">
                    <h5 class="fw-bold text-white mb-3">Sosial Media</h5>
                    <div class="d-flex gap-3 justify-content-center justify-content-md-end">
                        <a href="#" class="text-white fs-5"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white fs-5"><i class="bi bi-facebook"></i></a>
                    </div>
                </div>
            </div>
            <hr class="opacity-25">
            <div class="text-center small opacity-50">
                © 2025 Dewi Cookies. 
                <a href="login.php" class="hidden-login ms-2" title="Login Admin"><i class="bi bi-lock-fill"></i></a>
            </div>
        </div>
    </footer>

    <div class="modal fade modal-catalog" id="catalogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-serif fw-bold">Semua Menu Kami</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="container-fluid">
                        <div class="catalog-grid">
                            <?php 
                            if ($resultAll->num_rows > 0): 
                                while($p = $resultAll->fetch_assoc()): 
                                    // PANGGIL FUNGSI DETEKSI LAGI
                                    $img_src = get_product_image($p['id_produk']);
                                    $wa_prod = "https://wa.me/$wa_number?text=" . urlencode("Halo, saya mau pesan " . $p['nama_produk']);
                            ?>
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                                <img src="<?php echo $img_src; ?>" class="card-img-top" style="height: 150px; object-fit: cover;" alt="<?php echo $p['nama_produk']; ?>">
                                <div class="card-body text-center p-3">
                                    <h6 class="font-serif fw-bold text-dark mb-1"><?php echo $p['nama_produk']; ?></h6>
                                    <p class="small text-muted mb-2"><?php echo ucfirst($p['satuan']); ?></p>
                                    <h6 class="fw-bold text-primary mb-3">Rp <?php echo number_format($p['harga_jual'], 0, ',', '.'); ?></h6>
                                    <a href="<?php echo $wa_prod; ?>" target="_blank" class="btn btn-sm btn-brown w-100 rounded-pill">Order</a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12 text-center text-muted">Belum ada produk.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <small class="text-muted">Hubungi kami via WhatsApp untuk ketersediaan stok.</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Efek Navbar Transparan saat Scroll
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                nav.style.boxShadow = "0 4px 20px rgba(0,0,0,0.05)";
                nav.style.background = "rgba(255, 255, 255, 0.95)";
            } else {
                nav.style.boxShadow = "none";
                nav.style.background = "rgba(255, 255, 255, 0.9)";
            }
        });
    </script>
</body>
</html>