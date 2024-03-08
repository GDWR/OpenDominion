<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;

class OpsCalculator
{
    /**
     * @var float Base amount of infamy lost each hour
     */
    protected const INFAMY_DECAY_BASE = -15;

    /**
     * @var float Base amount of resilience lost each hour
     */
    protected const SPY_RESILIENCE_DECAY = -8;
    protected const WIZARD_RESILIENCE_DECAY = -8;

    /**
     * @var float Base amount of resilience gained per op
     */
    protected const SPY_RESILIENCE_GAIN = 10;
    protected const WIZARD_RESILIENCE_GAIN = 10;

    /** @var GovernmentService */
    protected $governmentService;

    /** @var GuardMembershipService */
    protected $guardMembershipService;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    private $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var SpellHelper */
    protected $spellHelper;

    /**
     * OpsCalculator constructor.
     *
     * @param GovernmentService $governmentService
     * @param GuardMembershipService $guardMembershipService
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     * @param PopulationCalculator $populationCalculator
     * @param RangeCalculator $rangeCalculator
     * @param SpellHelper $spellHelper
     */
    public function __construct(
        GovernmentService $governmentService,
        GuardMembershipService $guardMembershipService,
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        PopulationCalculator $populationCalculator,
        RangeCalculator $rangeCalculator,
        SpellHelper $spellHelper
    )
    {
        $this->governmentService = $governmentService;
        $this->guardMembershipService = $guardMembershipService;
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->populationCalculator = $populationCalculator;
        $this->rangeCalculator = $rangeCalculator;
        $this->spellHelper = $spellHelper;
    }

    /**
     * Returns the success modifier based on relative strength.
     *
     * @param float $selfStrength
     * @param float $targetStrength
     * @return float
     */
    public function getSuccessModifier(float $selfStrength, float $targetStrength) {
        return ($selfStrength - $targetStrength) / 1000;
    }

    /**
     * Returns the chance of success for an info operation or spell.
     *
     * @param float $selfRatio
     * @param float $targetRatio
     * @return float
     */
    public function infoOperationSuccessChance(float $selfRatio, float $targetRatio, float $selfStrength, float $targetStrength): float
    {
        if (!$targetRatio) {
            return 1;
        }

        $relativeRatio = $selfRatio / $targetRatio;
        $successChance = 0.8 ** (2 / (($relativeRatio * 1.4) ** 1.2));
        $successChance += $this->getSuccessModifier($selfStrength, $targetStrength);
        return clamp($successChance, 0.01, 0.98);
    }

    /**
     * Returns the chance of success for a theft operation.
     *
     * @param float $selfRatio
     * @param float $targetRatio
     * @return float
     */
    public function theftOperationSuccessChance(float $selfRatio, float $targetRatio, float $selfStrength, float $targetStrength): float
    {
        if (!$targetRatio) {
            return 1;
        }

        $relativeRatio = $selfRatio / $targetRatio;
        $successChance = 0.7 ** (2 / (($relativeRatio * 1.3) ** 1.2));
        $successChance += $this->getSuccessModifier($selfStrength, $targetStrength);
        return clamp($successChance, 0.01, 0.97);
    }

    /**
     * Returns the chance of success for a hostile operation or spell.
     *
     * @param float $selfRatio
     * @param float $targetRatio
     * @return float
     */
    public function blackOperationSuccessChance(float $selfRatio, float $targetRatio, float $selfStrength, float $targetStrength): float
    {
        if (!$targetRatio) {
            return 1;
        }

        $relativeRatio = $selfRatio / $targetRatio;
        $successChance = 0.7 ** (2 / (($relativeRatio * 1.3) ** 1.2));
        $successChance += $this->getSuccessModifier($selfStrength, $targetStrength);
        return clamp($successChance, 0.01, 0.97);
    }

