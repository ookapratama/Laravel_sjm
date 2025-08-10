
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SAIR JAYA MANDIRI | Menuju Kesuksesan Bersama</title>
    <meta content="" name="description"/>
    <meta content="" name="keywords"/>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo.ico') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Nunito+Sans:ital,wght@0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"/>
    <link href="{{ asset('front/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('front/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet"/>
    <link href="{{ asset('front/assets/vendor/aos/aos.css') }}" rel="stylesheet"/>
    <link href="{{ asset('front/assets/vendor/swiper/swiper-bundle.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('front/assets/vendor/glightbox/css/glightbox.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('front/assets/css/main.css') }}" rel="stylesheet"/>
    <style>
        /* Perbaikan CSS untuk validasi form */
        .is-invalid {
            border-color: #dc3545 !important;
        }
        .invalid-feedback {
            color: #dc3545;
            font-size: 0.85em;
            margin-top: 4px;
            display: block;
        }
        .drop-zone {
            background-color: #1f2a33;
            border: 2px dashed #f5c542;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            color: #f5c542;
            cursor: pointer;
            transition: background-color 0.3s ease;
            min-height: 60px;
        }
        .drop-zone:hover {
            background-color: #2a3845;
        }
        .drop-zone-text {
            font-weight: 500;
            font-size: 14px;
            display: inline-block;
            color: #f5c542;
        }
        /* Perbaikan warna Toastr agar kembali ke default */
        #toast-container > div {
            opacity: 1 !important;
            box-shadow: 0 0 12px #999 !important;
        }
        #toast-container > .toast-success {
            background-color: #51a351 !important;
        }
        #toast-container > .toast-error {
            background-color: #bd362f !important;
        }
        #toast-container > .toast-info {
            background-color: #2f96b4 !important;
        }
        #toast-container > .toast-warning {
            background-color: #f89406 !important;
        }
		.drop-zone-input {
    /* Sembunyikan secara visual, tapi tetap bisa diakses oleh DOM */
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}
    </style>
