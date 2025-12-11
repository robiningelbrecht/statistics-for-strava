<?php

namespace App\Application\Import\ImportActivities\Pipeline;

use App\Domain\Activity\Route\RouteGeography;
use App\Domain\Activity\Route\RouteGeographyAnalyzer;
use App\Domain\Integration\Geocoding\Nominatim\CouldNotReverseGeocodeAddress;
use App\Domain\Integration\Geocoding\Nominatim\Nominatim;
use App\Infrastructure\ValueObject\Geography\EncodedPolyline;

final readonly class AnalyzeRouteGeography implements ActivityImportStep
{
    public function __construct(
        private Nominatim $nominatim,
        private RouteGeographyAnalyzer $routeGeographyAnalyzer,
    ) {
    }

    public function process(ActivityImportContext $context): ActivityImportContext
    {
        $activity = $context->getActivity() ?? throw new \RuntimeException('Activity not set on $context');
        $sportType = $activity->getSportType();

        $rawRouteGeographyData = [];
        if (!$activity->getRouteGeography()->isReversedGeocoded() && $activity->getStartingCoordinate()) {
            if ($sportType->supportsReverseGeocoding()) {
                try {
                    $rawRouteGeographyData = $this->nominatim->reverseGeocode($activity->getStartingCoordinate());
                } catch (CouldNotReverseGeocodeAddress) {
                }
            } elseif ($activity->isZwiftRide() && ($zwiftMap = $activity->getLeafletMap())) {
                $rawRouteGeographyData = [
                    'state' => $zwiftMap->getLabel(),
                ];
            }
        }

        if (!$activity->getRouteGeography()->hasBeenAnalyzedForRouteGeography()
            && $sportType->supportsReverseGeocoding() && $activity->getPolyline()) {
            $rawRouteGeographyData[RouteGeography::PASSED_TROUGH_COUNTRIES] = $this->routeGeographyAnalyzer->analyzeForPolyline(
                EncodedPolyline::fromString($activity->getPolyline())
            );
        }
        $activity->updateRouteGeography(RouteGeography::create($rawRouteGeographyData));

        return $context->withActivity($activity);
    }
}
