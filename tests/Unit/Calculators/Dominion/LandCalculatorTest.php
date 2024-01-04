<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * @coversDefaultClass \OpenDominion\Calculators\Dominion\LandCalculator
 */
class LandCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $dominion;

    /** @var Mock|BuildingCalculator */
    protected $buildingCalculator;

    /** @var Mock|QueueService */
    protected $queueService;

    /** @var Mock|LandCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dominion = m::mock(Dominion::class);

        $this->sut = m::mock(LandCalculator::class, [
            $this->buildingCalculator = m::mock(BuildingCalculator::class),
            $this->app->make(BuildingHelper::class),
            $this->app->make(LandHelper::class),
            $this->queueService = m::mock(QueueService::class),
        ])->makePartial();
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(LandCalculator::class, $this->app->make(LandCalculator::class));
    }

    /**
     * @covers ::getTotalLand
     */
    public function testGetTotalLand()
    {
        $expected = 0;

        foreach ($this->getLandTypes() as $landType) {
            $this->dominion->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn(1);
            $expected++;
        }

        $this->assertEquals($expected, $this->sut->getTotalLand($this->dominion));
    }

    /**
     * @covers ::getTotalBarrenLand
     */
    public function testGetTotalBarrenLand()
    {
        foreach ($this->getLandTypes() as $landType) {
            $this->dominion->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn(10);
        }

        $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominion)->andReturn(1);
        $this->queueService->shouldReceive('getConstructionQueueTotal')->with($this->dominion)->andReturn(2);

        $this->assertEquals(67, $this->sut->getTotalBarrenLand($this->dominion));
    }

    /**
     * @covers ::getTotalBarrenLandByLandType
     */
    public function testGetTotalBarrenLandByLandType()
    {
        $this->markTestIncomplete();
    }

    /**
     * @covers ::getBarrenLandByLandType
     */
    public function testGetBarrenLandByLandType()
    {
        /** @var Mock|Race $raceMock */
        $raceMock = m::mock(Race::class);
        $raceMock->shouldReceive('getAttribute')->with('home_land_type')->andReturn('plain');

        $this->dominion->shouldReceive('getAttribute')->with('race')->andReturn($raceMock);

        $buildingTypesByLandType = [
            'plain' => [
                'home',
                'alchemy',
                'farm',
                'smithy',
                'masonry',
            ],
            'mountain' => [
                'ore_mine',
                'gryphon_nest',
            ],
            'swamp' => [
                'tower',
                'wizard_guild',
                'temple',
            ],
            'cavern' => [
                'diamond_mine',
                'school',
            ],
            'forest' => [
                'lumberyard',
                //'forest_haven',
            ],
            'hill' => [
                'factory',
                'guard_tower',
                'shrine',
                'barracks',
            ],
            'water' => [
                'dock',
            ],
        ];

        $expected = array_combine(
            array_keys($buildingTypesByLandType),
            array_fill(0, \count($buildingTypesByLandType), 0)
        );

        foreach ($buildingTypesByLandType as $landType => $buildingTypes) {
            $this->dominion->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn(100);
            $expected[$landType] += 100;

            foreach ($buildingTypes as $buildingType) {
                $this->dominion->shouldReceive('getAttribute')->with('building_' . $buildingType)->andReturn(2);
                $this->queueService->shouldReceive('getConstructionQueueTotalByResource')->with($this->dominion, "building_{$buildingType}")->andReturn(1);
                $expected[$landType] -= 3;
            }
        }

        $this->assertEquals($expected, $this->sut->getBarrenLandByLandType($this->dominion));
    }

    /**
     * Returns all the land types.
     *
     * todo: Maybe refactor to $this->landHelper->getLandTypes()?
     *
     * @return array
     */
    private function getLandTypes(): array
    {
        return [
            'plain',
            'mountain',
            'swamp',
            'cavern',
            'forest',
            'hill',
            'water',
        ];
    }
}
