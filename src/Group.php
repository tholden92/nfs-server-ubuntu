<?php

namespace Thomas\NfsServer;

class Group
{
    private Process $process;
    private Entity $entity;

    public function __construct(Process $process, Entity $entity)
    {
        $this->process = $process;
        $this->entity = $entity;
    }


    public function create(string $gid, string $name): void
    {
        $this->process->execute(["groupadd -g $gid $name"]);
    }
}