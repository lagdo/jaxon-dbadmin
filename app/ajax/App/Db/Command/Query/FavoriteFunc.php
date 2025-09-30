<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\App\Db\FuncComponent;
use Lagdo\DbAdmin\Ajax\Exception\ValidationException;
use Lagdo\DbAdmin\DbAdminPackage;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Service\DbAdmin\QueryFavorite;
use Lagdo\DbAdmin\Ui\Command\LogUiBuilder;
use Lagdo\DbAdmin\Translator;

use function compact;
use function is_string;
use function strlen;
use function trim;

class FavoriteFunc extends FuncComponent
{
    /**
     * @param LogUiBuilder $logUi
     * @param DbAdminPackage $package
     * @param DbFacade $db
     * @param Translator $trans
     * @param QueryFavorite|null $queryFavorite
     */
    public function __construct(private LogUiBuilder $logUi,
        protected DbAdminPackage $package, protected DbFacade $db,
        protected Translator $trans, private QueryFavorite|null $queryFavorite)
    {}

    /**
     * @param array $formValues
     *
     * @return array
     */
    private function validate(array $formValues): array
    {
        if (!isset($formValues['title'])) {
            throw new ValidationException($this->trans->lang('The %s field is missing.', 'title'));
        }
        if (!isset($formValues['query'])) {
            throw new ValidationException($this->trans->lang('The %s field is missing.', 'query'));
        }
        if (!is_string($formValues['title'])) {
            throw new ValidationException($this->trans->lang('The %s field is incorrect.', 'title'));
        }
        if (!is_string($formValues['query'])) {
            throw new ValidationException($this->trans->lang('The %s field is incorrect.', 'query'));
        }
        $title = trim($formValues['title']);
        $query = trim($formValues['query']);
        if ($title === '') {
            throw new ValidationException($this->trans->lang('The %s field is empty.', 'title'));
        }
        if (strlen($title) > 150) {
            throw new ValidationException($this->trans->lang('The %s field is too long.', 'title'));
        }
        if ($query === '') {
            throw new ValidationException($this->trans->lang('The %s field is empty.', 'query'));
        }

        return compact('title', 'query');
    }

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
        $values = $this->validate($formValues);

        // Get the driver name from the package config options.
        $server = $this->db()->getServerName();
        $values['driver'] = $this->package()->getServerDriver($server);
        $this->queryFavorite->createQuery($values);

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
        $values = $this->validate($formValues);

        // Get the driver name from the package config options.
        $server = $this->db()->getServerName();
        $values['driver'] = $this->package()->getServerDriver($server);
        $this->queryFavorite->updateQuery($queryId, $values);

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
