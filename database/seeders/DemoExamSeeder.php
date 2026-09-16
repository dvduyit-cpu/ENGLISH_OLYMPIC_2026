<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Category;
use App\Models\ExamEvent;
use App\Models\Level;
use App\Models\Question;
use App\Models\Round;
use App\Models\RoundQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoExamSeeder extends Seeder
{
    public function run(): void
    {
        $event = ExamEvent::where('status', 'active')->latest('id')->firstOrFail();
        $round = Round::where('exam_event_id', $event->id)->where('round_order', 1)->firstOrFail();
        $round->update(['status' => 'open', 'opened_at' => now(), 'closed_at' => null]);
        $category = Category::firstOrCreate(['name' => 'Language Use']);

        $sets = [
            'KET' => [
                ['My sister ___ to school by bus every day.', ['goes','go','going','gone'], 0],
                ['Choose the opposite of “expensive”.', ['cheap','heavy','modern','quiet'], 0],
                ['We have English lessons ___ Monday.', ['on','at','in','by'], 0],
                ['There ___ two books on the desk.', ['are','is','am','be'], 0],
                ['Would you like ___ orange juice?', ['some','a','many','any of'], 0],
                ['Tom is ___ than his brother.', ['taller','tall','tallest','more tall'], 0],
                ['I can swim, but I ___ play tennis.', ["can't",'am not','do not can','not'], 0],
                ['Where ___ you yesterday afternoon?', ['were','was','are','did'], 0],
                ['She ___ her homework last night.', ['finished','finishes','finish','finishing'], 0],
                ['Please be quiet. The baby ___.', ['is sleeping','sleeps','sleep','slept'], 0],
                ['A person who works in a hospital and helps doctors is a ___.', ['nurse','chef','pilot','farmer'], 0],
                ['We use a ___ to take photographs.', ['camera','printer','keyboard','radio'], 0],
                ['“How often do you exercise?” — “___”', ['Twice a week.','For two hours.','At the gym.','With my friend.'], 0],
                ['This bag belongs to Anna. It is ___.', ['hers','her','she','herself'], 0],
                ['You ___ wear a seat belt in a car.', ['must','might','could','would'], 0],
                ['There isn’t ___ milk in the fridge.', ['any','some','many','a'], 0],
                ['What ___ you going to do this weekend?', ['are','do','will','did'], 0],
                ['The library is ___ the bank and the café.', ['between','through','during','inside'], 0],
                ['I’m interested ___ learning English.', ['in','on','at','for'], 0],
                ['“Thank you for your help.” — “___”', ["You're welcome.",'Never mind me.','The same to you.','I agree.'], 0],
            ],
            'PET' => [
                ['If I ___ enough time, I’ll help you with the project.', ['have','had','will have','am having'], 0],
                ['The new sports centre ___ by the mayor next month.', ['will be opened','will open','is opening by','has opened'], 0],
                ['She asked me where I ___ the previous evening.', ['had been','have been','was being','am'], 0],
                ['I wish I ___ speak French more fluently.', ['could','can','will','should have'], 0],
                ['The film was ___ interesting that we watched it twice.', ['so','such','too','enough'], 0],
                ['You needn’t bring any food; we have ___.', ['plenty','many','a few','several'], 0],
                ['He apologized ___ arriving late.', ['for','about','of','with'], 0],
                ['By the time we arrived, the concert ___.', ['had started','started','has started','was starting'], 0],
                ['The word “reliable” is closest in meaning to ___.', ['dependable','impatient','ordinary','creative'], 0],
                ['Despite ___ tired, Mia continued working.', ['being','to be','she was','been'], 0],
                ['This is the café ___ we first met.', ['where','which','who','whose'], 0],
                ['Neither the students nor the teacher ___ aware of the change.', ['was','were','be','have'], 0],
                ['I’d rather you ___ me before borrowing my laptop.', ['asked','ask','will ask','have asked'], 0],
                ['The match was put ___ because of the heavy rain.', ['off','out','up','away'], 0],
                ['Not only ___ the exam, but she also achieved the highest score.', ['did she pass','she passed','has she passed','she did pass'], 0],
                ['We were advised ___ valuables in the hotel room.', ['not to leave','to not leaving','not leave','not leaving'], 0],
                ['His explanation was clear and ___.', ['convincing','convinced','convince','conviction'], 0],
                ['The company is looking for someone who can work ___.', ['independently','independent','independence','depend'], 0],
                ['Hardly had I sat down ___ the phone rang.', ['when','than','then','while'], 0],
                ['“Do you mind if I open the window?” — “___”', ['Not at all.','Yes, I open it.','No, I mind.','It does not matter me.'], 0],
            ],
        ];

        foreach ($sets as $code => $items) {
            $level = Level::where('code', $code)->firstOrFail();
            foreach ($items as $index => [$text, $options, $correct]) {
                $question = Question::updateOrCreate(
                    ['level_id' => $level->id, 'question_text' => $text],
                    ['category_id' => $category->id, 'question_type' => 'single_choice', 'points' => 1, 'is_active' => true]
                );
                foreach ($options as $i => $optionText) {
                    $question->options()->updateOrCreate(
                        ['option_code' => chr(65 + $i)],
                        ['option_text' => $optionText, 'is_correct' => $i === $correct]
                    );
                }
                RoundQuestion::updateOrCreate(
                    ['round_id' => $round->id, 'level_id' => $level->id, 'question_id' => $question->id],
                    ['sort_order' => $index + 1]
                );
            }
            Candidate::updateOrCreate(
                ['exam_event_id' => $event->id, 'candidate_code' => $code.'001'],
                ['level_id' => $level->id, 'full_name' => 'Thí sinh Demo '.$code, 'class_name' => 'Demo', 'computer_no' => $code === 'KET' ? '01' : '02', 'pin_hash' => Hash::make('1234'), 'status' => 'active']
            );
        }
    }
}