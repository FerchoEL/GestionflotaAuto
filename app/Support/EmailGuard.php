<?php

namespace App\Support;

final class EmailGuard
{
    /**
     * Dominios reservados por RFC 2606 / 6761 (no se debe enviar correo real).
     */
    private const RESERVED_DOMAINS = [
        'example.com',
        'example.net',
        'example.org',
        'test',
        'test.com',
        'invalid',
        'invalid.com',
        'localhost',
        'localhost.localdomain',
    ];

    /**
     * Valida email y filtra dominios reservados.
     * Si se define allowlist (env ALERT_MAIL_ALLOWED_DOMAINS), solo permite esos dominios.
     */
    public static function canSend(?string $email): bool
    {
        $email = trim((string) $email);

        if ($email === '') {
            return false;
        }

        // Validación de formato
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = strtolower((string) substr(strrchr($email, '@'), 1));

        if ($domain === '' || $domain === false) {
            return false;
        }

        // Bloquear dominios reservados
        if (in_array($domain, self::RESERVED_DOMAINS, true)) {
            return false;
        }

        // Bloquear cualquier subdominio de example.com/net/org por si acaso
        if (preg_match('/(^|\.)example\.(com|net|org)$/i', $domain)) {
            return false;
        }

        // Allowlist opcional
        $allowed = config('alerts.mail_allowed_domains');
        if (is_array($allowed) && count($allowed) > 0) {
            return in_array($domain, $allowed, true);
        }

        return true;
    }
}