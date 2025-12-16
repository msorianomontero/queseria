<?php
// $page comes from index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheese Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<header class="topbar">
    <div class="brand">🧀 Cheese Manager</div>
    <nav class="nav">
        <a href="index.php?page=dashboard" class="<?= ($page === 'dashboard') ? 'active' : '' ?>">Stock</a>
        <a href="index.php?page=cheeses" class="<?= ($page === 'cheeses') ? 'active' : '' ?>">Cheeses</a>
        <a href="index.php?page=deliveries" class="<?= ($page === 'deliveries') ? 'active' : '' ?>">Deliveries</a>
        <a href="index.php?page=sales" class="<?= ($page === 'sales') ? 'active' : '' ?>">Sales</a>
    </nav>
</header>
<main class="main">
