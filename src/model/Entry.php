<?php

namespace Thomas\NfsServer\model;

class Entry
{
    public string $name;
    public string $id;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}