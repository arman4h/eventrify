<?php

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function redirectBack(): void
{
    $url = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/';
    header('Location: ' . $url);
    exit;
}

function url(string $path = '/'): string
{
    return BASE_URL . $path;
}
