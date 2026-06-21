<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\RoutineDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\UserTypeDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\DetailDto;

use function array_map;

class DatabaseContent extends AbstractDriverProxy
{
    /**
     * @param int|null $number
     *
     * @return string
     */
    private function formatNumber(int|null $number): string
    {
        return $number === null ? '' : $this->utils()->formatNumber($number);
    }

    /**
     * @param array<TableDto> $tables
     *
     * @return array
     */
    public function tables(array $tables): array
    {
        return array_map(fn(TableDto $status) => new DetailDto([
            'name' => '<div title="' . $this->utils()->html($status->comment ?? '') .
                '">' . $this->pageUi()->tableName($status) . '</div>',
            'engine' => $status->engine,
            'collation' => $status->collation,
            'auto_increment' => $status->hasAutoIncrement ? $status->autoIncrement : '',
            'data_length' => $this->formatNumber($status->dataLength),
            'index_length' => $this->formatNumber($status->indexLength),
            'data_free' => $this->formatNumber($status->dataFree),
            'row_count' => $this->formatNumber($status->rowCount),
        ]), $tables);
    }

    /**
     * @param array<TableDto> $views
     *
     * @return array
     */
    public function views(array $views): array
    {
        return array_map(fn(TableDto $status) => new DetailDto([
            'name' => '<div title="' . $this->utils()->html($status->comment ?? '') .
                '">' . $this->pageUi()->tableName($status) . '</div>',
            'engine' => $status->engine,
            'data_length' => $this->formatNumber($status->dataLength),
            'index_length' => $this->formatNumber($status->indexLength),
            'row_count' => $this->formatNumber($status->rowCount),
        ]), $views);
    }

    /**
     * @param array<RoutineDto> $routines
     *
     * @return array
     */
    public function routines(array $routines): array
    {
        return array_map(fn(RoutineDto $routine) => new DetailDto([
            // not computed on the pages to be able to print the header first
            // $name = ($routine["SPECIFIC_NAME"] == $routine["ROUTINE_NAME"] ?
            //     "" : "&name=" . urlencode($routine["ROUTINE_NAME"]));

            'name' => $this->utils()->html($routine->name),
            'type' => $this->utils()->html($routine->type),
            'returnType' => $this->utils()->html($routine->dtd),
            // 'alter' => $this->utils()->lang('Alter'),
        ]), $routines);
    }

    /**
     * @param array<string> $sequences
     *
     * @return array
     */
    public function sequences(array $sequences): array
    {
        return array_map(fn(string $sequence) => new DetailDto([
            'name' => $this->utils()->html($sequence),
        ]), $sequences);
    }

    /**
     * @param array<UserTypeDto> $types
     *
     * @return array
     */
    public function userTypes(array $types): array
    {
        return array_map(fn(UserTypeDto $userType) => new DetailDto([
            'name' => $this->utils()->html($userType->name),
        ]), $types);
    }

    /**
     * @param array<array> $events
     *
     * @return array
     */
    public function events(array $events): array
    {
        return array_map(fn(array $event) => new DetailDto(!$event["Execute at"] ? [
            'name' => $this->utils()->html($event["Name"]),
            'schedule' => $this->utils()->lang('Every') . " " .
                $event["Interval value"] . " " . $event["Interval field"],
            'start' => $event["Starts"],
            // 'end' => '',
        ] : [
            'name' => $this->utils()->html($event["Name"]),
            'schedule' => $this->utils()->lang('At given time'),
            'start' => $event["Execute at"],
            // 'end' => '',
        ]), $events);
    }
}