    /**
     * Returns the percentage of spies killed after a failed operation.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getSpyLosses(Dominion $dominion, Dominion $target, string $type): float
    {
        // Values (percentage)
        if ($type == 'info') {
            $spiesKilledBasePercentage = 0.25;
            $min = 0.25;
            $max = 1;
        } elseif ($type == 'theft') {
            $spiesKilledBasePercentage = 1;
            $min = 0.5;
            $max = 1.5;
        } else {
            $spiesKilledBasePercentage = 1;
            $min = 0.5;
            $max = 1.5;
        }

        $selfRatio = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
        $targetRatio = $this->militaryCalculator->getSpyRatio($target, 'defense');

        $spyLossSpaRatio = ($targetRatio / $selfRatio);
        $spiesKilledPercentage = clamp($spiesKilledBasePercentage * $spyLossSpaRatio, $min, $max);

        // Guilds
        $guildSpyCasualtyReduction = 2.5;
        $guildSpyCasualtyReductionMax = 25;

        $spiesKilledMultiplier = (1 - min(
            (($dominion->building_wizard_guild / $this->landCalculator->getTotalLand($dominion)) * $guildSpyCasualtyReduction),
            ($guildSpyCasualtyReductionMax / 100)
        ));

        // Spells
        $spiesKilledMultiplier += $dominion->getSpellPerkMultiplier('spy_losses');

        // Techs
        $spiesKilledMultiplier += $dominion->getTechPerkMultiplier('spy_losses');

        // Mutual War
        if ($this->governmentService->isAtMutualWar($dominion->realm, $target->realm)) {
            $spiesKilledMultiplier *= 0.8;
        }

        return ($spiesKilledPercentage / 100) * $spiesKilledMultiplier;
    }

    /**
     * Returns the percentage of assassins killed after a failed operation.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getAssassinLosses(Dominion $dominion, Dominion $target, string $type): float
    {
        return $this->getSpyLosses($dominion, $target, $type);
    }

    /**
     * Returns the percentage of wizards killed after a failed spell.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getWizardLosses(Dominion $dominion, Dominion $target, string $type): float
    {
        // Values (percentage)
        if ($type == 'hostile') {
            $wizardsKilledBasePercentage = 1;
            $min = 0.5;
            $max = 1.5;
        } else {
            return 0;
        }

        $selfRatio = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
        $targetRatio = $this->militaryCalculator->getWizardRatio($target, 'defense');

        $wizardLossSpaRatio = ($targetRatio / $selfRatio);
        $wizardsKilledPercentage = clamp($wizardsKilledBasePercentage * $wizardLossSpaRatio, $min, $max);

        // Guilds
        $guildCasualtyReduction = 2.5;
        $guildWizardCasualtyReductionMax = 25;

        $wizardsKilledMultiplier = (1 - min(
            (($dominion->building_wizard_guild / $this->landCalculator->getTotalLand($dominion)) * $guildCasualtyReduction),
            ($guildWizardCasualtyReductionMax / 100)
        ));

        // Mutual War
        if ($this->governmentService->isAtMutualWar($dominion->realm, $target->realm)) {
            $wizardsKilledMultiplier *= 0.8;
        }

        return ($wizardsKilledPercentage / 100) * $wizardsKilledMultiplier;
    }

    /**
     * Returns the percentage of archmages killed after a failed spell.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getArchmageLosses(Dominion $dominion, Dominion $target, string $type): float
    {
        return $this->getWizardLosses($dominion, $target, $type) / 10;
    }

    /**
     * Returns the amount of infamy gained by a Dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @param float $modifier
     * @return int
     */
    public function getInfamyGain(Dominion $dominion, Dominion $target, string $type, float $modifier = 1): int
    {
        $infamy = 5;
        if ($type == 'spy') {
            $selfRatio = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
            $targetRatio = $this->militaryCalculator->getSpyRatio($target, 'defense');
            $selfStrength = $dominion->spy_strength;
            $targetStrength = $target->spy_strength;
        } elseif ($type == 'wizard') {
            $selfRatio = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
            $targetRatio = $this->militaryCalculator->getWizardRatio($target, 'defense');
            $selfStrength = $dominion->wizard_strength;
            $targetStrength = $target->wizard_strength;
        } else {
            return 0;
        }

        $successRate = $this->blackOperationSuccessChance($selfRatio, $targetRatio, $selfStrength, $targetStrength);
        if ($successRate < 0.7 && $successRate >= 0.6) {
            $infamy += 15;
        } elseif ($successRate < 0.6 && $successRate >= 0.5) {
            $infamy += 30;
        } elseif ($successRate < 0.5 && $successRate >= 0.4) {
            $infamy += 40;
        } elseif ($successRate < 0.4) {
            $infamy += 50;
        }

        $range = $this->rangeCalculator->getDominionRange($dominion, $target);
        if ($range >= 75) {
            $infamy += 10;
        }

        // Reduced outside of Shadow League or Mutual War
        $blackGuard = $this->guardMembershipService->isBlackGuardMember($dominion) && $this->guardMembershipService->isBlackGuardMember($target);
        $mutualWarDeclared = $this->governmentService->isAtMutualWar($dominion->realm, $target->realm);
        if (!($blackGuard || $mutualWarDeclared)) {
            $infamy = $infamy / 3;
        }

        return round($infamy * $modifier);
    }

