<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Ui\Builder\BuilderInterface;

class Builder
{
    use Traits\MenuTrait;
    use Traits\MainTrait;
    use Traits\ServerTrait;

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
                                    ->option($title)->setValue($name);
            if ($name == $values['default'])
            {
                $this->htmlBuilder
                                        ->setSelected('selected');
            }
            $this->htmlBuilder
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
}
