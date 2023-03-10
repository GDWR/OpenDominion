<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Tech;

class TechHelper
{
    public function getTechs()
    {
        return Tech::with('perks')->active()->get()->keyBy('key');
    }

    public function getTechPerkStrings()
    {
        return [
            // Military related
            'defense' => '%s%% defensive power',
            'offense' => '%s%% offensive power',
            'military_cost' => '%s%% military training cost',
            'guard_tax' => '%s%% platinum tax from Royal Guard',
            'prestige_gains' => '%s%% increased prestige gains',
            'boat_capacity' => '%s boat capacity',
            'barracks_housing' => '%s barracks housing',

            // Casualties related
            'casualties' => '%s%% casualties',
            'casualties_defense' => '%s%% defensive casualties',
            'casualties_offense' => '%s%% offensive casualties',
            'casualties_wonders' => '%s%% casualties when attacking wonders',

            // Logistics
            'construction_cost' => '%s%% construction platinum cost',
            'construction_platinum_cost' => '%s%% construction platinum cost',
            'construction_lumber_cost' => '%s%% construction lumber cost',
            'destruction_discount' => '%s%% of destroyed buildings can be rebuilt at a discount',
            'destruction_refund' => '%s%% refund when destroying buildings',
            'exchange_bonus' => '%s%% better exchange rates',
            'explore_draftee_cost' => '%s draftee per acre explore cost (min 6)',
            'explore_morale_cost' => '%s%% exploring morale drop',
            'explore_platinum_cost' => '%s%% exploring platinum cost',
            'extra_barren_max_population' => '%s population from barren land',
            'max_population' => '%s%% maximum population',
            'population_growth' => '%s%% population growth',
            'rezone_cost' => '%s%% rezoning platinum cost',
            'invest_bonus_harbor' => '%s%% bonus to harbor investment',
            'invest_bonus_spires' => '%s%% bonus to spires investment',

            // Spy related
            'enemy_assassinate_draftees_damage' => '%s%% draftees lost in assassination attempts',
            'enemy_assassinate_wizards_damage' => '%s%% wizards lost in assassination attempts',
            'spy_cost' => '%s%% cost of spies',
            'spy_losses' => '%s%% spy losses on failed operations',
            'spy_strength' => '%s%% spy power',
            'spy_strength_defense' => '%s%% defensive spy power',
            'spy_strength_recovery' => '%s spy strength per hour',
            'theft_gains' => '%s%% resources gained from theft',
            'theft_losses' => '%s%% resources lost to theft',
            'fools_gold_cost' => '%s%% Fool\'s Gold mana cost',
            'improved_fools_gold' => 'Fool\'s Gold now protects ore/lumber/mana',

            // Resource related
            'food_consumption' => '%s%% food consumption',
            'food_production' => '%s%% food production',
            'food_production_docks' => '%s%% food production from docks',
            'food_production_prestige' => '%s%% food production from prestige',
            'boat_production' => '%s%% boat production',
            'gem_production' => '%s%% gem production',
            'lumber_production' => '%s%% lumber production',
            'mana_production' => '%s%% mana production',
            'mana_production_raw' => '%s mana production per tower',
            'wartime_mana_production_raw' => '%s mana production per tower for each war relation (max 2)',
            'ore_production' => '%s%% ore production',
            'platinum_production' => '%s%% platinum production',
            'food_decay' => '%s%% food decay',
            'lumber_decay' => '%s%% lumber rot',
            'mana_decay' => '%s%% mana drain',

            // Wizard related
            'enemy_disband_spies_damage' => '%s%% enemy disband spies damage',
            'enemy_fireball_damage' => '%s%% enemy fireball damage',
            'enemy_lightning_bolt_damage' => '%s%% enemy lightning bolt damage',
            'enemy_spell_duration' => '%s black op spell duration',
            'spell_cost' => '%s%% cost of spells',
            'self_spell_cost' => '%s%% cost of self spells',
            'racial_spell_cost' => '%s%% cost of racial spells',
            'wizard_cost' => '%s%% cost of wizards',
            'wizard_strength' => '%s%% wizard power',
            'wizard_strength_recovery' => '%s wizard strength per hour',
            'wonder_damage' => '%s%% wonder damage',
        ];
    }

    public function getTechDescription(Tech $tech, string $separator = ', '): string
    {
        $perkTypeStrings = $this->getTechPerkStrings();

        $perkStrings = [];
        foreach ($tech->perks as $perk) {
            if (isset($perkTypeStrings[$perk->key])) {
                $perkValue = (float)$perk->pivot->value;
                if ($perkValue < 0) {
                    $perkStrings[] = vsprintf($perkTypeStrings[$perk->key], $perkValue);
                } else {
                    $perkStrings[] = vsprintf($perkTypeStrings[$perk->key], '+' . $perkValue);
                }
            }
        }

        return implode($separator, $perkStrings);
    }

    public function getX(Tech $tech): int
    {
        $parts = explode('_', $tech->key);
        if (isset($parts[1])) {
            return 10 * $parts[1];
        }
        return 0;
    }

    public function getY(Tech $tech): int
    {
        $parts = explode('_', $tech->key);
        if (isset($parts[2])) {
            return 10 * $parts[2];
        }
        return 0;
    }

    public function getTechPerkJSON(Tech $tech): string
    {
        $techPerks = [];
        foreach ($tech->perks as $perk) {
            $techPerks[$perk->key] = $perk->pivot->value;
        }
        return htmlentities(json_encode($techPerks));
    }
}
