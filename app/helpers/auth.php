<?php

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function currentUserRole(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

function isAdmin(): bool
{
    return currentUserRole() === 'admin';
}

function isClubAdmin(): bool
{
    return currentUserRole() === 'club_admin';
}

function isRegularUser(): bool
{
    return currentUserRole() === 'user';
}
