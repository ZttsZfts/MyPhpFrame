<?php

class Table
{
    protected DataTable $Table;
    // 使用常量替代硬编码的表名，提高可维护性
    protected DataLine $DataLine;

    public function __construct()
    {
        // 初始化表对象，传入表名
        $this->Table = new DataTable($this->TABLE_NAME);

        // 获取传入的列数组
        $inputColumns = $this->Field;

        // 获取数据库中的现有列
        $existingColumns = $this->Table->getColumns();

        // 找出需要添加的列（存在于输入列但不存在于数据库列）
        $columnsToAdd = array_filter($inputColumns, function ($inputColumn) use ($existingColumns) {
            return !in_array($inputColumn[0], $existingColumns);
        });

        // 找出需要删除的列（存在于数据库列但不存在于输入列）
        $columnsToRemove = array_filter($existingColumns, function ($existingColumn) use ($inputColumns) {
            foreach ($inputColumns as $inputColumn) {
                if ($inputColumn[0] == $existingColumn) {
                    return false;
                }
            }
            return true;
        });

        // 检查表是否不存在
        if (!$this->Table->exists()) {
            // 如果表不存在，则创建表
            $this->Table->create();
        }

        // 添加需要添加的列
        foreach ($columnsToAdd as $value) {
            if (count($value) !== 3) {
                error_log("字段定义错误: " . implode(",", $value));
                continue;
            }
            $this->Table->addColumn($value[0], $value[1], $value[2]);
        }

        // 删除需要删除的列
        foreach ($columnsToRemove as $columnName) {
            $this->Table->deleteColumn($columnName);
        }

        // 初始化 DataLine 对象，传入表名
        $this->DataLine = new DataLine($this->TABLE_NAME);
    }

    /**
     * 获取共有多少行
     */
    public function count(): int
    {
        return $this->Table->count();
    }

    /**
     * 自定义查询方法，支持传入原始的 WHERE 和 GROUP BY 子句
     *
     * @param string $where 自定义 WHERE 子句，例如 "Age > 30"
     * @param string $groupBy 自定义 GROUP BY 子句，例如 "Age"
     * @param array $fields 查询的字段数组，如果为空则查询所有字段。
     * @param array $order 排序条件的数组（键为字段名，值为排序顺序 'ASC' 或 'DESC'）。
     * @param int $limit 返回结果的限制数量，如果为 0 则不限制数量。
     * @param int $offset 返回结果的偏移量。
     * @return array|bool 返回查询结果数组，如果失败则返回 false。
     */
    public function findCustom(string $where = "1", string $groupBy = "", array $fields = [], array $order = [], int $limit = 0, int $offset = 0): array|bool
    {
        // 如果没有指定字段，查询所有字段
        $field = empty($fields) ? "*" : implode(", ", $fields);

        // 构建 ORDER BY 子句
        $orderBy = "";
        if (!empty($order)) {
            $orderBy = implode(", ", array_map(fn($f, $d) => "`$f` $d", array_keys($order), $order));
        }

        // 构建 SQL 查询
        $sql = "SELECT $field FROM `{$this->TABLE_NAME}` WHERE $where";

        // 如果指定了 GROUP BY
        if ($groupBy) {
            $sql .= " GROUP BY $groupBy";
        }

        // 如果指定了 ORDER BY
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        // 添加 LIMIT 和 OFFSET
        if ($limit > 0) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        // 使用 DataLine 类的 read 方法执行查询
        return $this->DataLine->executeQuery($sql);
    }

