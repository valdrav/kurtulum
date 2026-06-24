@php
    $user = $user ?? auth()->user();
    $size = $size ?? 'sm';
    $class = trim('avatar avatar-' . $size . ' ' . ($class ?? ''));
    $avatarUrl = user_avatar_url($user);
@endphp
@if($avatarUrl)
<img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="{{ $class }} ef-avatar-img" loading="lazy">
@else
<span class="{{ $class }} bg-primary-lt">{{ user_avatar_initials($user) }}</span>
@endif
