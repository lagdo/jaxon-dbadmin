<?php

namespace Lagdo\DbAdmin\Command;

use function max;
use function microtime;

class TimerService
{
    /**
     * @var float
     */
    private float $startTimestamp = 0;

    /**
     * @var float
     */
    private float $endTimestamp = 0;

    /**
     * @return void
     */
    public function start(): void
    {
        $this->startTimestamp = microtime(true);
    }

    /**
     * @return void
     */
    public function stop(): void
    {
        $this->endTimestamp = microtime(true);
    }

    /**
     * @return float
     */
    public function duration(): float
    {
        return max(0, $this->endTimestamp - $this->startTimestamp);
    }
}