    /**
     * 查找数据
     *
     * 根据给定的字段数组和条件数组查询数据。
     *
     * @param array $fields 查询的字段数组，如果为空则查询所有字段。
     * @param array $conditions 查询条件的关联数组（键为字段名，值为查询的值或操作符数组）。
     *                         支持的操作符：'=', '>', '<', '>=', '<='.
     *                         示例：['Age' => ['>' => 30], 'Points' => ['<' => 100]].
     * @param array $order 排序条件的数组（键为字段名，值为排序顺序 'ASC' 或 'DESC'）。
     *                     示例：['Age' => 'ASC', 'Points' => 'DESC'].
     * @param int $limit 返回结果的限制数量，如果为 0 则不限制数量。
     * @return array|bool 返回查询结果数组，如果失败则返回 false。
     *
     * @example
     * $conditions = ['Email' => 'john@example.com', 'IsActive' => 1];
     * $order = ['Age' => 'ASC', 'Name' => 'DESC'];
     * $user = UserData::find(['Name', 'Email'], $conditions, $order, 10);
     * if ($user) {
     *     print_r($user);
     * } else {
     *     echo 'User not found';
     * }
     */
    public function find(array $fields = [], array $conditions = [], array $order = [], int $limit = 0, int $offset = 0, string $add = "", string $keywords = ""): array|bool
    {
        // 初始化 WHERE 子句和对应的值数组
        $where = "";
        $values = [];

        if ($keywords !== "") {
            if (strpos($keywords, ' ') !== false) {
                // 将关键词分割成数组
                $keywordsArray = explode(' ', $keywords);
            } else {
                $keywordsArray = [$keywords];
            }

            // 构建 WHERE 子句
            foreach ($keywordsArray as $keyword) {
                $keywordWhere = "";
                foreach ($fields as $field) {
                    if (str_contains($field, 'COUNT(*)')) {
                        continue;
                    }
                    if ($keywordWhere !== "") {
                        $keywordWhere .= " OR ";
                    }
                    $keywordWhere .= "`$field` LIKE ?";
                    $values[] = "%$keyword%";
                }

                if ($keywordWhere === "") {
                    continue;
                }
                if ($where !== "") {
                    $where .= " AND ";
                }
                $where .= "($keywordWhere)";

            }

        }


        // 构建 WHERE 子句
        foreach ($conditions as $field => $condition) {
            if ($where !== "") {
                $where .= " AND ";
            }

            // 判断条件是否是数组，包含操作符
            if (is_array($condition)) {
                $operator = key($condition);
                $value = current($condition);

                switch ($operator) {
                    case '>':
                    case '<':
                    case '>=':
                    case '<=':
                        $where .= "`$field` $operator ?";
                        if (is_int($value)) {
                            $values[] = ["INT" => $value];
                        } else {
                            $values[] = $value;
                        }

                        break;
                    default:
                        $where .= "`$field` = ?";
                        $values[] = $value;
                        break;
                }
            } else {
                // 默认操作符为等于
                $where .= "`$field` = ?";
                $values[] = $condition;
            }
        }

        if (empty($where)) {
            $where = "1";
        }

        if (empty($fields)) {
            $field = "*";
        } else {
            $field = implode(", ", $fields);
        }

        // 构建 ORDER BY 子句
        $orderBy = "";
        if (!empty($order)) {
            $orderBy = implode(", ", array_map(fn($f, $d) => "`$f` $d", array_keys($order), $order));
        }

        // 使用 DataLine 类的 read 方法执行查询，并传递 $limit 参数
        return $this->DataLine->read($field, $where, $values, $orderBy, $limit, $offset, $add);
    }


    /**
     * 检查用户是否存在
     *
     * 根据给定的条件数组检查是否存在符合条件的用户。
     *
     * @param array $conditions 查询条件的关联数组（键为字段名，值为查询的值）。
     * @return bool 如果存在符合条件的用户则返回 true，否则返回 false。
     *
     * @example
     * $conditions = ['Email' => 'john@example.com'];
     * $exists = UserData::Exist($conditions);
     * echo $exists ? 'User exists' : 'User does not exist';
     */
    public function exist(array $conditions = []): bool
    {
        // 初始化 WHERE 子句和对应的值数组
        $where = "";
        $values = [];

        // 构建 WHERE 子句
        foreach ($conditions as $field => $value) {
            if ($where !== "") {
                $where .= " AND ";
            }
            $where .= "`$field` = ?";
            $values[] = $value;
        }

        // 使用 DataLine 类的 read 方法执行查询，返回记录数
        $result = $this->DataLine->read("COUNT(*) AS count", $where, $values);

        // 返回记录数是否大于 0
        return $result && $result[0]['count'] > 0;
    }

    /**
     * 写入用户数据
     *
     * 插入新的用户数据到数据库中。
     *
     * @param array $data 包含用户数据的关联数组（键为字段名，值为对应的值）。
     * @return bool 如果插入成功则返回 true，否则返回 false。
     *
     * @example
     * $newUser = [
     *     'Name' => 'John Doe',
     *     'Phone' => '12345678901',
     *     'Email' => 'john@example.com',
     *     'Password' => password_hash('password123', PASSWORD_DEFAULT),
     *     'Salt' => uniqid(),
     *     'Group' => 'user',
     *     'ErrorNumber' => 0,
     *     'GetCodeTime' => date('Y-m-d H:i:s'),
     *     'IsEmailVerified' => 0,
     *     'IsActive' => 1,
     *     'RegDate' => date('Y-m-d H:i:s'),
     *     'LastLogDate' => date('Y-m-d H:i:s'),
     *     'IPAddress' => '127.0.0.1',
     *     'Login' => 0,
     * ];
     * $success = UserData::Write($newUser);
     * echo $success ? 'User added successfully' : 'Failed to add user';
     */
    public function write(array $data): bool
    {
        // 使用 DataLine 类的 add 方法执行插入操作
        return $this->DataLine->add($data);
    }


