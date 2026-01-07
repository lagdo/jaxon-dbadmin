<?php

namespace Lagdo\DbAdmin\Ui\Table;

trait FieldMetadataTrait
{
    /**
     * @var array
     */
    protected $metadata = [];

    /**
     * @return self
     */
    protected function support(): array
    {
        return $this->metadata['support'] ?? [];
    }

    /**
     * @return self
     */
    protected function engines(): array
    {
        return $this->metadata['engines'] ?? [];
    }

    /**
     * @return self
     */
    protected function collations(): array
    {
        return $this->metadata['collations'] ?? [];
    }

    /**
     * @return self
     */
    protected function unsigned(): array
    {
        return $this->metadata['unsigned'] ?? [];
    }

    /**
     * @return self
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
     * @return self
     */
    public function metadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }
}
