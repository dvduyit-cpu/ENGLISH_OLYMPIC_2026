<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ExamEvent;
use App\Models\Level;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Round;
use App\Models\RoundQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class QuestionAdminController extends Controller
{
    public function index(Request $request)
    {
        $events = ExamEvent::latest('id')->get();
        $event = $request->integer('event_id') ? $events->firstWhere('id', $request->integer('event_id')) : null;
        $event ??= $events->firstWhere('status', 'active') ?? $events->first();
        abort_unless($event, 404, 'Chưa có kỳ thi.');
        $allowedCodes = collect(['KET'=>$event->allow_ket, 'PET'=>$event->allow_pet])->filter()->keys();
        $levels = Level::whereIn('code', $allowedCodes)->orderBy('id')->get();
        $rounds = Round::where('exam_event_id', $event->id)->orderBy('round_order')->get();
        $questions = Question::query()->with(['level','category','options','roundAssignments.round'])
            ->whereHas('roundAssignments.round', fn ($q) => $q->where('exam_event_id', $event->id))
            ->when($request->integer('level_id'), fn ($q, $id) => $q->where('level_id', $id))
            ->when($request->integer('round_id'), fn ($q, $id) => $q->whereHas('roundAssignments', fn ($r) => $r->where('round_id', $id)))
            ->when($request->integer('round_order'), fn ($q, $order) => $q->whereHas('roundAssignments.round', fn ($r) => $r->where('exam_event_id', $event->id)->where('round_order', $order)))
            ->when($request->filled('search'), fn ($q) => $q->where('question_text', 'like', '%'.$request->string('search').'%'))
            ->latest('id')->paginate(30)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $folderTree = $levels->map(function ($level) use ($event) {
            $level->folder_counts = collect([1,2,3])->mapWithKeys(fn ($order) => [$order => Question::where('level_id', $level->id)->whereHas('roundAssignments.round', fn ($q) => $q->where('exam_event_id', $event->id)->where('round_order', $order))->count()]);
            return $level;
        });
        return view('admin.questions', compact('events','event','questions','levels','categories','rounds','folderTree'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $data) {
            $question = Question::create($this->questionPayload($request, $data));
            $this->syncOptions($question, $data);
            $this->syncRound($question, $data);
        });
        return back()->with('message', 'Đã tạo câu hỏi mới.');
    }

    public function update(Request $request, Question $question)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $data, $question) {
            $question->update($this->questionPayload($request, $data, $question));
            $this->syncOptions($question, $data);
            RoundQuestion::where('question_id', $question->id)->delete();
            $this->syncRound($question, $data);
        });
        return back()->with('message', 'Đã cập nhật câu hỏi #'.$question->id.'.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate(['question_ids' => ['required','array','min:1'], 'question_ids.*' => ['integer','exists:questions,id']]);
        $questions = Question::whereIn('id', $data['question_ids'])->get();
        foreach ($questions as $question) {
            foreach ([$question->image_path, $question->audio_path] as $path) if ($path) Storage::disk('public')->delete($path);
            $question->delete();
        }
        return back()->with('message', 'Đã xóa '.$questions->count().' câu hỏi đã chọn.');
    }
    public function destroy(Question $question)
    {
        foreach ([$question->image_path, $question->audio_path] as $path) if ($path) Storage::disk('public')->delete($path);
        $question->delete();
        return back()->with('message', 'Đã xóa câu hỏi.');
    }

    public function assign(Request $request, Question $question)
    {
        $data = $request->validate(['round_id'=>['required','exists:rounds,id'],'sort_order'=>['required','integer','min:1']]);
        RoundQuestion::updateOrCreate(['round_id'=>$data['round_id'],'level_id'=>$question->level_id,'question_id'=>$question->id],['sort_order'=>$data['sort_order']]);
        return back()->with('message', 'Đã gán câu hỏi vào Round.');
    }

    public function downloadTemplate(Request $request)
    {
        $event=ExamEvent::findOrFail($request->integer('event_id')); $sheet=$this->questionSheet('MẪU CÂU HỎI - '.$event->name);
        $sheet->fromArray([1,'Grammar','single_choice','Choose the correct answer.','','Answer A','Answer B','Answer C','Answer D','A',1,1,'',''],null,'A3');
        $sheet->fromArray([3,'Reading','single_choice','What is the main idea?','Paste the reading passage here.','A','B','C','D','B',1,1,'',''],null,'A4');
        return $this->xlsxDownload($sheet->getParent(),'mau-cau-hoi-ky-thi-'.$event->id.'.xlsx');
    }

    public function exportExcel(Request $request)
    {
        $event=ExamEvent::findOrFail($request->integer('event_id'));
        $rows=RoundQuestion::with(['round','question.category','question.options'])->whereHas('round',fn($q)=>$q->where('exam_event_id',$event->id))->orderBy('round_id')->orderBy('sort_order')->get();
        $sheet=$this->questionSheet('BỘ CÂU HỎI - '.$event->name);
        foreach($rows as $i=>$a){$q=$a->question;$correct=optional($q->options->firstWhere('is_correct',true))->option_code;$opts=collect(['A','B','C','D'])->map(fn($c)=>optional($q->options->firstWhere('option_code',$c))->option_text);
            $sheet->fromArray([$a->round->round_order,$q->category?->name,$q->question_type,$q->question_text,$q->passage_text,...$opts,$correct,$q->points,$a->sort_order,$q->image_path,$q->audio_path],null,'A'.($i+3));}
        return $this->xlsxDownload($sheet->getParent(),'bo-cau-hoi-ky-thi-'.$event->id.'.xlsx');
    }

    public function importExcel(Request $request)
    {
        $data=$request->validate(['exam_event_id'=>['required','exists:exam_events,id'],'level_id'=>['required','exists:levels,id'],'excel_file'=>['required','file','mimes:xlsx,xls,csv','max:20480']]);
        $event=ExamEvent::findOrFail($data['exam_event_id']); $level=Level::findOrFail($data['level_id']);
        if (!(($level->code==='KET'&&$event->allow_ket)||($level->code==='PET'&&$event->allow_pet))) return back()->withErrors(['level_id'=>'Cấp độ không thuộc kỳ thi.']);
        try{$rows=IOFactory::load($request->file('excel_file')->getRealPath())->getActiveSheet()->toArray(null,true,true,false);}catch(\Throwable $e){return back()->withErrors(['excel_file'=>'Không đọc được file Excel. Hãy tải lại file mẫu.']);}
        $rounds=Round::where('exam_event_id',$event->id)->get()->keyBy('round_order');$imported=0;$errors=[];
        foreach($rows as $index=>$row){$v=array_pad(array_map(fn($x)=>trim((string)$x),array_slice($row,0,14)),14,'');[$roundOrder,$categoryName,$type,$text,$passage,$a,$b,$c,$d,$correct,$points,$sort,$image,$audio]=$v;
            if(($roundOrder===''&&$text==='')||!is_numeric($roundOrder))continue;$round=$rounds->get((int)$roundOrder);$correct=strtoupper($correct);
            if(!$round||!$text||!in_array($type,['single_choice','listening_single_choice'],true)||!$a||!$b||!$c||!$d||!in_array($correct,['A','B','C','D'],true)){$errors[]='Dòng '.($index+1).': dữ liệu Round, loại câu, nội dung hoặc đáp án chưa hợp lệ.';continue;}
            DB::transaction(function()use(&$imported,$level,$round,$categoryName,$type,$text,$passage,$a,$b,$c,$d,$correct,$points,$sort,$image,$audio){$category=$categoryName!==''?Category::firstOrCreate(['name'=>$categoryName]):null;
                $question=Question::create(['level_id'=>$level->id,'category_id'=>$category?->id,'question_type'=>$type,'question_text'=>$text,'passage_text'=>$passage?:null,'image_path'=>$image?:null,'audio_path'=>$audio?:null,'points'=>is_numeric($points)?$points:1,'is_active'=>true]);
                foreach(['A'=>$a,'B'=>$b,'C'=>$c,'D'=>$d]as$code=>$answer)QuestionOption::create(['question_id'=>$question->id,'option_code'=>$code,'option_text'=>$answer,'is_correct'=>$code===$correct]);
                RoundQuestion::create(['round_id'=>$round->id,'level_id'=>$level->id,'question_id'=>$question->id,'sort_order'=>is_numeric($sort)?max(1,(int)$sort):1]);$imported++;});}
        return redirect()->route('admin.questions.index',['event_id'=>$event->id,'level_id'=>$level->id])->with('message','Đã nhập '.$imported.' câu hỏi từ Excel.')->with('import_errors',$errors);
    }

    private function questionSheet(string $title)
    {
        $spreadsheet=new Spreadsheet();$sheet=$spreadsheet->getActiveSheet();$sheet->setTitle('Cau hoi');$sheet->setCellValue('A1',$title)->mergeCells('A1:N1');
        $sheet->fromArray(['Round','Phần thi','Loại câu','Nội dung câu hỏi','Bài đọc','Đáp án A','Đáp án B','Đáp án C','Đáp án D','Đáp án đúng','Điểm','Thứ tự','Đường dẫn ảnh','Đường dẫn audio'],null,'A2');
        $sheet->getStyle('A1:N2')->getFont()->setBold(true);$sheet->getStyle('A2:N2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0B5ED7');$sheet->getStyle('A2:N2')->getFont()->getColor()->setARGB('FFFFFFFF');
        foreach(range('A','N')as$col)$sheet->getColumnDimension($col)->setAutoSize(true);$sheet->freezePane('A3');return $sheet;
    }

    private function xlsxDownload(Spreadsheet $spreadsheet,string $filename)
    {
        return response()->streamDownload(function()use($spreadsheet){(new Xlsx($spreadsheet))->save('php://output');$spreadsheet->disconnectWorksheets();},$filename,['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
    public function preview(Request $request, Round $round)
    {
        $level = Level::findOrFail($request->integer('level_id'));
        $event = $round->examEvent;
        $levelAllowed = ($level->code === 'KET' && $event->allow_ket)
            || ($level->code === 'PET' && $event->allow_pet);
        abort_unless($levelAllowed, 404);

        $questions = Question::query()
            ->select('questions.*')
            ->join('round_questions', 'round_questions.question_id', '=', 'questions.id')
            ->where('round_questions.round_id', $round->id)
            ->where('round_questions.level_id', $level->id)
            ->where('questions.is_active', true)
            ->orderBy('round_questions.sort_order')
            ->limit($round->number_questions)
            ->with(['options' => fn ($query) => $query->orderBy('option_code')])
            ->get();

        return view('admin.exam-preview', compact('event', 'round', 'level', 'questions'));
    }
    private function validated(Request $request): array
    {
        return $request->validate([
            'level_id'=>['required','exists:levels,id'],'category_id'=>['nullable','exists:categories,id'],
            'question_type'=>['required','in:single_choice,listening_single_choice'],'question_text'=>['required','string'],
            'passage_text'=>['nullable','string'],'image'=>['nullable','image','max:5120'],
            'audio'=>['nullable','file','mimes:mp3,wav,ogg,m4a','max:15360'],'points'=>['required','numeric','min:0.01','max:100'],
            'option_a'=>['required','string'],'option_b'=>['required','string'],'option_c'=>['required','string'],'option_d'=>['required','string'],
            'correct_option'=>['required','in:A,B,C,D'],'round_id'=>['nullable','exists:rounds,id'],'sort_order'=>['nullable','integer','min:1'],
            'is_active'=>['nullable','boolean'],
        ]);
    }

    private function questionPayload(Request $request, array $data, ?Question $question = null): array
    {
        $payload = ['level_id'=>$data['level_id'],'category_id'=>$data['category_id']??null,'question_type'=>$data['question_type'],
            'question_text'=>$data['question_text'],'passage_text'=>$data['passage_text']??null,'points'=>$data['points'],'is_active'=>$request->boolean('is_active', true)];
        if ($request->hasFile('image')) { if ($question?->image_path) Storage::disk('public')->delete($question->image_path); $payload['image_path']=$request->file('image')->store('questions/images','public'); }
        if ($request->hasFile('audio')) { if ($question?->audio_path) Storage::disk('public')->delete($question->audio_path); $payload['audio_path']=$request->file('audio')->store('questions/audio','public'); }
        return $payload;
    }

    private function syncOptions(Question $question, array $data): void
    {
        foreach (['A','B','C','D'] as $code) QuestionOption::updateOrCreate(
            ['question_id'=>$question->id,'option_code'=>$code],
            ['option_text'=>$data['option_'.strtolower($code)],'is_correct'=>$data['correct_option']===$code]
        );
    }

    private function syncRound(Question $question, array $data): void
    {
        if (!empty($data['round_id'])) RoundQuestion::updateOrCreate(
            ['round_id'=>$data['round_id'],'level_id'=>$question->level_id,'question_id'=>$question->id],
            ['sort_order'=>$data['sort_order']??1]
        );
    }
}