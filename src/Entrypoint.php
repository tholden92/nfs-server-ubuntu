<?php

namespace Thomas\NfsServer;

use DI\Container;
use Exception;

require_once "vendor/autoload.php";

declare(ticks=1);

const DEFAULT_NUM_SERVERS = 8;

class Entrypoint
{
    private Process $process;
    private Group $group;
    private User $user;

    public function __construct(Process $process, Group $group, User $user)
    {
        $this->process = $process;
        $this->group = $group;
        $this->user = $user;
    }

    private function getFromEnv(?string $param)
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
    private function stop(): void
    {
        echo "Terminating" . PHP_EOL;

        list($code, $nfspid) = $this->process->execute(["pidof rpc.nfsd"]);
        list($code, $mountdPid) = $this->process->execute(["pidof rpc.mountd"]);
        list($code, $rpcBindPid) = $this->process->execute(["pidof rpcbind"]);

        $pids = array_filter([$nfspid[0] ?? null, $mountdPid[0] ?? null, $rpcBindPid[0] ?? null], function ($pid) {
            return $pid !== null;
        });

        $this->process->execute(["/usr/sbin/exportfs -uav"]);
        $this->process->execute(["/usr/sbin/rpc.nfsd 0"]);

        $this->process->execute([sprintf("kill -TERM %s", implode(" ", $pids))]);
    }

    private function setupIdMapD()
    {
        $domain = $this->getFromEnv("IDMAP_DOMAIN");
        $username = $this->getFromEnv("IDMAP_USERNAME");
        $group = $this->getFromEnv("IDMAP_GROUP");

        if ($domain === null) {
            return null;
        }

        $config = file_get_contents("idmapd.conf");

        $config = str_replace("{domain}", $domain, $config);
        $config = str_replace("{user}", $username !== null ? $username : "nobody", $config);
        $config = str_replace("{group}", $group !== null ? $group : "nogroup", $config);

        file_put_contents("/etc/idmapd.conf", $config);

        // Modern kernels disables idmap when using auth=sys, so enable it...
        $this->process->execute(["echo \"N\" > /sys/module/nfsd/parameters/nfs4_disable_idmapping"]);

        // Start daemon and pipe to stdout
        $this->process->execute(["rpc.idmapd -f -vvvvvvv 1>&2 &"]);

        // Wait for daemon to became active
        while (!$this->process->exists("rpc.idmapd")) {
            sleep(1);
        }
    }


    /**
     * @return void
     * @throws Exception
     */
    private function setupExports(): void
    {
        $env = self::getFromEnv(null);

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
    private function setupUsers(): void
    {
        $index = 0;

        while (self::getFromEnv("USER_{$index}_NAME") !== null) {
            $index++;
        }

        $largestIndex = $index;

        for ($i = 0; $i < $largestIndex; $i++) {
            $userKeyPrefix = "USER_{$i}_";

            $name = self::getFromEnv($userKeyPrefix . "NAME");
            $identifier = self::getFromEnv($userKeyPrefix . "IDENTIFIER");
            $primary_group_identifier = self::getFromEnv($userKeyPrefix . "PRIMARY_GROUP_IDENTIFIER");

            $secondary_group_identifiers = self::getFromEnv($userKeyPrefix . "SECONDARY_GROUP_IDENTIFIERS");
            $secondary_group_names = self::getFromEnv($userKeyPrefix . "SECONDARY_GROUP_NAMES");

            if ($name === null || $identifier === null || $primary_group_identifier === null) {
                continue;
            }

            $this->group->create($primary_group_identifier, $name);
            $this->user->create($name, $identifier, $primary_group_identifier);

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

                $this->group->create($groupId, $groupName);
                $this->user->addToGroup($name, $groupId);
            }
        }
    }

    /**
     * @return void
     */
    private function setupSignals(): void
    {
        pcntl_signal(SIGTERM, function () {
            $this->stop();
        });

        pcntl_signal(SIGHUP, function () {
            $this->stop();
        });

        pcntl_signal(SIGINT, function () {
            $this->stop();
        });

        pcntl_signal(SIGWINCH, function () {
            $this->stop();
        });
    }

    /**
     * @return void
     */
    private function start(): void
    {
        $numThreads = self::getFromEnv("NUM_THREADS") ?? DEFAULT_NUM_SERVERS;

        $this->process->execute(["mount rpc_pipefs"]);
        $this->process->execute(["mount nfsd"]);

        $this->process->execute(["cat /etc/exports"]);

        $this->process->execute(["/sbin/rpcbind -s -d"]);
        $this->process->execute(["/sbin/rpcinfo"]);

        $this->process->execute(["/usr/sbin/exportfs -rv"]);
        $this->process->execute(["/usr/sbin/exportfs"]);

        $this->process->execute(["/usr/sbin/rpc.mountd --debug all --no-udp --no-nfs-version 3"]);

        $this->setupIdMapD();

        $this->process->execute(["/usr/sbin/rpc.nfsd --host 0.0.0.0 --debug --no-udp --no-nfs-version 3 $numThreads"]);
    }

    /**
     * @throws Exception
     */
    public function init(): void
    {
        $this->setupSignals();
        $this->setupUsers();
        $this->setupExports();

        $this->start();

        while (true) {
            // Terminates if NFS is not running
            if (!$this->process->exists("rpc.mountd")) {
                exit(0);
            }

            sleep(1);
        }
    }
}

try {
    $container = new Container();

    $entrypoint = $container->get(Entrypoint::class);
    $entrypoint->init();
} catch (Exception $e) {
    echo $e->getTraceAsString() . PHP_EOL;
    exit(0);
}