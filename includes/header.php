<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

// optional: set page title dari halaman (contoh: $pageTitle = 'Produk';)
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle).' - PIS' : 'PIS'; ?></title>

  <!-- Bootstrap CSS (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Custom CSS (muat setelah Bootstrap supaya overrides bekerja) -->
  <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/index.php">PIS</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="/index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="/pages/produk/index.php">Produk</a></li>
        <li class="nav-item"><a class="nav-link" href="/pages/pembelian/index.php">Pembelian</a></li>
        <li class="nav-item"><a class="nav-link" href="/pages/penjualan/index.php">Penjualan</a></li>
        <li class="nav-item"><a class="nav-link" href="/pages/supplier/index.php">Supplier</a></li>
        <li class="nav-item"><a class="nav-link" href="/pages/laporan/index.php">Laporan</a></li>
        <li class="nav-item"><a class="nav-link" href="/pages/customer/index.php">Customer</a></li>
        <?php if (isset($_SESSION['user'])): ?>
          <li class="nav-item"><a class="nav-link" href="/logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- main content wrapper -->
<main class="container py-4">
