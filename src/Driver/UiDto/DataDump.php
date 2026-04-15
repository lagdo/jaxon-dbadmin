<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto;

use function implode;
use function strlen;

class DataDump
{
    /**
     * @param string $table
     * @param int $maxRowSize
     * @param string $separator
     * @param string $insert
     * @param array $dataRows
     * @param int $dataSize
     * @param string $suffix
     */
    public function __construct(public string $table, public int $maxRowSize,
        public string $separator, public string $insert = '', public array $dataRows = [],
        public int $dataSize = 0, public string $suffix = '')
    {}

    /**
     * @param array $row
     *
     * @return void
     */
    public function addRow(array $row): void
    {
        $dataRow = '(' . implode(",\t", $row) . ')';
        $this->dataRows[] = $dataRow;
        $this->dataSize += strlen($dataRow) + 2; // 2 chars for the separator.
    }

    /**
     * @return bool
     */
    public function limitExceeded(): bool
    {
        // Set a limit to the size of a single INSERT query.
        // 4 - length specification
        return $this->dataSize + 4 + strlen($this->suffix) >= $this->maxRowSize;
    }

    /**
     * @return string
     */
    public function makeQuery(): string
    {
        $rows = implode(",$this->separator", $this->dataRows);
        $this->dataRows = [];
        $this->dataSize = 0;

        return "{$this->insert}{$this->separator}$rows{$this->suffix}";
    }
}
