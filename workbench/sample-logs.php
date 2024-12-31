<?php

// Generates a realistic sample log file for local UI testing.
// Usage: php workbench/sample-logs.php

$dir = __DIR__ . '/../vendor/orchestra/testbench-core/laravel/storage/logs';
@mkdir($dir, 0777, true);

$lines = [];
$base = new DateTime('2026-06-29 08:00:00');

$samples = [
    ['INFO', 'User {id} authenticated successfully', ''],
    ['DEBUG', 'Cache hit for key "users.profile.{id}"', ''],
    ['NOTICE', 'Scheduled job "ProcessPayouts" started', ''],
    ['WARNING', 'Slow query detected (1.8s): select * from orders where status = ?', ''],
    ['ERROR', 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry',
        "#0 /app/vendor/laravel/framework/src/Illuminate/Database/Connection.php(814): PDO->prepare()\n#1 /app/app/Models/Order.php(45): Illuminate\\Database\\Connection->statement()\n#2 {main}"],
    ['CRITICAL', 'Payment gateway timeout after 30s {\"gateway\":\"stripe\",\"order\":4821}', ''],
    ['INFO', 'Email queued to user-{id}@example.com', ''],
    ['EMERGENCY', 'Disk space critically low on /var (98% used)', ''],
];

for ($i = 0; $i < 240; $i++) {
    $s = $samples[$i % count($samples)];
    $dt = (clone $base)->modify("+{$i} minutes")->format('Y-m-d H:i:s');
    $msg = str_replace('{id}', (string) (1000 + $i), $s[1]);
    $lines[] = "[$dt] production.{$s[0]}: $msg";
    if ($s[2] !== '') {
        $lines[] = $s[2];
    }
}

file_put_contents("$dir/laravel.log", implode("\n", $lines) . "\n");

@mkdir("$dir/archive", 0777, true);
file_put_contents("$dir/archive/laravel-2026-06-28.log", "[2026-06-28 23:59:01] production.INFO: Nightly backup completed\n");

echo "Sample logs written to $dir\n";
