<?php
defined("DEBUG") or die("拒绝访问。");

include PATH_CORE . "/core.php";


if (empty($_GET['t'])) {
    $_GET['t'] = 'main';
}

Http::VerifyToken($token);
define("Token", $token);

$result = Index::processUrlParam($_GET['t']);


class Index
{
    private array $pageMap = [];


    public function __construct()
    {
        $this->buildPageMap();
    }

    private function buildPageMap(): void
    {
        $extensions = ['.php'];
        $excludedStrings = ["include"];

        // 定义目录常量
        define("DIRECTORY_PUBLIC", PATH_PUBLIC);

        // 抽象出处理文件的函数
        $this->processFiles(DIRECTORY_PUBLIC, $this->pageMap, $extensions, $excludedStrings);
    }

    /**
     * 处理指定目录下的文件，将符合条件的文件添加到映射中
     *
     * @param string $directory 要处理的目录
     * @param array $targetMap 要添加到映射中的文件列表
     * @param array $extensions 要处理的文件扩展名
     * @param array $excludedStrings 要排除的文件名包含的字符串
     * @return void
     *
     */
    private function processFiles(string $directory, array &$targetMap, array $extensions, array $excludedStrings): void
    {
        if (!is_dir($directory)) {
            // 处理目录不存在的情况
            error_log("Directory does not exist: $directory");
            return;
        }

        $files = scandir($directory);

        if ($files === false) {
            // 处理scandir失败的情况
            error_log("Failed to read directory: $directory");
            return;
        }

        foreach ($files as $file) {
            if (is_file($directory . DIRECTORY_SEPARATOR . $file)) {
                foreach ($extensions as $extension) {
                    // 预处理扩展名，提高性能
                    $lowercaseExtension = strtolower($extension);
                    if (strtolower(substr($file, -strlen($lowercaseExtension))) === $lowercaseExtension) {
                        // 使用 strpos 来检查是否包含排除的字符串
                        $excludeFound = false;
                        foreach ($excludedStrings as $excludeString) {
                            if (str_contains(strtolower($file), $excludeString)) {
                                $excludeFound = true;
                                break;
                            }
                        }
                        if (!$excludeFound) {
                            $fileInfo = pathinfo($directory . DIRECTORY_SEPARATOR . $file);
                            $targetMap[$fileInfo['filename']] = $directory . DIRECTORY_SEPARATOR . $file;
                        }
                    }
                }
            }
        }
    }

    public static function processUrlParam(string $param): string|bool
    {
        return match ($param) {
            "api" => self::handleApiRequests(),
            "page" => self::handlePageRequest($_GET['id'], "pageMap"),
            default => self::handlePageRequest('main', "pageMap")
        };
    }

    private static function handleApiRequests(): bool
    {
        // 从数据库中读取API对应关系
        $apiData = ApiData->find(conditions: ['Enable' => 1]);
        if (empty($apiData)) {
            // 如果数据库中没有数据，则返回 false
            echo json_encode(new standardOutput(-1, "参数不存在"));
            return false;
        }

        $apiHandlers = [];
        for ($i = 0; $i < count($apiData); $i++) {
            $apiHandlers[$apiData[$i]['Label']] = [$apiData[$i]['Clazz'], $apiData[$i]['Method']];
        }

        // 获取传入的 id 参数
        $id = $_GET['id'] ?? '';

        // 检查 $id 是否在 $apiHandlers 数组中
        if (array_key_exists($id, $apiHandlers)) {
            // 获取类名和方法名
            $className = $apiHandlers[$id][0];
            $methodName = $apiHandlers[$id][1];

            if (class_exists($className) && method_exists($className, $methodName)) {

                // 调用对应的静态方法
                try {
                    $result = call_user_func([$className, $methodName]);
                    if (is_string($result)) {
                        echo $result;
                    } else {
                        echo json_encode(new standardOutput(-1, "{$className}->{$methodName}，API不存在或未启用", obj: ['type' => LogType::Error, 'content' => "{$className}->{$methodName}，API不存在或未启用", 'user' => '']));
                    }
                } catch (Exception) {
                    echo json_encode(new standardOutput(-1, "{$className}->{$methodName}，API不存在或未启用", obj: ['type' => LogType::Error, 'content' => "{$className}->{$methodName}，API不存在或未启用", 'user' => '']));
                }
            } else {
                echo json_encode(new standardOutput(-1, "{$className}->{$methodName}，API不存在或未启用", obj: ['type' => LogType::Error, 'content' => "{$className}->{$methodName}，API不存在或未启用", 'user' => '']));
            }

        } else {
            // 参数不存在时返回错误信息
            echo json_encode(new standardOutput(-1, "API不存在或未启用"));
        }

        return true;
    }


    private static function handlePageRequest(string $id, string $group): string
    {

        $index = new self();
        $pageContent = '';
        if ($pagePath = $index->call($id, $group)) {
            $pageContent = include $pagePath;
        } else {
            $pageContent = include "404.html";
        }
        return $pageContent;
    }

    /**
     * 访问页面
     * @param string $item
     * @param string $group
     * @return string|bool 返回页面路径，或为false
     */
    public function call(string $item, string $group): string|bool
    {
        $group = match ($group) {
            default => $this->pageMap,
        };

        return array_key_exists($item, $group) && file_exists($group[$item]) ? $group[$item] : false;
    }
}
