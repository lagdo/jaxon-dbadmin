<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\Component;
use Lagdo\DbAdmin\Ui\Select\SelectUiBuilder;
use Lagdo\DbAdmin\Ui\TabEditor;

use function is_float;

/**
 * This component displays the SQL query duration.
 */
#[Exclude]
class Duration extends Component
{
    /**
     * @param SelectUiBuilder $selectUi The HTML UI builder
     */
    public function __construct(protected SelectUiBuilder $selectUi)
    {}

    /**
     * @inheritDoc
     */
    protected function setupComponent(): void
    {
        // Customize the item ids.
        $this->helper()->extend('item', TabEditor::item(...));
        // By default, set an id for the component.
        // This will trigger a call to the above extension.
        $this->item('');
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $duration = $this->stash()->get('query.duration', null);
        return is_float($duration) ? $this->selectUi->duration($duration) : '';
    }
}
