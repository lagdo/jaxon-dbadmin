<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Callback;
use Jaxon\Attributes\Attribute\Inject;
use Jaxon\Attributes\Attribute\Upload;
use Jaxon\Request\Upload\FileInterface;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\App\Ui\Command\ImportUiBuilder;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Service\Import\StorageInterface;
use DateTimeImmutable;

use function array_diff_key;
use function hash;
use function json_encode;
use function str_replace;

trait ImportTrait
{
    /**
     * @var ImportUiBuilder
     */
    #[Inject]
    private ImportUiBuilder $importUi;

    /**
     * @var StorageInterface|null
     */
    #[Inject]
    private StorageInterface|null $storage;

    /**
     * @return DriverProxy
     */
    abstract protected function driver(): DriverProxy;

    /**
     * Get the uploaded files
     *
     * @return array<string, array<FileInterface>>
     */
    abstract protected function files(): array;

    /**
     * @return string
     */
    protected function content(): string
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $options = $this->driver()->getImportOptions();
        return $this->importUi->import($options['contents'], $this->rq());
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->response()->jo('jaxon.dbadmin')->setFileUpload($this->importUi->filesDivId());
    }

    /**
     * @return void
     */
    #[Before('notYetAvailable')]
    public function importFromStorage(): void
    {}

    /**
     * @return array
     */
    private function date(): array
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s:v');
        return [
            'create' => $now,
            'update' => $now,
        ];
    }

    /**
     * @return array
     */
    private function connection(): array
    {
        [$server, $database, $schema] = $this->getCurrentDb();
        $options = $this->driver()->getServerConfig()->getValues();
        // Don't take the password in the hash.
        $options = array_diff_key($options, ['password' => '']);
        return [
            'server' => $server,
            'database' => $database,
            'schema' => $schema,
            'hash' => hash('sha256', json_encode($options)),
        ];
    }

    /**
     * @param array $formValues
     *
     * @return void
     */
    #[Callback('jaxon.dbadmin.upload')]
    #[Upload('dbadmin-import-sql-files-input')]
    public function importFromUpload(array $formValues): void
    {
        if (!$this->storage) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('The import feature is not setup.');
            return;
        }
        if(!($file = $this->files()['sql_files'][0] ?? null))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('No file uploaded!');
            return;
        }

        // Create the import status file.
        $statusFilename = str_replace($file->filename(),
            $file->name() . '.json', $file->path());
        $file->filesystem()->write($statusFilename, json_encode([
            'status' => 'starting',
            'file' => [
                'path' => $file->path(),
                'size' => $file->size(),
            ],
            'date' => $this->date(),
            'connect' => $this->connection(),
            'inputs' => [
                'error_stops' => $formValues['error_stops'] ?? false,
                'only_errors' => $formValues['only_errors'] ?? false,
            ],
            'count' => [
                'total' => 0,
                'runs' => 0,
                'errors' => 0,
            ],
        ], JSON_PRETTY_PRINT));

        $importUrl = $this->storage->url($file->path());
        $this->response()->jo('jaxon.dbadmin')->downloadFile($importUrl);
    }
}
