@extends('layouts.app')

@section('title', 'New Request | EngiStart')
@section('header', 'New Request')
@section('subheader', 'Create and submit a project initialization request.')

@section('sidebar')
    <a href="#"
       class="flex items-center rounded-md px-3 py-2 text-sm font-medium
              bg-apis-bg border text-apis-text"
       style="border-color: var(--border2)">
        New Request
    </a>
    <a href="#"
       class="flex items-center rounded-md px-3 py-2 text-sm text-apis-text2
              transition-colors hover:bg-apis-bg2 hover:text-apis-text">
        My Requests
    </a>
@endsection

@section('sidebarFooter')
    <p class="mb-1 text-[10px] text-apis-text3">Signed in as</p>
    <p class="text-xs font-medium leading-tight text-apis-text">Jose Santos</p>
    <p class="mt-0.5 text-[11px] text-apis-blue">Farm Manager</p>
@endsection

@section('content')
    <div class="p-6">
        {{-- your form content goes here --}}
    </div>
@endsection