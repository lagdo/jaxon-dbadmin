<?php

namespace Lagdo\DbAdmin\Support\Service\Query;

use Generator;

use function array_filter;
use function array_reverse;
use function implode;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace_callback;
use function strlen;
use function strpos;
use function strtoupper;
use function str_repeat;
use function substr;
use function substr_replace;
use function trim;

class QuerySplitter
{
    /**
     * @var string
     */
    private string $queryRegex = "(?:[^'\"`]*?(?:'[^'\"`]*'|\"[^'\"`]*\"|`[^'\"`]*`))";

    /**
     * @var string
     */
    private string $functionDelimiterRegex = "\\$[a-z0-9]*?\\$";

    /**
     * @var string
     */
    private string $delimiterQueryRegex = "~^\\s*+DELIMITER\\s+(\\S+)~i";

    /**
     * @param QueryStream $stream
     *
     * @return string
     */
    private function getBufferedQuery(QueryStream $stream): string
    {
        $query = trim(implode('', $stream->queryBuffer));
        $stream->queryBuffer = [];

        return $query;
    }

    /**
     * @param QueryStream $stream
     *
     * @return int|null
     */
    private function findEndOfQuery(QueryStream $stream): int|null
    {
        $offset = strpos($stream->inputLine, $stream->queryDelimiter);
        return $offset === false ? null : $offset;
    }

    /**
     * Return the delimiter position, or null.
     *
     * @param QueryStream $stream
     * @param string $delimiter
     * @param bool $withLength
     *
     * @return int|null
     */
    private function findDelimiterPosition(QueryStream $stream,
        string $delimiter, bool $withLength): int|null
    {
        $regex = "/^{$this->queryRegex}*[^'\"`]*\$/s";
        $offset = 0;
        $delimiterLength = strlen($delimiter);

        // Todo: can this be done with a single regex?
        while (($offset = strpos($stream->inputLine, $delimiter, $offset)) !== false) {
            // Take only the delimiters not enclosed into quotes or double quotes.
            if (preg_match($regex, substr($stream->inputLine, 0, $offset), $matches)) {
                return $withLength ? $offset + $delimiterLength : $offset;
            }

            $offset += $delimiterLength;
        }

        return null;
    }

    /**
     * @param QueryStream $stream
     *
     * @return bool
     */
    private function parseMultiLineCommentEnd(QueryStream $stream): bool
    {
        // Find the end of comment delimiter.
        $offset = $this->findDelimiterPosition($stream, '*/', true);
        if ($offset === null) {
            // Middle of a multiline comment. Skip the line.
            return false;
        }

        // Last line of a multiline comment. Truncate the start.
        $this->truncateStartOfLine($stream, $offset);

        // Switch out of the comment context.
        $stream->context = QueryStreamContext::NONE;

        return true;
    }

    /**
     * @param QueryStream $stream
     *
     * @return void
     */
    private function bufferLineContent(QueryStream $stream): void
    {
        if ($stream->queryLine !== '') {
            $stream->queryBuffer[] = $stream->queryLine;
            $stream->queryLine = '';
        }
    }

    /**
     * @param QueryStream $stream
     * @param int $offset
     *
     * @return void
     */
    private function bufferEndOfQuery(QueryStream $stream, int $offset): void
    {
        // Copy the start of the line to the buffer.
        $stream->queryBuffer[] = substr($stream->queryLine, 0, $offset);
        // Truncate the start of the line.
        $this->truncateStartOfLine($stream, $offset + strlen($stream->queryDelimiter));
    }

    /**
     * @param QueryStream $stream
     * @param int $length
     *
     * @return void
     */
    private function truncateStartOfLine(QueryStream $stream, int $length): void
    {
        $stream->queryLine = substr($stream->queryLine, $length);
        $stream->inputLine = substr($stream->inputLine, $length);
    }

    /**
     * @param QueryStream $stream
     * @param int $offset
     *
     * @return void
     */
    private function truncateEndOfLine(QueryStream $stream, int $offset): void
    {
        $stream->queryLine = substr($stream->queryLine, 0, $offset);
        $stream->inputLine = substr($stream->inputLine, 0, $offset);
    }

    /**
     * @param QueryStream $stream
     * @param int $offset
     * @param int $length
     *
     * @return void
     */
    private function truncatePartOfLine(QueryStream $stream, int $offset, int $length): void
    {
        $stream->queryLine = substr($stream->queryLine, 0, $offset) .
            substr($stream->queryLine, $offset + $length);
        $stream->inputLine = substr($stream->inputLine, 0, $offset) .
            substr($stream->inputLine, $offset + $length);
    }

