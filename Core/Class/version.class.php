<?php

defined("DEBUG") or die("拒绝访问。");

class Version
{
    public static string $version = "1.0.1.0612";

    public static function GetVersion(): false|string
    {
        return json_encode(new standardOutput(1, self::$version));
    }

    public static function GetTime(): string
    {
        return json_encode(new standardOutput(1, date('Y-m-d H:i:s')));
    }

    public static function GetTimestamp(): string
    {
        return json_encode(new standardOutput(1, "" . time()));
    }
}