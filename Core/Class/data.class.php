<?php

defined("DEBUG") or die("拒绝访问。");

class Data
{
    private static array $SQL = [
        "CREATE TABLE" => "CREATE TABLE `{%NAME%}` (`id` INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`)) ENGINE=InnoDB",
        "SHOW TABLES" => "SHOW TABLES LIKE ?",
        "SHOW COLUMNS" => "SHOW COLUMNS FROM `{%NAME%}` LIKE {%ROW%}",
        "ALTER TABLE" => "ALTER TABLE `{%NAME%}` ADD `{%ITEM%}` {%TYPE%}{%NUM%} NOT NULL",
        "INSERT INTO" => "INSERT INTO `{%NAME%}` (`id`, {%FIELD%}) VALUES (NULL, {%PLACEHOLDER%})",
        "SELECT" => "SELECT {%FIELD%} FROM `{%NAME%}` WHERE {%WHERE%}",
        "UPDATE" => "UPDATE `{%NAME%}` SET {%SET%} WHERE {%WHERE%}",
        "DELETE" => "DELETE FROM `{%NAME%}` WHERE {%WHERE%}",
        "COUNT" => "SELECT COUNT(*) FROM `{%NAME%}` AS COUNT",
    ];

    private static ?self $instance = null;
    protected PDO $connect;
    private ?DataTable $dataTable = null;

    private function __construct()
    {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $_SERVER['CONF']['data']['host'],
                $_SERVER['CONF']['data']['db'],
                $_SERVER['CONF']['data']['charset']
            );
            $this->connect = new PDO($dsn, $_SERVER['CONF']['data']['user'], $_SERVER['CONF']['data']['pass']);
            $this->connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("数据库连接失败：{$e->getMessage()}");
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getSQL(string $key): string
    {
        return self::$SQL[$key] ?? '';
    }

    public function getDataTable(string $tableName): DataTable
    {
        if ($this->dataTable === null || $this->dataTable->getTableName() !== $tableName) {
            $this->dataTable = new DataTable($tableName);
        }
        return $this->dataTable;
    }

    public function execute(string $sql, array $data = [], array $softReplace = [], array &$result = null): bool
    {
        $sql = str_replace(array_map(fn($k) => "{%$k%}", array_keys($softReplace)), array_values($softReplace), $sql);

        try {
            $stmt = $this->connect->prepare($sql);

            // 绑定参数并指定类型
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $operator = key($value);
                    $current = current($value);
                    switch ($operator) {
                        case "INT":
                            $stmt->bindValue($key + 1, (int)$current, PDO::PARAM_INT);
                            break;
                        default:
                            $stmt->bindValue($key + 1, $current, PDO::PARAM_STR);
                    }
                } else {
                    $stmt->bindValue($key + 1, $value, PDO::PARAM_STR);
                }
            }

            $result_bool = $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $result_bool;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function __clone()
    {
    }
}

class DataLine
{
    private string $TableName;

    public function __construct(string $tableName)
    {
        $this->TableName = $tableName;
    }

    /**
     * 执行自定义查询
     */
    public function executeQuery(string $sql): array|bool
    {
        $result = [];
        $db = Data::getInstance();
        $db->execute($sql, [], [], $result);
        return $result;
    }

    public function add(array $data): bool
    {
        $fields = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $values = array_values($data);
        $result = Data::getInstance()->execute(Data::getSQL('INSERT INTO'), $values, [
            "NAME" => $this->TableName,
            "FIELD" => $fields,
            "PLACEHOLDER" => $placeholders,
        ]);

        return is_bool($result) ? $result : false;
    }

    public function read($field, string $where, array $values, string $orderBy = "", int $limit = 0, int $offset = 0,string $add = ""): array|bool
    {
        $fields = is_array($field) ? implode(", ", array_map(fn($f) => "`$f`", $field)) : $field;
        $result = [];
        if (empty($where)) {
            $where = "1";
        }
        $sql = Data::getSQL('SELECT');
        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }

        if (!empty($add)){
            $sql .= " $add";
        }

        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }



        $result_bool = Data::getInstance()->execute($sql, $values, [
            "NAME" => $this->TableName,
            "FIELD" => $fields,
            "WHERE" => $where,
        ], $result);

        return $result_bool ? $result : false;
    }


    public function update(string $set, string $where, array $values): bool
    {
        $result = [];
        $result_bool = Data::getInstance()->execute(Data::getSQL('UPDATE'), $values, [
            "NAME" => $this->TableName,
            "SET" => $set,
            "WHERE" => $where,
        ], $result);

        return is_bool($result_bool) ? $result_bool : false;
    }

    public function delete(string $where, array $values): bool
    {
        $result = [];
        $result_bool = Data::getInstance()->execute(Data::getSQL('DELETE'), $values, [
            "NAME" => $this->TableName,
            "WHERE" => $where,
        ], $result);

        return is_bool($result_bool) ? $result_bool : false;
    }
}

class DataTable
{
    private string $TableName;
    private ?DataLine $dataLine = null;

    public function __construct(string $tableName)
    {
        $this->TableName = $tableName;
    }

    public function getTableName(): string
    {
        return $this->TableName;
    }

    public function getDataLine(): DataLine
    {
        if ($this->dataLine === null) {
            $this->dataLine = new DataLine($this->TableName);
        }
        return $this->dataLine;
    }

    public function count(): int|false
    {
        $value = Data::getInstance()->execute(Data::getSQL('COUNT'), [], ["NAME" => $this->TableName], $result);
        return $value ? (int)$result[0]['COUNT(*)'] : false;
    }

    public function create(): int
    {
        if ($this->exists()) {
            return 0;
        }

        Data::getInstance()->execute(Data::getSQL('CREATE TABLE'), [], ["NAME" => $this->TableName]);

        return $this->exists() ? 1 : -1;
    }

    public function exists(): bool
    {
        $result = [];
        $result_bool = Data::getInstance()->execute(Data::getSQL('SHOW TABLES'), [$this->TableName], [], $result);

        return $result_bool && count($result) > 0;
    }

    public function delete(): bool
    {
        // implement the logic for deleting the table if needed
        return false;
    }

    public function addColumn(string $columnName, string $type, string $length): int
    {
        if ($this->columnExists($columnName)) {
            return 0;
        }

        $result = [];
        $result_bool = Data::getInstance()->execute(Data::getSQL('ALTER TABLE'), [], [
            "NAME" => $this->TableName,
            "ITEM" => $columnName,
            "TYPE" => $type,
            "NUM" => $length,
        ], $result);

        return $result_bool ? 1 : -1;
    }


    public function columnExists(string $columnName): bool
    {
        $result = [];
        $result_bool = Data::getInstance()->execute(Data::getSQL('SHOW COLUMNS'), [$columnName], [
            "NAME" => $this->TableName,
            "ROW" => $columnName
        ], $result);

        return $result_bool && count($result) > 0;
    }

    public function deleteColumn(string $columnName): bool
    {
        if ($columnName == "id") return true;
        $sql = "ALTER TABLE `{$this->TableName}` DROP COLUMN `{$columnName}`";
        return Data::getInstance()->execute($sql);
    }

    public function getColumns(): array
    {
        $result = [];
        $result_bool = Data::getInstance()->execute(Data::getSQL('SHOW COLUMNS'), [], [
            "NAME" => $this->TableName,
            "ROW" => "'%'"
        ], $result);

        return $result_bool ? array_column($result, 'Field') : [];
    }
}
