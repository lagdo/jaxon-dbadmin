<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Ui\Builder\BuilderInterface;

use function htmlentities;

class Builder
{
    use Traits\MenuTrait;
    use Traits\MainTrait;
    use Traits\ServerTrait;
    use Traits\DatabaseTrait;
    use Traits\QueryTrait;

    /**
     * @var BuilderInterface
     */
    protected $htmlBuilder;

    /**
     * @param BuilderInterface $htmlBuilder
     */
    public function __construct(BuilderInterface $htmlBuilder)
    {
        $this->htmlBuilder = $htmlBuilder;
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
                                ->select()->setId('adminer-dbhost-select');
        foreach($values['servers'] as $name => $title)
        {
            $this->htmlBuilder
                                    ->option($title, ($name == $values['default']))->setValue($name)
                                    ->end();
        }
        $this->htmlBuilder
                                ->end()
                                ->button('Show', 'primary', 'btn-select')->setOnclick($values['connect'] . ';return false;')
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
                ->select();
        foreach($options as $key => $label)
        {
            $value = $useKeys ? $key : $label;
            $this->htmlBuilder
                    ->option($label, false, $optionClass)->setValue(htmlentities($value))
                    ->end();
        }
        $this->htmlBuilder
                ->end();
        return $this->htmlBuilder->build();
    }
}
