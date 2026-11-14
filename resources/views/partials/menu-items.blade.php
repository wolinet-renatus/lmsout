@php
    $items = $items ?? [];
    $isRoot = $isRoot ?? false;
    $reqPath = trim(request()->path(), '/');
@endphp

<?php foreach ($items as $index => $item): ?>
    @php
        $children = $item['children'] ?? [];
        $hasChildren = !empty($children);
        $url = $item['url'] ?? '#';
        $target = $item['target'] ?? '_self';
        $iconClass = $isRoot ? 'fa-solid fa-chevron-down' : 'fas fa-angle-right';

        $itemPath = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $basePrefix = trim((string) parse_url(url('/'), PHP_URL_PATH), '/');
        if ($basePrefix !== '' && str_starts_with($itemPath, $basePrefix)) {
            $itemPath = trim(substr($itemPath, strlen($basePrefix)), '/');
        }
        $isActive = ($itemPath === '' && $reqPath === '') || ($itemPath !== '' && ($reqPath === $itemPath || str_starts_with($reqPath, $itemPath . '/')));
    @endphp
    <li class="{{ $hasChildren ? 'has-dropdown' : '' }} {{ $isActive ? 'is-active' : '' }}" data-nav-index="{{ $loopIndex ?? 0 }}-{{ $index }}">
        <a href="{{ $url }}" target="{{ $target }}" class="border-none {{ $isActive ? 'is-active' : '' }}">
            {{ $item['title'] ?? 'Menu Item' }}
            @if($hasChildren)
                <i class="{{ $iconClass }}"></i>
            @endif
        </a>
        @if($hasChildren)
            <ul class="submenu">
                @include('partials.menu-items', ['items' => $children, 'isRoot' => false, 'loopIndex' => ($loopIndex ?? 0) . '-' . $index])
            </ul>
        @endif
    </li>
<?php endforeach; ?>
