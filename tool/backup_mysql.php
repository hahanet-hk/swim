<?php
set_time_limit(0);
function backupDatabase($dbConfig, $backupPath)
{
    // 数据库配置信息
    $host = $dbConfig['host'];
    $dbname = $dbConfig['dbname'];
    $username = $dbConfig['username'];
    $password = $dbConfig['password'];
    $charset = $dbConfig['charset'];
    // 备份文件名
    $backupFileName = 'backup_'.$dbname.'_'.date('YmdHis').'.sql';
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $tables = array();
        $query = $pdo->query('SHOW TABLES');
        while ($row = $query->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        // 备份文件内容
        $output = '';
        // 循环所有表进行备份
        foreach ($tables as $table) {
            if (strpos($table, '_edu_') === false)
            {
                continue;
            }
            // 表结构
            $query = $pdo->query('SHOW CREATE TABLE ' . $table);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            $output .= "\n\n" . $row['Create Table'] . ";\n\n";
            // 表数据
            $query = $pdo->query('SELECT * FROM ' . $table);
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $keys = array_keys($row);
                $keys = array_map(function ($key) use ($pdo) {
                    return $pdo->quote($key);
                }, $keys);
                $values = array_values($row);
                $values = array_map(function ($value) use ($pdo) {
                    return is_null($value) ? 'NULL' : $pdo->quote($value);
                }, $values);
                $output .= 'INSERT INTO ' . $table . ' (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $values) . ');' . "\n";
            }
        }
        // 将内容写入备份文件
        file_put_contents($backupPath . DIRECTORY_SEPARATOR . $backupFileName, $output);
        return $backupFileName;
    } catch (PDOException $e) {
        throw $e;
    }
}
// begin
date_default_timezone_set('PRC');
$config_text = file_get_contents(__DIR__.'/../wp-config.php');
preg_match_all('/define\s*\(\s*\'([A-Z_]+)\'\s*,\s*\'((?:[^\']|\'\')+)\'\s*\)\s*;/', $config_text, $matches);
$constants = array_combine($matches[1], $matches[2]);
foreach ($constants as $key => $value)
{
    defined($key) or define($key, $value);
}
preg_match('/\$table_prefix.*?=.*?\'(?<prefix>.*?)\';/', $config_text, $matches);
$table_prefix = $matches['prefix'];
// 使用示例
$dbConfig = [
    'host' => DB_HOST,
    'dbname' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASSWORD,
    'charset' => DB_CHARSET
];
$backupPath = './#backup_mysql';
try {
    $backupFileName = backupDatabase($dbConfig, $backupPath);
    echo "数据库备份成功{$backupPath}/{$backupFileName}";
} catch (Exception $e) {
    echo "数据库备份失败：{$e->getMessage()}";
}
