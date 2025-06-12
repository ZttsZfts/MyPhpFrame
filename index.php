<?php

defined("DEBUG") or define("DEBUG", true);

define("PATH_ROOT", getcwd());
const PATH_CORE = PATH_ROOT . "/Core";
const PATH_CLASS = PATH_CORE . "/Class";
const PATH_CONFIG = PATH_ROOT . "/Config";
const PATH_PUBLIC = PATH_ROOT . "/Public";

error_reporting(DEBUG ? E_ALL : E_ERROR);

include_once PATH_ROOT . "/index.inc.php";

date_default_timezone_set("PRC");



