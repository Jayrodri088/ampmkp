<?php
// Simple QR generator for Big Church Festival
// Outputs a PNG using api.qrserver.com for the fixed destination URL

$url = 'https://angelmarketplace.org/big-church-festival';
$size = '300x300';
$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=' . urlencode($size) . '&data=' . urlencode($url);

// Prefer proxying the image; if it fails, redirect to provider URL
$img = @file_get_contents($qrApiUrl);

if ($img !== false) {
    header('Content-Type: image/png');
    header('Content-Disposition: inline; filename="angelmarketplace-bcf-qr.png"');
    header('Cache-Control: public, max-age=86400');
    echo $img;
    exit;
}

// Fallback: redirect to QR provider
header('Location: ' . $qrApiUrl, true, 302);
exit;