    /**
     * @param QueryStream $stream
     * @param int $length
     *
     * @return void
     */
    private function maskStartOfLine(QueryStream $stream, int $length): void
    {
        $spaces = str_repeat(' ', $length);
        $stream->inputLine = substr_replace($stream->inputLine, $spaces, 0, $length);
    }

    /**
     * @param QueryStream $stream
     * @param int $offset
     *
     * @return void
     */
    private function maskEndOfLine(QueryStream $stream, int $offset): void
    {
        $length = strlen($stream->inputLine) - $offset;
        $spaces = str_repeat(' ', $length);
        $stream->inputLine = substr_replace($stream->inputLine, $spaces, $offset, $length);
    }

    /**
     * @param QueryStream $stream
     *
     * @return bool
     */
    private function parseMultiLineStringEnd(QueryStream $stream): bool
    {
        $regex = "/('\s*)/s";
        $flags = PREG_OFFSET_CAPTURE;
        $found = preg_match($regex, $stream->inputLine, $matches, $flags);
        if (!$found) {
            // Middle of a multiline string. Add the line to the buffer.
            $this->bufferLineContent($stream);
            return false;
        }

        // Last line of a multiline string.
        $this->maskStartOfLine($stream, $matches[1][1] + strlen($matches[1][0]));

        // Switch out of the string context.
        $stream->context = QueryStreamContext::NONE;

        return true;
    }

    /**
     * @param QueryStream $stream
     *
     * @return bool
     */
    private function parseMultiLineFunctionEnd(QueryStream $stream): bool
    {
        // Find the end of function delimiter.
        $regex = "/{$stream->functionDelimiterRegex}/si";
        $flags = PREG_OFFSET_CAPTURE;
        $found = preg_match($regex, $stream->inputLine, $matches, $flags);
        if (!$found) {
            // Middle of a multiline function. Add the line to the buffer.
            $this->bufferLineContent($stream);
            return false;
        }

        // Last line of a multiline function.
        $this->maskStartOfLine($stream, $matches[1][1] + strlen($matches[1][0]));

        // Switch out of the function context.
        $stream->context = QueryStreamContext::NONE;
        $stream->functionDelimiterRegex = '';

        return true;
    }

    /**
     * @param QueryStream $stream
     *
     * @return void
     */
    private function parseTokensAfterDelimiter(QueryStream $stream): void
    {
        // Find the start of comment or multiline string.
        $regex = "/('|--|\/\*|#|BEGIN|{$this->functionDelimiterRegex})/si";
        $flags = PREG_OFFSET_CAPTURE;
        $found = preg_match($regex, $stream->inputLine, $matches, $flags);
        // Nothing found.
        if (!$found) {
            return;
        }

        $delimiter = $matches[0][0];
        $offset = $matches[0][1];

        // Start of multiline string found.
        if ($delimiter === "'") {
            // Mask the end of the line.
            $this->maskEndOfLine($stream, $offset);
            // Switch into the string context.
            $stream->context = QueryStreamContext::MULTILINE_STRING;
            return;
        }

        // Start of multiline comment found.
        if ($delimiter === '/*') {
            // Truncate the end of the line.
            $this->truncateEndOfLine($stream, $offset);
            // Switch into the comment context.
            $stream->context = QueryStreamContext::MULTILINE_COMMENT;
            return;
        }

        // Single line comment found.
        if ($delimiter === '--' || $delimiter === '#') {
            // Truncate the end of the line.
            $this->truncateEndOfLine($stream, $offset);
            return;
        }

        // Start of multiline function found
        // Mask the end of the line.
        $this->maskEndOfLine($stream, $offset);
        // Switch into the function context.
        $stream->context = QueryStreamContext::MULTILINE_FUNCTION;
        // "END" must always be followed by a delimiter. Not "END IF", for example.
        $stream->functionDelimiterRegex = strtoupper($delimiter) === 'BEGIN' ?
            "(END)\\s*{$stream->pregQueryDelimiter}" : '(' . preg_quote($delimiter) . ')';
    }

