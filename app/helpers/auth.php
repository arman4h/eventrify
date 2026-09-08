<?php

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function currentUserType(): ?string
{
    return $_SESSION['user_type'] ?? null;
}

function currentUserRole(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

function isStudent(): bool
{
    return isLoggedIn() && currentUserType() === 'student';
}

function isClubUser(): bool
{
    return isLoggedIn() && currentUserType() === 'club_user';
}

function isSystemAdmin(): bool
{
    return isLoggedIn() && currentUserType() === 'system_admin';
}

function isClubOwner(): bool
{
    return isClubUser() && currentUserRole() === 'owner';
}

function isClubAdminRole(): bool
{
    return isClubUser() && currentUserRole() === 'admin';
}

function isExecutive(): bool
{
    return isClubUser() && currentUserRole() === 'executive';
}

function loginStudent(array $student): void
{
    $_SESSION['user_id']   = (int) $student['student_id'];
    $_SESSION['user_type'] = 'student';
    $_SESSION['user'] = [
        'id'            => (int) $student['student_id'],
        'university_id' => $student['university_id'],
        'name'          => $student['full_name'],
        'email'         => $student['email'],
        'department'    => $student['department'],
        'batch'         => $student['batch'],
    ];
}

function loginClubUser(array $clubUser): void
{
    $_SESSION['user_id']   = (int) $clubUser['club_user_id'];
    $_SESSION['user_type'] = 'club_user';
    $_SESSION['user'] = [
        'id'       => (int) $clubUser['club_user_id'],
        'club_id'  => (int) $clubUser['club_id'],
        'name'     => $clubUser['full_name'],
        'email'    => $clubUser['email'],
        'role'     => $clubUser['role'],
    ];
}

function loginSystemAdmin(array $admin): void
{
    $_SESSION['user_id']   = (int) $admin['admin_id'];
    $_SESSION['user_type'] = 'system_admin';
    $_SESSION['user'] = [
        'id'    => (int) $admin['admin_id'],
        'name'  => $admin['full_name'],
        'email' => $admin['email'],
    ];
}
