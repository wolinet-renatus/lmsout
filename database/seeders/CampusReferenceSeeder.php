<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Country;
use App\Models\Venue;
use Illuminate\Database\Seeder;

/**
 * Campus reference seeder — seeds OUT regional branches and campus training venues idempotently.
 *
 * @author Renatus Bernardo
 */
class CampusReferenceSeeder extends Seeder
{
    /**
     * Execute the database seeder.
     */
    public function run(): void
    {
        $tanzaniaId = Country::where('iso', 'TZ')->value('id');

        $branches = [
            ['name' => 'OUT Headquarters', 'branch_code' => 'OUT-HQ', 'address' => 'Dar es Salaam', 'venue' => 'Kinondoni Regional Center'],
            ['name' => 'Mwanza Regional Center', 'branch_code' => 'OUT-MWZ', 'address' => 'Mwanza', 'venue' => 'Mwanza Computer Lab'],
            ['name' => 'Kinondoni Regional Center', 'branch_code' => 'OUT-KIN', 'address' => 'Dar es Salaam', 'venue' => 'Kinondoni Seminar Hall'],
            ['name' => 'Manyara Regional Center', 'branch_code' => 'OUT-MNY', 'address' => 'Manyara', 'venue' => 'Manyara Computer Lab'],
            ['name' => 'Iringa Regional Center', 'branch_code' => 'OUT-IRG', 'address' => 'Iringa', 'venue' => 'OUT Iringa Computer Lab'],
            ['name' => 'Kigoma Regional Center', 'branch_code' => 'OUT-KGM', 'address' => 'Kigoma', 'venue' => 'OUT Kigoma Center Computer Lab'],
            ['name' => 'Rukwa Regional Center', 'branch_code' => 'OUT-RKW', 'address' => 'Rukwa', 'venue' => 'OUT Rukwa Center Computer Lab'],
            ['name' => 'Mara Regional Center', 'branch_code' => 'OUT-MRA', 'address' => 'Mara', 'venue' => 'OUT Mara Center Computer Lab'],
            ['name' => 'Katavi Regional Center', 'branch_code' => 'OUT-KTV', 'address' => 'Mpanda', 'venue' => 'Katavi Computer Lab'],
            ['name' => 'Kagera Regional Center', 'branch_code' => 'OUT-KGR', 'address' => 'Kagera', 'venue' => 'OUT Kagera Center Computer Lab'],
            ['name' => 'Singida Regional Center', 'branch_code' => 'OUT-SGD', 'address' => 'Singida', 'venue' => 'Singida Computer Lab'],
            ['name' => 'Dodoma Regional Center', 'branch_code' => 'OUT-DDM', 'address' => 'Dodoma', 'venue' => 'OUT Dodoma Center Computer Lab'],
            ['name' => 'Kahama Coordination Center', 'branch_code' => 'OUT-KHM', 'address' => 'Kahama', 'venue' => 'OUT Kahama Center Computer Lab'],
            ['name' => 'Mtwara Regional Center', 'branch_code' => 'OUT-MTW', 'address' => 'Mtwara', 'venue' => 'Mtwara Computer Lab'],
            ['name' => 'Lindi Regional Center', 'branch_code' => 'OUT-LND', 'address' => 'Lindi', 'venue' => 'OUT Lindi Center Computer Lab'],
            ['name' => 'Simiyu Regional Center', 'branch_code' => 'OUT-SMY', 'address' => 'Simiyu', 'venue' => 'OUT Simiyu Center Computer Lab'],
            ['name' => 'Mbeya Regional Center', 'branch_code' => 'OUT-MBY', 'address' => 'Mbeya', 'venue' => 'OUT Mbeya Center Computer Lab'],
        ];

        foreach ($branches as $row) {
            $branch = Branch::updateOrCreate(
                ['name' => $row['name']],
                [
                    'branch_code' => $row['branch_code'],
                    'address' => $row['address'],
                    'country_id' => $tanzaniaId,
                    'is_active' => true,
                ]
            );

            Venue::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'name' => $row['venue'],
                ],
                [
                    'capacity' => 30,
                    'equipment' => 'Projector, Workstation PCs, High-Speed Wi-Fi',
                    'is_active' => true,
                ]
            );
        }
    }
}
