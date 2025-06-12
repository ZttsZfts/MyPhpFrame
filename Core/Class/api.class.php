<?php

class Api extends Http
{
    public static function EnableApi(): string
    {
        $token = [];
        if (!self::VerifyToken($token)) {
            return json_encode(new standardOutput(-1, "token验证失败"));
        }

        if ($token['permission'] < 999) {
            return json_encode(new standardOutput(-1, "权限不足"));
        }

        $dataJson = self::getInput("enableApi");

        if (!$dataJson) {
            return json_encode(new standardOutput(-1, "参数错误"));
        }

        $target = $dataJson["target"];
        $state = $dataJson["state"];

        $name = $token['phone'];

        $apiPermission = ApiData->find(['Permissions'], ['Label' => $target]);
        if ($apiPermission[0]['Permissions'] == 1000) {
            return json_encode(new standardOutput(-1, "禁止操作该接口"));
        }

        $newIsActive = [
            'Enable' => $state == 0 ? 0 : 1,
        ];
        $update = ApiData->update($newIsActive, ['Label' => $target]);
        if (!$update) {
            return json_encode(new standardOutput(-1, "操作失败，请重试", obj: ['type' => LogType::Error, 'content' => '接口操作失败，请重试', 'user' => $name]));
        }
        return json_encode(new standardOutput(1, "操作成功", obj: ['type' => LogType::Success, 'content' => ($state == 1 ? "启用接口：{$target}" : "禁用接口：{$target}"), 'user' => $name]));

    }
}