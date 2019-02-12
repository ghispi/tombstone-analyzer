<?php

namespace Scheb\Tombstone\Analyzer;

interface ResultAggregateInterface
{
    public function containsTombstonesInSourceCode(): bool;

    public function getDeadCount(): int;

    public function getUndeadCount(): int;

    public function getDeletedCount(): int;
}
