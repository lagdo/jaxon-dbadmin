<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto;

class ExecOptions
{
    /**
     * @var bool
     */
    public bool $inTransaction = false;

    /**
     * @var bool
     */
    public bool $inBatch = false;

    /**
     * @var bool
     */
    public bool $saveResults = false;

    /**
     * @var bool
     */
    public bool $decompressFile = false;

    /**
     * @var bool
     */
    public bool $withTimer = false;

    /**
     * @var bool
     */
    public bool $withLogger = false;

    /**
     * @param bool $stopOnError
     * @param bool $onlyErrors
     * @param int $limit
     */
    public function __construct(public bool $stopOnError = false,
        public bool $onlyErrors = false, public int $limit = 0)
    {}

    /**
     * @param bool $inTransaction
     * @param bool $inBatch
     * @param bool $saveResults
     *
     * @return void
     */
    public function setExecOptions(bool $inTransaction, bool $inBatch, bool $saveResults): void
    {
        $this->inTransaction = $inTransaction;
        $this->inBatch = $inBatch;
        $this->saveResults = $saveResults;
    }
}