    /**
     * @param QueryStream $stream
     * @param array $regexes
     *
     * @return void
     */
    private function maskTokens(QueryStream $stream, array $regexes): void
    {
        // Make sure the delimiter is not masked.
        $regexes = array_filter($regexes, fn($regex) => $regex !== $stream->pregQueryDelimiter);

        $callback = fn(array $matches) => str_repeat(' ', strlen($matches[0]));
        $regex = implode('|', $regexes);
        $stream->inputLine = preg_replace_callback("/$regex/s", $callback, $stream->inputLine);
        // foreach ($regexes as $regex) {
        //     $stream->inputLine = preg_replace_callback("/$regex/s", $callback, $stream->inputLine);
        // }
    }

    /**
     * @param QueryStream $stream
     *
     * @return bool
     */
    private function setDelimiter(QueryStream $stream): bool
    {
        // Delimiter queries are not sent to the server.
        // $copyRegex = "~^(\\s*+COPY\\s+)[^;]+\\s+FROM\\s+stdin;~i";
        // if ($this->_engine()->pgsql() && preg_match($copyRegex, $query, $matches)) {
        //     $stream->queryDelimiters = ['' => "\n\\\\\\.\r?\n"];
        //     return true;
        // }
        if (preg_match($this->delimiterQueryRegex, $stream->queryLine, $matches)) {
            $stream->queryDelimiter = $matches[1];
            $stream->pregQueryDelimiter = preg_quote($stream->queryDelimiter);
            return true;
        }

        return false;
    }

    /**
     * @param QueryStream $stream
     *
     * @return void
     */
    private function removeSingleLineStarComments(QueryStream $stream): void
    {
        $regex = "/\/\*.*?\*\//s";
        $flags = PREG_OFFSET_CAPTURE;
        if (!preg_match_all($regex, $stream->inputLine, $matches, $flags)) {
            // Middle of a multiline function. Add the line to the buffer.
            return;
        }

        foreach (array_reverse($matches[0]) as $match) {
            $this->truncatePartOfLine($stream, $match[1], strlen($match[0]));
        }
    }

    /**
     * @param QueryStream $stream
     *
     * @return bool
     */
    private function makeInputLine(QueryStream $stream): bool
    {
        if ($this->setDelimiter($stream)) {
            $stream->queryLine = '';
            return false;
        }

        $stream->inputLine = $stream->queryLine;

        // Mask double quotes and antislashed quotes.
        $this->maskTokens($stream, [
            "''",
            "\\\\\\\\",
            "\\\\'",
            "\\\\`",
            "\\\\\"",
        ]);

        if ($stream->context === QueryStreamContext::MULTILINE_STRING &&
            !$this->parseMultiLineStringEnd($stream)) {
            return false;
        }

        // Mask quoted strings.
        $this->maskTokens($stream, [
            "'[^']*'",
            "`[^`]*`",
            "\"[^\"]*\"",
        ]);

        if ($stream->context === QueryStreamContext::MULTILINE_FUNCTION &&
            !$this->parseMultiLineFunctionEnd($stream)) {
            return false;
        }

        if ($stream->context === QueryStreamContext::MULTILINE_COMMENT &&
            !$this->parseMultiLineCommentEnd($stream)) {
            return false;
        }

        // Remove comments in "/*  */".
        $this->removeSingleLineStarComments($stream);

        // Mask function in "$x$  $x$".
        $this->maskTokens($stream, [
            "({$this->functionDelimiterRegex}).*?\\1",
        ]);

        $this->parseTokensAfterDelimiter($stream);

        if (trim($stream->queryLine) === '') {
            $stream->queryLine = '';
            return false;
        }

        return true;
    }

    /**
     * Split a string or a file containing SQL queries.
     *
     * @param QueryStream $stream
     *
     * @return Generator
     */
    public function splitQueries(QueryStream $stream): Generator
    {
        $stream->context = QueryStreamContext::NONE;

        while (($stream->queryLineReader)($stream)) {
            if (!$this->makeInputLine($stream)) {
                continue;
            }

            while (($offset = $this->findEndOfQuery($stream)) !== null) {
                $this->bufferEndOfQuery($stream, $offset);

                // Return the query for processing.
                if (($query = $this->getBufferedQuery($stream)) !== '') {
                    $stream->queryCount++;
                    yield $query;
                }
            }

            if (trim($stream->queryLine) !== '') {
                // Add the remaining input line to the query buffer.
                $this->bufferLineContent($stream);
            }
        }

        // Return the last query.
        if (($query = $this->getBufferedQuery($stream)) !== '') {
            $stream->queryCount++;
            yield $query;
        }
    }
}
