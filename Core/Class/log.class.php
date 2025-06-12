<?php


class Log extends Http
{


    public static function write(LogType $type, $content, $user): void
    {

        $newLog = [
            'Type' => $type->name,
            'Content' => $content ?? "",
            'User' => $user ?? "",
            'Time' => date('Y-m-d H:i:s'),
            'IP' => getClientIP(),
        ];

        LogData->write($newLog);
    }

    // 获取今天一天每个类别日志的数量
    public static function GetTodayLogCount(): false|string
    {
        $token = [];
        if (!self::VerifyToken($token)) {
            return json_encode(new standardOutput(-1, "token验证失败"));
        }

        if ($token['permission'] < 999) {
            return json_encode(new standardOutput(-1, "权限不足"));
        }


        $today = date('Y-m-d');
        $logs = LogData->find(["Type", "Time"]);
        $count = [];

        // 初始化count数组
        for ($i = 0; $i < 24; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
            $count[$hour] = [
                'Success' => 0,
                'Warning' => 0,
                'Error' => 0
            ];
        }

        foreach ($logs as $log) {
            $time = strtotime($log['Time']);
            $logDate = date('Y-m-d', $time);

            // 确保日志是今天的
            if ($logDate === $today) {
                $hour = date('H', $time) . ':00'; // 获取日志的小时部分并格式化

                switch ($log['Type']) {
                    case 'Success':
                        $count[$hour]['Success']++;
                        break;
                    case 'Warning':
                        $count[$hour]['Warning']++;
                        break;
                    case 'Error':
                        $count[$hour]['Error']++;
                        break;
                }
            }
        }

        return json_encode(new standardOutput(1, "获取成功", $count));
    }


}
