<?php

class Http
{
    const REQUIRED_FIELDS = [
        "register" => ["phone", "password", "confirm", /* "sms-code", "vaptchaToken", "vaptchaServer", "vaptchaScene"*/],
        "login" => ["phone", "password",/* "vaptchaToken", "vaptchaServer", "vaptchaScene"*/],
        "forget" => ["phone", /*"sms-code",*/
            "password", "confirm-password",/* "vaptchaToken", "vaptchaServer", "vaptchaScene"*/],
        "smsCode" => ["phone",/* "vaptchaToken", "vaptchaServer", "vaptchaScene"*/],
        "logout" => [],
    ];

    public static function VerifyToken(&$token): bool
    {
        $token = $_COOKIE["token"] ?? "";

        if (!$token) {
            return false;
        }

        // 验证 JWT
        $decodedToken = Jwt::verifyToken($token);

        if (!$decodedToken) {
            return false;
        }

        $token = $decodedToken;

        return true;
    }

    /**
     * 获取输入
     * @param string $type
     * @param array $field
     * @return array|bool 返回输入，或为false
     */
    protected static function getInput(string $type, array $field = []): array|bool
    {
        $data = file_get_contents("php://input");

        $dataJson = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        if (!self::checkInput($type, $dataJson, $field)) {
            return false;
        }
        if (!$dataJson) $dataJson = [];
        return $dataJson;
    }

    /**
     * 检查输入
     * @param string $type
     * @param array $dataJson
     * @param array $field
     * @return bool
     */
    private static function checkInput(string $type, array $dataJson, array $field = []): bool
    {
        if (empty($field)) {
            $required = self::REQUIRED_FIELDS[$type] ?? null;
        } else {
            $required = $field;
        }


        if ($required === null) {
            return false;
        }

        foreach ($required as $key) {
            if (!isset($dataJson[$key]) || !self::validateField($key, $dataJson[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 验证字段
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    private static function validateField(string $key, $value): bool
    {
        return match ($key) {
            "phone" => preg_match("/^1[3456789]\d{9}$/", $value) === 1,
            "email" => preg_match("/^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/", $value) === 1,
            default => true,
        };
    }
}