    /**
     * Returns the Dominion's hourly infamy decay.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getInfamyDecay(Dominion $dominion): int
    {
        $decay = static::INFAMY_DECAY_BASE;

        $masteryCombined = min($dominion->spy_mastery, 500) + min($dominion->wizard_mastery, 500);
        $minInfamy = floor($masteryCombined / 100) * 50;
        if ($dominion->infamy + $decay < $minInfamy) {
            return $minInfamy - $dominion->infamy;
        }

        return max($decay, -$dominion->infamy);
    }

    /**
     * Returns the amount of resilience gained by a Dominion.
     *
     * @param Dominion $dominion
     * @param string $type
     * @return int
     */
    public function getResilienceGain(Dominion $dominion, string $type): int
    {
        if ($type == 'spy') {
            $resilience = static::SPY_RESILIENCE_GAIN;
            if ($dominion->spy_resilience + $resilience > 1000) {
                return 1000 - $dominion->spy_resilience;
            }
        } elseif ($type == 'wizard') {
            $resilience = static::WIZARD_RESILIENCE_GAIN;
            if ($dominion->wizard_resilience + $resilience > 1000) {
                return 1000 - $dominion->wizard_resilience;
            }
        } else {
            return 0;
        }

        // TODO: Placeholder for tech perk

        return $resilience;
    }

    /**
     * Returns the Dominion's hourly resilience decay.
     *
     * @param Dominion $dominion
     * @param string $type
     * @return int
     */
    public function getResilienceDecay(Dominion $dominion, string $type): int
    {
        if ($type == 'spy') {
            $decay = static::SPY_RESILIENCE_DECAY;
            $resilience = $dominion->spy_resilience;
        } elseif ($type == 'wizard') {
            $decay = static::WIZARD_RESILIENCE_DECAY;
            $resilience = $dominion->wizard_resilience;
        } else {
            return 0;
        }

        // TODO: Placeholder for tech perk

        return max($decay, -$resilience);
    }

    /**
     * Returns the damage reduction from defensive ratio.
     *
     * @param Dominion $dominion
     * @param string $type
     * @return float
     */
    public function getDamageReduction(Dominion $dominion, string $type): float
    {
        $ratio = 0;

        if ($type == 'spy') {
            $ratio = $this->militaryCalculator->getSpyRatio($dominion, 'defense');
        } elseif ($type == 'wizard') {
            $ratio = $this->militaryCalculator->getWizardRatio($dominion, 'defense');
        }

        if ($ratio == 0) {
            return 0;
        }

        // Scale ratio required from 0.5 at Day 4, to 1.0 at Day 24, to 1.5 at Day 44
        $days = clamp($dominion->round->daysInRound() - 4, 0, 40);
        $daysModifier = (0.025 * $days) + 0.5;
        $modifiedRatio = $ratio / $daysModifier;

        return min(0.5, 0.72 * log(1 + 4 * $modifiedRatio, 10));
    }

    /**
     * Returns the amount of mastery gained by a Dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @param float $modifier
     * @return int
     */
    public function getMasteryGain(Dominion $dominion, Dominion $target, string $type, float $modifier = 1): int
    {
        if ($type == 'spy') {
            $selfMastery = $dominion->spy_mastery;
            $targetMastery = $target->spy_mastery;
        } elseif ($type == 'wizard') {
            $selfMastery = $dominion->wizard_mastery;
            $targetMastery = $target->wizard_mastery;
        } else {
            return 0;
        }

        $mastery = round($this->getInfamyGain($dominion, $target, $type, $modifier) / 10);
        $masteryDifference = $selfMastery - $targetMastery;

        if ($masteryDifference > 500) {
            return 0;
        }
        if (abs($masteryDifference) <= 100) {
            $mastery += 1;
        }
        if ($targetMastery > $selfMastery) {
            $mastery += 1;
        }

        return $mastery;
    }

    /**
     * Returns the amount of mastery lost by a Dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return int
     */
    public function getMasteryLoss(Dominion $dominion, Dominion $target, string $type): int
    {
        if ($type == 'spy') {
            $selfMastery = $dominion->spy_mastery;
            $targetMastery = $target->spy_mastery;
        } elseif ($type == 'wizard') {
            $selfMastery = $dominion->wizard_mastery;
            $targetMastery = $target->wizard_mastery;
        } else {
            return 0;
        }

        $mastery = 0;
        $masteryDifference = $selfMastery - $targetMastery;

        if ($targetMastery <= 100) {
            return 0;
        }
        if (abs($masteryDifference) <= 100) {
            $mastery += 1;
        }
        if ($targetMastery > $selfMastery) {
            $mastery += 1;
        }

        return $mastery;
    }

