<?php

namespace Lagdo\DbAdmin\Ajax\Log;

use Jaxon\App\Dialog\DialogTrait;
use Jaxon\App\PageComponent;
use Jaxon\Attributes\Attribute\Callback;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Service\Logging\QueryLogger;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\Logging\LogUiBuilder;
use DateTime;

use function count;
use function implode;
use function preg_match;
use function trim;

#[Databag('dbadmin.logging')]
#[Callback('jaxon.dbadmin.callback.spinner')]
class Commands extends PageComponent
{
    use DialogTrait;

    /**
     * @var array
     */
    private array $errors;

    /**
     * @var string
     */
    private const BAG = 'dbadmin.logging';

    /**
     * @param QueryLogger $queryLogger
     * @param LogUiBuilder $uiBuider
     * @param Translator $trans
     */
    public function __construct(private QueryLogger $queryLogger,
        private LogUiBuilder $uiBuider, private Translator $trans)
    {}

    /**
     * @inheritDoc
     */
    protected function limit(): int
    {
        return $this->queryLogger->getLimit();
    }

    /**
     * @inheritDoc
     */
    protected function count(): int
    {
        $filters = $this->bag(self::BAG)->get('filters', []);
        return $this->queryLogger->getCommandCount($filters);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $filters = $this->bag(self::BAG)->get('filters', []);
        $commands = $this->queryLogger->getCommands($filters, $this->currentPage());
        return $this->uiBuider->commands($commands, $this->queryLogger->getCategories());
    }

    /**
     * Render the page and pagination components
     *
     * @param int $pageNumber
     *
     * @return void
     */
    public function page(int $pageNumber = 0): void
    {
        // Get the paginator. This will also set the current page number value.
        $paginator = $this->paginator($pageNumber);
        // Render the page content.
        $this->render();
        // Render the pagination component.
        $paginator->render($this->rq()->page());
    }

    /**
     * @param array $formValues
     *
     * @return void
     */
    private function checkUsername(array $formValues): void
    {
        $username = trim($formValues['username']);
        if ($username === '') {
            return;
        }

        // $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
        $pattern = "/^((\.)?[_a-z0-9-]+)*@?[a-z0-9-]*(\.[a-z0-9-]+)*(\.)?([a-z]{2,}){0,1}$/i";
        if (preg_match($pattern, $username)) {
            $filters = $this->bag(self::BAG)->get('filters', []);
            $filters['username'] = $username;
            $this->bag(self::BAG)->set('filters', $filters);
            return;
        }

        $this->errors[] = $this->trans
            ->lang('The user value "%s" is incorrect', $username);
    }

    /**
     * @param array $formValues
     *
     * @return void
     */
    private function checkCategory(array $formValues): void
    {
        $category = trim($formValues['category']);
        if ($category === '' || $category === '0') {
            return;
        }

        $categories = $this->queryLogger->getCategories();
        if (isset($categories[$category])) {
            $filters = $this->bag(self::BAG)->get('filters', []);
            $filters['category'] = $category;
            $this->bag(self::BAG)->set('filters', $filters);
            return;
        }

        $this->errors[] = $this->trans
            ->lang('The category value "%s" is incorrect', $category);
    }

    /**
     * @param array $formValues
     * @param string $field
     * @param string $defaultTime
     *
     * @return void
     */
    private function checkDate(array $formValues, string $field, string $defaultTime): void
    {
        $date = trim($formValues["{$field}_date"]);
        if ($date === '') {
            return;
        }

        if (!DateTime::createFromFormat('Y-m-d', $date)) {
            $this->errors[] = $this->trans
                ->lang('The date value "%s" is incorrect', $date);
            return;
        }

        $time = trim($formValues["{$field}_time"]);
        if ($time === '') {
            // Save the date only
            $filters = $this->bag(self::BAG)->get('filters', []);
            $filters[$field] = "$date $defaultTime";
            $this->bag(self::BAG)->set('filters', $filters);
            return;
        }

        if (!DateTime::createFromFormat('H:i', $time)) {
            $this->errors[] = $this->trans
                ->lang('The time value "%s" is incorrect', $time);
            return;
        }

        // Save the date and time
        $filters = $this->bag(self::BAG)->get('filters', []);
        $filters[$field] = "$date $time:00";
        $this->bag(self::BAG)->set('filters', $filters);
    }

    /**
     * @param array $formValues
     *
     * @return void
     */
    public function show(array $formValues): void
    {
        $this->errors = [];
        $this->bag(self::BAG)->set('filters', []);
        $this->checkCategory($formValues);
        $this->checkUsername($formValues);
        $this->checkDate($formValues, 'from', '00:00:00');
        $this->checkDate($formValues, 'to', '23:59:59');

        if (count($this->errors) === 0) {
            $this->page(1);
            return;
        }

        $this->alert()->title($this->trans->lang('Error'))
            ->error(implode('<br/>', $this->errors));
    }
}
