@extends('layouts.app')
@section('title','Kết quả '.$round->name)
@section('topbar')
<div class="topbar"><div class="brand">KẾT QUẢ — {{ $round->name }}</div><div class="nav"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div></div>
@endsection
@section('content')
<div class="card"><h1>{{ $round->name }}</h1><p class="muted">Xếp theo điểm giảm dần, sau đó thời gian hoàn thành tăng dần.</p></div>
<div class="card table-wrap"><table><thead><tr><th>Hạng</th><th>SBD</th><th>Họ tên</th><th>Cấp độ</th><th>Điểm</th><th>Đúng</th><th>Thời gian</th><th>Trạng thái</th></tr></thead><tbody>
@forelse($attempts as $a)
<tr><td><strong>{{ $a->rank }}</strong></td><td>{{ $a->candidate->candidate_code }}</td><td>{{ $a->candidate->full_name }}</td><td>{{ $a->candidate->level->code }}</td><td><strong>{{ $a->score }}</strong></td><td>{{ $a->correct_answers }}</td><td>{{ $a->elapsed_ms ? number_format($a->elapsed_ms/1000,3) : '-' }} s</td><td>{{ $a->status }}</td></tr>
@empty<tr><td colspan="8">Chưa có bài nộp.</td></tr>@endforelse
</tbody></table></div>
{{ $attempts->links() }}
@endsection
