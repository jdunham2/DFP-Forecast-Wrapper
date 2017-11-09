<?php

namespace App\Http\Controllers;

use DateTime;
use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google\AdsApi\Dfp\DfpServices;
use Google\AdsApi\Dfp\DfpSession;
use Google\AdsApi\Dfp\DfpSessionBuilder;
use Google\AdsApi\Dfp\Util\v201708\DfpDateTimes;
use Google\AdsApi\Dfp\Util\v201708\StatementBuilder;
use Google\AdsApi\Dfp\v201708\AdUnitTargeting;
use Google\AdsApi\Dfp\v201708\AvailabilityForecastOptions;
use Google\AdsApi\Dfp\v201708\CostType;
use Google\AdsApi\Dfp\v201708\CreativePlaceholder;
use Google\AdsApi\Dfp\v201708\CreativeRotationType;
use Google\AdsApi\Dfp\v201708\ForecastService;
use Google\AdsApi\Dfp\v201708\Goal;
use Google\AdsApi\Dfp\v201708\GoalType;
use Google\AdsApi\Dfp\v201708\InventoryStatus;
use Google\AdsApi\Dfp\v201708\InventoryTargeting;
use Google\AdsApi\Dfp\v201708\LineItem;
use Google\AdsApi\Dfp\v201708\LineItemType;
use Google\AdsApi\Dfp\v201708\NetworkService;
use Google\AdsApi\Dfp\v201708\PlacementService;
use Google\AdsApi\Dfp\v201708\ProspectiveLineItem;
use Google\AdsApi\Dfp\v201708\Size;
use Google\AdsApi\Dfp\v201708\StartDateTimeType;
use Google\AdsApi\Dfp\v201708\Targeting;
use Google\AdsApi\Dfp\v201708\UnitType;
use Illuminate\Http\Request;

class DfpForcast extends Controller
{
    function action_inventory2()
    {
        $view['content'] = View::forge('reporting/inventory2');
        return View::forge('layout', $view);
//        self::main();
    }

    function index()
    {
        self::main();
    }

    public static function getAvailbleForcast(DfpServices $dfpServices,
                                              DfpSession $session, $advertiserId = 11137450)
    {
        $forecastService = $dfpServices->get($session, ForecastService::class);
        // Create a run-of-network line item to forecast on.
        $lineItem = new LineItem();
        $lineItem->setLineItemType(LineItemType::STANDARD);
        $lineItem->setCreativeRotationType(CreativeRotationType::OPTIMIZED);
        if ($_REQUEST['startdate'] == date('m/d/Y')) {
            $lineItem->setStartDateTimeType(StartDateTimeType::IMMEDIATELY);
        } else {
            $lineItem->setStartDateTime(DfpDateTimes::fromDateTime(
                DateTime::createFromFormat('m/d/Y', $_REQUEST['startdate'])
            ));
        }
        $lineItem->setEndDateTime(DfpDateTimes::fromDateTime(
            DateTime::createFromFormat('m/d/Y', $_REQUEST['enddate'])));
        $lineItem->setCostType(CostType::CPM);
        // Set the line item to use 50% of the impressions.
        $goal = new Goal();
        $goal->setGoalType(GoalType::LIFETIME);
        $goal->setUnitType(UnitType::IMPRESSIONS);
        $goal->setUnits(50);
        $lineItem->setPrimaryGoal($goal);
        // Set the size of creatives that can be associated with this line item.
        $size = new Size();
//        $size->setWidth(728);
//        $size->setHeight(90);
        $size->setIsAspectRatio(false);
        $creativePlaceholder = new CreativePlaceholder();
//        $creativePlaceholder->setSize($size);
//        $lineItem->setCreativePlaceholders([$creativePlaceholder]);
        // Create ad unit targeting for the root ad unit.
        $networkService = $dfpServices->get($session, NetworkService::class);
        $rootAdUnitId =
            $networkService->getCurrentNetwork()->getEffectiveRootAdUnitId();
        $adUnitTargeting = new AdUnitTargeting();
        $adUnitTargeting->setAdUnitId(301360);
        $adUnitTargeting->setIncludeDescendants(true);
        $adUnitTargeting2 = new AdUnitTargeting();
        $adUnitTargeting2->setAdUnitId(301900);
        $adUnitTargeting2->setIncludeDescendants(true);
        $inventoryTargeting = new InventoryTargeting();
        $inventoryTargeting->setTargetedAdUnits([$adUnitTargeting, $adUnitTargeting2]);
        $targeting = new Targeting();
        $targeting->setInventoryTargeting($inventoryTargeting);
        $lineItem->setTargeting($targeting);
        // Get forecast for prospective line item.
        $prospectiveLineItem = new ProspectiveLineItem();
        $prospectiveLineItem->setAdvertiserId($advertiserId);
        $prospectiveLineItem->setLineItem($lineItem);
        $options = new AvailabilityForecastOptions();
        $options->setIncludeContendingLineItems(true);
        $options->setIncludeTargetingCriteriaBreakdown(true);
        $forecast = $forecastService->getAvailabilityForecast(
            $prospectiveLineItem, $options);
        // Print out forecast results.
        $matchedUnits = $forecast->getMatchedUnits();
        $unitType = strtolower($forecast->getUnitType());
        printf("%d %s matched.\n", $matchedUnits, $unitType);
        if ($matchedUnits > 0) {
            $percentAvailableUnits =
                $forecast->getAvailableUnits();
            $percentPossibleUnits =
                $forecast->getPossibleUnits();
            printf("%.2d%% %s available.\n", $percentAvailableUnits, $unitType);
            printf("%.2d%% %s possible.\n", $percentPossibleUnits, $unitType);
        }
        printf("%d contending line items.\n",
            count($forecast->getContendingLineItems()));
    }

    public static function getActivePlacements(DfpServices $dfpServices,
                                               DfpSession $session)
    {
        $placementService =
            $dfpServices->get($session, PlacementService::class);
        // Create a statement to select placements.
        $pageSize = StatementBuilder::SUGGESTED_PAGE_LIMIT;
        $statementBuilder = (new StatementBuilder())
            ->where('status = :status')
            ->orderBy('id ASC')
            ->limit($pageSize)
            ->withBindVariableValue('status', InventoryStatus::ACTIVE);
        // Retrieve a small amount of placements at a time, paging
        // through until all placements have been retrieved.
        $totalResultSetSize = 0;
        do {
            $page = $placementService->getPlacementsByStatement(
                $statementBuilder->toStatement());
            // Print out some information for each placement.
            if ($page->getResults() !== null) {
                $totalResultSetSize = $page->getTotalResultSetSize();
                $i = $page->getStartIndex();
                foreach ($page->getResults() as $placement) {
                    var_dump($placement);
                    printf(
                        "%d) Placement with ID %d and name '%s' was found.\n",
                        $i++,
                        $placement->getId(),
                        $placement->getName()
                    );
                }
            }
            $statementBuilder->increaseOffsetBy($pageSize);
        } while ($statementBuilder->getOffset() < $totalResultSetSize);
        printf("Number of results found: %d\n", $totalResultSetSize);
    }

    public static function main()
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->fromFile("../adsapi_php.ini")
            ->build();
        $session = (new DfpSessionBuilder())
            ->fromFile("../adsapi_php.ini")
            ->withOAuth2Credential($oAuth2Credential)
            ->build();
        self::getAvailbleForcast(new DfpServices(), $session);
//        self::getActivePlacements(new DfpServices(), $session);
    }

}
