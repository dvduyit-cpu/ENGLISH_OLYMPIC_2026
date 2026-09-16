<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Level;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Round;
use App\Models\RoundQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuestionAdminController extends Controller
{
    public function index(Request $request)
    {
        $questions = Question::query()->with(['level','category','options'])
            ->with(['roundAssignments.round'])
            ->when($request->integer('level_id'), fn ($q, $id) => $q->where('level_id', $id))
            ->when($request->integer('round_id'), fn ($q, $id) => $q->whereHas('roundAssignments', fn ($r) => $r->where('round_id', $id)))
            ->when($request->filled('search'), fn ($q) => $q->where('question_text', 'like', '%'.$request->string('search').'%'))
            ->latest('id')->paginate(30)->withQueryString();
        $levels = Level::orderBy('id')->get();
        $categories = Category::orderBy('name')->get();
        $rounds = Round::orderBy('round_order')->get();
        return view('admin.questions', compact('questions','levels','categories','rounds'));
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