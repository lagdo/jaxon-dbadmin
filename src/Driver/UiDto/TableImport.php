<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;

use function extension_loaded;
use function ini_get;

/**
 * File import options.
 */
class TableImport extends AbstractDriverProxy
{
    /**
     * Get data for import
     *
     * @return array
     */
    public function getOptions(): array
    {
        // From sql.inc.php
        $gz = extension_loaded('zlib') ? '[.gz]' : '';
        // ignore post_max_size because it is for all form fields
        // together and bytes computing would be necessary.
        $contents = $this->utils()->iniBool('file_uploads') ?
            ['upload' => "SQL$gz (&lt; " . ini_get('upload_max_filesize') . 'B)'] :
            ['upload_disabled' => $this->utils()->lang('File uploads are disabled.')];
        if (($importServerPath = $this->pageUi()->importServerPath())) {
            $contents['path'] = $this->utils()->html($importServerPath) . $gz;
        }

        return ['contents' => $contents];
    }
}
