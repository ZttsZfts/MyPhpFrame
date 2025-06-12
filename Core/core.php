<?php

defined("DEBUG") or define("DEBUG", true);

ini_set('memory_limit', '512M');

// 使用相对路径定义项目路径常量，提高可移植性
defined("PATH_ROOT") or define("PATH_ROOT", dirname(__DIR__, 1));
defined("PATH_CORE") or define("PATH_CORE", PATH_ROOT . '/Core');
defined("PATH_CLASS") or define("PATH_CLASS", PATH_CORE . '/Class');
defined("PATH_CONFIG") or define("PATH_CONFIG", PATH_ROOT . '/Config');
defined("PATH_PUBLIC") or define("PATH_PUBLIC", PATH_ROOT . '/Public');

// 根据DEBUG模式设置错误报告级别
error_reporting(DEBUG ? E_ALL : E_ERROR);

// 设置时区
date_default_timezone_set("PRC");

// 加载配置文件，增加异常处理
try {
    $_SERVER['CONF']['data'] = include PATH_CONFIG . "/data.conf.php";
} catch (Exception $e) {
    error_log("Failed to load configuration: " . $e->getMessage());
}

// 使用spl_autoload_register实现自动加载，提高性能和可维护性
spl_autoload_register(function ($className) {
    if (preg_match('/^data$/i', $className)) {
        $result = 'data.class.php';
    } elseif (preg_match('/data/i', $className)) {
        $result = preg_replace('/data/i', '', $className);
        $result = strtolower($result) . '.data.class.php';
    } else {
        $result = strtolower($className) . '.class.php';
    }
    $fileName = PATH_CLASS . DIRECTORY_SEPARATOR . $result;
    if (file_exists($fileName)) {
        include $fileName;
    }
});

// 初始化核心功能和自定义数据类型
include PATH_CORE . "/core.func.php";
include PATH_CORE . "/Struct/standardOutput.php";
include PATH_CORE . "/Enum/LogType.php";

// 初始化核心类库和数据
$_SERVER['Class_Data'] = Data::GetInstance();

const LogData = new LogData();//优先载入
const UserData = new UserData();
const JwtData = new JwtData();
const ApiData = new ApiData();