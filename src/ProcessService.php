<?php

namespace Thomas\NfsServer;

class ProcessService
{
    /**
     * @param array $cmd
     * @param bool $debug
     * @return array
     */
    public function execute(array $cmd, bool $debug = true): array
    {
        if ($debug) {
            echo "Executing command: " . implode(PHP_EOL, $cmd) . PHP_EOL;
        }

        exec(implode(" ", $cmd), $outputs, $code);

        if ($debug) {
            foreach ($outputs as $output) {
                if (strlen($output) > 0) {
                    echo $output . PHP_EOL;
                }
            }
        }

        return [$code, $outputs];
    }

    public function exists($processName): bool
    {
        $exists = false;

        list ($code, $pids) = $this->execute(["ps -A | grep -i $processName | grep -v grep"], false);

        if (count($pids) > 0) {
            $exists = true;
        }

        return $exists;
    }

}