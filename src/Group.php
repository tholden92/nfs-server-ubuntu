<?php

namespace Thomas\NfsServer;

use Exception;

class Group
{
    public static function exists(string $identifier): bool
    {
        list($code, $output) = Process::execute(["getent group $identifier"]);
        return count($output) > 0;
    }

    /**
     * @throws Exception
     */
    public static function getNameById(string $identifier): ?string
    {
        list($code, $output) = Process::execute(["getent group $identifier 2>/dev/null"], false);

        if (count($output) !== 1) {
            throw new Exception("Could not find group with gid $identifier");
        }

        return explode(":", $output[0])[0];
    }

    public static function create(string $primary_group_identifier, string $name): void
    {
        if (!self::exists($primary_group_identifier)) {
            Process::execute(["groupadd -g $primary_group_identifier $name"]);
        }
    }
}