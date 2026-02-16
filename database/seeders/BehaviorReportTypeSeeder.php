<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BehaviorReportType;

class BehaviorReportTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reportTypes = [
            [
                'report_type_name' => 'Monthly Behavior Report',
                'report_type_key' => 'monthly-behavior-report',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'report_type_name' => 'Yearly Behavior Report',
                'report_type_key' => 'yearly-behavior-report',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'report_type_name' => 'Behavior Track Report',
                'report_type_key' => 'behavior-track-report',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($reportTypes as $reportType) {
            BehaviorReportType::updateOrCreate(
                ['report_type_key' => $reportType['report_type_key']],
                $reportType
            );
        }
    }
}
