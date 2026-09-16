<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ExamEvent;
use App\Models\Level;
use App\Models\Round;
use App\Models\Candidate;
use App\Models\User;
use App\Models\SpeakingCriterion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EnglishOlympicSeeder extends Seeder
{
    public function run(): void
    {
        $ket = Level::updateOrCreate(['code' => 'KET'], ['name' => 'KET']);
        $pet = Level::updateOrCreate(['code' => 'PET'], ['name' => 'PET']);

        $event = ExamEvent::updateOrCreate(
            ['name' => 'ENGLISH OLYMPIC'],
            ['exam_date' => null, 'status' => 'active']
        );

        $rounds = [
            [1, 'ROUND 1 – GENERAL KNOWLEDGE', 20, 15 * 60],
            [2, 'ROUND 2 – LANGUAGE KNOWLEDGE', 30, 30 * 60],
            [3, 'ROUND 3 – SKILLS CHALLENGE', 30, 40 * 60],
        ];

        foreach ($rounds as [$order,$name,$questions,$seconds]) {
            Round::updateOrCreate(
                ['exam_event_id' => $event->id, 'round_order' => $order],
                [
                    'name' => $name,
                    'number_questions' => $questions,
                    'time_limit_seconds' => $seconds,
                    'status' => 'waiting',
                ]
            );
        }

        foreach (['Social English','Nature','Everyday English','Vocabulary','Grammar','Listening','Reading'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        foreach (['Pronunciation','Fluency','Vocabulary/Grammar','Communication'] as $name) {
            SpeakingCriterion::updateOrCreate(['name' => $name], ['max_score' => 10, 'weight' => 1]);
        }

        $email = env('OLYMPIC_ADMIN_EMAIL', 'admin@example.com');
        $password = env('OLYMPIC_ADMIN_PASSWORD', 'ChangeMe123!');
        $admin = User::query()->firstOrNew(['email' => $email]);
        $admin->forceFill([
            'name' => 'Olympic Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ])->save();

        if (filter_var(env('OLYMPIC_SEED_DEMO_CANDIDATES', false), FILTER_VALIDATE_BOOL)) {
            foreach (range(1, 32) as $i) {
                $code = 'KET'.str_pad((string)$i, 3, '0', STR_PAD_LEFT);
                Candidate::updateOrCreate(
                    ['exam_event_id' => $event->id, 'candidate_code' => $code],
                    ['level_id' => $ket->id, 'full_name' => 'Demo '.$code, 'pin_hash' => Hash::make('1234'), 'status' => 'active']
                );
            }
            foreach (range(1, 5) as $i) {
                $code = 'PET'.str_pad((string)$i, 3, '0', STR_PAD_LEFT);
                Candidate::updateOrCreate(
                    ['exam_event_id' => $event->id, 'candidate_code' => $code],
                    ['level_id' => $pet->id, 'full_name' => 'Demo '.$code, 'pin_hash' => Hash::make('1234'), 'status' => 'active']
                );
            }
        }
    }
}
