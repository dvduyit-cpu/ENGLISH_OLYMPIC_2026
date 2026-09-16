<?php

namespace App\Services;

use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\DB;

class ScoringService
{
    public function score(ExamAttempt $attempt): ExamAttempt
    {
        return DB::transaction(function () use ($attempt) {
            $attempt->load('answers.question');

            $score = 0;
            $correct = 0;
            $wrong = 0;

            foreach ($attempt->answers as $answer) {
                $isCorrect = false;
                $awarded = 0;

                if ($answer->option_id) {
                    $isCorrect = QuestionOption::query()
                        ->whereKey($answer->option_id)
                        ->where('question_id', $answer->question_id)
                        ->where('is_correct', true)
                        ->exists();
                }

                if ($isCorrect) {
                    $awarded = (float) $answer->question->points;
                    $score += $awarded;
                    $correct++;
                } else {
                    $wrong++;
                }

                AttemptAnswer::query()->whereKey($answer->id)->update([
                    'is_correct' => $isCorrect,
                    'points_awarded' => $awarded,
                ]);
            }

            $attempt->update([
                'score' => $score,
                'correct_answers' => $correct,
                'wrong_answers' => $wrong,
            ]);

            return $attempt->fresh();
        });
    }
}
