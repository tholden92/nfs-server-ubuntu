<?php

namespace Thomas\NfsServer;

use Thomas\NfsServer\model\Group;
use Thomas\NfsServer\model\User;

class EntryService
{
    private ProcessService $process;


    public function __construct(ProcessService $process)
    {
        $this->process = $process;
    }

    /**
     * @param string $identifier
     * @return Group|null
     */
    public function getUser(string $identifier): ?User
    {
        $entry = $this->getEntry($identifier, "passwd");

        if ($entry === null) {
            return null;
        }

        return new User([
            "username" => $entry[0],
            "uid" => $entry[2],
            "gid" => $entry[3],
            "home" => $entry[5],
            "shell" => $entry[6]
        ]);
    }

    /**
     * @param string $identifier
     * @return Group|null
     */
    public function getGroup(string $identifier): ?Group
    {
        $entry = $this->getEntry($identifier, "group");

        if ($entry === null) {
            return null;
        }

        return new Group([
            "groupname" => $entry[0],
            "gid" => $entry[2],
            "userList" => $entry[3]
        ]);
    }

    /**
     * @param string $identifier
     * @param string $database
     * @return string[]|null
     */
    private function getEntry(string $identifier, string $database): ?array
    {
        $output = $this->lookup($identifier, $database);

        if (count($output) <= 0) {
            return null;
        }

        $entity = explode(":", $output[0]);

        if (count($entity) < 3) {
            return null;
        }

        return $entity;
    }

    /**
     * @param string $identifier
     * @param string $database
     * @return array
     */
    private function lookup(string $identifier, string $database): array
    {
        list($code, $output) = $this->process->execute(["getent $database $identifier"], false);

        return $output;
    }
}