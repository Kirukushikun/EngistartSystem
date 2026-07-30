<?php

namespace Database\Seeders;

use App\Models\ProjectRequest;
use App\Models\User;
use App\Support\ProjectTimelineCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProjectCalendarDemoSeeder extends Seeder
{
    /**
     * Seeds extra farm managers/engineers and a spread of project requests
     * (multiple farms, budget categories, statuses, and both actual and
     * not-yet-computed timelines) so the Project Calendar page has enough
     * variety to click through and review.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Maria Cruz', 'email' => 'm.cruz@brooksidegroup.org', 'role' => 'farm_manager', 'farm' => 'Farm B – Capas, Tarlac'],
            ['name' => 'Pedro Reyes', 'email' => 'p.reyes@brooksidegroup.org', 'role' => 'farm_manager', 'farm' => 'Farm C – Concepcion, Tarlac'],
            ['name' => 'Ramon Torres', 'email' => 'r.torres@brooksidegroup.org', 'role' => 'farm_manager', 'farm' => 'Farm D – Angeles, Pampanga'],
            ['name' => 'Engr. L. Bautista', 'email' => 'l.bautista@brooksidegroup.org', 'role' => 'engineer', 'farm' => null],
            ['name' => 'Engr. R. Ramos', 'email' => 'r.ramos@brooksidegroup.org', 'role' => 'engineer', 'farm' => null],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'farm' => $user['farm'],
                    'is_active' => true,
                    'password' => Hash::make('1234'),
                ]
            );
        }

        $jose = User::where('email', 'j.santos@brooksidegroup.org')->first();
        $maria = User::where('email', 'm.cruz@brooksidegroup.org')->first();
        $pedro = User::where('email', 'p.reyes@brooksidegroup.org')->first();
        $ramon = User::where('email', 'r.torres@brooksidegroup.org')->first();
        $bautista = User::where('email', 'l.bautista@brooksidegroup.org')->first();
        $ramos = User::where('email', 'r.ramos@brooksidegroup.org')->first();

        $requests = [
            [
                'code' => 'CAL-DEMO-001',
                'title' => 'Poultry House Renovation',
                'farm_name' => 'Farm A – Bamban, Tarlac',
                'requestor_id' => $jose->id,
                'requestor_role' => 'farm_manager',
                'budget_category' => 'small',
                'current_status' => 'submitted',
                'current_owner_role' => 'division_head',
                'submitted_days_ago' => 2,
                'with_actual_dates' => false,
            ],
            [
                'code' => 'CAL-DEMO-002',
                'title' => 'Feed Line Automation',
                'farm_name' => 'Farm A – Bamban, Tarlac',
                'requestor_id' => $jose->id,
                'requestor_role' => 'farm_manager',
                'budget_category' => 'medium',
                'current_status' => 'accepted',
                'current_owner_role' => 'engineer',
                'assigned_engineer_id' => $bautista->id,
                'submitted_days_ago' => 60,
                'with_actual_dates' => true,
            ],
            [
                'code' => 'CAL-DEMO-003',
                'title' => 'Layer House Expansion',
                'farm_name' => 'Farm A – Bamban, Tarlac',
                'requestor_id' => $jose->id,
                'requestor_role' => 'farm_manager',
                'budget_category' => 'large',
                'current_status' => 'submitted',
                'current_owner_role' => 'division_head',
                'submitted_days_ago' => 1,
                'with_actual_dates' => false,
            ],
            [
                'code' => 'CAL-DEMO-004',
                'title' => 'Irrigation Pump Upgrade',
                'farm_name' => 'Farm B – Capas, Tarlac',
                'requestor_id' => $maria->id,
                'requestor_role' => 'farm_manager',
                'budget_category' => 'small',
                'current_status' => 'recommended',
                'current_owner_role' => 'vp_gen_services',
                'submitted_days_ago' => 20,
                'with_actual_dates' => false,
            ],
            [
                'code' => 'CAL-DEMO-005',
                'title' => 'Water Tank Installation',
                'farm_name' => 'Farm B – Capas, Tarlac',
                'requestor_id' => $maria->id,
                'requestor_role' => 'farm_manager',
                'budget_category' => 'small',
                'current_status' => 'vp_approved',
                'current_owner_role' => 'dh_gen_services',
                'assigned_engineer_id' => $ramos->id,
                'submitted_days_ago' => 50,
                'with_actual_dates' => true,
            ],
            [
                'code' => 'CAL-DEMO-006',
                'title' => 'Feed Storage Expansion',
                'farm_name' => 'Farm C – Concepcion, Tarlac',
                'requestor_id' => $pedro->id,
                'requestor_role' => 'farm_manager',
                'budget_category' => 'large',
                'current_status' => 'noted',
                'current_owner_role' => 'ed_manager',
                'submitted_days_ago' => 80,
                'with_actual_dates' => true,
            ],
            [
                'code' => 'CAL-DEMO-007',
                'title' => 'Perimeter Fencing (Late Filing)',
                'farm_name' => 'Farm C – Concepcion, Tarlac',
                'requestor_id' => $pedro->id,
                'requestor_role' => 'farm_manager',
                'budget_category' => 'medium',
                'current_status' => 'jl_pending',
                'current_owner_role' => 'division_head',
                'is_exception_flow' => true,
                'exception_status' => 'pending_division_head',
                'submitted_days_ago' => 5,
                'with_actual_dates' => false,
            ],
            [
                'code' => 'CAL-DEMO-008',
                'title' => 'Biogas Plant Repair',
                'farm_name' => 'Farm D – Angeles, Pampanga',
                'requestor_id' => $ramon->id,
                'requestor_role' => 'farm_manager',
                'budget_category' => 'small',
                'current_status' => 'initialized',
                'current_owner_role' => 'engineer',
                'assigned_engineer_id' => $bautista->id,
                'submitted_days_ago' => 70,
                'with_actual_dates' => true,
            ],
            [
                'code' => 'CAL-DEMO-009',
                'title' => 'Equipment Shed',
                'farm_name' => 'Farm D – Angeles, Pampanga',
                'requestor_id' => $ramon->id,
                'requestor_role' => 'farm_manager',
                'budget_category' => 'medium',
                'current_status' => 'accepted',
                'current_owner_role' => 'engineer',
                'assigned_engineer_id' => $ramos->id,
                'submitted_days_ago' => 100,
                'with_actual_dates' => true,
            ],
        ];

        foreach ($requests as $data) {
            $submittedAt = now()->subDays($data['submitted_days_ago']);
            $timeline = ProjectTimelineCalculator::forCategory($data['budget_category'], $submittedAt);

            ProjectRequest::updateOrCreate(
                ['request_number' => $data['code']],
                [
                    'requestor_id' => $data['requestor_id'],
                    'requestor_role' => $data['requestor_role'],
                    'current_status' => $data['current_status'],
                    'current_step' => null,
                    'current_owner_role' => $data['current_owner_role'],
                    'current_owner_id' => null,
                    'assigned_engineer_id' => $data['assigned_engineer_id'] ?? null,
                    'is_late' => false,
                    'is_exception_flow' => $data['is_exception_flow'] ?? false,
                    'exception_status' => $data['exception_status'] ?? null,
                    'title' => $data['title'],
                    'request_type' => 'Building',
                    'budget_category' => $data['budget_category'],
                    'farm_name' => $data['farm_name'],
                    'purpose' => $data['title'] . ' — seeded for Project Calendar demo purposes.',
                    'date_needed' => $submittedAt->copy()->addDays(90)->toDateString(),
                    'description' => $data['title'] . ' — seeded for Project Calendar demo purposes.',
                    'project_start_date' => $data['with_actual_dates'] ? $timeline['start_date'] : null,
                    'project_completion_date' => $data['with_actual_dates'] ? $timeline['completion_date'] : null,
                    'submitted_at' => $submittedAt,
                    'last_transitioned_at' => $submittedAt,
                ]
            );
        }
    }
}
