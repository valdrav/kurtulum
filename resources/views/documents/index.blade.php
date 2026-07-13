@extends('layouts.app')
@section('title', __('documents.depot'))
@section('content')
@include('partials.page-header', ['title' => __('documents.depot'), 'subtitle' => __('documents.folder_subtitle')])

@include('documents.partials.browser', [
    'folders' => $folders,
    'currentFolderSlug' => null,
    'search' => $search,
    'storageStats' => $storageStats ?? null,
])
@endsection
