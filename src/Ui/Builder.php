<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Db\Translator;
use Lagdo\UiBuilder\AbstractBuilder;
use Lagdo\UiBuilder\BuilderInterface;

use function htmlentities;

class Builder
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
     * @var BuilderInterface
     */
    protected $htmlBuilder;

    /**
     * @var Translator
     */
    protected $trans;

    /**
     * @param BuilderInterface $htmlBuilder
     * @param Translator $trans
     */
    public function __construct(BuilderInterface $htmlBuilder, Translator $trans)
    {
        $this->htmlBuilder = $htmlBuilder;
        $this->trans = $trans;
        $this->inputBuilder = new InputBuilder($this->htmlBuilder, $this->trans);
    }

    /**
     * @return string
     */
    public function formRowTag(): string
    {
        return $this->htmlBuilder->formRowTag();
    }

    /**
     * @param string $class
     *
     * @return string
     */
    public function formRowClass(string $class = ''): string
    {
        return $this->htmlBuilder->formRowClass($class);
    }

    /**
     * @param array $values
     *
     * @return string
     */
    public function home(array $values): string
    {
        $this->htmlBuilder->clear()
            ->row()->setId($values['containerId'])
                ->col(3)
                    ->row()
                        ->col(12)
                            ->inputGroup()
                                ->formSelect()->setId('adminer-dbhost-select');
        foreach($values['servers'] as $name => $title)
        {
            $this->htmlBuilder
                                    ->option($name == $values['default'], $title)->setValue($name)
                                    ->end();
        }
        $this->htmlBuilder
                                ->end()
                                ->button(AbstractBuilder::BTN_PRIMARY)->setClass('btn-select')
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
        return $this->htmlBuilder->build();
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
        $this->htmlBuilder->clear()
                ->formSelect();
        foreach($options as $key => $label)
        {
            $value = $useKeys ? $key : $label;
            $this->htmlBuilder
                    ->option(false, $label)->setClass($optionClass)->setValue(htmlentities($value))
                    ->end();
        }
        $this->htmlBuilder
                ->end();
        return $this->htmlBuilder->build();
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
        $this->htmlBuilder->clear()
                ->pagination();
        foreach($pages as $page)
        {
            if ($page->type === 'disabled') {
                $this->htmlBuilder
                    ->paginationDisabledItem()->addHtml($page->text)
                    ->end();
            } elseif ($page->type === 'current') {
                $this->htmlBuilder
                    ->paginationActiveItem()->addHtml($page->text)
                    ->end();
            } else {
                $this->htmlBuilder
                    ->paginationItem(['href' => 'javascript:void;', 'onclick' => $page->call])->addHtml($page->text)
                    ->end();
            }
        }
        $this->htmlBuilder
                ->end();
        return $this->htmlBuilder->build();
    }
}