</head>
<body class="index-page">
    <header class="header d-flex align-items-center fixed-top" id="header">
        <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a class="logo d-flex align-items-center me-auto me-xl-0" href="/">
                <h1 class="sitename">PT.SAIR JAYA MANDIRI</h1>
            </a>
            <nav class="navmenu" id="navmenu">
                <ul>
                    <li><a class="active" href="#hero">Home</a></li>
                    <li><a href="#about">Visi dan Misi</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#portfolio">Portfolio</a></li>
                    <li><a href="#team">Team</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
            @guest
                <a href="{{ route('login') }}" class="btn-getstarted">Login</a>
                <a class="btn-getstarted" href="#register">Register</a>
            @else
                @php
                    $role = Auth::user()->role;
                    $dashboard = match($role) {
                        'admin' => '/admin',
                        'member' => '/member',
                        'super-admin' => '/super-admin',
                        'finance' => '/finance',
                        default => '/'
                    };
                @endphp
                <a href="{{ $dashboard }}" class="btn-getstarted">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-getstarted">Logout</button>
                </form>
            @endguest
        </div>
    </header>
    <main class="main">
        <section class="hero section" id="hero">
            <div class="container">
                <div class="row">
                    <div class="col-lg-7 content-col" data-aos="fade-up">
                        <div class="content">
                            <div class="agency-name"></div>
                            <div class="main-heading">
                                <h1>RAIH IMPIANMU <br/>SEKARANG</h1>
                            </div>
                            <div class="divider"></div>
                            <div class="description">
                                <p>Dapatkan penghasilan tak terbatas melalui sistem MLM Binary yang adil dan transparan. Bonus pairing hingga Rp16.000.000 per level, dukungan penuh dari tim, dan produk berkualitas tinggi. Gabung sekarang — karena sukses tidak perlu ditunda!</p>
                            </div>
                            <div class="cta-button">
                                <a class="btn" href="#services">
                                    <span>EXPLORE SERVICES</span>
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5" data-aos="zoom-out">
                        <div class="visual-content">
                            <div class="fluid-shape">
                                <img alt="Abstract Fluid Shape" class="fluid-img" src="front/assets/img/product.png"/>
                            </div>
                            <div class="stats-card">
                                <div class="stats-number">
                                    <h2>1.5JT</h2>
                                </div>
                                <div class="stats-label">
                                    <p>Daftar Sekarang Juga</p>
                                </div>
                                <div class="stats-arrow">
                                    <a href="#portfolio"><i class="bi bi-arrow-up-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="about section" id="about">
            <div class="container section-title" data-aos="fade-up">
                <h2>Visi dan Misi</h2>
                <div><span>TENTANG </span> <span class="description-title">PERUSAHAAN KAMI</span></div>
            </div><div class="container">
                <div class="row gx-5 align-items-center">
                    <div class="col-lg-6" data-aos="fade-right" data-aos-delay="200">
                        <div class="about-image position-relative">
                            <img alt="About Image" class="img-fluid rounded-4 shadow-sm" loading="lazy" src="front/assets/img/about/kantor.webp"/>
                            <div class="experience-badge">
                                <span class="years">20+</span>
                                <span class="text">Years of Expertise</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mt-4 mt-lg-0" data-aos="fade-left" data-aos-delay="300">
                        <div class="about-content">
                            <h2>Visi Perusahaan</h2>
                            <p class="lead">Menjadi perusahaan distribusi dan pengembangan jaringan terbaik di Indonesia yang mendorong pertumbuhan ekonomi mandiri, serta membawa perubahan positif bagi masyarakat melalui peluang usaha yang jujur, berkelanjutan, dan saling menguntungkan.</p>
                            <h2>Misi Perusahaan</h2>
                            <p class="lead">Membangun jaringan bisnis yang kuat, profesional, dan berintegritas melalui sistem kemitraan dan edukasi yang transparan. Memberikan peluang usaha kepada masyarakat luas dengan modal terjangkau dan sistem bonus yang adil. Meningkatkan kesejahteraan para mitra melalui produk berkualitas dan dukungan pelatihan yang berkelanjutan. Menjadi wadah pertumbuhan bersama (Tumbuh Bersama) di mana setiap individu memiliki kesempatan yang sama untuk sukses. Mengembangkan inovasi digital dan teknologi sistem binary untuk mempercepat dan mempermudah pencapaian mitra di seluruh Indonesia.</p>
                            <a class="btn btn-primary mt-4" href="#">Explore Our Services</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="services section" id="services"></section>
        <section class="steps section" id="steps">
            <div class="container section-title" data-aos="fade-up">
                <h2>CARA KERJA</h2>
                <div><span>Bagaimana</span> <span class="description-title">Langkah-Langkahnya</span></div>
            </div><div class="container" data-aos="fade-up" data-aos-delay="100">
                <div class="steps-wrapper">
                    <div class="step-item" data-aos="fade-right" data-aos-delay="200">
                        <div class="step-content">
                            <div class="step-icon">
                                <i class="bi bi-lightbulb"></i>
                            </div>
                            <div class="step-info">
                                <span class="step-number">Step 01</span>
                                <h3>Daftar Sebagai Member</h3>
                                <p><li>Isi data diri lengkap di halaman pendaftaran.</li>
                                <li>Pilih paket keanggotaan sesuai kebutuhan Anda.</li>
                                <li>Aktivasi akun dan langsung dapatkan <strong class="text-green-600">voucher bonus awal senilai Rp7.500.000</strong>!</li>
                                </p>
                            </div>
                        </div>
                    </div><div class="step-item" data-aos="fade-left" data-aos-delay="300">
                        <div class="step-content">
                            <div class="step-icon">
                                <i class="bi bi-gear"></i>
                            </div>
                            <div class="step-info">
                                <span class="step-number">Step 02</span>
                                <h3>Bangun Dua Kaki Jaringan</h3>
                                <p><li>Ajak 2 orang pertama Anda bergabung.</li>
                                <li>Tempatkan satu di kiri dan satu di kanan.</li>
                                <li>Langsung dapat <strong class="text-blue-600">Bonus Pairing Level 1 sebesar Rp1.000.000</strong>!</li></p>
                            </div>
                        </div>
                    </div><div class="step-item" data-aos="fade-right" data-aos-delay="400">
                        <div class="step-content">
                            <div class="step-icon">
                                <i class="bi bi-bar-chart"></i>
                            </div>
                            <div class="step-info">
                                <span class="step-number">Step 03</span>
                                <h3>Lanjutkan dan Dapatkan Cuan!</h3>
                                <p><li>Terus kembangkan jaringan Anda.</li>
                                <li>Dapatkan bonus pairing hingga level 10 dan ulangi cycle.</li>
                                <li>Reaktivasi untuk terus hasilkan cuan tanpa batas!</li></p>
                            </div>
                        </div>
                    </div></div>
            </div>
        </section><section class="portfolio section" id="portfolio">
            <div class="container section-title" data-aos="fade-up">
                <h2>ProdukS</h2>
                <div><span>Produk</span> <span class="description-title">yang kami tawarkan</span></div>
            </div><div class="container-fluid" data-aos="fade-up" data-aos-delay="100">
                <div class="isotope-layout" data-default-filter="*" data-layout="masonry" data-sort="original-order">
                    <ul class="portfolio-filters isotope-filters" data-aos="fade-up" data-aos-delay="200">
                        <li class="filter-active" data-filter="*">
                            <i class="bi bi-grid-3x3"></i> Produk Kami
                        </li>
                    </ul>
                    <div class="row g-4 isotope-container" data-aos="fade-up" data-aos-delay="300">
                        <div class="col-xl-3 col-lg-4 col-md-6 portfolio-item isotope-item filter-ui">
                            <article class="portfolio-entry">
                                <figure class="entry-image">
                                    <img alt="" class="img-fluid" loading="lazy" src="front/assets/img/portfolio/produk.webp"/>
                                    <div class="entry-overlay">
                                        <div class="overlay-content">
                                            <div class="entry-meta">Azka</div>
                                            <h3 class="entry-title">Of Arabia</h3>
                                            <div class="entry-links">
                                                <a class="glightbox" data-gallery="portfolio-gallery-ui" data-glightbox="title: Azka Of Arabiah description: Praesent commodo cursus magna, vel scelerisque nisl consectetur." href="front/assets/img/portfolio/produk.wep">
                                                    <i class="bi bi-arrows-angle-expand"></i>
                                                </a>
                                                <a href="portfolio-details.html">
                                                    <i class="bi bi-arrow-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </figure>
                            </article>
                        </div><div class="row g-4 isotope-container" data-aos="fade-up" data-aos-delay="300">
                            <div class="col-xl-3 col-lg-4 col-md-6 portfolio-item isotope-item filter-ui">
                                <article class="portfolio-entry">
                                    <figure class="entry-image">
                                        <img alt="" class="img-fluid" loading="lazy" src="front/assets/img/portfolio/produk2.webp"/>
                                        <div class="entry-overlay">
                                            <div class="overlay-content">
                                                <div class="entry-meta">Jaspear</div>
                                                <h3 class="entry-title">Bloom</h3>
                                                <div class="entry-links">
                                                    <a class="glightbox" data-gallery="portfolio-gallery-ui" data-glightbox="title: Jaspear Bloom  description: Praesent commodo cursus magna, vel scelerisque nisl consectetur." href="front/assets/img/portfolio/produk2.webp">
                                                        <i class="bi bi-arrows-angle-expand"></i>
                                                    </a>
                                                    <a href="portfolio-details.html">
                                                        <i class="bi bi-arrow-right"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </figure>
                                </article>
                            </div><div class="col-xl-3 col-lg-4 col-md-6 portfolio-item isotope-item filter-ui">
                                <article class="portfolio-entry">
                                    <figure class="entry-image">
                                        <img alt="" class="img-fluid" loading="lazy" src="front/assets/img/portfolio/produk2.webp"/>
                                        <div class="entry-overlay">
                                            <div class="overlay-content">
                                                <div class="entry-meta">Fresh </div>
                                                <h3 class="entry-title">Vibes</h3>
                                                <div class="entry-links">
                                                    <a class="glightbox" data-gallery="portfolio-gallery-ui" data-glightbox="title: Azka Of Arabiah description: Praesent commodo cursus magna, vel scelerisque nisl consectetur." href="front/assets/img/portfolio/produk.wep">
                                                        <i class="bi bi-arrows-angle-expand"></i>
                                                    </a>
                                                    <a href="portfolio-details.html">
                                                        <i class="bi bi-arrow-right"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </figure>
                                </article>
                            </div><div class="row g-4 isotope-container" data-aos="fade-up" data-aos-delay="300">
                                <div class="col-xl-3 col-lg-4 col-md-6 portfolio-item isotope-item filter-ui">
                                    <article class="portfolio-entry">
                                        <figure class="entry-image">
                                            <img alt="" class="img-fluid" loading="lazy" src="front/assets/img/portfolio/produk2.webp"/>
                                            <div class="entry-overlay">
                                                <div class="overlay-content">
                                                    <div class="entry-meta">Arabian</div>
                                                    <h3 class="entry-title">Vibes</h3>
                                                    <div class="entry-links">
                                                        <a class="glightbox" data-gallery="portfolio-gallery-ui" data-glightbox="title: Jaspear Bloom  description: Praesent commodo cursus magna, vel scelerisque nisl consectetur." href="front/assets/img/portfolio/produk2.webp">
                                                            <i class="bi bi-arrows-angle-expand"></i>
                                                        </a>
                                                        <a href="portfolio-details.html">
                                                            <i class="bi bi-arrow-right"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </figure>
                                    </article>
                                </div></div></div></div>
                </div>
            </div>
        </section><section class="team section light-background" id="team">
            <div class="container section-title" data-aos="fade-up">
                <h2>Team</h2>
                <div><span>Check Our</span> <span class="description-title">Team</span></div>
            </div><div class="container" data-aos="fade-up" data-aos-delay="100">
                <div class="row gy-4">
                    <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="team-member d-flex">
                            <div class="member-img">
                                <img alt="" class="img-fluid" loading="lazy" src="front/assets/img/person/5.webp"/>
                            </div>
                            <div class="member-info flex-grow-1">
                                <h4>Hj.Satriani</h4>
                                <span>Komisaris</span>
                                <p>“Saya di balik layar, memastikan semuanya berjalan lancar. Kalian yang di garis depan — saya hanya bantu menyalakan panggungnya.”</p>
                                <div class="social">
                                    <a href=""><i class="bi bi-facebook"></i></a>
                                    <a href=""><i class="bi bi-twitter-x"></i></a>
                                    <a href=""><i class="bi bi-linkedin"></i></a>
                                    <a href=""><i class="bi bi-youtube"></i></a>
                                </div>
                            </div>
                        </div>
                    </div><div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="team-member d-flex">
                            <div class="member-img">
                                <img alt="" class="img-fluid" loading="lazy" src="front/assets/img/person/3.webp"/>
                            </div>
                            <div class="member-info flex-grow-1">
                                <h4>H. Irwan S.E</h4>
                                <span>CEO</span>
                                <p>“Bergabung dengan SJM bukan hanya soal cuan, tapi juga membangun masa depan. Saya pastikan sistem ini terus berkembang bersama Anda.”</p>
                                <div class="social">
                                    <a href=""><i class="bi bi-facebook"></i></a>
                                    <a href=""><i class="bi bi-twitter-x"></i></a>
                                    <a href=""><i class="bi bi-linkedin"></i></a>
                                    <a href=""><i class="bi bi-youtube"></i></a>
                                </div>
                            </div>
                        </div>
                    </div><div class="col-lg-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="team-member d-flex">
                            <div class="member-img">
                                <img alt="" class="img-fluid" loading="lazy" src="front/assets/img/person/2.webp"/>
                            </div>
                            <div class="member-info flex-grow-1">
                                <h4>H. Kamri S.Pd</h4>
                                <span>Enginer</span>
                                <p>“Tugas saya adalah memastikan setiap proses dalam sistem Sair Beauty berjalan efisien, aman, dan sesuai kebutuhan bisnis. </p>
                                <div class="social">
                                    <a href=""><i class="bi bi-facebook"></i></a>
                                    <a href=""><i class="bi bi-twitter-x"></i></a>
                                    <a href=""><i class="bi bi-linkedin"></i></a>
                                    <a href=""><i class="bi bi-youtube"></i></a>
                                </div>
                            </div>
                        </div>
                    </div><div class="col-lg-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="team-member d-flex">
                            <div class="member-img">
                                <img alt="" class="img-fluid" loading="lazy" src="front/assets/img/person/4.webp"/>
                            </div>
                            <div class="member-info flex-grow-1">
                                <h4>Halim AK</h4>
                                <span>System Analist</span>
                                <p>“Dengan pengalaman membangun sistem MLM yang dinamis, saya percaya teknologi adalah kunci pertumbuhan SJM. Bersama, kita tumbuh, kita jaya!”</p>
                                <div class="social">
                                    <a href=""><i class="bi bi-facebook"></i></a>
                                    <a href=""><i class="bi bi-twitter-x"></i></a>
                                    <a href=""><i class="bi bi-linkedin"></i></a>
                                    <a href=""><i class="bi bi-youtube"></i></a>
                                </div>
                            </div>
                        </div>
                    </div><div class="col-lg-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="team-member d-flex">
                            <div class="member-img">
                                <img alt="" class="img-fluid" loading="lazy" src="front/assets/img/person/1.webp"/>
                            </div>
                            <div class="member-info flex-grow-1">
                                <h4>Muhammad Ikhsan S.Kom</h4>
                                <span>Software Enginer</span>
                                <p>Kami membangun sistem ini dengan fokus pada keamanan, kecepatan, dan kemudahan. Tujuan kami adalah menciptakan platform yang bisa mendorong kesuksesan semua member Sair Beauty.</p>
                                <div class="social">
                                    <a href=""><i class="bi bi-facebook"></i></a>
                                    <a href=""><i class="bi bi-twitter-x"></i></a>
                                    <a href=""><i class="bi bi-linkedin"></i></a>
                                    <a href=""><i class="bi bi-youtube"></i></a>
                                </div>
                            </div>
                        </div>
                    </div></div>
            </div>
        </section><section class="pricing section" id="pricing">
            <div class="container section-title" data-aos="fade-up">
                <h2>Pricing</h2>
                <div><span>Check Our</span> <span class="description-title">Pricing</span></div>
            </div><div class="container" data-aos="fade-up" data-aos-delay="100">
                <div class="row gy-4">
                    <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="200">
                        <div class="pricing-card">
                            <div class="plan-header">
                                <div class="plan-icon">
                                    <i class="bi bi-box"></i>
                                </div>
                                <h3>Starter</h3>
                                <p>For individuals just getting started</p>
                            </div>
                            <div class="plan-pricing">
                                <div class="price">
                                    <span class="currency">Rp</span>
                                    <span class="amount">1.5 Jt</span>
                                    <span class="period">/ID</span>
                                </div>
                            </div>
                            <div class="plan-features">
                                <ul>
                                    <li  class="disabled"><i class="bi bi-check-circle-fill"></i> Langsung Dapat Bonus Pasangan</li>
                                    <li  class="disabled"><i class="bi bi-check-circle-fill"></i> Bonus Lebih Banyaks</li>
                                    <li><i class="bi bi-check-circle-fill"></i> 2 Direct Sponsor Dapatkan Bonusnyat</li>
                                </ul>
                            </div>
                            <div class="plan-cta">
                                <a class="btn-plan" href="#">Choose Plan</a>
                            </div>
                        </div>
                    </div><div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="300">
                        <div class="pricing-card popular">
                            <div class="popular-tag">Most Popular</div>
                            <div class="plan-header">
                                <div class="plan-icon">
                                    <i class="bi bi-briefcase"></i>
                                </div>
                                <h3>Professional</h3>
                                <p>For small teams and growing businesses</p>
                            </div>
                            <div class="plan-pricing">
                                <div class="price">
                                    <span class="currency">Rp</span>
                                    <span class="amount">4.5 Jt</span>
                                    <span class="period">/3 ID</span>
                                </div>
                            </div>
                            <div class="plan-features">
                                <ul>
                                    <li><i class="bi bi-check-circle-fill"></i> Langsung Dapat Bonus Pasangan</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Bonus Lebih Banyaks</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Suskes Semakin Cepat</li>
                                </ul>
                            </div>
                            <div class="plan-cta">
                                <a class="btn-plan" href="#">Choose Plan</a>
                            </div>
                        </div>
                    </div><div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="400">
                        <div class="pricing-card">
                            <div class="plan-header">
                                <div class="plan-icon">
                                    <i class="bi bi-building"></i>
                                </div>
                                <h3>Enterprise</h3>
                                <p>For large organizations and corporations</p>
                            </div>
                            <div class="plan-pricing">
                                <div class="price">
                                    <span class="currency">Rp</span>
                                    <span class="amount">10.5 jt</span>
                                    <span class="period">/7 ID</span>
                                </div>
                            </div>
                            <div class="plan-features">
                                <ul>
                                    <li><i class="bi bi-check-circle-fill"></i> 3x Bonus Pairing Lanssung</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Bonus melimpah</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Pencapaiaan Point Lebih Cepatn</li>
                                </ul>
                            </div>
                            <div class="plan-cta">
                                <a class="btn-plan" href="#">Choose Plan</a>
                            </div>
                        </div>
                    </div></div>
            </div>
        </section>@php
            $orderId = request('orderId') ?? $orderId ?? null;
        @endphp
        @if ($orderId)
            <script>
                const orderId = "{{ $orderId }}";
                const checkPaymentStatus = () => {
                    fetch(`/check-payment-status/${orderId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'paid') {
                                toastr.success("Pembayaran berhasil!");
                                clearInterval(polling);
                            }
                        });
                };
                const polling = setInterval(checkPaymentStatus, 5000);
            </script>
        @endif
        <section class="contact section" id="contact">
            <div class="container section-title" data-aos="fade-up">
                <h2>Contact</h2>
                <div><span>Let's</span> <span class="description-title">Connect</span></div>
            </div><div class="container" data-aos="fade-up" data-aos-delay="100">
                <div class="row gy-4 mb-5">
                    <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="contact-info-box">
                            <div class="icon-box">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div class="info-content">
                                <h4>Our Address</h4>
                                <p>Jl.Mattoanging, Desa Lassang Barat, Kec. Polongbangkeng Utara, Kab.Takalar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="contact-info-box">
                            <div class="icon-box">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div class="info-content">
                                <h4>Email Address</h4>
                                <p>admin@sairjayamandiri.com</p>
                                <p>085242755807</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="contact-info-box">
                            <div class="icon-box">
                                <i class="bi bi-headset"></i>
                            </div>
                            <div class="info-content">
                                <h4>Hours of Operation</h4>
                                <p>Sunday-Fri: 9 AM - 6 PM</p>
                                <p>Saturday: 9 AM - 4 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="map-section" data-aos="fade-up" data-aos-delay="200">
               <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3973.453564551408!2d119.46832407523111!3d-5.191159694786379!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dbee300460f70f1%3A0x661b686d91c7735e!2sPT%20SAIR%20JAYA%20MANDIRI!5e0!3m2!1sid!2sid!4v1754561211883!5m2!1sid!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <div class="container form-container-overlap register" id="register">
                <div class="row justify-content-center" data-aos="fade-up" data-aos-delay="300">
                    <div class="col-lg-10">
                        <div class="contact-form-wrapper">
                            <h2 class="text-center mb-4">Register</h2>
                            @if(session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                            {{-- Tampilkan error validasi --}}
                            @if($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form id="pre-register-form" action="{{ route('pre-register.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    {{-- Nama --}}
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="input-with-icon">
                                                <i class="bi bi-person"></i>
                                                <input class="form-control" name="name" placeholder="Nama Sesuai KTP" type="text" required>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Email --}}
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="input-with-icon">
                                                <i class="bi bi-envelope"></i>
                                                <input class="form-control" name="email" placeholder="Email Address" type="email" required>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Nomor WA --}}
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="input-with-icon">
                                                <i class="bi bi-telephone"></i>
                                                <input class="form-control" name="phone" placeholder="No WhatsApp Aktif" type="text" required>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Sponsor --}}
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="input-with-icon">
                                                <i class="bi bi-people"></i>
                                                <input class="form-control" name="sponsor_id" placeholder="Kode Sponsor" type="text" required>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Metode Pembayaran --}}
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label text-white">Metode Pembayaran</label>
                                            <div class="d-flex flex-column flex-md-row gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="method_rekening" value="rekening" required>
                                                    <label class="form-check-label text-white" for="method_rekening">Transfer ke Rekening</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Nomor Rekening --}}
                                    <div class="col-12" id="rekening-info" style="display: none;">
                                        <div class="bg-dark p-3 rounded border border-warning text-white">
                                            <p class="mb-2 fw-bold text-warning">Transfer ke salah satu rekening:</p>
                                            <ul class="mb-0">
                                                <li>Mandiri: 1740011176609 a.n. PT Sair Jaya Mandiri</li>
                                            </ul>
                                        </div>
                                        <div class="col-12" >
                                            <div class="form-group">
                                                <label for="payment_proof" class="form-label text-white">Upload Bukti Pembayaran</label>
                                                <div class="drop-zone" id="drop-zone-area" onclick="document.getElementById('payment_proof').click()">
												<span class="drop-zone-text" id="drop-zone-label">Klik atau drag file ke sini</span>
												<input type="file" name="payment_proof" id="payment_proof" class="drop-zone-input" required accept="image/jpeg, image/png">
											</div>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- QRIS --}}
                                    <div class="col-12 text-center" id="qris-info" style="display: none;"></div>
                                    {{-- Submit --}}
                                    <div class="col-12 text-center">
                                        <button class="btn btn-warning btn-submit" id="submit-btn" type="submit">Kirim Pendaftaran</button>
                                    </div>
                                    <div class="col-12">
                                        <div id="form-result" class="text-center mt-3 text-white fw-bold"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="qrisModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-white border border-warning">
                        <div class="modal-header">
                            <h5 class="modal-title">Pembayaran via QRIS</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <p>Silakan scan QR Code berikut:</p>
                            <img id="qr-image" src="" alt="QRIS" class="img-fluid border border-warning rounded" style="max-width: 250px;">
                            <p class="mt-3" id="countdown">Waktu tersisa: <strong>10:00</strong></p>
                            <p id="waiting-text" class="text-warning">Menunggu pembayaran...</p>
                        </div>
                        <a href="#" id="simulate-link" target="_blank" class="btn btn-sm btn-outline-light mt-3 d-none">
                            🔁 Simulasikan Pembayaran (Sandbox)
                        </a>
                    </div>
                </div>
            </div>
            <div id="order-id" data-order="" style="display:none;"></div>
        </section>
    </main>
    <footer class="footer" id="footer">
        <div class="container footer-top">
            <div class="row gy-4">
                <div class="col-lg-2 col-6 footer-links">
                    <h4>Contact Us</h4>
                    <ul>
                        <li><a href="#">Jl. Bontotangnga</a></li>
                        <li><a href="#">Paccinongan</a></li>
                        <li><a href="#">Kec. Somba Opu</a></li>
                        <li><a href="#">Kab.Gowa</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="container copyright text-center mt-4">
            <p>© <span>Copyright 2025</span> <strong class="px-1 sitename">Sair Jaya Mandiri</strong> <span>All Rights Reserved</span></p>
            <div class="credits">Designed by <a href="#">Art Media</a></div>
        </div>
    </footer>
    <a class="scroll-top d-flex align-items-center justify-content-center" href="#" id="scroll-top"><i class="bi bi-arrow-up-short"></i></a>
    <div id="preloader"></div>
    <script src="{{ asset('front/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('front/assets/vendor/php-email-form/validate.js') }}"></script>
    <script src="{{ asset('front/assets/vendor/aos/aos.js') }}"></script>
    <script src="{{ asset('front/assets/vendor/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('front/assets/vendor/glightbox/js/glightbox.min.js') }}"></script>
    <script src="{{ asset('front/assets/vendor/imagesloaded/imagesloaded.pkgd.min.js') }}"></script>
    <script src="{{ asset('front/assets/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>
    <script src="{{ asset('front/assets/js/main.js') }}"></script>
    <div id="order-id" data-order="" style="display:none;"></div>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const form = document.getElementById('pre-register-form');
        const submitBtn = document.getElementById('submit-btn');
        const resultDiv = document.getElementById('form-result');
        const input = document.getElementById('payment_proof');
        const label = document.getElementById('drop-zone-label');
        const dropzoneArea = document.getElementById('drop-zone-area');
        const orderIdContainer = document.getElementById('order-id');
        let polling = null;

        // --- Event Listener untuk Validasi File di Awal ---
        input.addEventListener('change', function () {
            const file = this.files[0];

            // Hapus highlight dan pesan error sebelumnya
            dropzoneArea.classList.remove('is-invalid');
            const oldError = dropzoneArea.nextElementSibling;
            if (oldError && oldError.classList.contains('invalid-feedback')) {
                oldError.remove();
            }

            if (file) {
                // Cek jenis file (hanya jpg dan png)
                const allowedTypes = ['image/jpeg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    toastr.error("File yang diunggah harus berformat JPG atau PNG.");
                    this.value = ''; // Hapus file yang salah dari input
                    label.textContent = 'Klik atau drag file ke sini'; // Kembalikan teks default
                    dropzoneArea.classList.add('is-invalid'); // Highlight dropzone
                    return;
                }

                // Cek ukuran file (maksimal 500KB)
                const maxSize = 500 * 1024; // 500 KB dalam bytes
                if (file.size > maxSize) {
                    toastr.error("Ukuran file tidak boleh melebihi 500KB.");
                    this.value = ''; // Hapus file yang salah dari input
                    label.textContent = 'Klik atau drag file ke sini'; // Kembalikan teks default
                    dropzoneArea.classList.add('is-invalid'); // Highlight dropzone
                    return;
                }

                // Jika validasi berhasil, tampilkan nama file
                label.textContent = file.name;
            }
        });

        // --- Logika saat Form di-submit (Hanya Cek Required) ---
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Hapus semua sorotan error dan pesan yang ada
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            const formData = new FormData(form);
            submitBtn.disabled = true;
            resultDiv.innerHTML = "⏳ Memproses pendaftaran...";

            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            const paymentProofInput = document.getElementById('payment_proof');

            // Validasi wajib isi untuk bukti pembayaran (saat metode rekening dipilih)
            if (paymentMethod && paymentMethod.value === 'rekening' && paymentProofInput.files.length === 0) {
                toastr.error("Harap unggah bukti pembayaran.");
                dropzoneArea.classList.add('is-invalid');
                submitBtn.disabled = false;
                return;
            }

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw errorData;
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log("🚀 Response data:", data);
                if (data.redirect) {
                    const url = new URL(data.redirect);
                    const qrUrl = url.searchParams.get('qrUrl');
                    const orderId = url.searchParams.get('orderId');
                    if (qrUrl && orderId) {
                        orderIdContainer.dataset.order = orderId;
                        showQrisModal(qrUrl);
                        startPolling(orderId);
                    } else {
                        throw new Error("QRIS URL atau Order ID tidak ditemukan.");
                    }
                } else if (data.success) {
                    toastr.success(data.message);
                    resultDiv.innerHTML = "✅ " + data.message;
                    // Reset form
                    form.reset();
                    label.textContent = 'Klik atau drag file ke sini';
                    document.getElementById('rekening-info').style.display = 'none';
                    document.getElementById('qris-info').style.display = 'none';
                    document.getElementById('payment_proof').required = false;
                } else {
                    toastr.error("❌ Terjadi kesalahan.");
                    resultDiv.innerHTML = "❌ Terjadi kesalahan.";
                }
            })
            .catch(error => {
                console.error("❌ Error:", error);
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
                if (error.errors) {
                    for (const fieldName in error.errors) {
                        const inputElement = document.querySelector(`[name="${fieldName}"]`);
                        if (inputElement) {
                            inputElement.classList.add('is-invalid');
                            const errorDiv = document.createElement('div');
                            errorDiv.classList.add('invalid-feedback');
                            errorDiv.textContent = error.errors[fieldName][0];
                            inputElement.parentNode.appendChild(errorDiv);
                        }
                    }
                    toastr.error("❌ " + (error.message || "Terjadi kesalahan. Silakan periksa kembali formulir Anda."));
                    resultDiv.innerHTML = "❌ " + (error.message || "Terjadi kesalahan.");
                } else {
                    toastr.error("❌ " + (error.message || "Terjadi kesalahan."));
                    resultDiv.innerHTML = "❌ " + (error.message || "Terjadi kesalahan.");
                }
            })
            .finally(() => {
                submitBtn.disabled = false;
            });
        });

        const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
        const rekeningInfo = document.getElementById('rekening-info');
        const qrisInfo = document.getElementById('qris-info');
        paymentRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                if (this.value === 'rekening') {
                    rekeningInfo.style.display = 'block';
                    qrisInfo.style.display = 'none';
                    input.required = true;
                } else {
                    rekeningInfo.style.display = 'none';
                    qrisInfo.style.display = 'block';
                    input.required = false;
                }
            });
        });

        function showQrisModal(qrUrl) {
            const qrImage = document.getElementById('qr-image');
            const simulateBtn = document.getElementById('simulate-link');
            qrImage.src = qrUrl;
            simulateBtn.href = qrUrl;
            simulateBtn.classList.remove('d-none');
            const modal = new bootstrap.Modal(document.getElementById('qrisModal'));
            modal.show();
            let time = 600;
            const countdown = document.getElementById('countdown');
            const interval = setInterval(() => {
                if (time <= 0) {
                    clearInterval(interval);
                    countdown.innerHTML = "<span class='text-danger fw-bold'>QR telah kadaluarsa</span>";
                } else {
                    let m = String(Math.floor(time / 60)).padStart(2, '0');
                    let s = String(time % 60).padStart(2, '0');
                    countdown.innerHTML = `Waktu tersisa: <strong>${m}:${s}</strong>`;
                    time--;
                }
            }, 1000);
        }

        function startPolling(orderId) {
            if (polling) clearInterval(polling);
            polling = setInterval(() => {
                fetch(`/check-payment-status/${orderId}`)
                    .then(response => {
                        if (!response.ok) throw new Error("Gagal cek status");
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'paid') {
                            clearInterval(polling);
                            const modalElement = document.getElementById('qrisModal');
                            const modalInstance = bootstrap.Modal.getInstance(modalElement);
                            if (modalInstance) modalInstance.hide();
                            toastr.success("✅ Pembayaran berhasil!");
                            form.reset();
                            label.textContent = 'Klik atau drag file ke sini';
                            document.getElementById('rekening-info').style.display = 'none';
                            document.getElementById('qris-info').style.display = 'none';
                            document.getElementById('payment_proof').required = true;
                            document.getElementById('form-result').innerHTML = "✅ Pendaftaran berhasil. Silakan cek email Anda.";
                        } else {
                            console.log("ℹ️ Status:", data.status);
                        }
                    })
                    .catch(err => console.warn("❌ Error saat polling:", err));
            }, 8000);
        }
    });
    </script>
</body>
</html>
