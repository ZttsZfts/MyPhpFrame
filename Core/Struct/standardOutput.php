<?php

defined("DEBUG") or die("拒绝访问。");

class standardOutput
{
    public int $code;
    public string $msg;
    public bool|int|string|array|null|object $result;

    public function __construct($code, $msg, $result = null, $obj = [])
    {
        if (!empty($obj)) {
            Log::write($obj['type'], $obj['content'], $obj['user']);
        }

        $this->code = $code;
        $this->msg = $msg;
        $this->result = $result;
    }
}