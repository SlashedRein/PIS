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
        /* --- STYLE TAMBAHAN KHUSUS HALAMAN UTAMA --- */
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

        h1, h2, h3, .font-serif {
            font-family: 'Playfair Display', serif;
        }

        /* NAVBAR GLASSMORPHISM */
        .navbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(139, 69, 19, 0.1);
            transition: all 0.3s ease;
            padding: 15px 0;
        }
        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            padding: 10px 0;
        }
        .nav-link {
            font-weight: 600;
            color: #5D4037 !important;
            margin: 0 10px;
            position: relative;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0; height: 2px;
            bottom: 0; left: 0;
            background-color: var(--primary);
            transition: width 0.3s;
        }
        .nav-link:hover::after { width: 100%; }

        /* HERO SECTION DINAMIS */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: radial-gradient(circle at top right, #FFF8DC 0%, transparent 40%),
                        radial-gradient(circle at bottom left, #FFE4C4 0%, transparent 40%);
            padding-top: 80px;
            position: relative;
            overflow: hidden;
        }
        .hero-img {
            animation: float 6s ease-in-out infinite;
            border-radius: 30px;
            box-shadow: 20px 20px 60px rgba(139, 69, 19, 0.15);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        /* CARDS YANG LEBIH MODERN */
        .product-card {
            border: none;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 20px rgba(0,0,0,0.03);
        }
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(139, 69, 19, 0.15);
        }
        .card-img-wrapper {
            overflow: hidden;
            height: 250px;
        }
        .card-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .product-card:hover .card-img-wrapper img {
            transform: scale(1.1);
        }

        /* TESTIMONI */
        .testi-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            margin: 10px;
            border: 1px solid rgba(139, 69, 19, 0.05);
        }

        /* FOOTER */
        footer {
            background: #2D1B18;
            color: #D7BCA2;
            padding: 60px 0 20px;
        }
        
        /* Hidden Login Button Style */
        .hidden-login {
            color: #3E2723; /* Hampir sama dengan background footer agar samar */
            transition: all 0.3s;
            font-size: 1.2rem;
            opacity: 0.3;
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
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#home">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#produk">Produk</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimoni">Kata Mereka</a></li>
                    <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                    <li class="nav-item ms-2">
                        <a href="#produk" class="btn rounded-pill px-4 py-2 text-white fw-bold" style="background: linear-gradient(45deg, #8B4513, #A0522D); box-shadow: 0 4px 15px rgba(139,69,19,0.3);">
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
                    <h1 class="display-3 fw-bold mb-4" style="color: #3E2723; line-height: 1.2;">
                        Kue Kering <br><span style="color: #D7BCA2;">Premium & Autentik</span>
                    </h1>
                    <p class="lead mb-5 text-muted">
                        Rasakan kelezatan resep turun-temurun khas Cikarang. Tekstur renyah, bahan premium, dan dikemas elegan untuk momen spesial Anda.
                    </p>
                    <div class="d-flex gap-3 justify-content-center justify-content-lg-start">
                        <a href="#produk" class="btn btn-lg rounded-pill px-5 text-white shadow" style="background-color: #8B4513;">Lihat Katalog</a>
                        <a href="https://wa.me/6281298316967" target="_blank" class="btn btn-lg btn-outline-dark rounded-pill px-4 border-2">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://images.unsplash.com/photo-1558961363-fa8fdf82db35?q=80&w=800&auto=format&fit=crop" 
                         alt="Cookies Hero" class="img-fluid hero-img w-75">
                </div>
            </div>
        </div>
    </section>

    <section id="produk" class="py-5">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h5 class="text-uppercase text-muted fw-bold letter-spacing-2">Best Seller</h5>
                <h2 class="display-5 font-serif fw-bold" style="color: #3E2723;">Pilihan Favorit Pelanggan</h2>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="product-card h-100">
                        <div class="card-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1606890658317-7d14490b76fd?w=600&q=80" alt="Kue">
                        </div>
                        <div class="p-4 text-center">
                            <h4 class="font-serif fw-bold text-dark">Java Bli Special</h4>
                            <p class="text-muted small">Kerenyahan khas dengan butter premium.</p>
                            <h5 class="fw-bold text-primary mb-3">Rp 585.000</h5>
                            <a href="https://wa.me/6281298316967?text=Halo%20saya%20mau%20pesan%20Java%20Bli" class="btn btn-sm btn-outline-dark rounded-pill w-100">Order</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="product-card h-100">
                        <div class="card-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1624353365286-3f8d62daad51?w=600&q=80" alt="Kue">
                        </div>
                        <div class="p-4 text-center">
                            <h4 class="font-serif fw-bold text-dark">Sai Instant</h4>
                            <p class="text-muted small">Camilan gurih teman santai.</p>
                            <h5 class="fw-bold text-primary mb-3">Rp 380.000</h5>
                            <a href="https://wa.me/6281298316967?text=Halo%20saya%20mau%20pesan%20Sai%20Instant" class="btn btn-sm btn-outline-dark rounded-pill w-100">Order</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="product-card h-100">
                        <div class="card-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1558326567-98ae2405596b?w=600&q=80" alt="Kue">
                        </div>
                        <div class="p-4 text-center">
                            <h4 class="font-serif fw-bold text-dark">Melcher Choice</h4>
                            <p class="text-muted small">Manisnya pas, lumer di mulut.</p>
                            <h5 class="fw-bold text-primary mb-3">Rp 15.000</h5>
                            <a href="https://wa.me/6281298316967?text=Halo%20saya%20mau%20pesan%20Melcher" class="btn btn-sm btn-outline-dark rounded-pill w-100">Order</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="product-card h-100">
                        <div class="card-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1603532648953-5845c9ac1e82?w=600&q=80" alt="Kue">
                        </div>
                        <div class="p-4 text-center">
                            <h4 class="font-serif fw-bold text-dark">Hampers Lebaran</h4>
                            <p class="text-muted small">Paket hadiah elegan untuk kerabat.</p>
                            <h5 class="fw-bold text-primary mb-3">Rp 350.000</h5>
                            <a href="https://wa.me/6281298316967?text=Halo%20saya%20mau%20pesan%20Hampers" class="btn btn-sm btn-outline-dark rounded-pill w-100">Order</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="testimoni" class="py-5" style="background-color: #FFF3E0;">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="font-serif fw-bold" style="color: #3E2723;">Apa Kata Mereka?</h2>
            </div>
            
            <div id="carouselTestimoni" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active text-center">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="testi-card">
                                    <div class="mb-3 text-warning fs-4">★★★★★</div>
                                    <p class="fs-5 fst-italic text-muted">"Rasanya benar-benar premium! Beda banget sama kue kering pasaran. Packagingnya juga aman sampai luar kota."</p>
                                    <h5 class="fw-bold mt-4 text-dark">— Ibu Sarah, Jakarta</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item text-center">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="testi-card">
                                    <div class="mb-3 text-warning fs-4">★★★★★</div>
                                    <p class="fs-5 fst-italic text-muted">"Sudah langganan tiap tahun buat hampers kantor. Pelayanan ramah dan kuenya selalu fresh."</p>
                                    <h5 class="fw-bold mt-4 text-dark">— Bapak Budi, Bekasi</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselTestimoni" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon bg-dark rounded-circle" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselTestimoni" data-bs-slide="next">
                    <span class="carousel-control-next-icon bg-dark rounded-circle" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </section>

    <section id="kontak" class="py-5 position-relative text-white" style="background: #3E2723;">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="font-serif fw-bold mb-4">Kunjungi Dapur Kami</h2>
                    <p class="mb-4 opacity-75">Kami selalu terbuka untuk pesanan partai besar maupun kecil. Silakan hubungi kami atau datang langsung.</p>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <i class="bi bi-geo-alt fs-4 text-warning"></i>
                        <span>Perum Telaga Murni J.Mancaga 2 Block C8 No.18,<br>Cikarang Barat, Bekasi</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-whatsapp fs-4 text-warning"></i>
                        <span>0812-9831-6967</span>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="ratio ratio-16x9 rounded-4 overflow-hidden shadow-lg border border-3 border-white">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.036357716949!2d107.11289837499065!3d-6.25894799372958!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e698f9a569326df%3A0x3c55572972073202!2sTelaga%20Murni!5e0!3m2!1sen!2sid!4v1700000000000!5m2!1sen!2sid" loading="lazy"></iframe>
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
                    <p class="small opacity-75">Menghadirkan kehangatan keluarga melalui setiap gigitan kue kering berkualitas.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold text-white mb-3">Link Cepat</h5>
                    <ul class="list-unstyled">
                        <li><a href="#home" class="text-decoration-none text-white-50 hover-white">Beranda</a></li>
                        <li><a href="#produk" class="text-decoration-none text-white-50 hover-white">Katalog</a></li>
                        <li><a href="#kontak" class="text-decoration-none text-white-50 hover-white">Hubungi Kami</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4 text-center text-md-end">
                    <h5 class="fw-bold text-white mb-3">Ikuti Kami</h5>
                    <div class="d-flex gap-3 justify-content-center justify-content-md-end">
                        <a href="#" class="text-white fs-5"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white fs-5"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white fs-5"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>
            </div>
            <hr class="opacity-25">
            <div class="text-center small opacity-50 d-flex justify-content-center align-items-center gap-2">
                &copy; 2025 Dewi Cookies. All Rights Reserved. 
                <a href="login.php" class="hidden-login" title="Admin Area"><i class="bi bi-lock-fill"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar berubah warna saat discroll
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                document.querySelector('.navbar').classList.add('scrolled');
            } else {
                document.querySelector('.navbar').classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>