<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command;

use Jaxon\App\Component;
use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\Exclude;

#[Exclude]
class ImportMenuButton extends Component
{
    use ComponentDataTrait;

    /**
     * @param string $icon
     * @param string $color
     *
     * @return string
     */
    private function iconStack(string $icon, string $color): string
    {
        return '<span class="fa-layers fa-fw">' .
            '<i class="fa-solid fa-database"></i>' .
            '<i class="fa-solid ' . $icon . '" style="color: ' . $color .
            ';" data-fa-transform="shrink-6 up-1 right-1"></i></span>';
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $import = $this->get('import', []);
        $status = $import['status'] ?? '';
        $result = $import['result'] ?? '';
        $success = $status === 'completed' && $result === 'success';
        $failure = $status === 'completed' && $result === 'failure';

        return match(true) {
            $status === 'preparing',
            $status === 'running' => '<i class="fa-solid fa-spinner fa-spin"></i>',
            $success => $this->iconStack('fa-check', '#fcad03'),
            $failure => $this->iconStack('fa-xmark', '#cf1fa6'),
            default => '<i class="fa-solid fa-database"></i>',
        };
    }
}
