<?php

namespace App;


use DateTime;
use Google\AdsApi\Dfp\DfpSession;
use Google\AdsApi\Dfp\DfpServices;
use Google\AdsApi\Dfp\Util\v201708\StatementBuilder;
use Google\AdsApi\Dfp\v201708\Goal;
use Google\AdsApi\Dfp\v201708\CostType;
use Google\AdsApi\Dfp\v201708\GoalType;
use Google\AdsApi\Dfp\v201708\InventoryStatus;
use Google\AdsApi\Dfp\v201708\LineItem;
use Google\AdsApi\Dfp\v201708\PlacementService;
use Google\AdsApi\Dfp\v201708\Targeting;
use Google\AdsApi\Dfp\v201708\UnitType;
use Google\AdsApi\Dfp\v201708\LineItemType;
use Google\AdsApi\Dfp\v201708\ForecastService;
use Google\AdsApi\Dfp\v201708\AdUnitTargeting;
use Google\AdsApi\Dfp\Util\v201708\DfpDateTimes;
use Google\AdsApi\Dfp\v201708\StartDateTimeType;
use Google\AdsApi\Dfp\v201708\InventoryTargeting;
use Google\AdsApi\Dfp\v201708\ProspectiveLineItem;
use Google\AdsApi\Dfp\v201708\CreativeRotationType;
use Google\AdsApi\Dfp\v201708\AvailabilityForecastOptions;

class DFPWrapper
{
    private $session;
    private $dfpServices;
    private $advertiserId;
    private $startdate;
    private $enddate;

    public function __construct(DfpServices $dfpServices, DfpSession $session)
    {
        $this->dfpServices = $dfpServices;
        $this->session = $session;
        $this->advertiserId = 11137450;
    }

    public function setDateRange($start, $end)
    {
        $this->startdate = $start;
        $this->enddate = $end;
    }


    /**
     * @param array $domain domain names to limit search to
     * @return array
     */
    public function forecast($domain = null)
    {
        if ($domain & !is_array($domain))
            $domain = [$domain];

        $forecasts = [];
        foreach ($this->getActivePlacements($domain) as $name => $placement) {
            $starttime = microtime(true);
            $name = str_replace("_", " ", strtolower($name));
            $domain = substr($name, 0, strpos($name, ' '));
            $name = ucwords(substr($name, strpos($name, ' ') + 1));

            $adUnitTargeting = $this->buildTargetFromPlacement($placement);
            $inventoryTargeting = (new InventoryTargeting)->setTargetedAdUnits($adUnitTargeting);

            $lineItem = $this->newLineItem();

            $targeting = new Targeting();
            $targeting->setInventoryTargeting($inventoryTargeting);
            $lineItem->setTargeting($targeting);


            // Get forecast for prospective line item.
            $prospectiveLineItem = new ProspectiveLineItem();
            $prospectiveLineItem->setAdvertiserId($this->advertiserId);
            $prospectiveLineItem->setLineItem($lineItem);

            $options = new AvailabilityForecastOptions();
            $options->setIncludeContendingLineItems(false);

            $forecastService = $this->dfpServices->get($this->session, ForecastService::class);
            $forecast = $forecastService->getAvailabilityForecast(
                $prospectiveLineItem, $options);

            $forecasts[$domain][$name] =  [
                "matched" => $forecast->getMatchedUnits(),
                "available" => $forecast->getAvailableUnits(),
                "unitType" => $forecast->getUnitType(),
                "timeToProcess" => microtime(true) - $starttime
            ];
        }

        return $forecasts;
    }

    private function getActivePlacements(Array $domain = null)
    {
        $placementService =
            $this->dfpServices->get($this->session, PlacementService::class);
        // Create a statement to select placements.
        $pageSize = StatementBuilder::SUGGESTED_PAGE_LIMIT;

        $where_query = "status = :status";
        $where_query = $this->limitQueryToDomain($where_query, $domain);

        $statementBuilder = (new StatementBuilder())
            ->where($where_query)
            ->orderBy('id ASC')
            ->limit($pageSize)
            ->withBindVariableValue(
                'status', InventoryStatus::ACTIVE
            );

        // Retrieve a small amount of placements at a time, paging
        // through until all placements have been retrieved.
        $totalResultSetSize = 0;
        $placements = [];
        do {
            $page = $placementService->getPlacementsByStatement(
                $statementBuilder->toStatement());
            // Print out some information for each placement.
            if ($page->getResults() !== null) {
                $totalResultSetSize = $page->getTotalResultSetSize();
                $i = $page->getStartIndex();

                foreach ($page->getResults() as $placement) {
                    $placements[$placement->getName()] = $placement->getTargetedAdUnitIds();
                }
            }
            $statementBuilder->increaseOffsetBy($pageSize);
        } while ($statementBuilder->getOffset() < $totalResultSetSize);

        return $placements;
    }

    protected function newLineItem()
    {
        // Create a run-of-network line item to forecast on.
        $lineItem = new LineItem();
        $lineItem->setLineItemType(LineItemType::STANDARD);
        $lineItem->setCreativeRotationType(CreativeRotationType::OPTIMIZED);

        if ($this->startdate == date('m/d/Y')) {
            $lineItem->setStartDateTimeType(StartDateTimeType::IMMEDIATELY);
        } else {
            $lineItem->setStartDateTime(DfpDateTimes::fromDateTime(
                DateTime::createFromFormat('m/d/Y', $this->startdate)
            ));
        }
        $lineItem->setEndDateTime(DfpDateTimes::fromDateTime(
            DateTime::createFromFormat('m/d/Y', $this->enddate)));
        $lineItem->setCostType(CostType::CPM);

        // Set the line item to use 50% of the impressions.
        $goal = new Goal();
        $goal->setGoalType(GoalType::LIFETIME);
        $goal->setUnitType(UnitType::IMPRESSIONS);
        $goal->setUnits(50);
        $lineItem->setPrimaryGoal($goal);

        return $lineItem;
    }

    private function buildTargetFromPlacement($placement)
    {
        $adUnitTargeting = [];

        foreach ($placement as $id) {
            $adUnitTarget = new AdUnitTargeting();
            $adUnitTarget->setAdUnitId($id);
            $adUnitTarget->setIncludeDescendants(true);

            $adUnitTargeting[] = $adUnitTarget;
        }

        return $adUnitTargeting;
    }

    private function limitQueryToDomain($where_query, $domain)
    {
        if (!$domain)
            return $where_query;

        $where_query .= " AND (";

        $first = true;
        foreach ($domain as $name) {
            if ($first) {
                $where_query .= "name LIKE '{$name}%'";
                $first = false;
                continue;
            }

            $where_query .= " OR name LIKE '{$name}%'";
        }

        $where_query .= ")";

        return $where_query;
    }
}

