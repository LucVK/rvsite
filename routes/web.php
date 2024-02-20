<?php

use Illuminate\Support\Facades\Route;

use Statamic\Facades\Asset;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;
use League\Csv\Statement;
use Statamic\Facades\Entry;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::statamic('example', 'example-view', [
//    'title' => 'Example'
// ]);

Route::get('/l', function() {

    $asset = Asset::query()
        ->where('container', 'admin')
        ->where('folder', 'leden')
        ->where('title', 'o1.csv')
        ->first();

        // dump($asset->container->disk, $asset->path);

        $path = Storage::disk($asset->container->disk)->path($asset->path);
        $reader = Reader::createFromPath($path, 'r');
        $reader->setDelimiter(';');
        $reader->setHeaderOffset(0);
        $reader->setOutputBOM(Reader::BOM_UTF8);
        $reader->addStreamFilter('convert.iconv.ISO-8859-15/UTF-8');

        // $records = $reader->getRecords();
        //get 25 records starting from the 11th row
        $stmt = Statement::create()
            ->offset(0)
            ->limit(2)
        ;
        $records = $stmt->process($reader);



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

            if ($afdeling == 'voetbal den een') {
                $deptartment = 'dept-den-een';
            } else if ($afdeling == 'voetbal den twee') {
                $deptartment = 'dept-den-twee';
            } else if ($afdeling == 'voetbal den drie') {
                $deptartment = 'dept-den-drie';
            } else if ($afdeling == 'voetbal dames') {
                $deptartment = 'dept-voetbal-dames';
            } else if ($afdeling == 'petanque') {
                $deptartment = 'dept-petanque';
            }  else if ($afdeling == 'biljarten + losse leden') {
                $deptartment = 'dept-biljart';
            } else if ($afdeling == 'kaarten') {
                $deptartment = 'dept-kaart';
            } else if ($afdeling == 'fietsers') {
                $deptartment = 'dept-wt';
            } 




            // $entry = Entry::make()->collection('clubmembers')->slug((string) Str::uuid());
            $entry = Entry::make()->collection('clubmembers');
            $entry
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
                        'department' => $deptartment,
                        'payment' => 'tb',
                        'type' =>  'rv_lidgeld',
                        'enabled' => true,
                    ],
                    // [
                    //     'season' => 2023,
                    //     'department' => $deptartment,
                    //     'payment' => 'b',
                    //     'type' =>  'rv_lidgeld',
                    //     'enabled' => true,
                    // ],

                    ],
                    'departments' => [
                        $deptartment,
                    ]
            ]);
            $entry->save();


        }
});