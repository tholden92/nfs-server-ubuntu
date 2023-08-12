<?php

use Thomas\NfsServer\Group;
use Thomas\NfsServer\Process;
use Thomas\NfsServer\User;

require_once "vendor/autoload.php";

declare(ticks=1);

const DEFAULT_NUM_SERVERS = 8;

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
 */
function stop(): void
{
    echo "Terminating" . PHP_EOL;

    list($code, $nfspid) = Process::execute(["pidof rpc.nfsd"]);
    list($code, $mountdPid) = Process::execute(["pidof rpc.mountd"]);
    list($code, $rpcBindPid) = Process::execute(["pidof rpcbind"]);

    Process::execute(["/usr/sbin/exportfs -uav"]);
    Process::execute(["/usr/sbin/rpc.nfsd 0"]);

    Process::execute(["kill -TERM $nfspid[0] $mountdPid[0] $rpcBindPid[0]"]);

    exit(true);
}

function setupIdMapD()
{
    $domain = getFromEnv("IDMAP_DOMAIN");
    $username = getFromEnv("IDMAP_USERNAME");
    $group = getFromEnv("IDMAP_GROUP");

    if ($domain === null) {
        return null;
    }

    $config = file_get_contents("idmapd.conf");

    $config = str_replace("{domain}", $domain, $config);
    $config = str_replace("{user}", $username !== null ? $username : "nobody", $config);
    $config = str_replace("{group}", $group !== null ? $group : "nogroup", $config);

    file_put_contents("/etc/idmapd.conf", $config);

    // Modern kernels disables idmap when using auth=sys, so enable it...
    Process::execute(["echo \"N\" > /sys/module/nfsd/parameters/nfs4_disable_idmapping"]);

    // Start daemon and pipe to stdout
    Process::execute(["rpc.idmapd -f -vvvvvvv 1>&2 &"]);

    // Wait for daemon to became active
    while (!Process::exists("rpc.idmapd")) {
        sleep(1);
    }
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
 * @throws Exception
 */
function setupUsers(): void
{
    $index = 0;

    while (getFromEnv("USER_{$index}_NAME") !== null) {
        $index++;
    }

    $largestIndex = $index;

    for ($i = 0; $i < $largestIndex; $i++) {
        $userKeyPrefix = "USER_{$i}_";

        $name = getFromEnv($userKeyPrefix . "NAME");
        $identifier = getFromEnv($userKeyPrefix . "IDENTIFIER");
        $primary_group_identifier = getFromEnv($userKeyPrefix . "PRIMARY_GROUP_IDENTIFIER");

        $secondary_group_identifiers = getFromEnv($userKeyPrefix . "SECONDARY_GROUP_IDENTIFIERS");
        $secondary_group_names = getFromEnv($userKeyPrefix . "SECONDARY_GROUP_NAMES");

        if ($name === null || $identifier === null || $primary_group_identifier === null) {
            continue;
        }

        Group::create($primary_group_identifier, $name);
        User::create($name, $identifier, $primary_group_identifier);

        if ($secondary_group_identifiers === null || $secondary_group_names === null) {
            continue;
        }

        $secondary_groups = explode(",", $secondary_group_identifiers);
        $secondary_groups_names = explode(",", $secondary_group_names);

        if (count($secondary_groups) !== count($secondary_groups_names)) {
            continue;
        }

        for ($j = 0; $j < count($secondary_groups); $j++) {
            $groupId = $secondary_groups[$j];
            $groupName = $secondary_groups_names[$j];

            Group::create($groupId, $groupName);
            User::addToGroup($name, $groupId);
        }
    }
}

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

/**
 * @return void
 */
function start(): void
{
    $numThreads = getFromEnv("NUM_THREADS") ?? DEFAULT_NUM_SERVERS;

    Process::execute(["mount rpc_pipefs"]);
    Process::execute(["mount nfsd"]);

    Process::execute(["cat /etc/exports"]);

    Process::execute(["/sbin/rpcbind -s -d"]);
    Process::execute(["/sbin/rpcinfo"]);

    Process::execute(["/usr/sbin/exportfs -rv"]);
    Process::execute(["/usr/sbin/exportfs"]);

    Process::execute(["/usr/sbin/rpc.mountd --debug all --no-udp --no-nfs-version 3"]);

    setupIdMapD();

    Process::execute(["/usr/sbin/rpc.nfsd --host 0.0.0.0 --debug --no-udp --no-nfs-version 3 $numThreads"]);
}

/**
 * @throws Exception
 */
function init(): void
{
    setupSignals();
    setupUsers();
    setupExports();

    start();

    while (true) {
        // Terminates if NFS is not running
        if (!Process::exists("rpc.mountd")) {
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