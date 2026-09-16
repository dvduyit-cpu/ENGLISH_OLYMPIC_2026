<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\ExamEvent;
use App\Models\ExamAttempt;
use App\Models\Round;
use App\Models\RoundQuestion;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CandidateAdminController extends Controller
{
    public function index(Request $request)
    {
        $events = ExamEvent::latest('id')->get();
        $event = $request->integer('event_id') ? $events->firstWhere('id', $request->integer('event_id')) : null;
        $event ??= $events->firstWhere('status', 'active') ?? $events->first();
        abort_unless($event, 404, 'Chưa có kỳ thi.');
        $candidates = Candidate::where('exam_event_id',$event->id)->with('level')
            ->when($request->filled('search'),fn($q)=>$q->where(fn($s)=>$s->where('candidate_code','like','%'.$request->search.'%')->orWhere('full_name','like','%'.$request->search.'%')))
            ->when($request->integer('level_id'),fn($q,$id)=>$q->where('level_id',$id))
            ->orderBy('candidate_code')->paginate(30)->withQueryString();
        $levels = Level::whereIn('code', collect(['KET' => $event->allow_ket, 'PET' => $event->allow_pet])->filter()->keys())->orderBy('id')->get();
        return view('admin.candidates',compact('events','event','candidates','levels'));
    }

    public function store(Request $request)
    {
        $data=$request->validate(['exam_event_id'=>['required','exists:exam_events,id'],'level_id'=>['required','exists:levels,id'],'candidate_code'=>['required','string','max:50'],'full_name'=>['required','string','max:255'],'class_name'=>['nullable','string','max:255'],'computer_no'=>['nullable','string','max:50'],'pin'=>['required','string','min:4','max:50']]);
        Candidate::updateOrCreate(['exam_event_id'=>$data['exam_event_id'],'candidate_code'=>trim($data['candidate_code'])],['level_id'=>$data['level_id'],'full_name'=>$data['full_name'],'class_name'=>$data['class_name']??null,'computer_no'=>$data['computer_no']??null,'pin_hash'=>Hash::make($data['pin']),'status'=>'active']);
        return back()->with('message','Đã lưu thí sinh.');
    }

    public function show(Candidate $candidate)
    {
        $candidate->load(['level','examEvent']);
        $rounds = Round::where('exam_event_id',$candidate->exam_event_id)->orderBy('round_order')->get()->map(function ($round) use ($candidate) {
            $round->attempt_record = ExamAttempt::where('candidate_id',$candidate->id)->where('round_id',$round->id)->withCount('answers')->first();
            $round->question_count = RoundQuestion::join('questions','questions.id','=','round_questions.question_id')
                ->where('round_questions.round_id',$round->id)->where('round_questions.level_id',$candidate->level_id)->where('questions.is_active',true)->count();
            return $round;
        });
        return view('admin.candidate-show',compact('candidate','rounds'));
    }

    public function allowExam(Candidate $candidate)
    {
        $candidate->update(['status'=>'active']);
        return back()->with('message','Đã cho phép '.$candidate->candidate_code.' tiếp tục dự thi.');
    }

    public function lockExam(Candidate $candidate)
    {
        $candidate->update(['status'=>'locked']);
        return back()->with('message','Đã khóa quyền thi của '.$candidate->candidate_code.'.');
    }

    public function retake(Candidate $candidate, Round $round)
    {
        abort_unless($round->exam_event_id === $candidate->exam_event_id,403);
        $attempt = ExamAttempt::where('candidate_id',$candidate->id)->where('round_id',$round->id)->first();
        if (!$attempt) return back()->withErrors(['retake'=>'Thí sinh chưa có lượt thi ở Round này.']);
        DB::transaction(function () use ($attempt,$candidate) {
            $attempt->delete();
            $candidate->update(['status'=>'active','last_seen_at'=>null]);
        });
        return back()->with('message','Đã đặt lại Round '.$round->round_order.'. Thí sinh có thể bắt đầu thi lại từ đầu khi Round đang mở.');
    }
    public function update(Request $request, Candidate $candidate)
    {
        $data = $request->validate([
            'level_id' => ['required','exists:levels,id'],
            'candidate_code' => ['required','string','max:50',Rule::unique('candidates')->where(fn ($query) => $query->where('exam_event_id', $candidate->exam_event_id))->ignore($candidate->id)],
            'full_name' => ['required','string','max:255'],
            'class_name' => ['nullable','string','max:255'],
            'computer_no' => ['nullable','string','max:50'],
            'status' => ['required','in:active,locked,finished'],
            'pin' => ['nullable','string','min:4','max:50'],
        ]);
        $payload = [
            'level_id' => $data['level_id'], 'candidate_code' => trim($data['candidate_code']),
            'full_name' => $data['full_name'], 'class_name' => $data['class_name'] ?? null,
            'computer_no' => $data['computer_no'] ?? null, 'status' => $data['status'],
        ];
        if (!empty($data['pin'])) $payload['pin_hash'] = Hash::make($data['pin']);
        $candidate->update($payload);
        return back()->with('message', 'Đã cập nhật thí sinh '.$candidate->candidate_code.'.');
    }

    public function destroy(Candidate $candidate)
    {
        $code = $candidate->candidate_code;
        $candidate->delete();
        return back()->with('message', 'Đã xóa thí sinh '.$code.' và dữ liệu bài thi liên quan.');
    }
    public function exportExcel(Request $request)
    {
        $event=ExamEvent::findOrFail($request->integer('event_id'));
        $rows=Candidate::where('exam_event_id',$event->id)->with('level')->orderBy('candidate_code')->get();
        $sheet=$this->baseSheet('DANH SÁCH THÍ SINH ENGLISH OLYMPIC');
        foreach($rows as $i=>$candidate){$row=$i+3;$sheet->fromArray([$candidate->candidate_code,$candidate->full_name,$candidate->level->code,$candidate->class_name,$candidate->computer_no,$candidate->status,optional($candidate->checkin_at)->format('d/m/Y H:i:s')],null,'A'.$row);}
        $sheet->setAutoFilter('A2:G'.max(2,$rows->count()+2));
        return $this->xlsxDownload($sheet->getParent(),'danh-sach-thi-sinh-'.now()->format('Ymd-His').'.xlsx');
    }

    public function downloadTemplate(Request $request)
    {
        $event=ExamEvent::findOrFail($request->integer('event_id'));
        $codes=collect(['KET'=>$event->allow_ket,'PET'=>$event->allow_pet])->filter()->keys()->values();
        $sheet=$this->baseSheet('MẪU NHẬP THÍ SINH - '.$event->name, true);
        foreach($codes as $i=>$code){
            $sheet->fromArray([$code.'001','Nguyễn Văn A',$code,'Lớp 6A','03','0123'],null,'A'.($i+3));
        }
        $noteRow=$codes->count()+4;
        $sheet->setCellValue('A'.$noteRow,'Chỉ nhập cấp độ '.$codes->join('/').'. PIN tối thiểu 4 ký tự. Không đổi tên hoặc thứ tự cột.');
        $sheet->mergeCells('A'.$noteRow.':G'.$noteRow);
        return $this->xlsxDownload($sheet->getParent(),'mau-thi-sinh-ky-thi-'.$event->id.'.xlsx');
    }

    public function importExcel(Request $request)
    {
        $data=$request->validate(['exam_event_id'=>['required','exists:exam_events,id'],'excel_file'=>['required','file','mimes:xlsx,xls,csv','max:10240']]);
        try{$rows=IOFactory::load($request->file('excel_file')->getRealPath())->getActiveSheet()->toArray(null,true,true,false);}catch(\Throwable $e){return back()->withErrors(['excel_file'=>'Không đọc được tệp Excel: '.$e->getMessage()]);}
        $event=ExamEvent::findOrFail($data['exam_event_id']);
        $allowedCodes=collect(['KET'=>$event->allow_ket,'PET'=>$event->allow_pet])->filter()->keys();
        $levels=Level::whereIn('code',$allowedCodes)->get()->keyBy(fn($l)=>strtoupper($l->code));$imported=0;$errors=[];
        foreach($rows as $index=>$row){if($index<2)continue;[$code,$name,$levelCode,$className,$computerNo,$pin]=array_pad(array_map(fn($v)=>trim((string)$v),array_slice($row,0,6)),6,'');if($code===''&&$name==='')continue;$level=$levels->get(strtoupper($levelCode));if(!$code||!$name||!$level||strlen($pin)<4){$errors[]='Dòng '.($index+1).': thiếu SBD, họ tên, cấp KET/PET hoặc PIN tối thiểu 4 ký tự.';continue;}Candidate::updateOrCreate(['exam_event_id'=>$data['exam_event_id'],'candidate_code'=>$code],['level_id'=>$level->id,'full_name'=>$name,'class_name'=>$className?:null,'computer_no'=>$computerNo?:null,'pin_hash'=>Hash::make($pin),'status'=>'active']);$imported++;}
        return back()->with('message','Đã nhập '.$imported.' thí sinh từ Excel.')->with('import_errors',$errors);
    }

    public function bulkImport(Request $request)
    {
        $data=$request->validate(['exam_event_id'=>['required','exists:exam_events,id'],'csv_text'=>['required','string']]);$levels=Level::all()->keyBy(fn($l)=>strtoupper($l->code));$imported=0;$errors=[];
        foreach(preg_split('/\r\n|\r|\n/',trim($data['csv_text'])) as $i=>$line){if(!trim($line))continue;$cols=str_getcsv($line);if(count($cols)<4){$errors[]='Dòng '.($i+1).': cần SBD, Họ tên, Cấp độ, PIN.';continue;}[$code,$name,$levelCode,$pin]=array_map('trim',array_slice($cols,0,4));$level=$levels->get(strtoupper($levelCode));if(!$level){$errors[]='Dòng '.($i+1).': cấp độ không hợp lệ.';continue;}Candidate::updateOrCreate(['exam_event_id'=>$data['exam_event_id'],'candidate_code'=>$code],['level_id'=>$level->id,'full_name'=>$name,'pin_hash'=>Hash::make($pin),'status'=>'active']);$imported++;}
        return back()->with('message','Đã nhập '.$imported.' thí sinh.')->with('import_errors',$errors);
    }

    private function baseSheet(string $title, bool $template = false)
    {
        $spreadsheet=new Spreadsheet();$sheet=$spreadsheet->getActiveSheet();$sheet->setTitle('Thi sinh');$sheet->setCellValue('A1',$title)->mergeCells('A1:G1');
        $headers = $template ? ['SBD','Họ và tên','Cấp độ','Lớp','Số máy','PIN','Ghi chú'] : ['SBD','Họ và tên','Cấp độ','Lớp','Số máy','Trạng thái','Check-in'];$sheet->fromArray($headers,null,'A2');$sheet->getStyle('A1:G2')->getFont()->setBold(true);$sheet->getStyle('A2:G2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0B5ED7');$sheet->getStyle('A2:G2')->getFont()->getColor()->setARGB('FFFFFFFF');foreach(range('A','G') as $col)$sheet->getColumnDimension($col)->setAutoSize(true);$sheet->freezePane('A3');return $sheet;
    }

    private function xlsxDownload(Spreadsheet $spreadsheet,string $filename)
    {
        return response()->streamDownload(function()use($spreadsheet){(new Xlsx($spreadsheet))->save('php://output');$spreadsheet->disconnectWorksheets();},$filename,['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}