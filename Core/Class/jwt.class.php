<?php

defined("DEBUG") or die("拒绝访问。");

class Jwt
{
    private static $privateKey = 'file://' . PATH_CONFIG . '/private_key.pem';  // 私钥文件路径
    private static $publicKey = 'file://' . PATH_CONFIG . '/public_key.pem';  // 公钥文件路径
    private static $algorithm = 'RS512';  // 加密算法

    /**
     * 生成 JWT
     *
     * @param array $payload
     * @return string
     */
    public static function generateToken(array $payload): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        $base64UrlHeader = self::base64UrlEncode($header);

        // 设置 Token 的有效时间，这里设为 3 小时
        $payload['iat'] = time();
        $payload['exp'] = time() + 60 * 60 * 3;
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = self::sign($base64UrlHeader, $base64UrlPayload);

        $result = $base64UrlHeader . "." . $base64UrlPayload . "." . $signature;

        $newJwtData = [
            'Name' => $payload['name'],
            'Permission' => $payload['permission'],
            'Phone' => $payload['phone'],
            'Token' => $result,
            'Expire' => date("Y-m-d H:i:s", $payload['exp']),
        ];

        if (JwtData->exist(['Phone' => $payload['phone']])) {
            JwtData->update($newJwtData, ['Phone' => $payload['phone']]);
        } else {
            JwtData->write($newJwtData);
        }

        return $result;
    }

    /**
     * 更新 JWT
     *
     * @param string $token
     * @return string|bool
     */
    public static function refreshToken(string $token): string|bool
    {
        $payload = self::verifyToken($token);
        if (!$payload) {
            return false;
        }

        // 生成新的 Token 并更新数据库
        return self::generateToken($payload);
    }

    /**
     * Base64URL 编码
     *
     * @param string $data
     * @return string
     */
    private static function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * 生成签名
     *
     * @param string $header
     * @param string $payload
     * @return string
     */
    private static function sign(string $header, string $payload): string
    {
        $data = $header . "." . $payload;
        $privateKey = openssl_pkey_get_private(self::$privateKey);
        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA512);
        return self::base64UrlEncode($signature);
    }

    /**
     * 验证 JWT
     *
     * @param string $token
     * @return array|bool
     */
    public static function verifyToken(string $token): array|bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        if (!JwtData->exist(['Token' => $token])) {
            return false;
        }

        [$base64UrlHeader, $base64UrlPayload, $signatureProvided] = $parts;

        // 验证签名
        $data = $base64UrlHeader . "." . $base64UrlPayload;
        if (!self::verify($data, $signatureProvided)) {
            return false;
        }

        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);

        // 验证过期时间
        if ($payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    /**
     * 验证签名
     *
     * @param string $data
     * @param string $signature
     * @return bool
     */
    private static function verify(string $data, string $signature): bool
    {
        $publicKey = openssl_pkey_get_public(self::$publicKey);
        $signature = self::base64UrlDecode($signature);
        $result = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA512);
        return $result === 1;
    }

    /**
     * Base64URL 解码
     *
     * @param string $data
     * @return string
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
