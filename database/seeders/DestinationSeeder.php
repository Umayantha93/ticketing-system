<?php

namespace Database\Seeders;

use App\Models\Destination;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'COL', 'en' => 'Colombo', 'si' => 'කොළඹ', 'ta' => 'கொழும்பு', 'aliases' => ['Pettah Bus Stand', 'පිටකොටුව', 'பெட்டா']],
            ['code' => 'GAM', 'en' => 'Gampaha', 'si' => 'ගම්පහ', 'ta' => 'கம்பஹா', 'aliases' => []],
            ['code' => 'KAL', 'en' => 'Kalutara', 'si' => 'කළුතර', 'ta' => 'களுத்துறை', 'aliases' => []],
            ['code' => 'KAN', 'en' => 'Kandy', 'si' => 'මහනුවර', 'ta' => 'கண்டி', 'aliases' => []],
            ['code' => 'MAT', 'en' => 'Matale', 'si' => 'මාතලේ', 'ta' => 'மாத்தளை', 'aliases' => []],
            ['code' => 'NUE', 'en' => 'Nuwara Eliya', 'si' => 'නුවරඑළිය', 'ta' => 'நுவரெலியா', 'aliases' => ['Nuwaraeliya']],
            ['code' => 'GAL', 'en' => 'Galle', 'si' => 'ගාල්ල', 'ta' => 'காலி', 'aliases' => []],
            ['code' => 'MATR', 'en' => 'Matara', 'si' => 'මාතර', 'ta' => 'மாத்தறை', 'aliases' => []],
            ['code' => 'HAM', 'en' => 'Hambantota', 'si' => 'හම්බන්තොට', 'ta' => 'அம்பாந்தோட்டை', 'aliases' => []],
            ['code' => 'JAF', 'en' => 'Jaffna', 'si' => 'යාපනය', 'ta' => 'யாழ்ப்பாணம்', 'aliases' => []],
            ['code' => 'KIL', 'en' => 'Kilinochchi', 'si' => 'කිලිනොච්චි', 'ta' => 'கிளிநொச்சி', 'aliases' => []],
            ['code' => 'MAN', 'en' => 'Mannar', 'si' => 'මන්නාරම', 'ta' => 'மன்னார்', 'aliases' => []],
            ['code' => 'MUL', 'en' => 'Mullaitivu', 'si' => 'මුලතිව්', 'ta' => 'முல்லைத்தீவு', 'aliases' => []],
            ['code' => 'VAV', 'en' => 'Vavuniya', 'si' => 'වවුනියාව', 'ta' => 'வவுனியா', 'aliases' => []],
            ['code' => 'PUT', 'en' => 'Puttalam', 'si' => 'පුත්තලම', 'ta' => 'புத்தளம்', 'aliases' => []],
            ['code' => 'KUR', 'en' => 'Kurunegala', 'si' => 'කුරුණෑගල', 'ta' => 'குருநாகல்', 'aliases' => ['Kurunagala']],
            ['code' => 'ANU', 'en' => 'Anuradhapura', 'si' => 'අනුරාධපුර', 'ta' => 'அனுராதபுரம்', 'aliases' => []],
            ['code' => 'POL', 'en' => 'Polonnaruwa', 'si' => 'පොළොන්නරුව', 'ta' => 'பொலன்னறுவை', 'aliases' => []],
            ['code' => 'BAD', 'en' => 'Badulla', 'si' => 'බදුල්ල', 'ta' => 'பதுளை', 'aliases' => []],
            ['code' => 'MON', 'en' => 'Monaragala', 'si' => 'මොණරාගල', 'ta' => 'மோனராகலை', 'aliases' => []],
            ['code' => 'TRI', 'en' => 'Trincomalee', 'si' => 'ත්රිකුණාමලය', 'ta' => 'திருகோணமலை', 'aliases' => []],
            ['code' => 'BAT', 'en' => 'Batticaloa', 'si' => 'මඩකලපුව', 'ta' => 'மட்டக்களப்பு', 'aliases' => []],
            ['code' => 'AMP', 'en' => 'Ampara', 'si' => 'අම්පාර', 'ta' => 'அம்பாறை', 'aliases' => []],
            ['code' => 'RAT', 'en' => 'Ratnapura', 'si' => 'රත්නපුර', 'ta' => 'இரத்தினபுரி', 'aliases' => []],
            ['code' => 'KEG', 'en' => 'Kegalle', 'si' => 'කෑගල්ල', 'ta' => 'கேகாலை', 'aliases' => []],
        ];

        foreach ($rows as $row) {
            Destination::updateOrCreate(
                ['district_code' => $row['code']],
                [
                    'name_en' => $row['en'],
                    'name_si' => $row['si'],
                    'name_ta' => $row['ta'],
                    'aliases' => $row['aliases'],
                ]
            );
        }
    }
}
