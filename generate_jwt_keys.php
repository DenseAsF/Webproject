<?php
/**
 * Script to generate JWT RSA key pair for LexikJWTAuthenticationBundle
 * Run: php generate_jwt_keys.php
 */

$passphrase = 'hoteldiongco2024';
$jwtDir = __DIR__ . '/config/jwt';

// Create directory if it doesn't exist
if (!is_dir($jwtDir)) {
    mkdir($jwtDir, 0755, true);
    echo "Created directory: $jwtDir\n";
}

// Set OpenSSL config path for Windows
$opensslConfigPath = null;
$possiblePaths = [
    'C:/php/extras/ssl/openssl.cnf',
    'C:/xampp/php/extras/ssl/openssl.cnf',
    'C:/laragon/etc/ssl/openssl.cnf',
    getenv('OPENSSL_CONF'),
];

foreach ($possiblePaths as $path) {
    if ($path && file_exists($path)) {
        $opensslConfigPath = $path;
        break;
    }
}

// Generate private key - try without config first
$config = [
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

if ($opensslConfigPath) {
    $config['config'] = $opensslConfigPath;
}

$privateKey = @openssl_pkey_new($config);

if (!$privateKey) {
    // Try with minimal config
    $privateKey = @openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
}

if (!$privateKey) {
    echo "Error generating private key.\n";
    echo "OpenSSL Error: " . openssl_error_string() . "\n";
    echo "\nPlease run this inside your Docker container instead:\n";
    echo "docker exec symfony_app php bin/console lexik:jwt:generate-keypair\n";
    exit(1);
}

// Export private key with passphrase
$privateKeyPem = '';
if (!@openssl_pkey_export($privateKey, $privateKeyPem, $passphrase)) {
    // Try without passphrase
    if (!@openssl_pkey_export($privateKey, $privateKeyPem)) {
        echo "Error exporting private key: " . openssl_error_string() . "\n";
        exit(1);
    }
    echo "Note: Generated key without passphrase encryption.\n";
    $passphrase = '';
}

// Get public key
$publicKeyDetails = openssl_pkey_get_details($privateKey);
$publicKeyPem = $publicKeyDetails['key'];

// Save keys
$privateKeyPath = $jwtDir . '/private.pem';
$publicKeyPath = $jwtDir . '/public.pem';

file_put_contents($privateKeyPath, $privateKeyPem);
file_put_contents($publicKeyPath, $publicKeyPem);

echo "JWT keys generated successfully!\n";
echo "Private key: $privateKeyPath\n";
echo "Public key: $publicKeyPath\n";
if ($passphrase) {
    echo "Passphrase: $passphrase\n";
} else {
    echo "Passphrase: (none - update .env to remove JWT_PASSPHRASE or set empty)\n";
}
