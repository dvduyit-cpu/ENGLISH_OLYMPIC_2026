<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Level;
use App\Models\Question;
use App\Models\Round;
use App\Models\RoundQuestion;
use Illuminate\Database\Seeder;

class DemoAllRoundsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoExamSeeder::class);
        $grammar = Category::firstOrCreate(['name' => 'Grammar & Vocabulary']);
        $reading = Category::firstOrCreate(['name' => 'Reading']);
        $round2 = Round::where('round_order', 2)->firstOrFail();
        $round3 = Round::where('round_order', 3)->firstOrFail();
        $round2->update(['status' => 'open', 'opened_at' => now(), 'closed_at' => null]);
        $round3->update(['status' => 'open', 'opened_at' => now(), 'closed_at' => null]);

        $vocabulary = [
            'KET' => [
                ['begin','start','finish','break','forget'],['large','big','thin','short','slow'],['quick','fast','late','quiet','weak'],['silent','quiet','busy','bright','full'],['purchase','buy','sell','lend','lose'],['difficult','hard','soft','simple','empty'],['glad','happy','angry','tired','afraid'],['clever','smart','noisy','heavy','early'],['reply','answer','ask','listen','write'],['select','choose','carry','close','change'],
                ['repair','fix','drop','hide','throw'],['depart','leave','arrive','wait','return'],['nearby','close','far','inside','above'],['tiny','small','wide','deep','high'],['ill','sick','healthy','hungry','ready'],['gift','present','price','shop','card'],['journey','trip','lesson','game','meal'],['famous','well-known','private','local','usual'],['safe','not dangerous','expensive','crowded','modern'],['empty','with nothing inside','very full','not clean','quite old'],
                ['borrow','take and return','give forever','pay for','look after'],['invite','ask someone to come','tell someone to leave','meet by chance','wait for someone'],['weather','sun, rain and wind','roads and bridges','food and drink','books and films'],['neighbour','a person living near you','a family doctor','a school friend','a shop worker'],['entrance','a way into a place','a place to eat','a room upstairs','a bus stop'],['uniform','special clothes for work or school','clothes for sleeping','sports equipment','a travel bag'],['message','information sent to someone','money for a ticket','a school subject','food for lunch'],['competition','an event people try to win','a quiet holiday','a music lesson','a family meal'],['appointment','an arranged meeting','a surprise party','a long journey','an old photograph'],['environment','the natural world around us','a school timetable','a city building','a computer program'],
            ],
            'PET' => [
                ['accurate','correct','ordinary','flexible','patient'],['ancient','very old','very noisy','very narrow','very cheap'],['benefit','advantage','argument','accident','instruction'],['brief','short','complex','private','formal'],['decline','decrease','collect','repair','accept'],['essential','necessary','optional','traditional','available'],['expand','increase','remove','divide','replace'],['fortunate','lucky','serious','honest','curious'],['frequent','common','rare','sudden','recent'],['generous','willing to give','easy to annoy','afraid to speak','unable to decide'],
                ['maintain','keep in good condition','take apart','give away','write down'],['obvious','clear','doubtful','hidden','unusual'],['persuade','convince','prevent','remind','interrupt'],['reliable','dependable','creative','ordinary','impatient'],['require','need','offer','avoid','suggest'],['significant','important','similar','immediate','exact'],['temporary','not permanent','not expensive','not useful','not possible'],['variety','a range of different things','a difficult choice','a large amount','a clear result'],['achievement','something successfully completed','something recently purchased','something carefully hidden','something badly damaged'],['alternative','another choice','final decision','common mistake','written agreement'],
                ['challenge','a difficult task','a useful tool','a friendly chat','a safe journey'],['consequence','a result of an action','a reason to begin','a method of travel','a type of payment'],['efficient','working well without waste','working slowly for safety','looking attractive','costing very little'],['encourage','give confidence','cause confusion','make a complaint','refuse permission'],['independent','not controlled by others','not interested in others','not understood by others','not invited by others'],['opportunity','a good chance','a strict rule','a serious problem','a final warning'],['recommend','suggest as suitable','describe as unusual','refuse as impossible','announce as complete'],['responsible','having a duty','having a secret','having an advantage','having a rest'],['solution','an answer to a problem','a cause of a problem','a description of a problem','a discussion about a problem'],['voluntary','done by choice without payment','required by law','organized in secret','completed too late'],
            ],
        ];

        foreach ($vocabulary as $code => $items) {
            $level = Level::where('code', $code)->firstOrFail();
            foreach ($items as $index => [$word,$correct,$d1,$d2,$d3]) {
                $options = [$correct,$d1,$d2,$d3];
                $shift = $index % 4;
                $options = array_merge(array_slice($options,$shift),array_slice($options,0,$shift));
                $correctIndex = array_search($correct,$options,true);
                $this->makeQuestion($level->id,$grammar->id,$round2->id,$index+1,
                    'Choose the option closest in meaning to “'.$word.'”.',$options,$correctIndex);
            }
        }

        $passages = [
            'KET' => [
                ['A Saturday at Green Lake','Mia','Green Lake','Saturday','nine o’clock','joined a nature clean-up','she wanted to help local wildlife','bus','her cousin Leo','proud','Mia took the bus to Green Lake with her cousin Leo at nine o’clock on Saturday. They joined a nature clean-up because Mia wanted to help local wildlife. The volunteers collected plastic bottles and planted small trees. At the end of the morning, Mia felt tired but proud of their work.'],
                ['The School Food Fair','Ben','the school hall','Friday','six o’clock','sold homemade cakes','the class was raising money for new library books','bicycle','his friend Sam','excited','Ben cycled to the school hall with his friend Sam at six o’clock on Friday. Their class held a food fair to raise money for new library books. Ben sold homemade cakes while Sam served fruit juice. Many parents visited, and Ben felt excited when every cake was sold.'],
                ['A Visit to the Science Museum','Lily','the city science museum','Sunday','ten o’clock','watched a robot demonstration','she was preparing a school science project','train','her brother Tom','inspired','Lily travelled by train to the city science museum with her brother Tom at ten o’clock on Sunday. She was preparing a school science project, so she watched a robot demonstration and took notes. Her favourite exhibit showed how solar energy works. Lily returned home feeling inspired.'],
            ],
            'PET' => [
                ['Restoring the Riverside Path','Daniel','the Riverside Park','Saturday','eight thirty','helped restore a damaged footpath','recent storms had made the route unsafe','tram','his colleague Priya','satisfied','Daniel took a tram to Riverside Park with his colleague Priya at eight thirty on Saturday. They volunteered to restore a damaged footpath because recent storms had made the route unsafe. The team cleared fallen branches, replaced signs and spread gravel over muddy sections. Although the work was demanding, Daniel felt satisfied that local residents could use the path safely again.'],
                ['The Community Language Exchange','Sofia','the central library','Wednesday','half past five','led a language exchange session','new residents wanted to practise everyday English','underground','her neighbour Ahmed','confident','Sofia travelled by underground to the central library with her neighbour Ahmed at half past five on Wednesday. She led a language exchange session because several new residents wanted to practise everyday English. Participants discussed shopping, transport and local services in small groups. By the end, Sofia felt confident that the weekly sessions would become popular.'],
                ['Testing a Solar Bicycle','Oliver','the university engineering centre','Monday','two o’clock','tested a solar-powered bicycle','his team was entering a sustainable transport competition','coach','his teammate Grace','optimistic','Oliver travelled by coach to the university engineering centre with his teammate Grace at two o’clock on Monday. They tested a solar-powered bicycle because their team was entering a sustainable transport competition. Cloudy weather reduced the battery charge, but adjustments to the control system improved its performance. Oliver left feeling optimistic about the final event.'],
            ],
        ];

        foreach ($passages as $code => $groups) {
            $level = Level::where('code',$code)->firstOrFail(); $order = 1;
            foreach ($groups as [$title,$person,$place,$day,$time,$activity,$reason,$transport,$companion,$feeling,$passage]) {
                $questions = [
                    ['Who is the main person in the passage?',[$person,'Alex','Jordan','Taylor'],$person],
                    ['Where did the activity take place?',[$place,'the sports stadium','a shopping centre','the town hospital'],$place],
                    ['On which day did it happen?',[$day,'Tuesday','Thursday','Sunday evening'],$day],
                    ['What time did the journey begin?',[$time,'seven o’clock','midday','four thirty'],$time],
                    ['What did '.$person.' do?',[$activity,'attended a concert','bought new clothes','visited a doctor'],$activity],
                    ['Why did '.$person.' take part?',[$reason,'a teacher required it','the weather was excellent','tickets were free'],$reason],
                    ['How did '.$person.' travel?',[$transport,'car','taxi','on foot'],$transport],
                    ['Who went with '.$person.'?',[$companion,'a school teacher','no one','a tour guide'],$companion],
                    ['How did '.$person.' feel afterwards?',[$feeling,'disappointed','confused','angry'],$feeling],
                    ['Which is the best title for the passage?',[$title,'An Unexpected Shopping Trip','A Difficult Day at School','Missing the Last Bus'],$title],
                ];
                foreach ($questions as [$prompt,$opts,$answer]) {
                    $shift = ($order-1)%4; $opts=array_merge(array_slice($opts,$shift),array_slice($opts,0,$shift));
                    $this->makeQuestion($level->id,$reading->id,$round3->id,$order,$prompt,$opts,array_search($answer,$opts,true),$passage);
                    $order++;
                }
            }
        }
    }

    private function makeQuestion(int $levelId,int $categoryId,int $roundId,int $order,string $text,array $options,int $correct,?string $passage=null): void
    {
        $question=Question::updateOrCreate(['level_id'=>$levelId,'question_text'=>$text,'passage_text'=>$passage],['category_id'=>$categoryId,'question_type'=>'single_choice','points'=>1,'is_active'=>true]);
        foreach($options as $i=>$option)$question->options()->updateOrCreate(['option_code'=>chr(65+$i)],['option_text'=>$option,'is_correct'=>$i===$correct]);
        RoundQuestion::updateOrCreate(['round_id'=>$roundId,'level_id'=>$levelId,'question_id'=>$question->id],['sort_order'=>$order]);
    }
}