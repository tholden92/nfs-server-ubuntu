<?php

namespace Thomas\NfsServer;

use Exception;

class User
{
    private Process $process;
    private Entity $entity;

    /**
     * @param Process $process
     * @param Entity $entity
     */
    function __construct(Process $process, Entity $entity)
    {
        $this->process = $process;
        $this->entity = $entity;
    }

    /**
     * @throws Exception
     */
    public function create($name, $uid, $guid): void
    {
        $group = $this->entity->getGroup($guid);

        if ($group === null) {
            throw new Exception("Group does not exist");
        }

        list($groupName, $groupId) = $group;

        $this->process->execute([
            sprintf("useradd -u %s -g %s %s", $uid, $groupName, $name)
        ]);
    }

    /**
     * @throws Exception
     */
    public function addToGroup($name, $gid): void
    {
        $group = $this->entity->getGroup($gid);

        if ($group === null) {
            throw new Exception("Group does not exist");
        }

        list($groupName, $groupId) = $group;

        $this->process->execute([sprintf("gpasswd -a %s %s", $name, $groupName)]);
    }
}