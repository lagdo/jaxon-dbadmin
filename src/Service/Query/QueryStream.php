<?php

namespace Lagdo\DbAdmin\Support\Service\Query;

use Closure;

class QueryStream
{
    /**
     * @var string
     */
    public string $queryLine = '';

    /**
     * @var string
     */
    public string $inputLine = '';

    /**
     * @var int
     */
    public int $lineNumber = 0;

    /**
     * @var array<string>
     */
    public array $queryBuffer = [];

    /**
     * @var int
     */
    public int $queryCount = 0;

    /**
     * @var string
     */
    public string $queryDelimiter = ';';

    /**
     * @var string
     */
    public string $pregQueryDelimiter = ';';

    /**
     * @var QueryStreamContext
     */
    public QueryStreamContext $context;

    /**
     * @var string
     */
    public string $functionDelimiterRegex = '';

    /**
     * @param Closure $queryLineReader
     */
    public function __construct(public Closure $queryLineReader)
    {}
}
