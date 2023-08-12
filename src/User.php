<?php

namespace Thomas\NfsServer;

use Exception;

class User
{
    private Process $process;
    private Group $group;

    function __construct(Process $process, Group $group)
    {
        $this->process = $process;
        $this->group = $group;
    }

    public function exists(string $identifier): bool
    {
        list($code, $output) = $this->process->execute(["getent passwd $identifier"]);
        return count($output) > 0;
    }

    /**
     * @throws Exception
     */
    public function create($name, $uid, $guid): void
    {
        if (!self::exists($uid)) {
            $this->process->execute([sprintf("useradd -u %s -g %s %s", $uid, $this->group->getNameById($guid), $name)]);
        }
    }

    /**
     * @throws Exception
     */
    public function addToGroup($name, $gid): void
    {
        $this->process->execute([sprintf("gpasswd -a %s %s", $name, $this->group->getNameById($gid))]);
    }
}