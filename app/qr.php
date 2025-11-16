<?php
// app/qr.php
// Given an otpauth URI, it outputs an inline data-URI PNG using Google Chart QR generator
// NOTE: Google Charts QR API is an external call via image src; here we simply return the otpauth URI or an <img> tag.
// For privacy you may prefer to generate QR server-side with a PHP QR lib.

function otpauth_qr_img_tag($otpauth_uri, $size = 200)
{
    $u = rawurlencode($otpauth_uri);



    $u = rawurlencode($otpauth_uri);
    $src = "https://chart.googleapis.com/chart?cht=qr&chs={$size}x{$size}&chl={$u}&chld=M|0";
    return "<img alt='QR code' src='{$src}' width='{$size}' height='{$size}' />";
}