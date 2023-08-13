<?php

namespace Thomas\NfsServer\model;

class User
{
    public string $username;
    public string $uid;
    public string $gid;
    public string $home;
    public string $shell;

    public function __construct(array $params)
    {
        $this->username = $params["username"];
        $this->uid = $params["uid"];
        $this->gid = $params["gid"];
        $this->home = $params["home"];
        $this->shell = $params["shell"];
    }
}