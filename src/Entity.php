<?php

namespace Thomas\NfsServer;

class Entity
{
    private Process $process;


    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    /**
     * @param string $identifier
     * @return array|null
     */
    public function getUser(string $identifier): ?array
    {
        $output = $this->lookup($identifier, "passwd");

        if (count($output) <= 0) {
            return null;
        }

        $entity = explode(":", $output[0]);

        if (count($entity) < 3) {
            return null;
        }

        return [
            $entity[0],
            $entity[2]
        ];
    }

    /**
     * @param string $identifier
     * @return array|null
     */
    public function getGroup(string $identifier): ?array
    {
        $output = $this->lookup($identifier, "group");

        if (count($output) <= 0) {
            return null;
        }

        $entity = explode(":", $output[0]);

        if (count($entity) < 3) {
            return null;
        }

        return [
            $entity[0],
            $entity[2]
        ];
    }

    /**
     * @param string $identifier
     * @param string $database
     * @return array
     */
    private function lookup(string $identifier, string $database): array
    {
        list($code, $output) = $this->process->execute(["getent $database $identifier"]);

        return $output;
    }
}