    /*
     * Returns the spell vulnerability protection modifier
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpellVulnerablilityProtectionModifier(Dominion $dominion): float
    {
        $ratioProtection = $this->getDamageReduction($dominion, 'wizard');
        $spiresProtection = $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'spires', true);

        return (1 - $ratioProtection) * (1 - $spiresProtection);
    }

    /*
     * Returns the base percentage of max peasants that are vulnerable to fireball damage
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPeasantVulnerabilityByDayModifier(Dominion $dominion): float
    {
        // Scale vulnerability from 0.2 at Day 4, to 0.25 at Day 24, to 0.3 at Day 44
        $days = clamp($dominion->round->daysInRound() - 4, 0, 40);
        $daysModifier = (0.0025 * $days) + 0.35;

        return $daysModifier;
    }

    /*
     * Returns the final percentage of peasants that are vulnerable to fireball
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPeasantVulnerablilityModifier(Dominion $dominion): float
    {
        $vulnerabilityModifier = $this->getPeasantVulnerabilityByDayModifier($dominion);
        $protectionModifier = $this->getSpellVulnerablilityProtectionModifier($dominion);

        return $vulnerabilityModifier * $protectionModifier;
    }

    /*
     * Returns the raw number of max peasants that are protected from fireball damage
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPeasantsProtected(Dominion $dominion): int
    {
        $vulnerabilityModifier = $this->getImprovementVulnerablilityModifier($dominion);
        $vulnerablePeasants = max(0, $this->populationCalculator->getMaxPeasantPopulation($dominion));

        return round($vulnerablePeasants * (1 - $vulnerabilityModifier));
    }

    /*
     * Returns the raw number of peasants that are not protected from fireball damage
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPeasantsUnprotected(Dominion $dominion): int
    {
        $protectedPeasants = $this->getPeasantsProtected($dominion);

        return max(0, $dominion->peasants - $protectedPeasants);
    }

    /*
     * Returns the raw number of peasants that can be killed by fireball
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPeasantsVulnerable(Dominion $dominion): int
    {
        $maxPeasants = max(0, $this->populationCalculator->getMaxPeasantPopulation($dominion));

        return max(0, $maxPeasants - $this->getPeasantsProtected($dominion));
    }

    /*
     * Returns the base percentage of total investment that is vulnerable to lightning damage
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getImprovementVulnerablilityByDayModifier(Dominion $dominion): float
    {
        // Scale vulnerability from 0.3 at Day 4, to 0.25 at Day 24, to 0.2 at Day 44
        $days = clamp($dominion->round->daysInRound() - 4, 0, 40);
        $daysModifier = 0.3 - (0.0025 * $days);

        return $daysModifier;
    }

    /*
     * Returns the final percentage of improvements that are vulnerable to lightning damage
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getImprovementVulnerablilityModifier(Dominion $dominion): float
    {
        $vulnerabilityModifier = $this->getImprovementVulnerablilityByDayModifier($dominion);
        $protectionModifier = $this->getSpellVulnerablilityProtectionModifier($dominion);

        return $vulnerabilityModifier * $protectionModifier;
    }

    /*
     * Returns the raw amount of total improvements that are protected from lightning damage
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getImprovementsProtected(Dominion $dominion): int
    {
        $vulnerabilityModifier = $this->getImprovementVulnerablilityModifier($dominion);
        $vulnerableInvestments = max(0, $dominion->stat_total_investment - $dominion->improvement_spires - $dominion->improvement_harbor);

        return round($vulnerableInvestments * (1 - $vulnerabilityModifier));
    }

    /*
     * Returns the raw amount of current improvements that are not protected from lightning damage
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getImprovementsUnprotected(Dominion $dominion, ?string $improvementType = null): int
    {
        $protectedImprovements = $this->getImprovementsProtected($dominion);
        $currentImprovements = $this->improvementCalculator->getImprovementTotal($dominion);
        $destroyableImprovements = $currentImprovements - $dominion->improvement_spires - $dominion->improvement_harbor;

        if ($destroyableImprovements == 0) {
            return 0;
        }

        $modifier = 1;
        if ($improvementType !== null) {
            $modifier = max(0, $dominion->{$improvementType} / $destroyableImprovements);
        }

        return max(0, round(($destroyableImprovements - $protectedImprovements) * $modifier));
    }

    /*
     * Returns the raw amount of current improvements that can be destroyed by lightning damage
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getImprovementsVulnerable(Dominion $dominion, ?string $improvementType = null): int
    {
        $protectedImprovements = $this->getImprovementsProtected($dominion);
        $destroyableImprovements = $dominion->stat_total_investment - $dominion->improvement_spires - $dominion->improvement_harbor;

        if ($destroyableImprovements == 0) {
            return 0;
        }

        $modifier = 1;
        if ($improvementType !== null) {
            $modifier = max(0, $dominion->{$improvementType} / $destroyableImprovements);
        }

        return max(0, round(($destroyableImprovements - $protectedImprovements) * $modifier));
    }
}
