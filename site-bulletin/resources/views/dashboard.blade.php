@extends('layouts.app')

@section('content')
  @php
      $announcements = $announcements instanceof \Illuminate\Support\Collection ? $announcements : collect($announcements ?? []);
      $categories = $categories instanceof \Illuminate\Support\Collection ? $categories : collect($categories ?? []);
      $snapshots = $snapshots instanceof \Illuminate\Support\Collection ? $snapshots : collect($snapshots ?? []);
      $riskFlag = $riskFlag ?? false;
  @endphp

  @include('dashboard.partials.content', [
      'announcements' => $announcements,
      'categories' => $categories,
      'snapshots' => $snapshots,
      'riskFlag' => $riskFlag,
  ])
@endsection
