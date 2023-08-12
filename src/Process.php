<?php

namespace Thomas\NfsServer;

class Process
{
    /**
     * @param array $cmd
     * @param bool $debug
     * @return array
     */
    public static function execute(array $cmd, bool $debug = true): array
    {
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

    public static function exists($processName): bool
    {
        $exists = false;

        list ($code, $pids) = self::execute(["ps -A | grep -i $processName | grep -v grep"], false);

        if (count($pids) > 0) {
            $exists = true;
        }

        return $exists;
    }

}