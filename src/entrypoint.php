<?php

require_once "vendor/autoload.php";

declare(ticks=1);

const DEFAULT_NUM_SERVERS = 8;

/**
 * @return void
 */
function setupSignals(): void
{
    pcntl_signal(SIGTERM, function () {
        stop();
    });

    pcntl_signal(SIGHUP, function () {
        stop();
    });

    pcntl_signal(SIGINT, function () {
        stop();
    });

    pcntl_signal(SIGWINCH, function () {
        stop();
    });
}

function getFromEnv(?string $param)
{
    $env = getenv();

    if ($param === null) {
        return $env;
    }

    return array_key_exists($param, $env)
        ? $env[$param]
        : null;
}

/**
 * @return void
 * @throws Exception
 */
function setupExports(): void
{
    $env = getFromEnv(null);

    $exports = "";

    foreach ($env as $key => $value) {
        if (str_contains($key, "NFS_EXPORT")) {
            $exports .= $value . PHP_EOL;
        }
    }

    if (strlen($exports) <= 0) {
        throw new Exception("No exports found");
    }

    file_put_contents("/etc/exports", $exports);
}

/**
 * @return void
 * @throws Exception
 */
function setupConfiguration(): void
{
    setupExports();
}

/**
 * @throws Exception
 */
function setup(): void
{
    setupSignals();
    setupConfiguration();
}

/**
 * @return void
 */
function stop(): void
{
    echo "Terminating" . PHP_EOL;

    list($code, $nfspid) = execute(["pidof rpc.nfsd"]);
    list($code, $mountdPid) = execute(["pidof rpc.mountd"]);
    list($code, $rpcBindPid) = execute(["pidof rpcbind"]);
    
    execute(["/usr/sbin/exportfs -uav"]);
    execute(["/usr/sbin/rpc.nfsd 0"]);

    execute(["kill -TERM $nfspid[0] $mountdPid[0] $rpcBindPid[0]"]);
}

/**
 * @return void
 */
function start(): void
{
    $numThreads = getFromEnv("NUM_THREADS") ?? DEFAULT_NUM_SERVERS;

    execute(["mount rpc_pipefs"]);
    execute(["mount nfsd"]);

    execute(["cat /etc/exports"]);

    execute(["/sbin/rpcbind -w"]);
    execute(["/sbin/rpcinfo"]);
    execute(["/usr/sbin/rpc.nfsd --host 0.0.0.0 --debug --no-udp --no-nfs-version 3 $numThreads"]);
    execute(["/usr/sbin/exportfs -rv"]);
    execute(["/usr/sbin/exportfs"]);
    execute(["/usr/sbin/rpc.mountd --debug all --no-udp --no-nfs-version 3"]);
}

/**
 * @param array $cmd
 * @param bool $debug
 * @return array
 */
function execute(array $cmd, bool $debug = true): array
{
    exec(implode(" ", $cmd), $output, $code);

    if ($debug) {
        echo implode(PHP_EOL, $output);
    }

    return [$code, $output];
}

function processExists($processName): bool
{
    $exists = false;

    list ($code, $pids) = execute(["ps -A | grep -i $processName | grep -v grep"], false);

    if (count($pids) > 0) {
        $exists = true;
    }

    return $exists;
}

/**
 * @throws Exception
 */
function init(): void
{
    setup();

    start();

    while (true) {
        // Terminates if NFS is not running
        if (!processExists("rpc.mountd")) {
            exit(0);
        }

        sleep(1);
    }
}

try {
    init();
} catch (Exception $e) {
    exit(0);
}