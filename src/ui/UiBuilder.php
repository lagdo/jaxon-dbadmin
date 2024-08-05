<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\Jaxon\Builder;

use function htmlentities;
use function Jaxon\rq;

class UiBuilder
{
    use Traits\MenuTrait;
    use Traits\MainTrait;
    use Traits\ServerTrait;
    use Traits\DatabaseTrait;
    use Traits\QueryTrait;
    use Traits\TableTrait;
    use Traits\SelectTrait;

    /**
     * @var InputBuilder
     */
    protected $inputBuilder;

    /**
     * @param Translator $trans
     */
    public function __construct(protected Translator $trans)
    {
        $this->inputBuilder = new InputBuilder($this->trans);
    }

    /**
     * @return string
     */
    public function formRowTag(): string
    {
        return Builder::new()->formRowTag();
    }

    /**
     * @param string $class
     *
     * @return string
     */
    public function formRowClass(string $class = ''): string
    {
        return Builder::new()->formRowClass($class);
    }

    /**
     * @param array $values
     *
     * @return string
     */
    public function home(array $values): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->row()->setId($values['containerId'])
                ->col(3)
                    ->row()
                        ->col(12)
                            ->inputGroup()
                                ->formSelect()->setId('adminer-dbhost-select');
        foreach($values['servers'] as $serverId => $server)
        {
            $htmlBuilder
                                    ->option($serverId == $values['default'], $server['name'])->setValue($serverId)
                                    ->end();
        }
        $htmlBuilder
                                ->end()
                                ->button()->btnPrimary()->setClass('btn-select')
                                    ->setOnclick($values['connect'] . ';return false;')->addText('Show')
                                ->end()
                            ->end()
                        ->end()
                        ->col(12)->setId($values['serverActionsId'])
                        ->end()
                        ->col(12)->setId($values['dbListId'])
                        ->end()
                        ->col(12)->setId($values['schemaListId'])
                        ->end()
                        ->col(12)->setId($values['dbActionsId'])
                        ->end()
                        ->col(12)->setId($values['dbMenuId'])
                        ->end()
                    ->end()
                ->end()
                ->col(9)
                    ->row()->setId($values['serverInfoId'])
                    ->end()
                    ->row()
                        ->col(12)
                            ->span()->setId($values['breadcrumbsId'])
                            ->end()
                            ->span()->setId($values['mainActionsId'])
                            ->end()
                        ->end()
                    ->end()
                    ->row()
                        ->col(12)->setId($values['dbContentId'])
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $options
     * @param string $optionClass
     * @param bool $useKeys
     *
     * @return string
     */
    public function htmlSelect(array $options, string $optionClass, bool $useKeys = false): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
                ->formSelect();
        foreach($options as $key => $label)
        {
            $value = $useKeys ? $key : $label;
            $htmlBuilder
                    ->option(false, $label)->setClass($optionClass)->setValue(htmlentities($value))
                    ->end();
        }
        $htmlBuilder
                ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $pages
     *
     * @return string
     */
    public function pagination(array $pages): string
    {
        // There must be at least 2 pages to show in pagination.
        if (count($pages) < 4) {
            return '';
        }

        $htmlBuilder = Builder::new();
        $htmlBuilder
                ->pagination();
        foreach($pages as $page)
        {
            if ($page->type === 'disabled') {
                $htmlBuilder
                    ->paginationDisabledItem()->addHtml($page->text)
                    ->end();
            } elseif ($page->type === 'current') {
                $htmlBuilder
                    ->paginationActiveItem()->addHtml($page->text)
                    ->end();
            } else {
                $htmlBuilder
                    ->paginationItem(['href' => 'javascript:void(0)', 'onclick' => $page->call])
                        ->addHtml($page->text)
                    ->end();
            }
        }
        $htmlBuilder
                ->end();
        return $htmlBuilder->build();
    }
}
