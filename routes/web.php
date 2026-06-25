<?php

// Require Config & App Initialization
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Booking.php';
require_once __DIR__ . '/../app/controllers/BookingController.php';

use App\Controllers\BookingController;

// Simple Routing Mechanism
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Handle Form Submission
if ($method === 'POST' && ($requestUri === '/booking_store.php' || strpos($requestUri, 'booking_store') !== false)) {
    $controller = new BookingController();
    $controller->store();
    exit;
}
