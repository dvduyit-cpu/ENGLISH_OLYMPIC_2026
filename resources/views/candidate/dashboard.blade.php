@extends('layouts.app')
@section('title','Khu vực thí sinh')
@section('body_class', strtoupper($candidate->level->code ?? 'KET') === 'PET' ? 'theme-pet' : 'theme-ket')
@section('topbar')
<div class="topbar"><div><div class="brand">ENGLISH OLYMPIC 2026</div><div style="font-size:12px;opacity:.8;margin-top:3px">CANDIDATE EXAM PORTAL</div></div><div class="nav"><span class="hide-mobile">{{ $candidate->candidate_code }} · {{ $candidate->full_name }}</span><form method="post" action="{{ route('candidate.logout') }}">@csrf<button class="btn secondary">Đăng xuất</button></form></div></div>
@endsection
@push('head')
<style>
.candidate-hero{padding:28px;background:linear-gradient(135deg,var(--primary-dark),var(--primary));color:#fff;border:0;position:relative;overflow:hidden}.candidate-hero:after{content:attr(data-level);position:absolute;right:22px;bottom:-35px;font-size:150px;font-weight:1000;opacity:.08}.identity{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:rgba(255,255,255,.2);border-radius:13px;overflow:hidden;margin-top:24px}.identity>div{background:rgba(0,0,0,.08);padding:14px}.identity small{display:block;opacity:.72;margin-bottom:5px}.identity strong{font-size:16px}.exam-title{display:flex;gap:15px;align-items:center}.level-seal{width:66px;height:66px;border-radius:18px;background:#fff;color:var(--primary);display:grid;place-items:center;font-size:22px;font-weight:1000}.round-card{border-top:5px solid var(--primary);display:flex;flex-direction:column;min-height:260px}.round-card.is-locked{opacity:.72;border-top-color:#94a3b8}.round-no{font-size:12px;font-weight:900;color:var(--primary);letter-spacing:.1em}.round-meta{display:flex;gap:16px;margin:18px 0}.round-meta div{background:#f8fafc;border:1px solid #e2e8f0;border-radius:11px;padding:11px;flex:1}.round-meta strong{display:block;font-size:20px}.round-action{margin-top:auto}.round-action .btn{width:100%;padding:13px}.ready-note{background:var(--primary-soft);color:var(--primary-dark);border-radius:12px;padding:13px;font-size:14px;margin-bottom:15px}@media(max-width:700px){.identity{grid-template-columns:1fr 1fr}.candidate-hero:after{font-size:90px}.exam-title{align-items:flex-start}}
</style>
@endpush
@section('content')
@php($levelCode = strtoupper($candidate->level->code ?? 'KET'))
<div class="card candidate-hero" data-level="{{ $levelCode }}">
    <div class="exam-title"><div class="level-seal">{{ $levelCode }}</div><div><div style="opacity:.8;font-weight:700">Xin chào thí sinh</div><h1 style="margin:3px 0 0">{{ $candidate->full_name }}</h1></div></div>
    <div class="identity">
        <div><small>Số báo danh</small><strong>{{ $candidate->candidate_code }}</strong></div>
        <div><small>Cấp độ dự thi</small><strong>{{ $levelCode }}</strong></div>
        <div><small>Lớp / Đơn vị</small><strong>{{ $candidate->class_name ?: 'Chưa cập nhật' }}</strong></div>
        <div><small>Số máy</small><strong>{{ $candidate->computer_no ?: 'Theo giám thị' }}</strong></div>
    </div>
</div>
<div class="row between" style="margin:28px 0 14px"><div><h2 style="margin-bottom:5px">Bài thi của bạn</h2><div class="muted">Chọn vòng đang mở để bắt đầu. Đề thi tự động theo cấp độ {{ $levelCode }}.</div></div><span class="badge">● ĐÃ CHECK-IN {{ optional($candidate->checkin_at)->format('H:i') }}</span></div>
<div class="grid">
@foreach($rounds as $round)
    @php($attempt = $round->attempt_record)
    <article class="card round-card {{ (!$round->can_enter && !$attempt) ? 'is-locked' : '' }}">
        <div class="row between"><span class="round-no">VÒNG {{ $round->round_order }}</span><span class="badge">{{ $round->status === 'open' ? 'ĐANG MỞ' : ($round->status === 'closed' ? 'ĐÃ ĐÓNG' : 'CHỜ MỞ') }}</span></div>
        <h3 style="font-size:20px;margin:13px 0 5px">{{ $round->name }}</h3>
        <div class="round-meta"><div><small class="muted">Số câu</small><strong>{{ $round->number_questions }}</strong></div><div><small class="muted">Thời gian</small><strong>{{ floor($round->time_limit_seconds/60) }} phút</strong></div></div>
        <div class="round-action">
        @if($attempt && $attempt->status !== 'in_progress')
            <div class="ready-note"><strong>Đã nộp bài</strong><br>Điểm: {{ $attempt->score ?? 0 }} · Xếp hạng: {{ $attempt->rank ?? '—' }}</div><button class="btn" disabled>Đã hoàn thành</button>
        @elseif($attempt && $attempt->status === 'in_progress')
            <div class="ready-note">Bài làm đang được lưu. Hãy tiếp tục để hoàn thành.</div><a class="btn" href="{{ route('candidate.exam.show', [$round,$attempt]) }}">Tiếp tục làm bài →</a>
        @elseif($round->can_enter)
            <div class="ready-note">Đề {{ $levelCode }} đã sẵn sàng. Đồng hồ bắt đầu ngay khi bạn bấm nút.</div><form method="post" action="{{ route('candidate.exam.start',$round) }}">@csrf<button class="btn" type="submit">Bắt đầu làm bài →</button></form>
        @else
            @if($round->status === 'open' && ($round->question_count ?? 0) < $round->number_questions)
                <div class="ready-note">Bộ đề đang được chuẩn bị: {{ $round->question_count ?? 0 }}/{{ $round->number_questions }} câu.</div><button class="btn" disabled>Chưa đủ câu hỏi</button>
            @else
                <button class="btn" disabled>Chưa mở hoặc chưa đủ điều kiện</button>
            @endif
        @endif
        </div>
    </article>
@endforeach
</div>
@endsection