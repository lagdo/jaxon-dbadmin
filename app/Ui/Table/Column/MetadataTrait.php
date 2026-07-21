<?php

namespace Lagdo\DbAdmin\App\Ui\Table\Column;

use Lagdo\DbAdmin\Driver\EngineInterface;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\TableFormDto;

use function array_combine;
use function array_map;

trait MetadataTrait
{
    /**
     * @var TableFormDto
     */
    protected TableFormDto $metadata;

    /**
     * @return EngineInterface
     */
    protected function engine(): EngineInterface
    {
        return $this->metadata->engine;
    }

    /**
     * @param array $features
     *
     * @return array
     */
    protected function support(array $features): array
    {
        return array_combine($features, array_map($this->engine()->support(...), $features));
    }

    /**
     * @return array
     */
    protected function engines(): array
    {
        return $this->engine()->engines();
    }

    /**
     * @return array
     */
    protected function collations(): array
    {
        return $this->engine()->collations();
    }

    /**
     * @return array
     */
    protected function unsigned(): array
    {
        return $this->engine()->unsigned();
    }

    /**
     * @param string $option
     *
     * @return array
     */
    protected function columnOptions(string $option): array
    {
        return $this->metadata->getColumnOptions()[$option] ?? [];
    }

    /**
     * @param string $option
     *
     * @return array
     */
    protected function foreignKeyOptions(string $option): array
    {
        return $this->metadata->getForeignKeyOptions()[$option] ?? [];
    }

    /**
     * @return array
     */
    protected function defaults(): array
    {
        return $this->engine()->columnDefaults();
    }

    /**
     * @param TableFormDto $metadata
     *
     * @return static
     */
    public function metadata(TableFormDto $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return array<ColumnFormDto>
     */
    protected function inputs(): array
    {
        return $this->metadata->columns;
    }

    /**
     * @return array<string, string>
     */
    protected function referencableColumns(): array
    {
        return $this->metadata->referencableColumns;
    }

    /**
     * @param string $fkId
     *
     * @return string
     */
    protected function referencableColumn(string $fkId): string
    {
        return $this->metadata->referencableColumns[$fkId] ?? '';
    }
}
