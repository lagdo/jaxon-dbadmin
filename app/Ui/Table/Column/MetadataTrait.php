<?php

namespace Lagdo\DbAdmin\App\Ui\Table\Column;

use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;

use function array_combine;
use function array_map;

trait MetadataTrait
{
    /**
     * @var array
     */
    protected $metadata = [];

    /**
     * @param array $features
     *
     * @return array
     */
    protected function support(array $features): array
    {
        $support = $this->metadata['support'] ?? fn() => false;
        return array_combine($features, array_map($support, $features));
    }

    /**
     * @return array
     */
    protected function engines(): array
    {
        return $this->metadata['engines'] ?? [];
    }

    /**
     * @return array
     */
    protected function collations(): array
    {
        return $this->metadata['collations'] ?? [];
    }

    /**
     * @return array
     */
    protected function unsigned(): array
    {
        return $this->metadata['unsigned'] ?? [];
    }

    /**
     * @return array
     */
    protected function foreignKeys(): array
    {
        return $this->metadata['foreignKeys'] ?? [];
    }

    /**
     * @return array
     */
    protected function options(): array
    {
        return $this->metadata['options'] ?? [];
    }

    /**
     * @return array
     */
    protected function defaults(): array
    {
        return $this->metadata['defaults'] ?? [];
    }

    /**
     * @param array $metadata
     *
     * @return static
     */
    public function metadata(array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @var array<ColumnFormDto>
     */
    protected function inputs(): array
    {
        return $this->metadata['table']->columns;
    }
}
