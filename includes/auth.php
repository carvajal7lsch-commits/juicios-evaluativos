<?php
// ================================================================
// Autenticación — manejo de sesiones
// ================================================================

session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function getCurrentUser(): array {
    return [
        'id'     => $_SESSION['user_id']    ?? null,
        'nombre' => $_SESSION['user_name']  ?? '',
        'rol'    => $_SESSION['user_rol']   ?? '',
        'email'  => $_SESSION['user_email'] ?? '',
    ];
}

function logout(): void {
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
