<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\FuncComponent;
use Lagdo\DbAdmin\Service\DbAdmin\QueryFavorite;
use Lagdo\DbAdmin\Ui\Command\LogUiBuilder;
use Lagdo\DbAdmin\Translator;

class FavoriteFunc extends FuncComponent
{
    /**
     * @param LogUiBuilder $logUi
     * @param QueryFavorite|null $queryFavorite
     * @param Translator $trans
     */
    public function __construct(private LogUiBuilder $logUi,
        protected Translator $trans, private QueryFavorite|null $queryFavorite)
    {}

    /**
     * @param string $query
     *
     * @return void
     */
    public function add(string $query): void
    {
        if(!($query = trim($query)))
        {
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error($this->trans->lang('The query string is empty!'));
            return;
        }

        $title = $this->trans->lang('Add a favorite');
        $content = $this->logUi->addFavoriteForm($query);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->create($this->logUi->favoriteFormValues()),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * @param array $formValues
     *
     * @return void
     */
    public function create(array $formValues): void
    {
        $this->queryFavorite->createQuery($formValues);

        $this->modal()->hide();
        $this->alert()
            ->title($this->trans->lang('Success'))
            ->success($this->trans->lang('The query is saved.'));
        $this->cl(Favorite::class)->render();
    }

    /**
     * @param int $queryId
     * @param string $value
     *
     * @return void
     */
    public function edit(int $queryId, string $value): void
    {
        if(!($value = trim($value)))
        {
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error($this->trans->lang('The query string is empty!'));
            return;
        }
        if(!($query = $this->queryFavorite->getQuery($queryId)))
        {
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error($this->trans->lang('Unable to find the query in the favorites!'));
            return;
        }

        $query['query'] = $value;
        $title = $this->trans->lang('Edit a favorite');
        $content = $this->logUi->editFavoriteForm($query);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->update($queryId, $this->logUi->favoriteFormValues()),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * @param int $queryId
     * @param array $formValues
     *
     * @return void
     */
    public function update(int $queryId, array $formValues): void
    {
        $this->queryFavorite->updateQuery($queryId, $formValues);

        $this->modal()->hide();
        $this->alert()->title($this->trans->lang('Success'))
            ->success($this->trans->lang('The query is updated.'));
        $this->cl(Favorite::class)->render();
    }

    /**
     * @param int $queryId
     *
     * @return void
     */
    public function delete(int $queryId): void
    {
        $this->queryFavorite->deleteQuery($queryId);

        $this->alert()
            ->title($this->trans->lang('Success'))
            ->success($this->trans->lang('The query is deleted.'));
        $this->cl(Favorite::class)->render();
    }
}
