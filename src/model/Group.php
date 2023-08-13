<?php

namespace Thomas\NfsServer\model;

class Group
{
    public string $groupname;
    public string $gid;
    public array $userList;

    public function __construct($params)
    {
        $this->groupname = $params["groupname"];
        $this->gid = $params["gid"];
        $this->userList = explode(",", $params["userList"]);
    }
}