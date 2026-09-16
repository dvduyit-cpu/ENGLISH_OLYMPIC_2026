<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ExamEvent;
use App\Models\Level;
use App\Models\Question;
use App\Models\RoundQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoundQuestionQuotaSeeder extends Seeder
{
    public function run(): void
    {
        $targets = [1 => 20, 2 => 30, 3 => 30];
        $categories = [
            1 => Category::firstOrCreate(['name' => 'Grammar & Vocabulary']),
            2 => Category::firstOrCreate(['name' => 'Listening']),
            3 => Category::firstOrCreate(['name' => 'Reading']),
        ];

        foreach (ExamEvent::with('rounds')->get() as $event) {
            $level = Level::where('code', $event->allow_pet ? 'PET' : 'KET')->firstOrFail();

            foreach ($event->rounds as $round) {
                $target = $targets[$round->round_order] ?? 0;
                $current = RoundQuestion::where('round_id', $round->id)->where('level_id', $level->id)->count();

                for ($number = $current + 1; $number <= $target; $number++) {
                    $item = $this->makeItem($level->code, $round->round_order, $number);
                    DB::transaction(function () use ($round, $level, $categories, $item, $number) {
                        $question = Question::create([
                            'level_id' => $level->id,
                            'category_id' => $categories[$round->round_order]->id,
                            'question_type' => $item['type'],
                            'question_text' => $item['question'],
                            'passage_text' => $item['passage'],
                            'points' => 1,
                            'is_active' => true,
                        ]);
                        foreach ($item['options'] as $code => $text) {
                            $question->options()->create([
                                'option_code' => $code,
                                'option_text' => $text,
                                'is_correct' => $code === $item['correct'],
                            ]);
                        }
                        RoundQuestion::create([
                            'round_id' => $round->id,
                            'level_id' => $level->id,
                            'question_id' => $question->id,
                            'sort_order' => $number,
                        ]);
                    });
                }

                $round->update(['number_questions' => $target]);
            }
        }
    }

    private function makeItem(string $level, int $round, int $number): array
    {
        if ($round === 1) {
            $ket = [
                ['Sarah ___ breakfast at seven every day.',['A'=>'have','B'=>'has','C'=>'having','D'=>'had'],'B'],
                ['We ___ to the museum yesterday.',['A'=>'go','B'=>'goes','C'=>'went','D'=>'going'],'C'],
                ['This book is ___ than that one.',['A'=>'interesting','B'=>'more interesting','C'=>'most interesting','D'=>'interest'],'B'],
                ['There is ___ water in the bottle.',['A'=>'some','B'=>'many','C'=>'a few','D'=>'an'],'A'],
                ['They ___ TV at the moment.',['A'=>'watch','B'=>'watched','C'=>'are watching','D'=>'watches'],'C'],
            ];
            $pet = [
                ['If she ___ earlier, she would catch the bus.',['A'=>'left','B'=>'leaves','C'=>'will leave','D'=>'has left'],'A'],
                ['The report ___ by Friday afternoon.',['A'=>'must finish','B'=>'must be finished','C'=>'finishes','D'=>'finished'],'B'],
                ['He denied ___ the window.',['A'=>'break','B'=>'to break','C'=>'breaking','D'=>'broke'],'C'],
                ['I have lived here ___ five years.',['A'=>'since','B'=>'for','C'=>'from','D'=>'during'],'B'],
                ['She is the student ___ won the competition.',['A'=>'which','B'=>'where','C'=>'whose','D'=>'who'],'D'],
            ];
            [$question,$options,$correct] = ($level === 'PET' ? $pet : $ket)[($number - 1) % 5];
            return $this->item($question.' (Set '.$number.')', $options, $correct);
        }

        if ($round === 2) {
            $ket = [
                ['Choose the word closest in meaning to “quick”.',['A'=>'fast','B'=>'late','C'=>'weak','D'=>'quiet'],'A'],
                ['Which word describes a place with many people?',['A'=>'empty','B'=>'crowded','C'=>'silent','D'=>'private'],'B'],
                ['A journey by air is called a ___.',['A'=>'flight','B'=>'ride','C'=>'walk','D'=>'drive'],'A'],
                ['Choose the opposite of “dangerous”.',['A'=>'difficult','B'=>'safe','C'=>'heavy','D'=>'deep'],'B'],
                ['You use an umbrella when it is ___.',['A'=>'raining','B'=>'sunny','C'=>'dry','D'=>'warm'],'A'],
            ];
            $pet = [
                ['Choose the word closest in meaning to “accurate”.',['A'=>'correct','B'=>'ordinary','C'=>'flexible','D'=>'patient'],'A'],
                ['An “opportunity” is ___.',['A'=>'a strict rule','B'=>'a good chance','C'=>'a final warning','D'=>'a serious loss'],'B'],
                ['Choose the opposite of “temporary”.',['A'=>'brief','B'=>'recent','C'=>'permanent','D'=>'sudden'],'C'],
                ['To “persuade” someone means to ___.',['A'=>'convince them','B'=>'avoid them','C'=>'interrupt them','D'=>'follow them'],'A'],
                ['An efficient method works well without ___.',['A'=>'planning','B'=>'waste','C'=>'help','D'=>'change'],'B'],
            ];
            [$question,$options,$correct] = ($level === 'PET' ? $pet : $ket)[($number - 1) % 5];
            return $this->item($question.' (Set '.$number.')', $options, $correct, null, $number % 3 === 0 ? 'listening_single_choice' : 'single_choice');
        }

        $passage = $level === 'PET'
            ? 'Alex and his classmates started a recycling project at school. They placed separate bins in every classroom and explained how to use them. After three months, the school was sending half as much rubbish to landfill. The students now plan to teach nearby schools about their project.'
            : 'Emma and her father went to the beach on Sunday. They arrived early and collected shells near the water. After lunch, they played volleyball with another family. Before going home, Emma took photographs of the sunset.';

        $ket = [
            ['Who went to the beach with Emma?',['A'=>'Her father','B'=>'Her teacher','C'=>'Her sister','D'=>'Her friend'],'A'],
            ['When did Emma visit the beach?',['A'=>'Friday','B'=>'Saturday','C'=>'Sunday','D'=>'Monday'],'C'],
            ['What did they collect?',['A'=>'Stones','B'=>'Shells','C'=>'Flowers','D'=>'Leaves'],'B'],
            ['What game did they play?',['A'=>'Football','B'=>'Tennis','C'=>'Basketball','D'=>'Volleyball'],'D'],
            ['What did Emma photograph?',['A'=>'The sunset','B'=>'A boat','C'=>'The hotel','D'=>'A bird'],'A'],
        ];
        $pet = [
            ['What project did Alex and his classmates start?',['A'=>'A sports club','B'=>'A recycling project','C'=>'A reading group','D'=>'A garden project'],'B'],
            ['Where did they put the bins?',['A'=>'In every classroom','B'=>'Outside the school','C'=>'In the library only','D'=>'Near their homes'],'A'],
            ['When was the result measured?',['A'=>'After a week','B'=>'After a month','C'=>'After three months','D'=>'After a year'],'C'],
            ['How much less rubbish went to landfill?',['A'=>'One quarter','B'=>'One third','C'=>'Half','D'=>'All of it'],'C'],
            ['What do the students plan to do next?',['A'=>'Sell the bins','B'=>'Visit a factory','C'=>'Stop the project','D'=>'Teach nearby schools'],'D'],
        ];
        [$question,$options,$correct] = ($level === 'PET' ? $pet : $ket)[($number - 1) % 5];
        return $this->item($question.' (Reading set '.$number.')', $options, $correct, $passage);
    }

    private function item(string $question, array $options, string $correct, ?string $passage = null, string $type = 'single_choice'): array
    {
        return compact('question', 'options', 'correct', 'passage', 'type');
    }
}