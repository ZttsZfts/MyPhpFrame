<?php

defined("DEBUG") or die("拒绝访问。");

class ApiData extends Table
{
    public array $Field = [
        ["Type", "VARCHAR", "(100)"], //类型
        ["Label", "VARCHAR", "(100)"],//请求地址
        ["Clazz", "VARCHAR", "(100)"],//调用类
        ["Method", "VARCHAR", "(100)"],//调用方法
        ["Permissions", "INT", ""],//权限
        ["Enable", "TINYINT", "(1)"],//是否启用
        ["Remark", "VARCHAR", "(120)"],//备注
    ];
    protected string $TABLE_NAME = "Api";

    public function __construct()
    {
        parent::__construct();
        //若数据库中没有数据，则加入预设数据
        $presets = [
            ["Type" => "Api", "Label" => "version", "Clazz" => "Version", "Method" => "GetVersion", "Permissions" => 0, "Enable" => 1, "Remark" => "获取服务端版本"],
            ["Type" => "Api", "Label" => "time", "Clazz" => "Version", "Method" => "GetTime", "Permissions" => 0, "Enable" => 1, "Remark" => "当前时间"],
            ["Type" => "Api", "Label" => "timestamp", "Clazz" => "Version", "Method" => "GetTimestamp", "Permissions" => 0, "Enable" => 1, "Remark" => "当前时间戳"],
            ["Type" => "Api", "Label" => "register", "Clazz" => "User", "Method" => "VerificationRegister", "Permissions" => 0, "Enable" => 1, "Remark" => "注册账号"],
            ["Type" => "Api", "Label" => "login", "Clazz" => "User", "Method" => "VerificationLogin", "Permissions" => 0, "Enable" => 1, "Remark" => "登录账号"],
            ["Type" => "Api", "Label" => "forgot", "Clazz" => "User", "Method" => "VerificationForgot", "Permissions" => 0, "Enable" => 1, "Remark" => "找回密码"],
            ["Type" => "Api", "Label" => "logout", "Clazz" => "User", "Method" => "Logout", "Permissions" => 0, "Enable" => 1, "Remark" => "登出账号"],

            ];
        if (!$this->exist()) {
            //将preset数据写入数据库
            foreach ($presets as $preset) {
                $this->write($preset);
            }
        }


    }
}