    /**
     * 更新用户数据
     *
     * 根据给定的条件数组更新用户数据。
     *
     * @param array $data 包含要更新的数据的关联数组（键为字段名，值为对应的值）。
     * @param array $conditions 更新条件的关联数组（键为字段名，值为查询的值）。
     * @return bool 如果更新成功则返回 true，否则返回 false。
     *
     * @example
     * $data = ['Name' => 'John Doe', 'Email' => 'john.doe@example.com'];
     * $conditions = ['id' => 1];
     * $success = UserData::Update($data, $conditions);
     * echo $success ? 'User updated successfully' : 'Failed to update user';
     */
    public function update(array $data, array $conditions): bool
    {
        // 初始化 SET 子句和对应的值数组
        $set = "";
        $values = [];

        // 构建 SET 子句
        foreach ($data as $field => $value) {
            if ($set !== "") {
                $set .= ", ";
            }
            $set .= "`$field` = ?";
            $values[] = $value;
        }

        // 初始化 WHERE 子句
        $where = "";
        foreach ($conditions as $field => $value) {
            if ($where !== "") {
                $where .= " AND ";
            }
            $where .= "`$field` = ?";
            $values[] = $value;
        }

        // 使用 DataLine 类的 update 方法执行更新操作
        return $this->DataLine->update($set, $where, $values);
    }

    /**
     * 模糊关键词查询用户数据
     *
     * 根据给定的关键词进行模糊查询用户数据。
     *
     * @param string|null $keywords 查询关键词，可以是以空格分隔的多个关键词，允许为 null。
     * @param array $fields 查询的字段名数组，如果为空则查询所有字段。
     * @param array $order 排序条件的数组（键为字段名，值为排序顺序 'ASC' 或 'DESC'）。
     *                      示例：['Age' => 'ASC', 'Points' => 'DESC'].
     * @param int $limit 返回结果的限制数量，如果为 0 则不限制数量。
     * @return array|bool 返回查询结果数组，如果失败则返回 false。
     *
     * @example
     * $keywords = '登录 用户名';
     * $fields = ['Name', 'Email', 'Phone'];
     * $results = UserData::search($keywords, $fields);
     * print_r($results);
     */
    public function search(?string $keywords, array $fields = [], array $order = [], int $limit = 0, int $offset = 0, array $conditions = []): array|bool
    {
        if ($keywords === null || $keywords === '') {
            // 如果关键词为空，则查询所有记录
            $where = "1";
            $values = [];
        } else {
            // 检查关键词是否包含空格
            if (strpos($keywords, ' ') !== false) {
                // 将关键词分割成数组
                $keywordsArray = explode(' ', $keywords);
            } else {
                $keywordsArray = [$keywords];
            }

            $where = "";
            $values = [];
            // 构建 WHERE 子句
            foreach ($keywordsArray as $keyword) {
                $keywordWhere = "";
                foreach ($fields as $field) {
                    if ($keywordWhere !== "") {
                        $keywordWhere .= " OR ";
                    }
                    $keywordWhere .= "`$field` LIKE ?";
                    $values[] = "%$keyword%";
                }
                if ($where !== "") {
                    $where .= " AND ";
                }
                $where .= "($keywordWhere)";
            }


            foreach ($conditions as $field => $condition) {
                if ($where !== "") {
                    $where .= " AND ";
                }

                // 判断条件是否是数组，包含操作符
                if (is_array($condition)) {
                    $operator = key($condition);
                    $value = current($condition);

                    switch ($operator) {
                        case '>':
                        case '<':
                        case '>=':
                        case '<=':
                            $where .= "`$field` $operator ?";
                            if (is_int($value)) {
                                $values[] = ["INT" => $value];
                            } else {
                                $values[] = $value;
                            }

                            break;
                        default:
                            $where .= "`$field` = ?";
                            $values[] = $value;
                            break;
                    }
                } else {
                    // 默认操作符为等于
                    $where .= "`$field` = ?";
                    $values[] = $condition;
                }
            }

        }

        $field = empty($fields) ? "*" : implode(", ", $fields);

        // 构建 ORDER BY 子句
        $orderBy = "";
        if (!empty($order)) {
            $orderBy = implode(", ", array_map(fn($f, $d) => "`$f` $d", array_keys($order), $order));
        }

        // 使用 DataLine 类的 read 方法执行查询
        return $this->DataLine->read($field, $where, $values, $orderBy, $limit, $offset);
    }

    /**
     * 删除所有行
     */
    public function deleteAll(): bool
    {
        return $this->DataLine->delete("1", []);
    }

    /**
     * 删除用户数据
     *
     * 根据给定的条件数组删除用户数据。
     *
     * @param array $conditions 删除条件的关联数组（键为字段名，值为查询的值）。
     * @return bool 如果删除成功则返回 true，否则返回 false。
     *
     * @example
     * $conditions = ['id' => 1];
     * $success = UserData::delete($conditions);
     * echo $success ? 'User deleted successfully' : 'Failed to delete user';
     */
    public function delete(array $conditions): bool
    {
        // 初始化 WHERE 子句和对应的值数组
        $where = "";
        $values = [];

        // 构建 WHERE 子句
        foreach ($conditions as $field => $value) {
            if ($where !== "") {
                $where .= " AND ";
            }
            $where .= "`$field` = ?";
            $values[] = $value;
        }

        // 使用 DataLine 类的 delete 方法执行删除操作
        return $this->DataLine->delete($where, $values);
    }

}