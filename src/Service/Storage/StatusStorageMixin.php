<?php

namespace Lagdo\DbAdmin\Support\Service\Storage;

use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use DateTime;

use function array_diff_key;
use function hash;
use function json_decode;
use function json_encode;

trait StatusStorageMixin
{
    use StorageMixin;

    /**
     * @var string
     */
    public const DATETIME_FORMAT = 'Y-m-d H:i:s.v';

    /**
     * @var Filesystem|null
     */
    private Filesystem|null $fs;

    /**
     * @var DriverProxy
     */
    private DriverProxy $driver;

    /**
     * @return string
     */
    public function now(): string
    {
        return (new DateTime())->format(self::DATETIME_FORMAT);
    }

    /**
     * @return array
     */
    abstract protected function statusOptions(): array;

    /**
     * @param string $filename
     *
     * @return string
     */
    abstract protected function statusPath(string $filename): string;

    /**
     * @return Filesystem|null
     */
    public function statusStorage(): Filesystem|null
    {
        return $this->fs ??= $this->makeStorage($this->statusOptions());
    }

    /**
     * @param array $values
     *
     * @return string
     */
    private function hash(array $values): string
    {
        return hash('sha256', json_encode($values));
    }

    /**
     * @return array
     */
    private function connection(): array
    {
        $serverOptions = $this->driver->getServerConfig()->getValues();
        // Don't take the password in the hash.
        $serverOptions = array_diff_key($serverOptions, ['password' => '']);
        $currentDb = $this->driver->currentDb();
        $connection = [
            'server' => $currentDb->server,
            'database' => $currentDb->name,
            'schema' => $currentDb->schema,
        ];
        return [
            ...$connection,
            'hash' => $this->hash([...$serverOptions, ...$connection]),
        ];
    }

    /**
     * @param array $connection
     *
     * @return bool
     */
    public function verifyHash(array $connection): bool
    {
        $serverOptions = $this->driver->getServerConfig()->getValues();
        // Don't take the password in the hash.
        $serverOptions = array_diff_key($serverOptions, ['password' => '']);
        $hash = $connection['hash'];
        $connection = array_diff_key($connection, ['hash' => '']);

        return $hash === $this->hash([...$serverOptions, ...$connection]);
    }

    /**
     * @param string $filename
     * @param array $content
     *
     * @return bool
     */
    public function saveStatus(string $filename, array $content): bool
    {
        try {
            if (($storage = $this->statusStorage()) === null) {
                return false;
            }

            $storage->write($this->statusPath($filename),
                json_encode($content, JSON_PRETTY_PRINT));
        } catch (FilesystemException|UnableToWriteFile) {
            return false;
        }

        return true;
    }

    /**
     * @param string $filename
     *
     * @return array|null
     */
    public function readStatus(string $filename): array|null
    {
        try {
            if (($storage = $this->statusStorage()) === null) {
                return null;
            }

            $filepath = $this->statusPath($filename);
            return !$storage->fileExists($filepath) ? null :
                json_decode($storage->read($filepath), true);
        } catch (FilesystemException|UnableToReadFile) {
            return null;
        }
    }
}
