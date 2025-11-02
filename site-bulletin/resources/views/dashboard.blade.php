@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-md p-4 space-y-6">
  <header class="text-center">
    <h1 class="text-2xl font-bold">Site Bulletin</h1>
    <p class="text-sm text-gray-600">Local info, tickets & performance (demo)</p>
  </header>

  {{-- Announcements + Links sections (fill from controller) --}}

  @auth
  {{-- Performance widget --}}
  <section aria-labelledby="perf" class="rounded-lg border p-4 bg-white">
    <div class="flex items-center justify-between mb-2">
      <h2 id="perf" class="text-lg font-semibold">My Performance (last 6 weeks)</h2>
      <span id="perf-risk" class="text-xs px-2 py-1 rounded hidden"></span>
    </div>
    <div class="text-xs text-gray-500 mb-2">Placeholder data for coursework.</div>
    <div id="perf-weeks" class="grid grid-cols-3 sm:grid-cols-6 gap-2 text-center"></div>
    <script>
      fetch('{{ route('me.performance') }}',{headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(r=>r.json()).then(d=>{
          const wrap = document.getElementById('perf-weeks'); wrap.innerHTML='';
          d.weeks.forEach(w=>{
            const el=document.createElement('div');
            el.className='rounded border p-2';
            el.innerHTML=`<div class="text-[10px] text-gray-500">${w.week_start}</div>
                          <div class="text-base font-bold">${w.uph ?? '-'}</div>
                          <div class="text-[10px]">percentile: ${w.pctl ?? '-'}</div>`;
            wrap.appendChild(el);
          });
          const risk=document.getElementById('perf-risk');
          if(d.at_risk){ risk.textContent='ADAPT Risk'; risk.classList.remove('hidden'); risk.classList.add('bg-red-100','text-red-800'); }
          else { risk.textContent='OK'; risk.classList.remove('hidden'); risk.classList.add('bg-green-100','text-green-800'); }
        }).catch(()=>{ document.getElementById('perf-weeks').innerHTML='<div class="col-span-6 text-sm text-gray-500">No data.</div>';});
    </script>
  </section>
  @endauth
</div>
@endsection
