<?php
namespace App\Helpers;

class EncryptionService
{
    private $STATUS;
    private $AES_KEY;
    private $AES_IV;

    public function __construct()
    {
        $this->STATUS = config('encryption.STATUS');
        $this->AES_KEY = config('encryption.AES_KEY');
        $this->AES_IV = config('encryption.AES_IV');
    }

    public function encrypt($data)
    {
        if($data == null){
            return $data;
        }
        if (!$this->STATUS) {
            return $data;
        }

        $key = base64_decode($this->AES_KEY);
        $iv = base64_decode($this->AES_IV);

        $encrypted = openssl_encrypt(json_encode($data), 'AES-256-CBC', $key, 0, $iv);
        return $encrypted;
    }

    public function decrypt($encryptedData)
    {
        if($encryptedData == null){
            return $encryptedData;
        }
        if (!$this->STATUS) {
            return $encryptedData;
        }

        $key = base64_decode($this->AES_KEY);
        $iv = base64_decode($this->AES_IV);
        $decrypted = openssl_decrypt($encryptedData, 'AES-256-CBC', $key, 0, $iv);


        if (json_last_error() === JSON_ERROR_NONE) {
        $decoded = json_decode($decrypted, true); // true for associative array
        // Check if the decoded result is an array or object
            if (is_array($decoded) || is_object($decoded)) {
                return $decoded;
            }
        }

        if (json_decode($decrypted) !== null || $decrypted === 'null') {
            return json_decode($decrypted);
        } else {
            return $decrypted;
        }
    }

    public static function db_encrypt($string): string
    {
        $iv = substr(md5(self::getKey()), 0, 16);

        $encrypted = openssl_encrypt($string, self::getCipher(), self::getKey(), 0, $iv);

        return base64_encode($encrypted);
    }

    public static function db_decrypt($encryptedString): string
    {
        $iv = substr(md5(self::getKey()), 0, 16);

        $decrypted = openssl_decrypt(base64_decode($encryptedString), self::getCipher(), self::getKey(), 0, $iv);

        return $decrypted;
    }

    public static function getKey()
    {
        return config('laravel_encryption.key', config('app.key'));
    }

    public static function getCipher()
    {
        $cipher = strtolower(config('laravel_encryption.cipher', 'AES-256-CBC'));

        if (! in_array($cipher, openssl_get_cipher_methods())) {
            throw new \Exception('The cipher method "'.$cipher.'" is not supported.');
        }

        return $cipher;
    }

}