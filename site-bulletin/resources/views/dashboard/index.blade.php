@extends('layouts.app')

@section('content')
  @include('dashboard.partials.content', [
      'announcements' => $announcements,
      'categories' => $categories,
      'snapshots' => $snapshots,
      'riskFlag' => $riskFlag,
      'messagePreview' => $messagePreview,
      'unreadConversationCount' => $unreadConversationCount,
  ])
@endsection
