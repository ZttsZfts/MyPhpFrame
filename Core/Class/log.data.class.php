<?php

defined("DEBUG") or die("拒绝访问。");

class LogData extends Table
{
    protected string $TABLE_NAME = "Log";
    public array $Field = [
        ["Type", "VARCHAR", "(100)"],// 日志类型
        ["Content", "VARCHAR", "(100)"],// 日志内容
        ["User", "VARCHAR", "(100)"],// 用户名
        ["Time", "TIMESTAMP", ""],// 时间
        ["IP", "VARCHAR", "(100)"],// IP
    ];
}
