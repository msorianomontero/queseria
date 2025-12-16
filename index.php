<?php
require 'db.php';

$page = $_GET['page'] ?? 'dashboard';
include 'header.php';

switch ($page) {
    case 'cheeses':
        include 'pages/cheeses.php';
        break;
    case 'deliveries':
        include 'pages/deliveries.php';
        break;
    case 'sales':
        include 'pages/sales.php';
        break;
    default:
        include 'pages/dashboard.php';
}

include 'footer.php';
?>