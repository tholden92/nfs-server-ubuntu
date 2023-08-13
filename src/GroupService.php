<?php

namespace Thomas\NfsServer;

class GroupService
{
    private ProcessService $process;
    private EntryService $entry;

    /**
     * @param ProcessService $process
     * @param EntryService $entry
     */
    public function __construct(ProcessService $process, EntryService $entry)
    {
        $this->process = $process;
        $this->entry = $entry;
    }

    /**
     * @param string $gid
     * @param string $name
     * @return void
     */
    public function create(string $gid, string $name): void
    {
        $group = $this->entry->getGroup($name);

        if ($group !== null) {
            $this->process->execute([
                "groupmod -o -g $gid $group->name"
            ]);

            return;
        }

        $this->process->execute(["groupadd -o -g $gid $name"]);
    }
}