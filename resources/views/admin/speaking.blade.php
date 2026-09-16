@extends('layouts.app')
@section('title','Speaking Challenge')
@section('topbar')
<div class="topbar"><div class="brand">SPEAKING CHALLENGE</div><div class="nav"><a href="{{ route('admin.dashboard') }}">Dashboard</a><a href="{{ route('admin.candidates.index') }}">Thí sinh</a><a href="{{ route('admin.questions.index') }}">Câu hỏi</a></div></div>
@endsection
@section('content')
<div class="card"><h2>Thêm thí sinh Speaking</h2><form method="post" action="{{ route('admin.speaking.add') }}">@csrf<input type="hidden" name="exam_event_id" value="{{ $event->id }}"><div class="grid"><div><label>Thí sinh</label><select name="candidate_id">@foreach($candidates as $c)<option value="{{ $c->id }}">{{ $c->candidate_code }} — {{ $c->full_name }} ({{ $c->level->code }})</option>@endforeach</select></div><div><label>Phòng</label><input name="room" placeholder="Meeting room"></div></div><button class="btn" style="margin-top:12px">Thêm</button></form></div>
@foreach($sessions as $session)
@php($myScores = $session->scores->where('judge_id',auth()->id())->keyBy('criterion_id'))
<div class="card"><div class="row between"><div><h3 style="margin:0">{{ $session->candidate->candidate_code }} — {{ $session->candidate->full_name }}</h3><div class="muted">{{ $session->candidate->level->code }} · {{ $session->room }}</div></div><span class="badge">{{ $session->status }}</span></div>
<form method="post" action="{{ route('admin.speaking.score',$session) }}">@csrf<div class="grid" style="margin-top:14px">@foreach($criteria as $criterion)<div><label>{{ $criterion->name }} / {{ $criterion->max_score }}</label><input type="number" step="0.25" min="0" max="{{ $criterion->max_score }}" name="criterion_{{ $criterion->id }}" value="{{ optional($myScores->get($criterion->id))->score }}" required></div>@endforeach</div><div style="margin-top:12px"><label>Nhận xét</label><textarea name="comment">{{ optional($myScores->first())->comment }}</textarea></div><button class="btn success">Lưu điểm của giám khảo</button></form>
@if($session->scores->count())<p class="muted">Đã có {{ $session->scores->pluck('judge_id')->unique()->count() }} giám khảo chấm.</p>@endif</div>
@endforeach
@endsection
