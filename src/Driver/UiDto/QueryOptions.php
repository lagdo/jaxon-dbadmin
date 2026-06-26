<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto;

class QueryOptions
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
    public bool $getRowsets = false;

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
     * @param bool $getRowsets
     *
     * @return void
     */
    public function setExecOptions(bool $inTransaction, bool $inBatch, bool $getRowsets): void
    {
        $this->inTransaction = $inTransaction;
        $this->inBatch = $inBatch;
        $this->getRowsets = $getRowsets;
    }
}
