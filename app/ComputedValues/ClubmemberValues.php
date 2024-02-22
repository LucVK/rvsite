<?php

namespace App\ComputedValues;

use Carbon\Carbon;
use Statamic\Entries\Entry;

class ClubmemberValues
{
    public static function isActive(Entry $clubmember): bool
    {
        $currentSeason = Carbon::now()->year;
        $memberships = collect($clubmember->clubmemberships);

        return $memberships->contains(function($membership) use ($currentSeason) {
            $rr = $membership->type == 'rv_lidgeld' && $membership->season == $currentSeason;
            return $rr;
        });
    }
}