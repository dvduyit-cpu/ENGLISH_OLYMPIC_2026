<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ExamEvent;
use App\Models\Level;
use App\Models\Question;
use App\Models\RoundQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventDemoQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            1 => Category::firstOrCreate(['name' => 'Grammar & Vocabulary']),
            2 => Category::firstOrCreate(['name' => 'Listening']),
            3 => Category::firstOrCreate(['name' => 'Reading']),
        ];

        foreach (ExamEvent::with('rounds')->get() as $event) {
            $levelCode = $event->allow_pet ? 'PET' : 'KET';
            $level = Level::where('code', $levelCode)->firstOrFail();

            foreach ($event->rounds as $round) {
                foreach ($this->questions($levelCode, $round->round_order) as $index => $item) {
                    $sortOrder = $index + 1;
                    $existing = RoundQuestion::with('question')
                        ->where('round_id', $round->id)
                        ->where('level_id', $level->id)
                        ->where('sort_order', $sortOrder)
                        ->whereHas('question', fn ($q) => $q->where('question_text', $item['question']))
                        ->first();

                    if ($existing) {
                        continue;
                    }

                    DB::transaction(function () use ($round, $level, $categories, $item, $sortOrder) {
                        $question = Question::create([
                            'level_id' => $level->id,
                            'category_id' => $categories[$round->round_order]->id,
                            'question_type' => $item['type'] ?? 'single_choice',
                            'question_text' => $item['question'],
                            'passage_text' => $item['passage'] ?? null,
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
                            'sort_order' => $sortOrder,
                        ]);
                    });
                }
            }
        }
    }

    private function questions(string $level, int $round): array
    {
        $sets = $level === 'PET' ? $this->petSets() : $this->ketSets();
        return $sets[$round];
    }

    private function ketSets(): array
    {
        $passage = 'Lucy visits the town library every Saturday morning. She usually borrows adventure stories and books about animals. Last Saturday, the librarian showed her a new reading room for children. Lucy stayed there for an hour and finished a short story before going home by bus.';
        return [
            1 => [
                $this->q('My brother ___ football every Sunday.', ['A'=>'play','B'=>'plays','C'=>'playing','D'=>'played'], 'B'),
                $this->q('Choose the opposite of “early”.', ['A'=>'late','B'=>'quick','C'=>'quiet','D'=>'near'], 'A'),
                $this->q('There ___ three apples on the table.', ['A'=>'is','B'=>'am','C'=>'are','D'=>'be'], 'C'),
                $this->q('We have English lessons ___ Monday.', ['A'=>'at','B'=>'on','C'=>'in','D'=>'from'], 'B'),
                $this->q('“How are you today?” — “___”', ['A'=>'I am fine, thanks.','B'=>'It is Monday.','C'=>'At school.','D'=>'Yes, I do.'], 'A'),
            ],
            2 => [
                $this->q('Which word means the same as “begin”?', ['A'=>'finish','B'=>'start','C'=>'break','D'=>'forget'], 'B', null, 'listening_single_choice'),
                $this->q('A person who helps doctors in a hospital is a ___.', ['A'=>'pilot','B'=>'farmer','C'=>'nurse','D'=>'chef'], 'C'),
                $this->q('Tom is ___ than his younger brother.', ['A'=>'tall','B'=>'taller','C'=>'tallest','D'=>'more tall'], 'B'),
                $this->q('Please be quiet. The baby ___.', ['A'=>'sleeps','B'=>'slept','C'=>'is sleeping','D'=>'sleep'], 'C'),
                $this->q('You ___ wear a seat belt in a car.', ['A'=>'must','B'=>'might','C'=>'would','D'=>'could'], 'A'),
            ],
            3 => [
                $this->q('When does Lucy visit the library?', ['A'=>'Every Friday','B'=>'Every Saturday','C'=>'Every Sunday','D'=>'Every Monday'], 'B', $passage),
                $this->q('What kind of stories does Lucy usually borrow?', ['A'=>'Adventure stories','B'=>'History stories','C'=>'Science stories','D'=>'Funny stories'], 'A', $passage),
                $this->q('Who showed Lucy the new reading room?', ['A'=>'Her teacher','B'=>'Her mother','C'=>'The librarian','D'=>'Her friend'], 'C', $passage),
                $this->q('How long did Lucy stay in the new room?', ['A'=>'Half an hour','B'=>'One hour','C'=>'Two hours','D'=>'All day'], 'B', $passage),
                $this->q('How did Lucy go home?', ['A'=>'By train','B'=>'By bicycle','C'=>'On foot','D'=>'By bus'], 'D', $passage),
            ],
        ];
    }

    private function petSets(): array
    {
        $passage = 'Last year, Maya joined a community project that transformed an unused piece of land into a public garden. At first, the volunteers had very few tools and the soil was in poor condition. Local businesses later donated equipment and seeds. Six months later, residents were growing vegetables, attending outdoor workshops and enjoying a greener neighbourhood.';
        return [
            1 => [
                $this->q('If I ___ enough time, I will help you.', ['A'=>'have','B'=>'had','C'=>'will have','D'=>'having'], 'A'),
                $this->q('The new sports centre ___ next month.', ['A'=>'opens by','B'=>'will be opened','C'=>'was opening','D'=>'has open'], 'B'),
                $this->q('She apologized ___ arriving late.', ['A'=>'of','B'=>'with','C'=>'for','D'=>'about to'], 'C'),
                $this->q('The word “reliable” is closest to ___.', ['A'=>'ordinary','B'=>'dependable','C'=>'impatient','D'=>'creative'], 'B'),
                $this->q('By the time we arrived, the film ___.', ['A'=>'starts','B'=>'has started','C'=>'had started','D'=>'will start'], 'C'),
            ],
            2 => [
                $this->q('Choose the best meaning of “essential”.', ['A'=>'optional','B'=>'traditional','C'=>'necessary','D'=>'available'], 'C', null, 'listening_single_choice'),
                $this->q('The match was put ___ because of heavy rain.', ['A'=>'off','B'=>'up','C'=>'away','D'=>'out'], 'A'),
                $this->q('I wish I ___ speak French more fluently.', ['A'=>'can','B'=>'could','C'=>'will','D'=>'must'], 'B'),
                $this->q('This is the café ___ we first met.', ['A'=>'who','B'=>'whose','C'=>'which','D'=>'where'], 'D'),
                $this->q('Her explanation was both clear and ___.', ['A'=>'convincing','B'=>'convinced','C'=>'convince','D'=>'conviction'], 'A'),
            ],
            3 => [
                $this->q('What was the land used for before the project?', ['A'=>'A public garden','B'=>'It was unused','C'=>'A sports field','D'=>'A car park'], 'B', $passage),
                $this->q('What problem did volunteers have at first?', ['A'=>'Too many visitors','B'=>'No seeds at all','C'=>'Few tools and poor soil','D'=>'Bad weather'], 'C', $passage),
                $this->q('Who donated equipment and seeds?', ['A'=>'Local businesses','B'=>'The residents','C'=>'A university','D'=>'Tourists'], 'A', $passage),
                $this->q('How long did the transformation take?', ['A'=>'Six weeks','B'=>'Six months','C'=>'One year','D'=>'Two years'], 'B', $passage),
                $this->q('What is the main purpose of the text?', ['A'=>'To advertise tools','B'=>'To explain a successful community project','C'=>'To complain about residents','D'=>'To describe a business'], 'B', $passage),
            ],
        ];
    }

    private function q(string $question, array $options, string $correct, ?string $passage = null, string $type = 'single_choice'): array
    {
        return compact('question', 'options', 'correct', 'passage', 'type');
    }
}