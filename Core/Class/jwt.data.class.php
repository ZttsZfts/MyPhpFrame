<?php

defined("DEBUG") or die("拒绝访问。");

class JwtData extends Table
{
    protected string $TABLE_NAME = "Jwt";
    public array $Field = [
        ['Name', 'VARCHAR', '(64)'],
        ['Permission', 'VARCHAR', '(64)'],
        ['Phone', 'VARCHAR', '(11)'],
        ['Token', 'VARCHAR', '(2048)'],
        ['Expire', 'TIMESTAMP', ''],
    ];
}
