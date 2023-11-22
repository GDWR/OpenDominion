<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Mappers\Dominion\InfoMapper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Race;
use OpenDominion\Services\GameEventService;

class CalculationsController extends AbstractDominionController
{
    /**
     * @var SpellCalculator
     */
    private $spellCalculator;
    /**
     * @var InfoMapper
     */
    private $infoMapper;

    public function __construct(
        SpellCalculator $spellCalculator,
        InfoMapper $infoMapper
    )
    {
        $this->spellCalculator = $spellCalculator;
        $this->infoMapper = $infoMapper;
    }

    public function getIndex(Request $request)
    {
        $targetDominionId = $request->input('dominion');
        $targetDominion= null;
        $targetInfoOps = null;

        if ($targetDominionId !== null) {
            $dominion = $this->getSelectedDominion();
            $targetDominion = Dominion::find($targetDominionId);
            if ($dominion->inRealmAndSharesAdvisors($targetDominion)) {
                $targetInfoOps = collect([
                    (object)[
                        'type' => 'clear_sight',
                        'data' => $this->infoMapper->mapStatus($targetDominion, false)
                    ],
                    (object)[
                        'type' => 'revelation',
                        'data' => $this->spellCalculator->getActiveSpells($targetDominion)
                    ],
                    (object)[
                        'type' => 'castle_spy',
                        'data' => $this->infoMapper->mapImprovements($targetDominion)
                    ],
                    (object)[
                        'type' => 'barracks_spy',
                        'data' => $this->infoMapper->mapMilitary($targetDominion, false)
                    ],
                    (object)[
                        'type' => 'survey_dominion',
                        'data' => $this->infoMapper->mapBuildings($targetDominion)
                    ],
                    (object)[
                        'type' => 'land_spy',
                        'data' => $this->infoMapper->mapLand($targetDominion)
                    ],
                    (object)[
                        'type' => 'vision',
                        'data' => [
                            'techs' => $this->infoMapper->mapTechs($targetDominion)
                        ]
                    ],
                ])->keyBy('type');
            } else {
                $targetInfoOps = InfoOp::query()
                    ->where('target_dominion_id', $targetDominionId)
                    ->where('source_realm_id', $dominion->realm->id)
                    ->where('latest', true)
                    ->get()
                    ->filter(function ($infoOp) {
                        if ($infoOp->type == 'barracks_spy' && $infoOp->isInvalid()) {
                            return false;
                        }
                        return true;
                    })
                    ->keyBy('type');
            }
        }

        return view('pages.dominion.calculations', [
            'landCalculator' => app(LandCalculator::class),
            'targetDominion' => $targetDominion,
            'targetInfoOps' => $targetInfoOps,
            'races' => Race::with(['units', 'units.perks'])->where('playable', true)->orderBy('name')->get(),
            'buildingHelper' => app(BuildingHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function getMilitary(Request $request)
    {
        $targetDominionId = $request->input('dominion');
        if ($targetDominionId == null) {
            throw('exception');
        }

        $dominion = $this->getSelectedDominion();
        $targetDominion = Dominion::find($targetDominionId);
        $races = Race::with(['units', 'units.perks'])->where('playable', true)->orderBy('name')->get()->keyBy('id');
        if ($races->contains($targetDominion->race->id)) {
            $race = $races[$targetDominion->race->id];
        } else {
            $race = $targetDominion->race()->with(['units', 'units.perks'])->first();
        }

        if ($dominion->inRealmAndSharesAdvisors($targetDominion)) {
            $targetInfoOps = collect([
                (object)[
                    'type' => 'clear_sight',
                    'data' => $this->infoMapper->mapStatus($targetDominion, false)
                ],
                (object)[
                    'type' => 'revelation',
                    'data' => $this->spellCalculator->getActiveSpells($targetDominion)
                ],
                (object)[
                    'type' => 'castle_spy',
                    'data' => $this->infoMapper->mapImprovements($targetDominion)
                ],
                (object)[
                    'type' => 'barracks_spy',
                    'data' => $this->infoMapper->mapMilitary($targetDominion, false)
                ],
                (object)[
                    'type' => 'survey_dominion',
                    'data' => $this->infoMapper->mapBuildings($targetDominion)
                ],
                (object)[
                    'type' => 'land_spy',
                    'data' => $this->infoMapper->mapLand($targetDominion)
                ],
                (object)[
                    'type' => 'vision',
                    'data' => [
                        'techs' => $this->infoMapper->mapTechs($targetDominion)
                    ]
                ],
            ])->keyBy('type');
        } else {
            $targetInfoOps = InfoOp::query()
                ->where('target_dominion_id', $targetDominionId)
                ->where('source_realm_id', $dominion->realm->id)
                ->where('latest', true)
                ->get()
                ->filter(function ($infoOp) {
                    if ($infoOp->type == 'barracks_spy' && $infoOp->isInvalid()) {
                        return false;
                    }
                    return true;
                })
                ->keyBy('type');
        }

        return view('pages.dominion.calculations-military', [
            'landCalculator' => app(LandCalculator::class),
            'targetDominion' => $targetDominion,
            'targetInfoOps' => $targetInfoOps,
            'race' => $race,
            'races' => $races,
            'buildingHelper' => app(BuildingHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }
}
