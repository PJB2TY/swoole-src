--TEST--
swoole_coroutine_scheduler: start in server onShutdown callback
--SKIPIF--
<?php require __DIR__ . '/../include/skipif.inc'; ?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';

use Swoole\Coroutine\System;

$file = file_get_contents(__FILE__);

$server = new Swoole\Server('127.0.0.1', get_one_free_port(), SWOOLE_PROCESS);
$server->set(['worker_num' => 2, 'log_level' => SWOOLE_LOG_WARNING,]);

$server->on('WorkerStart', function (Swoole\Server $server, int $worker_id) use ($file) {
    if ($worker_id == 1) {
        Swoole\Timer::after(200, function () use ($server, $file) {
            System::sleep(0.1);
            echo "[1] Co " . Co::getCid() . "\n";
            Assert::same(System::readFile(__FILE__), $file);
            $server->shutdown();
        });
    }
});

$server->on('Receive', function (Swoole\Server $server, $fd, $reactor_id, $data) {
});

$server->on('workerStop', function (Swoole\Server $server, int $worker_id) use ($file) {
    if ($worker_id == 1) {
        $sch = new Co\Scheduler;
        $sch->add(function ($t, $n) use ($file) {
            System::sleep($t);
            echo "[2] Co " . Co::getCid() . "\n";
            Assert::same(System::readFile(__FILE__), $file);
        }, 0.05, 'A');
        $sch->start();
    }
});

$server->start();
?>
--EXPECT--
[1] Co 2
[2] Co 3
