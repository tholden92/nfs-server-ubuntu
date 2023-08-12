<?php

namespace Thomas\NfsServer;

use Exception;

class User
{
    public static function exists(string $identifier): bool
    {
        list($code, $output) = Process::execute(["getent passwd $identifier"]);
        return count($output) > 0;
    }

    /**
     * @throws Exception
     */
    public static function create($name, $uid, $guid): void
    {
        if (!self::exists($uid)) {
            Process::execute([sprintf("useradd -u %s -g %s %s", $uid, Group::getNameById($guid), $name)]);
        }
    }

    /**
     * @throws Exception
     */
    public static function addToGroup($name, $gid): void
    {
        Process::execute([sprintf("gpasswd -a %s %s", $name, Group::getNameById($gid))]);
    }
}