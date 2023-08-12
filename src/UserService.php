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

        echo "Creating user " . $name . PHP_EOL;

        $user = $this->entry->getUser($name);

        if ($user !== null) {
            $this->process->execute([
                "usermod -u $uid $name"
            ]);

            return;
        }

        $this->process->execute([
            sprintf("useradd -u %s -g %s %s", $uid, $group->name, $name)
        ]);
    }

    /**
     * @param string $name
     * @param string $gid
     * @return void
     * @throws Exception
     */
    public function addToGroup(string $name, string $gid): void
    {
        $group = $this->entry->getGroup($gid);

        if ($group === null) {
            throw new Exception("Group does not exist");
        }

        $this->process->execute([
            sprintf("gpasswd -a %s %s", $name, $group->name)
        ]);
    }
}