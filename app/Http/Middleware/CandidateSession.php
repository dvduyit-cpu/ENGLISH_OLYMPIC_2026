<?php

namespace App\Http\Middleware;

use App\Models\Candidate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CandidateSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $candidateId = $request->session()->get('candidate_id');
        $candidate = $candidateId ? Candidate::find($candidateId) : null;

        if (!$candidate || $candidate->status !== 'active') {
            $request->session()->forget('candidate_id');
            return redirect()->route('candidate.login');
        }

        $candidate->forceFill([
            'last_seen_at' => now(),
            'last_ip' => $request->ip(),
        ])->save();

        $request->attributes->set('candidate', $candidate);
        view()->share('candidate', $candidate);

        return $next($request);
    }
}
