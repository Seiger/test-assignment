<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DateTimeZone;
use League\Csv\Reader;
use League\Csv\Statement;

class InventoryService extends Controller
{
    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d';

    /**
     * @var string
     */
    protected $timezone = 'Europe/Amsterdam';

    /**
     * @var array
     */
    protected $inventory = [];

    /**
     * @var array
     */
    protected $prices = [
        'musical' => 70,
        'comedy' => 50,
        'drama' => 40
    ];
    /**
     * @var
     */
    protected $queryResult;

    /**
     * @param string $file
     * @param bool $rebuildCache
     */
    public function loadFile(string $file): void
    {
        $data = Reader::createFromPath($file, 'r');

        $stmt = new Statement();
        $records = $stmt->process($data);
        foreach ($records as $record) {
            $this->addShow($record[0], $record[1], $record[2]);
        }
    }

    /**
     * @param $show
     * @param $openingDate
     * @param $genre
     */
    public function addShow($show, $openingDate, $genre)
    {
        $openingDate = $this->parseDate($openingDate);
        $dateIterator = $openingDate->copy();
        for ($i = 1; $i <= 100; ++$i) {
            $key = $dateIterator->format($this->dateFormat);
            $dateIterator = $dateIterator->addDay();
            if (!array_key_exists($key, $this->inventory)) {
                $this->inventory[$key] = [];
            }

            $this->inventory[$key][] = [
                'title' => trim($show),
                'opening date' => $openingDate->format($this->dateFormat),
                'day_of_show' => $i,
                'genre' => strtolower(trim($genre, "\r")),
            ];
        }
    }

    /**
     * @param $date
     * @return Carbon
     */
    protected function parseDate($date): Carbon
    {
        return Carbon::createFromFormat($this->dateFormat, $date, new DateTimeZone($this->timezone));
    }

    /**
     * @param $inventoryDate
     * @param $showDate
     * @return $this
     */
    public function find($inventoryDate, $showDate)
    {
        $showDate = $this->parseDate($showDate);
        $inventoryDate = $this->parseDate($inventoryDate);
        $inventory = isset($this->inventory[$showDate->format($this->dateFormat)]) ? $this->inventory[$showDate->format($this->dateFormat)] : false;

        if ($inventory) {
            foreach ($inventory as $key => $value) {
                $diff = $showDate->diffInDays($inventoryDate, false);
                $inventory[$key]['tickets left'] = $this->calculateTicketsLeft(
                    $diff,
                    $inventory[$key]['day_of_show']
                );
                $inventory[$key]['tickets available'] = $this->calculateTicketsAvailable(
                    $diff,
                    $inventory[$key]['day_of_show']
                );
                $inventory[$key]['status'] = $this->getSaleStatus($diff);
                $inventory[$key]['price'] = $this->getPrice(
                    $inventory[$key]['day_of_show'],
                    $inventory[$key]['genre']
                );
            }
        }
        $this->queryResult = $inventory;

        return $this;
    }

    /**
     * @param $diff
     * @param $dayOfTheShow
     * @return int
     */
    protected function calculateTicketsLeft($diff, $dayOfTheShow)
    {
        $maxAvailableAmount = $this->getMaxAvailableAmount($dayOfTheShow);

        if ($diff < -24) {
            return $maxAvailableAmount;
        }
        if ($diff > -5) {
            return 0;
        }

        return $maxAvailableAmount - ((24 + $diff) * $this->getMaxAvailableTicketsPerDay($dayOfTheShow));
    }

    /**
     * @param $dayOfTheShow
     * @return int
     */
    protected function getMaxAvailableAmount($dayOfTheShow)
    {
        if ($dayOfTheShow < 1 || $dayOfTheShow > 100) {
            return 0;
        }
        return $dayOfTheShow <= 60 ? 200 : 100;
    }

    /**
     * @param $dayOfTheShow
     * @return int
     */
    protected function getMaxAvailableTicketsPerDay($dayOfTheShow)
    {
        if ($dayOfTheShow < 1 || $dayOfTheShow > 100) {
            return 0;
        }

        return $dayOfTheShow <= 60 ? 10 : 5;
    }

    /**
     * @param $diff
     * @param $dayOfTheShow
     * @return int
     */
    protected function calculateTicketsAvailable($diff, $dayOfTheShow)
    {
        if ($diff < -24 || $diff > -5) {
            return 0;
        }

        return $this->getMaxAvailableTicketsPerDay($dayOfTheShow);
    }

    /**
     * @param $diff
     * @return string
     */
    protected function getSaleStatus($diff)
    {

        if ($diff > 0) {
            return 'in the past';
        }
        if ($diff < -24) {
            return 'sale not started';
        }
        if ($diff > -5) {
            return 'sold out';
        }

        return 'open for sale';
    }

    /**
     * @param $dayOfTheShow
     * @param $genre
     * @return float|int|mixed
     */
    protected function getPrice($dayOfTheShow, $genre)
    {
        if ($dayOfTheShow < 1 || $dayOfTheShow > 100) {
            return 0;
        }

        if ($dayOfTheShow > 80) {
            return $this->prices[$genre] * 0.8;
        }

        return $this->prices[$genre];
    }

    /**
     * @param $data
     * @param $groupBy
     * @return array
     */
    protected function groupByField($data, $groupBy): array
    {
        $grouped = [];
        $inventory = ['inventory' => []];

        foreach ($data as $result) {
            $groupValue = $result[$groupBy];

            if (!array_key_exists($groupValue, $grouped)) {
                $grouped[$groupValue]  = [];
            }

            unset($result[$groupBy]);
            $grouped[$groupValue][] = $result;
        }

        foreach ($grouped as $key => $group) {
            $inventory['inventory'][] = [
                $groupBy => $key,
                'shows' => $group,
            ];
        }

        return $inventory;
    }

    /**
     * @param $groupBy
     * @param array ...$fields
     * @return array
     */
    public function groupBy($groupBy, ...$fields): array
    {
        if (!$this->queryResult) {
            return [];
        }
        $fields[] = $groupBy;
        $filteredResult = $this->filterResultFields($this->queryResult, $fields);
        return $this->groupByField($filteredResult, $groupBy);
    }

    /**
     * @param $data
     * @param $fields
     * @return array
     */
    protected function filterResultFields($data, $fields): array
    {
        foreach ($data as $key => $result) {
            $row = [];
            foreach ($fields as $field) {
                $row[$field] = $result[$field];
            }
            $data[$key] = $row;
        }

        return $data;
    }
}
