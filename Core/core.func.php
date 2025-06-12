<?php


defined("DEBUG") or die("拒绝访问。");


/**
 * 加密密码
 * @param string $password 密码原文
 * @param string $salt 盐
 * @return string
 */
function GenerateHashPassword(string $password, string $salt): string
{
    // 建议使用password_hash进行密码加密，但根据要求保持内部字符串不变，故此处不做修改
    return md5(sha1($password) . $salt);
}

/**
 * 获取客户端IP地址
 * @return string
 */
function getClientIP(): string
{
    if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $ip = getenv('REMOTE_ADDR');
    } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return preg_match('/[\d.]{7,15}/', $ip, $matches) ? $matches[0] : '';
}

/**
 * 发送HTTP请求
 * @param string $url 请求URL
 * @param array|string $data 请求数据
 * @param string $type 请求类型
 * @param array $header 请求头
 * @return false|string
 */
function sendHttp(string $url, array|string $data = [], string $type = 'GET', array $header = ["Content-type:application/json;charset='utf-8'"]): false|string
{
    $ch = curl_init();

    //跳过ssl验证
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("CURL error: {$error}");
        return false;
    }
    return $response;
}

/**
 * 生成UUID V5版本
 * @param string $name_space 命名空间
 * @param string $string 字符串
 * @return string
 */
function uuidV5(string $name_space, string $string): string
{
    $n_hex = str_replace(array('-', '{', '}'), '', $name_space);
    $binary_str = '';
    for ($i = 0; $i < strlen($n_hex); $i += 2) {
        $binary_str .= chr(hexdec($n_hex[$i] . $n_hex[$i + 1]));
    }
    $hashing = sha1($binary_str . $string);

    return sprintf('%08s-%04s-%04x-%04x-%12s',
        substr($hashing, 0, 8),
        substr($hashing, 8, 4),
        (hexdec(substr($hashing, 12, 4)) & 0x0fff) | 0x5000,
        (hexdec(substr($hashing, 16, 4)) & 0x3fff) | 0x8000,
        substr($hashing, 20, 12)
    );
}

/**
 * 生成随机字符串
 * @param int $length 字符串长度
 * @return string
 */
function getRandomString(int $length): string
{
    $bytes = openssl_random_pseudo_bytes($length);
    return bin2hex($bytes);
}

/**
 * 获取当前日期时间
 * @return string
 */
function getCurrentDate(): string
{
    $current_timestamp = time();
    return date("Y-m-d H:i:s", $current_timestamp);
}

/**
 * 生成随机数字串
 * @param int $count 数字个数
 * @return string
 */
function getRandomNumbers(int $count = 6): string
{
    $numbers = [];
    for ($i = 0; $i < $count; $i++) {
        $numbers[] = mt_rand(0, 9);
    }
    shuffle($numbers);
    return implode('', $numbers);
}