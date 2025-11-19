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
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
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