<?php

namespace Lagdo\DbAdmin\Db\Facades;

use function array_map;
use function extension_loaded;
use function file_get_contents;
use function function_exists;
use function iconv;
use function implode;
use function ini_get;
use function preg_match;
use function substr;

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
     * @param string $file
     * @param bool $decompress
     *
     * @return string
     */
    protected function readFile(string $file, bool $decompress = false): string
    {
        $compressed = preg_match('~\.gz$~', $file);
        if (!$decompress || !$compressed) {
            //! may not be reachable because of open_basedir
            return file_get_contents($file);
        }

        //! may not be reachable because of open_basedir
        $content = file_get_contents("compress.zlib://$file");
        $start = substr($content, 0, 3);
        return match(true) {
            preg_match("~^\xFE\xFF|^\xFF\xFE~", $start, $regs) &&
                function_exists("iconv") => iconv("utf-16", "utf-8", $content),
            // UTF-8 BOM
            $start === "\xEF\xBB\xBF" => substr($content, 3),
            default => $content,
        };
    }

    /**
     * Run queries from uploaded files
     *
     * @param array  $files         The uploaded files
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
