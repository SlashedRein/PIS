<?php
require_once 'config/database.php';

// --- FUNGSI DETEKSI GAMBAR ---
function get_product_image($id) {
    $folders = ["assets/images/foto_produk/kue_kering/", "assets/images/foto_produk/roti/"];
    $extensions = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG'];

    foreach ($folders as $folder) {
        foreach ($extensions as $ext) {
            $path = $folder . $id . "." . $ext;
            if (file_exists($path)) return $path;
        }
    }
    return "https://dummyimage.com/600x400/e0e0e0/8b4513&text=404+ID+$id";
}

// Ambil Data Produk
$resultSlider = $conn->query("SELECT * FROM produk WHERE stok > 0 ORDER BY harga_jual DESC LIMIT 6");
$resultAll = $conn->query("SELECT * FROM produk WHERE stok > 0 ORDER BY nama_produk ASC");

// Config WhatsApp
$wa_number = "6285287560800"; 
$wa_link_utama = "https://wa.me/$wa_number?text=" . urlencode("Halo Dewi Cookies, saya tertarik pesan kue.");

// --- DATA DUMMY GOOGLE MAPS REVIEW (5 TERBARU) ---
// Nanti bisa diganti database atau API jika sudah advanced
$google_reviews = [
    [
        'name' => 'Siti Aminah',
        'time' => '2 hari lalu',
        'text' => 'Nastar-nya juara banget! Lembut, nanasnya kerasa asli, ga kemanisan. Pas banget buat oleh-oleh.',
        'avatar' => 'https://ui-avatars.com/api/?name=Siti+Aminah&background=random',
        'stars' => 5
    ],
    [
        'name' => 'Budi Santoso',
        'time' => '1 minggu lalu',
        'text' => 'Langganan tiap lebaran disini. Packaging aman banget sampe Surabaya ga ada yang hancur. Mantap!',
        'avatar' => 'https://ui-avatars.com/api/?name=Budi+Santoso&background=random',
        'stars' => 5
    ],
    [
        'name' => 'Jessica Wong',
        'time' => '3 minggu lalu',
        'text' => 'Rotinya empuk, fresh from the oven. Suka banget sama varian coklatnya.',
        'avatar' => 'https://ui-avatars.com/api/?name=Jessica+Wong&background=random',
        'stars' => 5
    ],
    [
        'name' => 'Rahmat Hidayat',
        'time' => '1 bulan lalu',
        'text' => 'Harga sebanding dengan rasa. Premium taste memang beda. Recommended seller!',
        'avatar' => 'https://ui-avatars.com/api/?name=Rahmat+Hidayat&background=random',
        'stars' => 4
    ],
    [
        'name' => 'Dewi Lestari',
        'time' => '1 bulan lalu',
        'text' => 'Pelayanan ramah, pengiriman cepat. Kue putri saljunya lumer di mulut.',
        'avatar' => 'https://ui-avatars.com/api/?name=Dewi+Lestari&background=random',
        'stars' => 5
    ]
];
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
        :root { --primary: #8B4513; --accent: #D7BCA2; --bg-light: #FFFBF2; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }
        h1, h2, h3, h4, .font-serif { font-family: 'Playfair Display', serif; }
        
        /* NAVBAR & HERO (Sama) */
        .navbar { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(139, 69, 19, 0.1); padding: 15px 0; }
        .nav-link { font-weight: 600; color: #5D4037 !important; margin: 0 10px; }
        .hero-section { padding: 120px 0 80px; background: radial-gradient(circle at top right, #FFF8DC 0%, transparent 40%); }
        .hero-img { border-radius: 30px; box-shadow: 20px 20px 60px rgba(139, 69, 19, 0.15); animation: float 6s ease-in-out infinite; max-height: 400px; object-fit: cover; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }

        /* HORIZONTAL SCROLL PRODUCT */
        .scrolling-wrapper { display: flex; flex-wrap: nowrap; overflow-x: auto; gap: 20px; padding-bottom: 20px; padding-left: 5px; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
        .scrolling-wrapper::-webkit-scrollbar { display: none; }
        .scroll-card { flex: 0 0 auto; width: 280px; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 20px rgba(0,0,0,0.05); transition: transform 0.3s; border: 1px solid rgba(139, 69, 19, 0.05); }
        @media (max-width: 768px) { .scroll-card { width: 220px; } }
        .scroll-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(139, 69, 19, 0.15); }
        .card-img-top { height: 200px; object-fit: cover; background-color: #f8f9fa; }

        /* --- STYLE BARU: GOOGLE MAPS REVIEW MARQUEE --- */
        .marquee-container {
            position: relative;
            overflow: hidden;
            padding: 20px 0;
            mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
            -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
        }
        
        .marquee-track {
            display: flex;
            gap: 20px;
            width: max-content;
            animation: scrollLeft 30s linear infinite; /* Kecepatan geser */
        }
        
        /* Pause saat dihover biar bisa baca */
        .marquee-track:hover { animation-play-state: paused; }

        @keyframes scrollLeft {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); } /* Geser 50% karena konten diduplikasi */
        }

        .review-bubble {
            width: 320px;
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            flex-shrink: 0;
            position: relative;
            transition: transform 0.3s;
        }
        
        .review-bubble:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(139, 69, 19, 0.1);
        }

        .reviewer-info { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .reviewer-avatar { width: 40px; height: 40px; border-radius: 50%; }
        .reviewer-meta h6 { font-size: 0.95rem; margin: 0; color: #333; font-weight: 700; }
        .reviewer-meta small { font-size: 0.75rem; color: #777; }
        
        .star-rating { color: #FFC107; font-size: 0.9rem; margin-bottom: 8px; }
        .google-icon { 
            position: absolute; top: 20px; right: 20px; 
            width: 20px; height: 20px; opacity: 0.7; 
        }

        /* Catalog Modal */
        .catalog-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        @media (min-width: 768px) { .catalog-grid { grid-template-columns: repeat(4, 1fr); } }
        
        footer { background: #2D1B18; color: #D7BCA2; padding: 60px 0 20px; }
        .hidden-login { color: #3E2723; opacity: 0.3; transition: 0.3s; }
        .hidden-login:hover { color: var(--accent); opacity: 1; }
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
                    <li class="nav-item"><a class="nav-link" href="#testimoni">Review</a></li>
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
                        Rasakan kelezatan resep turun-temurun khas Cikarang. Tekstur renyah, bahan premium, dan dikemas elegan.
                    </p>
                    <div class="d-flex gap-3 justify-content-center justify-content-lg-start">
                        <a href="#produk" class="btn btn-lg rounded-pill px-5 text-white shadow" style="background-color: #8B4513;">Lihat Menu</a>
                        <a href="<?php echo $wa_link_utama; ?>" target="_blank" class="btn btn-lg btn-outline-dark rounded-pill px-4">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://images.unsplash.com/photo-1558961363-fa8fdf82db35?q=80&w=800" class="img-fluid hero-img w-75" alt="Cookies Hero">
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
                        $img_src = get_product_image($row['id_produk']);
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
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="testimoni" class="py-5" style="background-color: #FFF3E0;">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h5 class="text-uppercase text-muted fw-bold small">Apa Kata Mereka?</h5>
                <h2 class="font-serif fw-bold" style="color: #3E2723;">Ulasan Google Maps</h2>
                <div class="d-flex justify-content-center align-items-center gap-2 mt-2">
                    <span class="fw-bold fs-4">4.8</span>
                    <div class="text-warning">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                    </div>
                    <span class="text-muted small">(Berdasarkan 50+ Ulasan)</span>
                </div>
            </div>

            <div class="marquee-container">
                <div class="marquee-track">
                    <?php 
                    // Kita looping 2x agar animasi infinite scroll tidak putus (seamless loop)
                    for($i=0; $i<2; $i++): 
                        foreach($google_reviews as $rev): 
                    ?>
                    <div class="review-bubble">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/aa/Google_Maps_icon_%282020%29.svg/512px-Google_Maps_icon_%282020%29.svg.png" class="google-icon" alt="Gmaps">
                        
                        <div class="reviewer-info">
                            <img src="<?php echo $rev['avatar']; ?>" class="reviewer-avatar" alt="Avatar">
                            <div class="reviewer-meta">
                                <h6><?php echo $rev['name']; ?></h6>
                                <small><?php echo $rev['time']; ?></small>
                            </div>
                        </div>
                        
                        <div class="star-rating">
                            <?php for($s=0; $s<$rev['stars']; $s++) echo '<i class="bi bi-star-fill"></i> '; ?>
                        </div>
                        
                        <p class="mb-0 text-muted small" style="line-height: 1.6;">
                            "<?php echo $rev['text']; ?>"
                        </p>
                    </div>
                    <?php endforeach; endfor; ?>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="https://maps.google.com/?q=Dewi+Cookies+Cikarang" target="_blank" class="btn btn-outline-brown rounded-pill px-4" style="border-color: #8B4513; color: #8B4513;">
                    <i class="bi bi-geo-alt-fill me-1"></i> Lihat Lokasi di Maps
                </a>
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
                <a href="login.php" class="hidden-login ms-2"><i class="bi bi-lock-fill"></i></a>
            </div>
        </div>
    </footer>

    <div class="modal fade modal-catalog" id="catalogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-serif fw-bold">Semua Menu Kami</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="container-fluid">
                        <div class="catalog-grid">
                            <?php 
                            if ($resultAll->num_rows > 0): 
                                while($p = $resultAll->fetch_assoc()): 
                                    $img_src = get_product_image($p['id_produk']);
                                    $wa_prod = "https://wa.me/$wa_number?text=" . urlencode("Halo, saya mau pesan " . $p['nama_produk']);
                            ?>
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                                <img src="<?php echo $img_src; ?>" class="card-img-top" style="height: 150px; object-fit: cover;" alt="...">
                                <div class="card-body text-center p-3">
                                    <h6 class="font-serif fw-bold text-dark mb-1"><?php echo $p['nama_produk']; ?></h6>
                                    <p class="small text-muted mb-2"><?php echo ucfirst($p['satuan']); ?></p>
                                    <h6 class="fw-bold text-primary mb-3">Rp <?php echo number_format($p['harga_jual'], 0, ',', '.'); ?></h6>
                                    <a href="<?php echo $wa_prod; ?>" target="_blank" class="btn btn-sm btn-brown w-100 rounded-pill">Order</a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12 text-center">Produk tidak ditemukan.</div>
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
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) document.querySelector('.navbar').style.boxShadow = "0 4px 20px rgba(0,0,0,0.05)";
            else document.querySelector('.navbar').style.boxShadow = "none";
        });
    </script>
</body>
</html>