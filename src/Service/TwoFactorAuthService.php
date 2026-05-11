<?php

namespace App\Service;

use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Générer une clé secrète TOTP
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Valider un code TOTP
     */
    public function validateCode(string $secret, string $code): bool
    {
        try {
            return (bool) $this->google2fa->verifyKey($secret, $code);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Générer un code QR pour la configuration 2FA via QR Server API
     */
    public function generateQrCode(string $email, string $secret, string $appName = 'NoVas'): string
    {
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $appName,
            $email,
            $secret
        );

        // Utiliser QR Server API (plus simple et plus fiable)
        // Format: https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={encoded_data}
        $encodedUrl = urlencode($qrCodeUrl);
        $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={$encodedUrl}";
        
        return $qrImageUrl;
    }

    /**
     * Générer les codes de secours
     *
     * @return list<string>
     */
    public function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = bin2hex(random_bytes(4));
        }
        return $codes;
    }

    /**
     * Vérifier et consommer un code de secours
     *
     * @param array<int, string> $backupCodes
     * @param-out array<int, string> $backupCodes
     */
    public function validateBackupCode(array &$backupCodes, string $code): bool
    {
        foreach ($backupCodes as $key => $storedCode) {
            if (hash_equals($storedCode, $code)) {
                unset($backupCodes[$key]);
                $backupCodes = array_values($backupCodes);
                return true;
            }
        }
        return false;
    }
}

