<?php

namespace Lagdo\DbAdmin\Ui\Table;

trait FieldMetadataTrait
{
    /**
     * @var array
     */
    protected $support = [];

    /**
     * @var array
     */
    protected $engines = [];

    /**
     * @var array
     */
    protected $collations = [];

    /**
     * @var array
     */
    protected $unsigned = [];

    /**
     * @var array
     */
    protected $foreignKeys = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param array $support
     *
     * @return self
     */
    public function support(array $support): self
    {
        $this->support = $support;
        return $this;
    }

    /**
     * @param array $engines
     *
     * @return self
     */
    public function engines(array $engines): self
    {
        $this->engines = $engines;
        return $this;
    }

    /**
     * @param array $collations
     *
     * @return self
     */
    public function collations(array $collations): self
    {
        $this->collations = $collations;
        return $this;
    }

    /**
     * @param array $unsigned
     *
     * @return self
     */
    public function unsigned(array $unsigned): self
    {
        $this->unsigned = $unsigned;
        return $this;
    }

    /**
     * @param array $foreignKeys
     *
     * @return self
     */
    public function foreignKeys(array $foreignKeys): self
    {
        $this->foreignKeys = $foreignKeys;
        return $this;
    }

    /**
     * @param array $columns
     *
     * @return self
     */
    public function columns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @param array $options
     *
     * @return self
     */
    public function options(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param array $metadata
     *
     * @return self
     */
    public function metadata(array $metadata): self
    {
        return $this->support($metadata['support'])
            ->engines($metadata['engines'])
            ->collations($metadata['collations'])
            ->unsigned($metadata['unsigned'] ?? [])
            ->foreignKeys($metadata['foreignKeys'])
            ->options($metadata['options']);
    }
}
