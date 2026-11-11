<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command;

use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\App\Ui\Command\ExportUiBuilder;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Service\Export\StatusService;

trait ExportTrait
{
    /**
     * @var ExportUiBuilder
     */
    #[Inject]
    private ExportUiBuilder $exportUi;

    /**
     * @var StatusService
     */
    #[Inject]
    private StatusService $statusService;

    /**
     * @return DriverProxy
     */
    abstract protected function driver(): DriverProxy;

    /**
     * @return string
     */
    protected function content(): string
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $options = $this->driver()->getExportOptions();
        return $this->exportUi->export($this->rq(), $options);
    }

    /**
     * @param array  $databases     The databases to dump
     * @param array  $formValues
     *
     * @return void
     */
    protected function exportDb(array $databases, array $formValues): void
    {
        if ($this->statusService->statusStorage() === null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('The export feature is not setup.');
            return;
        }

        $errors = $this->statusService->checkInputs($formValues);
        if ($errors !== null) {
            [$name, $allowedValues] = $errors;
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($this->trans()->lang('The "%s" value is incorrect.', $name));
        }

        // Create the export status file.
        $exportUrl = $this->statusService->initStatus($databases, $formValues);
        if ($exportUrl === null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Unable to create the export status file.');
            return;
        }

        $output = $formValues['output'] ?? 'save';
        $output === 'open' ?
            $this->response()->jo()->open($exportUrl, '_blank')->focus() :
            $this->response()->jo('jaxon.dbadmin')->downloadFile($exportUrl);
    }
}
