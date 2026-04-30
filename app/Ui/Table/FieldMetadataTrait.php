<?php

namespace Lagdo\DbAdmin\App\Ui\Table;

trait FieldMetadataTrait
{
    /**
     * @var array
     */
    protected $metadata = [];

    /**
     * @return array
     */
    protected function support(): array
    {
        return $this->metadata['support'] ?? [];
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
}
