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
        if ($this->entry->getGroup($name) === null) {
            $this->process->execute(["groupadd -g $gid $name"]);
        }
    }
}