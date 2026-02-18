<?php

namespace Lagdo\DbAdmin\Ajax\Base;

use Lagdo\DbAdmin\Ui\Select\SelectUiBuilder;

/**
 * This component displays the SQL query duration.
 */
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
    public function html(): string
    {
        return $this->selectUi->duration($this->get('duration'));
    }

    /**
     * @param float|null $duration
     *
     * @return void
     */
    public function update(float|null $duration = null): void
    {
        if ($duration === null) {
            $this->clear();
            return;
        }

        $this->set('duration', $duration);
        $this->render();
    }
}
