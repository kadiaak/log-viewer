<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Log Viewer · {{ $config['app_name'] }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|jetbrains-mono:400,500" rel="stylesheet">
    <link rel="stylesheet" href="{{ route('log-viewer.assets', 'app.css') }}">
    <script>
        window.LogViewer = {
            csrfToken: @json(csrf_token()),
            urls: {
                files: @json(route('log-viewer.api.files.index')),
                logs: @json(route('log-viewer.api.logs.index', ['identifier' => '__ID__'])),
                download: @json(route('log-viewer.api.files.download', ['identifier' => '__ID__'])),
                clear: @json(route('log-viewer.api.files.clear', ['identifier' => '__ID__'])),
                destroy: @json(route('log-viewer.api.files.destroy', ['identifier' => '__ID__'])),
            },
            config: @json($config),
        };
    </script>
    <script defer src="{{ route('log-viewer.assets', 'app.js') }}"></script>
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-200">
<div x-data="logViewer" x-cloak class="flex h-full flex-col">

    {{-- ===== Header ===== --}}
    <header class="flex h-14 shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center gap-2 font-semibold">
            <svg class="h-6 w-6 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M8 9h2"/>
            </svg>
            <span>Log Viewer</span>
        </div>
        <span class="hidden text-sm text-slate-400 sm:inline">/ {{ $config['app_name'] }}</span>

        <div class="ml-auto flex items-center gap-2">
            <label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1 text-sm text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                <input type="checkbox" x-model="autoRefresh" class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-500 focus:ring-indigo-500">
                <span>Auto-refresh</span>
            </label>
            <button @click="refresh()" :class="loading && 'animate-spin'" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" title="Refresh">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
            </button>
            <button @click="toggleTheme()" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" title="Toggle theme">
                <svg x-show="!dark" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                <svg x-show="dark" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
            </button>
            @if($config['back_to_system_url'])
                <a href="{{ $config['back_to_system_url'] }}" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                    ← {{ $config['back_to_system_label'] }}
                </a>
            @endif
        </div>
    </header>

    <div class="flex min-h-0 flex-1">

        {{-- ===== Sidebar: files ===== --}}
        <aside class="flex w-72 shrink-0 flex-col border-r border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 p-3 dark:border-slate-800">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" x-model="fileSearch" placeholder="Filter files…"
                           class="w-full rounded-md border border-slate-200 bg-slate-50 py-2 pl-8 pr-3 text-sm placeholder-slate-400 focus:border-indigo-400 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-800">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-2">
                <template x-if="filteredFolders.length === 0">
                    <p class="px-2 py-8 text-center text-sm text-slate-400">No log files found.</p>
                </template>

                <template x-for="folder in filteredFolders" :key="folder.folder">
                    <div class="mb-2">
                        <template x-if="folder.folder">
                            <p class="px-2 py-1 text-xs font-semibold uppercase tracking-wide text-slate-400" x-text="folder.folder"></p>
                        </template>
                        <template x-for="file in folder.files" :key="file.identifier">
                            <div @click="selectFile(file)"
                                 :class="selectedFile && selectedFile.identifier === file.identifier
                                    ? 'bg-indigo-50 ring-1 ring-indigo-200 dark:bg-indigo-500/10 dark:ring-indigo-500/30'
                                    : 'hover:bg-slate-100 dark:hover:bg-slate-800'"
                                 class="group mb-0.5 cursor-pointer rounded-md px-2 py-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="truncate text-sm font-medium" :class="selectedFile && selectedFile.identifier === file.identifier ? 'text-indigo-700 dark:text-indigo-300' : ''" x-text="file.name"></span>
                                    <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition group-hover:opacity-100">
                                        <template x-if="config.allow_download">
                                            <a :href="url('download', file.identifier)" @click.stop class="rounded p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-700 dark:hover:bg-slate-700" title="Download">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                                            </a>
                                        </template>
                                        <template x-if="config.allow_delete">
                                            <button @click.stop="clearFile(file)" class="rounded p-1 text-slate-400 hover:bg-amber-100 hover:text-amber-600 dark:hover:bg-amber-500/20" title="Clear contents">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                            </button>
                                        </template>
                                        <template x-if="config.allow_delete">
                                            <button @click.stop="deleteFile(file)" class="rounded p-1 text-slate-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-500/20" title="Delete file">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-400">
                                    <span x-text="file.human_size"></span>
                                    <span>·</span>
                                    <span x-text="file.last_modified_human"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </aside>

        {{-- ===== Main: entries ===== --}}
        <main class="flex min-w-0 flex-1 flex-col">
            <template x-if="!selectedFile">
                <div class="flex flex-1 flex-col items-center justify-center text-slate-400">
                    <svg class="mb-3 h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    <p>Select a log file to get started.</p>
                </div>
            </template>

            <template x-if="selectedFile">
                <div class="flex min-h-0 flex-1 flex-col">
                    {{-- Toolbar --}}
                    <div class="shrink-0 border-b border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative min-w-[220px] flex-1">
                                <svg class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                <input type="text" x-model="query" @input.debounce.350ms="resetAndLoad()" placeholder="Search messages… (wrap in /…/ for regex)"
                                       class="w-full rounded-md border border-slate-200 bg-slate-50 py-2 pl-8 pr-3 text-sm focus:border-indigo-400 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-800">
                            </div>
                            <button @click="toggleDirection()" class="flex items-center gap-1.5 rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 16 4 4 4-4"/><path d="M7 20V4"/><path d="M11 4h4M11 8h7M11 12h10"/></svg>
                                <span x-text="direction === 'desc' ? 'Newest' : 'Oldest'"></span>
                            </button>
                            <select x-model.number="perPage" @change="resetAndLoad()" class="rounded-md border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                <option value="25">25 / page</option>
                                <option value="50">50 / page</option>
                                <option value="100">100 / page</option>
                                <option value="250">250 / page</option>
                            </select>
                        </div>

                        {{-- Level chips --}}
                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                            <template x-for="level in levelList" :key="level">
                                <button x-show="(levelCounts[level] || 0) > 0 || selectedLevels.includes(level)"
                                        @click="toggleLevel(level)"
                                        :class="chipClass(level)"
                                        class="flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition">
                                    <span class="h-2 w-2 rounded-full" :class="dotClass(level)"></span>
                                    <span x-text="capitalize(level)"></span>
                                    <span class="tabular-nums opacity-60" x-text="levelCounts[level] || 0"></span>
                                </button>
                            </template>
                            <button x-show="selectedLevels.length" @click="selectedLevels = []; resetAndLoad()" class="ml-1 text-xs text-slate-400 underline hover:text-slate-600">clear</button>
                        </div>
                    </div>

                    {{-- Entry list --}}
                    <div class="relative min-h-0 flex-1 overflow-y-auto" x-ref="list">
                        <template x-if="selectedFile.is_too_large">
                            <div class="m-4 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                This file is too large to be parsed in the browser. You can still download it.
                            </div>
                        </template>

                        <template x-if="!loading && entries.length === 0 && !selectedFile.is_too_large">
                            <div class="flex flex-1 flex-col items-center justify-center py-20 text-slate-400">
                                <p>No log entries match your filters.</p>
                            </div>
                        </template>

                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="entry in entries" :key="entry.index">
                                <div>
                                    <div @click="entry.has_body && toggle(entry)"
                                         :class="entry.has_body ? 'cursor-pointer' : ''"
                                         class="flex items-start gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <span class="mt-0.5 inline-flex shrink-0 items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-semibold" :class="badgeClass(entry.level)">
                                            <span x-text="entry.level_label"></span>
                                        </span>
                                        <time class="mt-0.5 shrink-0 font-mono text-xs text-slate-400" x-text="entry.time"></time>
                                        <span class="mt-0.5 shrink-0 rounded bg-slate-100 px-1.5 text-[10px] font-medium uppercase text-slate-500 dark:bg-slate-800" x-text="entry.environment"></span>
                                        <p class="min-w-0 flex-1 break-words font-mono text-sm" :class="expanded.includes(entry.index) ? '' : 'truncate'" x-text="entry.message"></p>
                                        <svg x-show="entry.has_body" :class="expanded.includes(entry.index) && 'rotate-90'" class="mt-1 h-4 w-4 shrink-0 text-slate-400 transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </div>
                                    <template x-if="entry.has_body && expanded.includes(entry.index)">
                                        <div class="relative bg-slate-900 px-4 py-3 dark:bg-black/40">
                                            <button @click="copy(entry)" class="absolute right-3 top-3 rounded bg-slate-700/60 px-2 py-1 text-xs text-slate-200 hover:bg-slate-600">
                                                <span x-text="copied === entry.index ? 'Copied!' : 'Copy'"></span>
                                            </button>
                                            <pre class="overflow-x-auto whitespace-pre-wrap break-words font-mono text-xs leading-relaxed text-slate-300" x-text="entry.body"></pre>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Loading overlay --}}
                        <div x-show="loading" class="pointer-events-none absolute inset-0 flex items-start justify-center bg-white/40 pt-20 dark:bg-slate-950/40">
                            <div class="flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm text-slate-500 shadow dark:bg-slate-800">
                                <svg class="h-4 w-4 animate-spin text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                Loading…
                            </div>
                        </div>
                    </div>

                    {{-- Pagination --}}
                    <div class="flex shrink-0 items-center justify-between border-t border-slate-200 bg-white px-4 py-2.5 text-sm dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-slate-500">
                            <span x-text="pagination.from"></span>–<span x-text="pagination.to"></span>
                            of <span class="font-medium" x-text="pagination.total"></span> entries
                        </p>
                        <div class="flex items-center gap-1">
                            <button @click="goToPage(1)" :disabled="pagination.current_page <= 1" class="rounded px-2 py-1 text-slate-500 disabled:opacity-30 hover:bg-slate-100 dark:hover:bg-slate-800">«</button>
                            <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="rounded px-2 py-1 text-slate-500 disabled:opacity-30 hover:bg-slate-100 dark:hover:bg-slate-800">‹</button>
                            <span class="px-2 text-slate-600 dark:text-slate-300">
                                <span x-text="pagination.current_page"></span> / <span x-text="pagination.last_page"></span>
                            </span>
                            <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="rounded px-2 py-1 text-slate-500 disabled:opacity-30 hover:bg-slate-100 dark:hover:bg-slate-800">›</button>
                            <button @click="goToPage(pagination.last_page)" :disabled="pagination.current_page >= pagination.last_page" class="rounded px-2 py-1 text-slate-500 disabled:opacity-30 hover:bg-slate-100 dark:hover:bg-slate-800">»</button>
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>

    {{-- Toast --}}
    <div x-show="toast" x-transition class="fixed bottom-4 right-4 rounded-md bg-slate-900 px-4 py-2 text-sm text-white shadow-lg dark:bg-slate-700" x-text="toast"></div>
</div>
</body>
</html>
