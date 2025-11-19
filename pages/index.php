<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dewi Cookies - Kue Kering Premium Khas Cikarang</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --choco-deep: #3E2723;      /* Coklat tua pekat */
            --choco-warm: #6D4C41;      /* Coklat hangat */
            --gold-soft: #D7BCA2;       /* Emas krem lembut */
            --gold-accent: #BCAAA4;     /* Aksen emas pudar */
            --white: #FFFFFF;
            --offwhite: #FFF8F0;
            --black-soft: #212121;
            --text: #444;
            --border: #E8E0D8;
        }

        * { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--offwhite);
            color: var(--text);
            line-height: 1.7;
            font-size: 16px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            color: var(--choco-deep);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        /* Navbar */
        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 1.2rem 0;
            box-shadow: 0 4px 20px rgba(62, 39, 35, 0.08);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            padding: 0.7rem 0;
            box-shadow: 0 6px 30px rgba(62, 39, 35, 0.12);
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--choco-deep) !important;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .navbar-brand svg {
            width: 36px;
            height: 36px;
            fill: var(--gold-soft);
        }

        .nav-link {
            color: var(--choco-warm) !important;
            font-weight: 600;
            font-size: 0.98rem;
            padding: 0.5rem 1.1rem !important;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--choco-deep) !important;
            background: linear-gradient(135deg, rgba(215, 188, 162, 0.15), rgba(188, 170, 164, 0.1));
        }

        .btn-login {
            background: linear-gradient(135deg, var(--gold-soft), var(--gold-accent));
            color: var(--choco-deep);
            border-radius: 50px;
            padding: 0.55rem 1.8rem;
            font-weight: 600;
            font-size: 0.92rem;
            box-shadow: 0 6px 20px rgba(215, 188, 162, 0.3);
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(215, 188, 162, 0.4);
            color: var(--choco-deep);
        }

        /* Hero */
        .hero {
            padding: 190px 0 130px;
            background: linear-gradient(135deg, var(--offwhite) 0%, #FFEBCD 40%, #F5DEB3 100%);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><path d="M0,10 Q25,0 50,10 T100,10 L100,20 L0,20 Z" fill="%23D7BCA2" opacity="0.15"/></svg>') bottom/100% 100% repeat-x;
            animation: wave 18s linear infinite;
        }

        @keyframes wave {
            0% { background-position: 0 0; }
            100% { background-position: 100% 0; }
        }

        .hero h1 {
            font-size: 4.2rem;
            line-height: 1.15;
            margin-bottom: 1.3rem;
            background: linear-gradient(135deg, var(--choco-deep), var(--choco-warm));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero .lead {
            font-size: 1.25rem;
            color: #555;
            max-width: 680px;
            margin: 0 auto 2.8rem;
            line-height: 1.9;
        }

        .btn-cta {
            background: linear-gradient(135deg, var(--gold-soft), var(--gold-accent));
            color: var(--choco-deep);
            padding: 1.1rem 3rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.15rem;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 10px 30px rgba(215, 188, 162, 0.35);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-cta::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: 0.6s;
        }

        .btn-cta:hover::before { left: 100%; }
        .btn-cta:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(215, 188, 162, 0.45);
        }

        /* Section */
        .section-padding { padding: 110px 0; }

        .section-title {
            text-align: center;
            margin-bottom: 4.5rem;
        }

        .section-title h2 {
            font-size: 2.8rem;
            position: relative;
            display: inline-block;
            padding-bottom: 1rem;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, var(--gold-soft), var(--gold-accent));
            border-radius: 2px;
        }

        /* About Us */
        .about-img {
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            width: 100%;
            height: auto;
        }

        .about-text {
            font-size: 1.05rem;
            color: var(--text);
            line-height: 1.9;
        }

        /* Slider Produk */
        .carousel-item img {
            height: 420px;
            object-fit: cover;
            border-radius: 22px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }

        .carousel-caption {
            background: rgba(62, 39, 35, 0.82);
            border-radius: 14px;
            padding: 1.2rem 1.8rem;
            bottom: 25px;
            left: 50%;
            transform: translateX(-50%);
            width: 85%;
            max-width: 520px;
            border: 1px solid rgba(215, 188, 162, 0.3);
        }

        .carousel-caption h3 {
            font-size: 1.7rem;
            margin-bottom: 0.5rem;
            color: var(--white);
        }

        .carousel-caption p {
            margin: 0;
            font-size: 1.15rem;
            color: var(--gold-soft);
            font-weight: 500;
        }

        .carousel-control-prev, .carousel-control-next {
            width: 56px;
            height: 56px;
            background: var(--gold-soft);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.9;
            box-shadow: 0 6px 20px rgba(215, 188, 162, 0.3);
        }

        .carousel-control-prev { left: 25px; }
        .carousel-control-next { right: 25px; }

        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-image: none;
            color: var(--choco-deep);
            font-weight: bold;
        }

        /* Products Grid */
        .product-card {
            background: var(--white);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 35px rgba(0,0,0,0.06);
            transition: all 0.4s ease;
            border: 1px solid var(--border);
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-14px);
            box-shadow: 0 28px 65px rgba(215, 188, 162, 0.22);
            border-color: var(--gold-soft);
        }

        .product-img {
            height: 270px;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .product-card:hover .product-img {
            transform: scale(1.1);
        }

        .product-body {
            padding: 2rem;
            text-align: center;
        }

        .product-title {
            font-size: 1.35rem;
            margin-bottom: 0.9rem;
            color: var(--choco-deep);
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--gold-soft), var(--gold-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Testimoni Pelanggan */
        .testimonial-slider .carousel-item {
            text-align: center;
            padding: 2rem;
        }

        .testimonial-slider img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            border: 3px solid var(--gold-soft);
        }

        .testimonial-text {
            font-style: italic;
            color: var(--text);
            font-size: 1.1rem;
            margin-bottom: 1rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .testimonial-author {
            font-weight: 600;
            color: var(--choco-deep);
        }

        .stars {
            color: var(--gold-soft);
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        /* WA Float - SUPER MEWAH */
        .wa-float {
            position: fixed;
            bottom: 32px;
            right: 32px;
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            box-shadow: 0 12px 40px rgba(37, 197, 102, 0.45);
            z-index: 1000;
            transition: all 0.4s ease;
            border: 4px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            animation: float 3s ease-in-out infinite, pulse 2s infinite;
        }

        .wa-float:hover {
            transform: translateY(-8px) scale(1.1);
            box-shadow: 0 20px 50px rgba(37, 197, 102, 0.55);
        }

        .wa-float span {
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(37, 197, 102, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(37, 197, 102, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 197, 102, 0); }
        }

        /* Responsive */
        @media (max-width: 992px) {
            .hero h1 { font-size: 3.2rem; }
            .hero { padding: 150px 0 100px; }
            .carousel-item img { height: 320px; }
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.6rem; }
            .section-padding { padding: 90px 0; }
            .section-title h2 { font-size: 2.3rem; }
            .wa-float { 
                width: 64px; 
                height: 64px; 
                font-size: 1.4rem; 
                bottom: 24px; 
                right: 24px;
            }
            .wa-float span { font-size: 0.65rem; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                Dewi Cookies
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="#home">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#slider">Slider</a></li>
                    <li class="nav-item"><a class="nav-link" href="#products">Produk</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimonials">Testimoni</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Kontak</a></li>
                    <li class="nav-item ms-2">
                        <a href="login.php" class="btn btn-login">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero text-center" id="home">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <h1>Dewi Cookies</h1>
                    <p class="lead">
                        Kue kering premium khas Cikarang dengan resep turun-temurun. 
                        Rasa autentik, tekstur renyah, dan kemasan elegan — 
                        sempurna untuk hampers dan momen spesial Anda.
                    </p>
                    <a href="https://wa.me/6281298316967?text=Halo%20Dewi%20Cookies,%20saya%20mau%20pesan..." 
                       class="btn-cta" target="_blank">Pesan via WhatsApp</a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1576613224752-6e11b92e0d55?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Dewi Cookies Shop" class="about-img">
                </div>
                <div class="col-lg-6">
                    <h2 class="section-title text-start">About Us</h2>
                    <p class="about-text">
                        <strong>Dewi Cookies</strong> didirikan pada tahun 2015 di Cikarang. 
                        Kami mengkhususkan diri pada kue kering premium dengan resep turun-temurun. 
                        Setiap kue dibuat dengan cinta, bahan berkualitas tinggi, dan sentuhan personal. 
                        Tujuan kami adalah menghadirkan rasa autentik yang membuat Anda merasa seperti di rumah.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Slider Produk -->
    <section class="section-padding bg-white" id="slider">
        <div class="container">
            <div class="section-title">
                <h2>Produk Unggulan Kami</h2>
            </div>
            <div id="productSlider" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="https://images.unsplash.com/photo-1606890658317-7d14490b76fd?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80" class="d-block w-100" alt="Java Bli Special">
                        <div class="carousel-caption">
                            <h3>Java Bli Special</h3>
                            <p>Rp 585.000 • Kue khas Jawa autentik</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="https://images.unsplash.com/photo-1624353365286-3f8d62daad51?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80" class="d-block w-100" alt="Sai Instant">
                        <div class="carousel-caption">
                            <h3>Sai Instant</h3>
                            <p>Rp 380.000 • Camilan renyah</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="https://images.unsplash.com/photo-1558326567-98ae2405596b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80" class="d-block w-100" alt="Melcher Choice">
                        <div class="carousel-caption">
                            <h3>Melcher Choice</h3>
                            <p>Rp 15.000 • Cookies premium</p>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#productSlider" data-bs-slide="prev">
                    <span>Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#productSlider" data-bs-slide="next">
                    <span>Next</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Products Grid -->
    <section class="section-padding bg-light" id="products">
        <div class="container">
            <div class="section-title">
                <h2>Semua Produk</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="product-card">
                        <img src="https://images.unsplash.com/photo-1606890658317-7d14490b76fd?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Java Bli" class="img-fluid product-img">
                        <div class="product-body">
                            <h3 class="product-title">Java Bli Special</h3>
                            <p>Kue khas Jawa autentik</p>
                            <div class="product-price">Rp 585.000</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="product-card">
                        <img src="https://images.unsplash.com/photo-1624353365286-3f8d62daad51?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Sai Instant" class="img-fluid product-img">
                        <div class="product-body">
                            <h3 class="product-title">Sai Instant</h3>
                            <p>Camilan renyah</p>
                            <div class="product-price">Rp 380.000</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="product-card">
                        <img src="https://images.unsplash.com/photo-1558326567-98ae2405596b?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Melcher Choice" class="img-fluid product-img">
                        <div class="product-body">
                            <h3 class="product-title">Melcher Choice</h3>
                            <p>Cookies premium</p>
                            <div class="product-price">Rp 15.000</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="product-card">
                        <img src="https://images.unsplash.com/photo-1603532648953-5845c9ac1e82?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Hampers" class="img-fluid product-img">
                        <div class="product-body">
                            <h3 class="product-title">Hampers Lebaran</h3>
                            <p>Paket spesial</p>
                            <div class="product-price">Rp 350.000</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimoni Pelanggan -->
    <section class="py-5 bg-light" id="testimonials">
        <div class="container">
            <h2 class="section-title">Apa Kata Pelanggan Kami</h2>
            <div id="testimonialSlider" class="carousel slide testimonial-slider" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Ibu Sarah">
                        <div class="stars">★★★★★</div>
                        <p class="testimonial-text">
                            "Java Bli Special-nya luar biasa! Renyah, manis pas, bikin kangen kampung halaman. 
                            Sudah jadi langganan untuk hampers Lebaran!"
                        </p>
                        <p class="testimonial-author">— Ibu Sarah, Jakarta</p>
                    </div>
                    <div class="carousel-item">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Bapak Andi">
                        <div class="stars">★★★★★</div>
                        <p class="testimonial-text">
                            "Kemasan elegan, rasanya premium. Cocok untuk hadiah perusahaan. 
                            Tim kantor suka banget!"
                        </p>
                        <p class="testimonial-author">— Bapak Andi, Bekasi</p>
                    </div>
                    <div class="carousel-item">
                        <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Ibu Dewi">
                        <div class="stars">★★★★★</div>
                        <p class="testimonial-text">
                            "Resep turun-temurun terasa banget. Kue kering terenak di Cikarang!"
                        </p>
                        <p class="testimonial-author">— Ibu Dewi, Cikarang</p>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#testimonialSlider" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#testimonialSlider" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
        </div>
    </section>
    <!-- Contact -->
    <section class="contact text-center" id="contact" style="background: linear-gradient(135deg, var(--choco-deep), var(--choco-warm)); color: white; padding: 110px 0;">
        <div class="container">
            <h2>Hubungi Kami</h2>
            <div class="row justify-content-center g-3">
                <div class="col-md-8 col-lg-6">
                    <div class="p-3 rounded" style="background: rgba(255,255,255,0.12); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.18);">
                        <strong>Alamat</strong><br>
                        Perum Telaga Murni J.Mancaga 2 Block C8 No.18<br>
                        Telaga Murni - Cikarang Barat
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-center py-4" style="background: var(--choco-deep); color: #ddd;">
        <div class="container">
            <p>&copy; 2025 <strong>Dewi Cookies</strong>. Kue Kering Premium untuk Setiap Momen Spesial.</p>
        </div>
    </footer>

    <!-- WA Float MEWAH -->
    <a href="https://wa.me/6281298316967?text=Halo%20Dewi%20Cookies,%20saya%20mau%20pesan..." 
       class="wa-float" target="_blank">
        WhatsApp
        <span>Pesan</span>
    </a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Navbar scroll
        window.addEventListener('scroll', () => {
            document.querySelector('.navbar').classList.toggle('scrolled', window.scrollY > 60);
        });
    </script>
</body>
</html>