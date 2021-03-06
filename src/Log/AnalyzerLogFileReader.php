<?php

declare(strict_types=1);

namespace Scheb\Tombstone\Analyzer\Log;

use Scheb\Tombstone\Analyzer\Exception\LogReaderException;
use Scheb\Tombstone\Analyzer\VampireIndex;
use Scheb\Tombstone\Logging\AnalyzerLogFormat;

class AnalyzerLogFileReader
{
    public function aggregateLog(string $file, VampireIndex $vampireIndex): void
    {
        $handle = fopen($file, 'r');
        if (false === $handle) {
            throw new LogReaderException('Could not read log file '.$file);
        }

        while (false !== ($line = fgets($handle))) {
            $vampire = AnalyzerLogFormat::logToVampire($line);
            if ($vampire) {
                $vampireIndex->addVampire($vampire);
            }
        }
        fclose($handle);
    }
}
