<?php

namespace Lagdo\DbAdmin\Support\Service\Storage;

use Jaxon\Upload\Manager\FileNameInterface;

use function base_convert;
use function floor;
use function microtime;
use function str_pad;

/**
 * Read storage options for import.
 */
class FileNameService implements FileNameInterface
{
    /**
     * Epoch for unique ids computation.
     *
     * @var int
     */
    private const EPOCH = 1735689600000;

    /**
     * @var int
     */
    private int $lastTimestamp = -1;

    /**
     * @var int
     */
    private int $sequence = 0;

    /**
     * @var int
     */
    private int $workerId = (1 & 0x3FF) << 12;

    /**
     * @return int
     */
    private function timestamp(): int
    {
        $timestamp = (int)floor(microtime(true) * 1000);
        if ($timestamp !== $this->lastTimestamp) {
            $this->sequence = 0;
            $this->lastTimestamp = $timestamp;
            return $timestamp;
        }

        // Increment sequence up to 4095 within the same millisecond.
        $this->sequence = ($this->sequence + 1) & 0xFFF;
        if ($this->sequence === 0) {
            // Sequence overflow: will happen only if more than
            // 4096 ids are generated in the same millisecond.
            // Wait until the next millisecond to avoid collisions.
            while ($timestamp <= $this->lastTimestamp) {
                $timestamp = (int)floor(microtime(true) * 1000);
            }
        }
        $this->lastTimestamp = $timestamp;
        return $timestamp;
    }

    /**
     * @return string
     */
    private function generate(): string
    {
        $timestamp = $this->timestamp();
        // 64-bit assembly: 41-bit timestamp | 10-bit worker ID | 12-bit sequence
        $id = (($timestamp - self::EPOCH) << 22) | $this->workerId | $this->sequence;

        // Native Base36 conversion (0-9a-z) + left-padding to 13 chars for lexicographical sorting
        return str_pad(base_convert((string)$id, 10, 36), 13, '0', STR_PAD_LEFT);
    }

    /**
     * Function of Jaxon\Upload\Manager\FileNameInterface.
     *
     * @param int $_
     *
     * @return string
     */
    public function random(int $_): string
    {
        return $this->generate();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->generate();
    }
}
