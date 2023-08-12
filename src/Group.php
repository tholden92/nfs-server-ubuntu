<?php

namespace Thomas\NfsServer;

use Exception;

class Group
{
    private Process $process;

    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    public function exists(string $identifier): bool
    {
        list($code, $output) = $this->process->execute(["getent group $identifier"]);
        return count($output) > 0;
    }

    public function create(string $primary_group_identifier, string $name): void
    {
        if (!$this->exists($primary_group_identifier)) {
            $this->process->execute(["groupadd -g $primary_group_identifier $name"]);
        }
    }

    /**
     * @throws Exception
     */
    public function getNameById(string $identifier): ?string
    {
        list($code, $output) = $this->process->execute(["getent group $identifier 2>/dev/null"], false);

        if (count($output) !== 1) {
            throw new Exception("Could not find group with gid $identifier");
        }

        return explode(":", $output[0])[0];
    }
}