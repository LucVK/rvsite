<?php

namespace App\Actions;

use Exception;
use Statamic\Actions\Action;
use Statamic\Assets\Asset;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use League\Csv\Statement;
use Statamic\Facades\Entry;
use Illuminate\Support\Str;

class ImportClubmembers extends Action
{
    public function __construct()
    {
        static::$title = __('ImportClubmembers');
    }


    /**
     * The run method
     *
     * @return void
     */
    public function run($items, $values)
    {
        foreach ($items as $key => $asset) {
            $path = Storage::disk($asset->container->disk)->path($asset->path);
            $reader = Reader::createFromPath($path, 'r');
            $reader->setDelimiter(';');
            $reader->setHeaderOffset(0);
            $reader->setOutputBOM(Reader::BOM_UTF8);
            $reader->addStreamFilter('convert.iconv.ISO-8859-15/UTF-8');

            $stmt = Statement::create()
                ->offset(0)
                ->limit(2000);
            $records = $stmt->process($reader);


            $found = 0;
            $notfound = 0;
            $nodept = 0;

            foreach ($records as $offset => $record) {
                $firstname = ucwords(trim($record['Voornaam']));
                $lastname = ucwords(trim($record['Achternaam']));
                $email = strtolower(trim($record['Email']));
                $birthday = $record['Geboortedatum'];
                $phone = trim($record['Telefoon']);
                $street = trim($record['Straat']);
                $zipcode = trim($record['Postcode']);
                $city = trim($record['Gemeente']);
                $afdeling = strtolower(trim($record['Afdeling']));
                $department = $this->resolveDepartment($afdeling);

                try {
                    $xx = $this->validateEmail($email);
                } catch (Exception $e) {
                    $email = null;
                }

                if (!$department) {
                    $nodept++;
                    continue;
                }

                $clubmember = Entry::query()->where('collection', 'clubmembers')
                    ->where('firstname', $firstname)
                    ->where('lastname', $lastname)
                    ->where('email', $email)->first();
                if ($clubmember) {
                    $found++;
                } else {
                    $notfound++;
                    $clubmember = Entry::make()->collection('clubmembers')->slug((string) Str::uuid());
                    $clubmember
                        ->data([
                            'firstname' => $firstname,
                            'lastname' => $lastname,
                            'email' => $email,
                            'birthday' => $birthday,
                            'phone' => $phone,
                            'address' => [
                                'street' => $street,
                                'zipcode' => $zipcode,
                                'city' => $city,
                            ],
                            'clubmemberships' => [
                                [
                                    'season' => 2024,
                                    'department' => $department,
                                    'payment' => 'b',
                                    'type' =>  'rv_lidgeld',
                                    'enabled' => true,
                                ],
                            ],
                            'departments' => [
                                $department,
                            ]
                        ]);
                    $clubmember->save();
                }
            }
        }

        return 'Entries found:' . $found .  ' not found:' . $notfound . ' / ' . $nodept;
    }

    private function validateEmail(string $email)
    {
        $val = Validator::make(['email' => $email], ['email' => 'required|email']);
        $result = $val->validate();
        // return $result;
    }

    private function resolveDepartment(string $afdeling): ?string
    {
        switch ($afdeling) {
            case 'voetbal den een':
                return 'dept-den-een';
                break;

            case 'voetbal den twee':
                return 'dept-den-twee';
                break;

            case 'voetbal den drie':
                return 'dept-den-drie';
                break;

            case 'voetbal dames':
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

    public function visibleTo($item)
    {
        $xx = $this->context;

        if ($item instanceof Asset) {
            return $this->context['container'] == 'admin' && $this->context['folder'] == 'leden';
        }
        return false;
    }
}
