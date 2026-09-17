<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\ExamEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class RankingsController extends Controller
{
 public function __invoke(Request $request)
 {
  $events=ExamEvent::latest('id')->get();
  $event=$this->selectedEvent($request,$events);
  $rankings=$event?$this->buildRankings($event):collect();
  return view('admin.rankings',compact('events','event','rankings'));
 }
 public function export(Request $request)
 {
  $events=ExamEvent::latest('id')->get();
  $event=$this->selectedEvent($request,$events);
  abort_unless($event,404,'Chưa có kỳ thi để xuất kết quả.');
  $rankings=$this->buildRankings($event);
  $spreadsheet=new Spreadsheet();$sheet=$spreadsheet->getActiveSheet();$sheet->setTitle('Bang xep hang');
  $sheet->setCellValue('A1','BẢNG TỔNG HỢP ĐIỂM VÀ XẾP HẠNG')->mergeCells('A1:L1');
  $sheet->setCellValue('A2',$event->name)->mergeCells('A2:L2');
  $sheet->fromArray(['Hạng','SBD','Họ và tên','Cấp độ','Lớp / Đơn vị','Round 1','Round 2','Round 3','Số câu đúng','Số câu sai','Tổng điểm','Tổng thời gian'],null,'A4');
  foreach($rankings as $index=>$candidate){$roundScores=[];foreach([1,2,3] as $order){$attempt=$candidate->attempts->first(fn($item)=>optional($item->round)->round_order===$order);$roundScores[]=$attempt?(float)$attempt->score:null;}$row=$index+5;$sheet->fromArray([$candidate->overall_rank,$candidate->candidate_code,$candidate->full_name,$candidate->level->code,$candidate->class_name,...$roundScores,$candidate->total_correct,$candidate->total_wrong,$candidate->total_score,$this->formatElapsed($candidate->total_elapsed)],null,'A'.$row);}
  $lastRow=max(4,$rankings->count()+4);$sheet->getStyle('A1:L2')->getFont()->setBold(true);$sheet->getStyle('A1:L1')->getFont()->setSize(16);$sheet->getStyle('A1:L2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);$sheet->getStyle('A4:L4')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');$sheet->getStyle('A4:L4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0B5ED7');$sheet->getStyle('A4:L'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin')->getColor()->setARGB('FFD9E2EC');$sheet->getStyle('F5:K'.$lastRow)->getNumberFormat()->setFormatCode('0.00');$sheet->freezePane('A5');$sheet->setAutoFilter('A4:L'.$lastRow);foreach(range('A','L') as $column)$sheet->getColumnDimension($column)->setAutoSize(true);
  $filename='bang-xep-hang-'.str($event->name)->slug().'-'.now()->format('Ymd-His').'.xlsx';
  return response()->streamDownload(function()use($spreadsheet){(new Xlsx($spreadsheet))->save('php://output');$spreadsheet->disconnectWorksheets();},$filename,['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
 }
 private function selectedEvent(Request $request,Collection $events):?ExamEvent{return $request->integer('event_id')?$events->firstWhere('id',$request->integer('event_id')):($events->firstWhere('status','active')??$events->first());}
 private function buildRankings(ExamEvent $event):Collection
 {
  $rankings=Candidate::where('exam_event_id',$event->id)->with(['level','attempts'=>fn($q)=>$q->whereIn('status',['submitted','auto_submitted'])->with('round')])->get()->map(function($candidate){$candidate->total_score=(float)$candidate->attempts->sum('score');$candidate->total_correct=$candidate->attempts->sum('correct_answers');$candidate->total_wrong=$candidate->attempts->sum('wrong_answers');$candidate->total_elapsed=$candidate->attempts->sum('elapsed_ms');$candidate->rounds_completed=$candidate->attempts->count();return $candidate;})->sort(function($a,$b){return $b->total_score<=>$a->total_score ?: $a->total_elapsed<=>$b->total_elapsed ?: strcmp($a->candidate_code,$b->candidate_code);})->values();$rank=0;$rankings->each(function($candidate)use(&$rank){$candidate->overall_rank=++$rank;});return $rankings;
 }
 private function formatElapsed(int|float|null $milliseconds):string{$seconds=(int)floor(($milliseconds??0)/1000);return sprintf('%02d:%02d:%02d',intdiv($seconds,3600),intdiv($seconds%3600,60),$seconds%60);}
}