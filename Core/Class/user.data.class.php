<?php

defined("DEBUG") or die("拒绝访问。");

class UserData extends Table
{
    protected string $TABLE_NAME = "User";
    public array $Field = [
        ["Name", "VARCHAR", "(100)"],
        ["Phone", "CHAR", "(11)"],
        ["Email", "VARCHAR", "(100)"],
        ["Password", "VARCHAR", "(255)"],
        ["Salt", "VARCHAR", "(255)"],
        ["Permission", "INT", ""],
        ["ErrorNumber", "INT",""],
        ["GetCodeTime", "TIMESTAMP", ""],
        ["IsEmailVerified", "TINYINT", "(1)"],
        ["IsActive", "TINYINT", "(1)"],
        ["RegDate", "TIMESTAMP", ""],
        ["LastLogDate", "TIMESTAMP", ""],
        ["IPAddress", "VARCHAR", "(45)"],
        ["Login", "INT",""],
    ];


}
