<?php
echo "PHP Version: " . phpversion() . "\n";
echo "PG SQL Loaded: " . (extension_loaded('pgsql') ? 'YES' : 'NO') . "\n\n";

$configs = [
    ['host' => 'aws-0-eu-west-1.pooler.supabase.com', 'port' => '6543'],
    ['host' => '34.241.16.247', 'port' => '6543'],
    ['host' => '108.128.216.176', 'port' => '6543'],
];

foreach ($configs as $cfg) {
    echo "Пробую {$cfg['host']}:{$cfg['port']}... ";
    try {
        $pdo = new PDO(
            "pgsql:host={$cfg['host']};port={$cfg['port']};dbname=postgres;sslmode=require",
            'postgres.cuibxmcjdkgjffmmzwgd',
            'Kosty33a332',
            [PDO::ATTR_TIMEOUT => 5]
        );
        echo "✅ УСПЕХ! Подключение установлено!\n";
        exit;
    } catch (PDOException $e) {
        echo "❌ " . substr($e->getMessage(), 0, 100) . "\n";
    }
}
echo "\nВсе варианты НЕ сработали. База данных недоступна.\n";