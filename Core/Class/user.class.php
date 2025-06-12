<?php

defined("DEBUG") or die("拒绝访问。");

class User extends Http
{
    public static function Logout(): string
    {
        $token = [];
        if (!self::VerifyToken($token)) {
            return json_encode(new standardOutput(-1, "token验证失败"));
        }

        $phone = $token["phone"];

        // 从数据库或内存中删除会话信息
        if (!JwtData->delete(["Phone" => $phone])) {
            return json_encode(new standardOutput(-1, "注销失败，请重试"));
        }

        // 清除客户端的 Cookie
        setcookie("token", "", time() - 60 * 60 * 3, "/");

        return json_encode(new standardOutput(1, "注销成功", obj: ['type' => LogType::Login, 'content' => '注销成功', 'user' => $phone]));
    }


    public static function VerificationLogin(): string
    {
        $dataJson = self::getInput("login");

        if (!$dataJson) {
            return json_encode(new standardOutput(-1, "账号或密码输入有误"));
        }

        $phone = $dataJson["phone"];
        $password = $dataJson["password"];

        $token = "";
        $login = self::Login($phone, $password, $token);

        switch ($login) {
            case 1:
                setcookie("token", $token, time() + 60 * 60 * 3, "/");
                return json_encode(new standardOutput(1, "登录成功", $token, obj: ['type' => LogType::Login, 'content' => ' 登录成功', 'user' => $phone]));
            case -1:
                return json_encode(new standardOutput(-1, "账号未注册", obj: ['type' => LogType::Login, 'content' => ' 账号未注册', 'user' => $phone]));
            case -2:
                return json_encode(new standardOutput(-1, "账号或密码错误", obj: ['type' => LogType::Login, 'content' => ' 账号或密码错误', 'user' => $phone]));
            case -3:
                return json_encode(new standardOutput(-1, "账号未启用", obj: ['type' => LogType::Login, 'content' => ' 账号未启用', 'user' => $phone]));
            case -4:
                return json_encode(new standardOutput(-1, "表数据更新失败", obj: ['type' => LogType::Login, 'content' => ' 表数据更新失败', 'user' => $phone]));
            default:
                return json_encode(new standardOutput(-1, "未知错误", obj: ['type' => LogType::Login, 'content' => ' 未知错误', 'user' => $phone]));
        }
    }

    /**
     * @param $phone
     * @param $password
     * @param $token
     * @return int 1:成功 -1:不存在 -2:密码错误 -3:账号未启用 -4:表写入失败
     */
    private static function Login($phone, $password, &$token): int
    {
        $conditions = ['Phone' => $phone];
        $exists = UserData->exist($conditions);
        if (!$exists) return -1;

        $password = openssl_digest($password, 'sha256');
        $salt = UserData->find(['Salt'], ['Phone' => $phone])[0]['Salt'];
        $salt = hex2bin($salt);
        $password = openssl_digest($password . $salt, 'sha256');

        $result = UserData->find(['Name', 'IsActive', 'Login', 'Permission'], ['Phone' => $phone, 'Password' => $password]);

        if (empty($result)) {
            return -2;
        } elseif ($result[0]['IsActive'] === 0) {
            return -3;
        } else {
            $update = UserData->update(
                [
                    'LastLogDate' => date('Y-m-d H:i:s'),
                    'IPAddress' => getClientIP(),
                    'Login' => $result[0]['Login'] + 1
                ],
                ['Phone' => $phone]
            );
            if ($update === false) {
                return -4;
            }

            $permission = $result[0]['Permission'];
            $name = $result[0]['Name'];

            $token = Jwt::generateToken(['name' => $name, 'phone' => $phone, 'permission' => $permission]);

            return 1;
        }

    }

    public static function VerificationRegister(): string
    {
        $dataJson = self::getInput("register");

        if (!$dataJson) {
            return json_encode(new standardOutput(-1, "信息填写有误，请检查是否填写正确"));
        }

        $nickname = $dataJson["nickname"];
        $phone = $dataJson["phone"];
        $password = $dataJson["password"];
        $confirm = $dataJson["confirm"];

        if ($password !== $confirm) {
            return json_encode(new standardOutput(-1, "密码不一致"));
        }

        $register = self::Register($nickname, $password, $phone);

        return match ($register) {
            1 => json_encode(new standardOutput(1, "注册成功", obj: ['type' => LogType::Register, 'content' => ' 注册成功', 'user' => $phone])),
            -1 => json_encode(new standardOutput(-1, "手机号已被注册", obj: ['type' => LogType::Register, 'content' => ' 手机号已被注册', 'user' => $phone])),
            default => json_encode(new standardOutput(-1, "未知错误", obj: ['type' => LogType::Error, 'content' => ' 未知错误', 'user' => $phone])),
        };

    }

    /**
     * 注册
     *
     * @param $nickname
     * @param $password
     * @param $phone
     * @param $type
     * @return int 1:注册成功，-1:手机号已被注册，0:未知错误
     */
    private static function Register($nickname, $password, $phone): int
    {

        $exists = UserData->find(['Name'], ['phone' => $phone]);
        if ($exists) return -1;


        //使用openssl加密密码，盐长度64
        $password = openssl_digest($password, 'sha256');
        $salt = openssl_random_pseudo_bytes(64);
        $password = openssl_digest($password . $salt, 'sha256');

        $newUser = [
            'Name' => $nickname,
            'StuId' => $phone,
            'Phone' => $phone,
            'Email' => '',
            'Password' => $password,
            'Salt' => bin2hex($salt),
            'Permission' => 0,
            'ErrorNumber' => 0,
            'GetCodeTime' => date('Y-m-d H:i:s'),
            'IsEmailVerified' => 0,
            'IsActive' => 0,
            'RegDate' => date('Y-m-d H:i:s'),
            'LastLogDate' => date('Y-m-d H:i:s'),
            'IPAddress' => getClientIP(),
            'Login' => 0,
        ];

        if (UserData->write($newUser)) {
            return 1;
        }

        return 0;
    }

    public static function VerificationForgot(): string
    {
        $dataJson = self::getInput("forgot");

        if (!$dataJson) {
            return json_encode(new standardOutput(-1, "账号或密码输入有误，请检查"));
        }

        $phone = $dataJson["phone"];
        $password = $dataJson["password"];
        $confirm = $dataJson["confirm"];

        if ($password !== $confirm) {
            return json_encode(new standardOutput(-1, "密码不一致"));
        }


        if (self::Forget($phone, $password)) {
            return json_encode(new standardOutput(1, "修改成功", obj: ['type' => LogType::Forget, 'content' => '修改密码成功', 'user' => $phone]));
        }

        return json_encode(new standardOutput(-1, "修改失败，请重试", obj: ['type' => LogType::Error, 'content' => '修改失败，请重试', 'user' => $phone]));
    }

    private static function Forget($phone, $password): bool
    {
        $exists = UserData->find(["Name"], ['phone' => $phone]);
        if (!$exists) return false;

        //使用openssl加密密码，盐长度64
        $password = openssl_digest($password, 'sha256');
        $salt = openssl_random_pseudo_bytes(64);
        $password = openssl_digest($password . $salt, 'sha256');

        $newPass = [
            'Password' => $password,
            'Salt' => bin2hex($salt),
        ];

        if (UserData->update($newPass, ['phone' => $phone])) {
            return true;
        }

        return false;
    }

}