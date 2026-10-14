<?php

/**
 * Apakah admin yang sedang login punya hak akses ini?
 * Owner selalu lolos.
 */
function admin_can(string $code): bool
{
    $session = session();

    if (!$session->get('admin_logged_in')) {
        return false;
    }

    if ($session->get('admin_role') === 'owner') {
        return true;
    }

    return in_array($code, (array) $session->get('admin_permissions'), true);
}