<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Jaxon\Request\Upload\FileInterface;

use function array_map;
use function extension_loaded;
use function implode;
use function ini_get;

/**
 * Facade to import functions
 */
class ImportFacade extends CommandFacade
{
    /**
     * Get data for import
     *
     * @return array
     */
    public function getImportOptions(): array
    {
        // From sql.inc.php
        $gz = extension_loaded('zlib') ? '[.gz]' : '';
        // ignore post_max_size because it is for all form fields
        // together and bytes computing would be necessary.
        $contents = $this->admin->iniBool('file_uploads') ?
            ['upload' => "SQL$gz (&lt; " . ini_get('upload_max_filesize') . 'B)'] :
            ['upload_disabled' => $this->utils->trans->lang('File uploads are disabled.')];
        if (($importServerPath = $this->admin->importServerPath())) {
            $contents['path'] = $this->utils->str->html($importServerPath) . $gz;
        }

        return ['contents' => $contents];
    }

    /**
     * From the get_file() function in functions.inc.php
     *
     * @param FileInterface $file
     * @param bool $decompress
     *
     * @return string
     */
    protected function readFile(FileInterface $file, bool $decompress = false): string
    {
        // $compressed = preg_match('~\.gz$~', $file->path());
        // if (!$decompress || !$compressed) {
            //! may not be reachable because of open_basedir
        // }

        return $file->filesystem()->read($file->path());
    }

    /**
     * Run queries from uploaded files
     *
     * @param array<FileInterface>  $files         The uploaded files
     * @param bool   $errorStops    Stop executing the requests in case of error
     * @param bool   $onlyErrors    Return only errors
     *
     * @return array
     */
    public function executeSqlFiles(array $files, bool $errorStops, bool $onlyErrors): array
    {
        $queries = array_map(fn($file) => $this->readFile($file), $files);
        $queries = implode("\n\n", $queries);
        return $this->executeCommands($queries, 0, $errorStops, $onlyErrors);
    }
}
