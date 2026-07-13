@extends('layouts.app')
@section('title', $displayName)
@section('content')
@include('partials.page-header', ['title' => $displayName, 'subtitle' => __('documents.folder_files')])

@include('documents.partials.browser', [
    'folders' => $folders,
    'currentFolderSlug' => $folderName !== '' ? $folderName : '__default',
    'displayName' => $displayName,
    'folderName' => $folderName,
    'documents' => $documents,
    'search' => request('search'),
])
@endsection
