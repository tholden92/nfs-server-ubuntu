<?php

namespace Thomas\NfsServer;

use Thomas\NfsServer\model\Entry;

class EntryService
{
    private ProcessService $process;


    public function __construct(ProcessService $process)
    {
        $this->process = $process;
    }

    /**
     * @param string $identifier
     * @return Entry|null
     */
    public function getUser(string $identifier): ?Entry
    {
        return $this->getEntry($identifier, "passwd");
    }

    /**
     * @param string $identifier
     * @return Entry|null
     */
    public function getGroup(string $identifier): ?Entry
    {
        return $this->getEntry($identifier, "group");
    }

    /**
     * @param string $identifier
     * @param string $database
     * @return Entry|null
     */
    private function getEntry(string $identifier, string $database): ?Entry
    {
        $output = $this->lookup($identifier, $database);

        if (count($output) <= 0) {
            return null;
        }

        $entity = explode(":", $output[0]);

        if (count($entity) < 3) {
            return null;
        }

        return new Entry($entity[2], $entity[0]);
    }

    /**
     * @param string $identifier
     * @param string $database
     * @return array
     */
    private function lookup(string $identifier, string $database): array
    {
        list($code, $output) = $this->process->execute(["getent $database $identifier"], false);

        return $output;
    }
}