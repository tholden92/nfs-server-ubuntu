<?php

namespace Thomas\NfsServer;

use Exception;

class UserService
{
    private ProcessService $process;
    private EntryService $entry;

    /**
     * @param ProcessService $process
     * @param EntryService $entry
     */
    function __construct(ProcessService $process, EntryService $entry)
    {
        $this->process = $process;
        $this->entry = $entry;
    }

    /**
     * @param string $name
     * @param string $uid
     * @param string $guid
     * @return void
     * @throws Exception
     */
    public function create(string $name, string $uid, string $guid): void
    {
        $group = $this->entry->getGroup($guid);

        if ($group === null) {
            throw new Exception("Group does not exist");
        }

        $user = $this->entry->getUser($name);

        if ($user !== null) {
            $this->process->execute([
                "usermod -o -u $uid $name"
            ]);

            return;
        }

        $this->process->execute([
            sprintf("useradd -o -u %s -g %s %s", $uid, $group->groupname, $name)
        ]);
    }

    /**
     * @param string $name
     * @param string $groupName
     * @return void
     */
    public function addToGroup(string $name, string $groupName): void
    {
        $this->process->execute([
            sprintf("gpasswd -a %s %s", $name, $groupName)
        ]);
    }
}