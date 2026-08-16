<?php

namespace Lagdo\DbAdmin\Support\Service\Admin;

use Lagdo\DbAdmin\Driver\Sql\Connection\QueryResultInterface;
use Lagdo\DbAdmin\Support\Driver\QueryCallback;
use DateTimeImmutable;

use function max;
use function microtime;
use function sprintf;

class QueryTimer implements QueryCallback
{
    /**
     * @var bool
     */
    private bool $enabled = false;

    /**
     * @var float
     */
    private float $startTimestamp = 0;

    /**
     * @var float
     */
    private float $endTimestamp = 0;

    /**
     * @param bool|null $enabled
     *
     * @return bool
     */
    public function enabled(bool|null $enabled = null): bool
    {
        if ($this->enabled !== null) {
            $this->enabled = $enabled;
        }
        return $this->enabled;
    }

    /**
     * @inheritDoc
     */
    public function beforeQueryExec(string $query): void
    {
        $this->startTimestamp = microtime(true);
    }

    /**
     * @inheritDoc
     */
    public function afterQueryExec(string $query, QueryResultInterface|bool $result): void
    {
        $this->endTimestamp = microtime(true);
    }

    /**
     * @return string
     */
    public function startTime(): string
    {
        $startTimestamp = DateTimeImmutable::createFromFormat('U.u',
            sprintf('%.6F', $this->startTimestamp));
        return $startTimestamp->format('Y-m-d H:i:s.u');;
    }

    /**
     * @param bool $asFloat
     *
     * @return float|int
     */
    public function duration(bool $asFloat = true): float|int
    {
        $interval = max(0, $this->endTimestamp - $this->startTimestamp);
        return $asFloat ? $interval : (int)($interval * 1_000_000);
    }
}
