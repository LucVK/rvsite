<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;
use Carbon\Carbon;
use DateTimeZone;
use Statamic\Entries\Entry;
use Illuminate\Support\Str;

class WtMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rv:wt-migrate {elements=tcmwpr}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate WT site';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $elements = $this->argument('elements');

        if (str_contains($elements, 'c')) {
            $this->migrateClubmembers();
        }
    }

    private function migrateClubmembers(): void
    {
        $this->warn(PHP_EOL . 'Migratie clubleden...');


        $leden = $this->withProgressBar(DB::connection('wtdb')->select('select * from leden'), function ($lid) {

            $currentSeason = Carbon::now()->year;
            $memberships = $this->getMemberships($lid->ID);

            $memberAttributes = [
                'firstname' => ucwords(strtolower(trim($lid->VOORNAAM))),
                'lastname' => strtoupper(trim($lid->NAAM)),
                'email' => $this->validateEmail(strtolower(trim($lid->EMAIL))),
                'phone' => trim(strtolower($lid->TEL_GSM)),
                'birthday' => $lid->GEBDAT == '0000-00-00' ? null :  $this->validateDate(strtolower($lid->GEBDAT))->format('d/m/Y'),
                'rvid' => $lid->ID,
                'address' => [
                    'street' => trim(strtolower($lid->STRAAT_NR)),
                    'zipcode' => intval(trim(strtolower($lid->POSTCODE))),
                    'city' => trim(strtolower($lid->GEMEENTE)),
                ],
                'clubmemberships' => [],
                'departments' => [],
            ];

            $clubmember = $this->findClubmember($memberAttributes);

            if ($clubmember) {
                $existing_memberships = $clubmember->get('clubmemberships');

                foreach ($memberships as $season => $membership) {
                    if ($season == $currentSeason) {
                        $departments = $clubmember->get('departments');
                        $departments[] = 'dept-wt';
                        $departments = $clubmember->set('departments', $departments);
                        continue;
                    }
                    $existing_memberships[] = [
                        'season' => $season,
                        'department' => $membership,
                        'payment' => 'b',
                        'type' => 'rv_lidgeld',
                        'enabled' => true,
                    ];
                }

                $clubmember->set('clubmemberships', $existing_memberships);
                $clubmember->save();
            } else {

                foreach ($memberships as $season => $membership) {
                    if ($season == $currentSeason) {
                        $memberAttributes['departments'][] = 'dept-wt';
                    }
                    $memberAttributes['clubmemberships'][] = [
                        'season' => $season,
                        'department' => $membership,
                        'payment' => 'b',
                        'type' => 'rv_lidgeld',
                        'enabled' => true,
                    ];
                }

                $clubmember = Entry::make()->collection('clubmembers')->slug((string) Str::uuid());
                $clubmember->data($memberAttributes);
                $clubmember->save();
            }
        });
    }

    private function findClubmember(array $attributes): ?Entry
    {
        $builder = \Statamic\Facades\Entry::query()
            ->where('firstname', $attributes['firstname'])
            ->where('lastname', $attributes['lastname']);

        if (!empty($attributes['email'])) {
            $builder = $builder
                ->where('email', $attributes['email']);
        }

        $clubmember = $builder->first();

        return $clubmember;
    }


    private function validateEmail(string $email): string
    {
        try {
            $val = Validator::make(['email' => $email], ['email' => 'required|email']);
            return $val->validate()['email'];
        } catch (Exception $e) {
            return '';
        }
    }

    private function validateDate($date, $format = 'Y-m-d'): Carbon
    {
        $xx = Carbon::createFromFormat($format, trim($date));
        $xx->hour = 0;
        $xx->minute = 0;
        $xx->second = 0;
        $xx->tz = new DateTimeZone('Europe/Brussels');
        return $xx;
    }

    private function getMemberships(int $lid_id): array
    {
        $result = [];
        $lidmaatschappen = DB::connection('wtdb')->table('lidmaatschap')->where('LID_ID', $lid_id)->orderBy('JAARTAL', 'desc')->get();
        foreach ($lidmaatschappen as $lidmaatschap) {
            if (!empty($lidmaatschap->AFDELING)) {
                // $this->warn(PHP_EOL . $lidmaatschap->LID_ID . ' ' . $lidmaatschap->JAARTAL . ' ' . $lidmaatschap->AFDELING);

                $department = $this->resolveDepartment($lidmaatschap->AFDELING);
                if ($department) {
                    $result[$lidmaatschap->JAARTAL] = $department;
                }
            }
        }
        return $result;
    }

    private function resolveDepartment(string $afdeling): ?string
    {
        switch (strtolower(trim($afdeling))) {
            case 'voetbal1':
                return 'dept-den-een';
                break;

            case 'voetbal2':
                return 'dept-den-twee';
                break;

            case 'voetbal3':
                return 'dept-den-drie';
                break;

            case 'voetbal4':
                return 'dept-voetbal-dames';
                break;

            case 'petanque':
                return 'dept-petanque';
                break;

            case 'biljarten + losse leden':
                return 'dept-biljart';
                break;

            case 'kaarten':
                return 'dept-kaart';
                break;

            case 'fietsers':
                return 'dept-wt';
                break;

            default:
                return null;
                break;
        }
    }
}
