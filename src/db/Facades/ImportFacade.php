<?php

namespace Lagdo\DbAdmin\Db\Facades;

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
        $contents = [];
        // From sql.inc.php
        $gz = \extension_loaded('zlib') ? '[.gz]' : '';
        // ignore post_max_size because it is for all form fields
        // together and bytes computing would be necessary.
        if ($this->admin->iniBool('file_uploads')) {
            $contents['upload'] = "SQL$gz (&lt; " . \ini_get('upload_max_filesize') . 'B)';
        } else {
            $contents['upload_disabled'] = $this->utils->trans->lang('File uploads are disabled.');
        }

        $importServerPath = $this->admin->importServerPath();
        if (($importServerPath)) {
            $contents['path'] = $this->utils->str->html($importServerPath) . $gz;
        }

        $labels = [
            'path' => $this->utils->trans->lang('Webserver file %s', ''),
            'file_upload' => $this->utils->trans->lang('File upload'),
            'from_server' => $this->utils->trans->lang('From server'),
            'execute' => $this->utils->trans->lang('Execute'),
            'run_file' => $this->utils->trans->lang('Run file'),
            'select' => $this->utils->trans->lang('Select'),
            'error_stops' => $this->utils->trans->lang('Stop on error'),
            'only_errors' => $this->utils->trans->lang('Show only errors'),
        ];

        return \compact('contents', 'labels');
    }

    /**
     * Get file contents from $_FILES
     * From the getFile() function in functions.inc.php
     *
     * @param array $files
     * @param bool $decompress
     *
     * @return string
     */
    protected function readFiles(array $files, bool $decompress = false): string
    {
        $queries = '';
        foreach ($files as $name) {
            $compressed = \preg_match('~\.gz$~', $name);
            if ($decompress && $compressed) {
                //! may not be reachable because of open_basedir
                $content = \file_get_contents("compress.zlib://$name");
                $start = \substr($content, 0, 3);
                if (\function_exists("iconv") && \preg_match("~^\xFE\xFF|^\xFF\xFE~", $start, $regs)) {
                    // not ternary operator to save memory
                    $content = \iconv("utf-16", "utf-8", $content);
                } elseif ($start == "\xEF\xBB\xBF") {
                    // UTF-8 BOM
                    $content = \substr($content, 3);
                }
                $queries .= $content . "\n\n";
            } else {
                //! may not be reachable because of open_basedir
                $queries .= \file_get_contents($name) . "\n\n";
            }
        }
        //! Does'nt support SQL files not ending with semicolon
        return $queries;
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
        $queries = $this->readFiles($files);
        return $this->executeCommands($queries, 0, $errorStops, $onlyErrors);
    }
}
