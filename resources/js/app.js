import Alpine from 'alpinejs'

const LEVEL_COLORS = {
    red: {
        chip: 'border-red-300 bg-red-50 text-red-700 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-300',
        chipOff: 'border-slate-200 bg-white text-slate-500 hover:border-red-300 dark:border-slate-700 dark:bg-slate-800',
        dot: 'bg-red-500',
        badge: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
    },
    amber: {
        chip: 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300',
        chipOff: 'border-slate-200 bg-white text-slate-500 hover:border-amber-300 dark:border-slate-700 dark:bg-slate-800',
        dot: 'bg-amber-500',
        badge: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    },
    blue: {
        chip: 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-300',
        chipOff: 'border-slate-200 bg-white text-slate-500 hover:border-blue-300 dark:border-slate-700 dark:bg-slate-800',
        dot: 'bg-blue-500',
        badge: 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
    },
    green: {
        chip: 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300',
        chipOff: 'border-slate-200 bg-white text-slate-500 hover:border-emerald-300 dark:border-slate-700 dark:bg-slate-800',
        dot: 'bg-emerald-500',
        badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    },
    gray: {
        chip: 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200',
        chipOff: 'border-slate-200 bg-white text-slate-500 hover:border-slate-400 dark:border-slate-700 dark:bg-slate-800',
        dot: 'bg-slate-400',
        badge: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    },
}

const LEVEL_TO_COLOR = {
    emergency: 'red', alert: 'red', critical: 'red', error: 'red',
    warning: 'amber',
    notice: 'blue', info: 'blue',
    debug: 'green',
    none: 'gray',
}

const colorFor = (level) => LEVEL_COLORS[LEVEL_TO_COLOR[level] || 'gray']

Alpine.data('logViewer', () => ({
    config: window.LogViewer.config,
    levelList: ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'none'],

    // state
    folders: [],
    selectedFile: null,
    entries: [],
    levelCounts: {},
    pagination: { current_page: 1, last_page: 1, per_page: 50, total: 0, from: 0, to: 0 },

    // filters
    fileSearch: '',
    query: '',
    selectedLevels: [],
    direction: 'desc',
    perPage: 50,

    // ui
    loading: false,
    expanded: [],
    copied: null,
    dark: false,
    autoRefresh: false,
    toast: '',
    _toastTimer: null,
    _refreshTimer: null,

    init() {
        this.perPage = this.config.per_page || 50
        this.initTheme()
        this.loadFiles()

        this.$watch('autoRefresh', (on) => {
            clearInterval(this._refreshTimer)
            if (on) this._refreshTimer = setInterval(() => this.refresh(), 5000)
        })
    },

    // ---- theme ----
    initTheme() {
        const stored = localStorage.getItem('log-viewer-theme')
        const pref = stored || this.config.theme || 'system'
        this.dark = pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)
        this.applyTheme()
    },
    applyTheme() {
        document.documentElement.classList.toggle('dark', this.dark)
    },
    toggleTheme() {
        this.dark = !this.dark
        localStorage.setItem('log-viewer-theme', this.dark ? 'dark' : 'light')
        this.applyTheme()
    },

    // ---- url helpers ----
    url(name, id) {
        return window.LogViewer.urls[name].replace('__ID__', id)
    },

    // ---- files ----
    async loadFiles() {
        try {
            const res = await fetch(window.LogViewer.urls.files, { headers: { Accept: 'application/json' } })
            const data = await res.json()
            this.folders = data.folders
            if (!this.selectedFile && this.folders.length) {
                const first = this.folders[0].files[0]
                if (first) this.selectFile(first)
            }
        } catch (e) {
            this.notify('Failed to load files')
        }
    },

    get filteredFolders() {
        const q = this.fileSearch.toLowerCase().trim()
        if (!q) return this.folders
        return this.folders
            .map((f) => ({ ...f, files: f.files.filter((file) => file.name.toLowerCase().includes(q)) }))
            .filter((f) => f.files.length)
    },

    selectFile(file) {
        this.selectedFile = file
        this.pagination.current_page = 1
        this.expanded = []
        this.loadLogs()
    },

    // ---- logs ----
    async loadLogs() {
        if (!this.selectedFile) return
        this.loading = true
        try {
            const params = new URLSearchParams()
            params.set('query', this.query)
            params.set('page', this.pagination.current_page)
            params.set('per_page', this.perPage)
            params.set('direction', this.direction)
            this.selectedLevels.forEach((l) => params.append('levels[]', l))

            const res = await fetch(`${this.url('logs', this.selectedFile.identifier)}?${params}`, {
                headers: { Accept: 'application/json' },
            })
            const data = await res.json()
            this.entries = data.entries
            this.levelCounts = data.level_counts
            this.pagination = data.pagination
            this.selectedFile = data.file
            if (this.$refs.list) this.$refs.list.scrollTop = 0
        } catch (e) {
            this.notify('Failed to load entries')
        } finally {
            this.loading = false
        }
    },

    refresh() {
        this.loadFiles()
        this.loadLogs()
    },

    resetAndLoad() {
        this.pagination.current_page = 1
        this.loadLogs()
    },

    goToPage(page) {
        if (page < 1 || page > this.pagination.last_page) return
        this.pagination.current_page = page
        this.loadLogs()
    },

    // ---- filters ----
    toggleLevel(level) {
        const i = this.selectedLevels.indexOf(level)
        if (i === -1) this.selectedLevels.push(level)
        else this.selectedLevels.splice(i, 1)
        this.resetAndLoad()
    },
    toggleDirection() {
        this.direction = this.direction === 'desc' ? 'asc' : 'desc'
        this.resetAndLoad()
    },

    // ---- entry ----
    toggle(entry) {
        const i = this.expanded.indexOf(entry.index)
        if (i === -1) this.expanded.push(entry.index)
        else this.expanded.splice(i, 1)
    },
    async copy(entry) {
        try {
            await navigator.clipboard.writeText(`${entry.message}\n${entry.body}`.trim())
            this.copied = entry.index
            setTimeout(() => (this.copied = null), 1500)
        } catch (e) {
            this.notify('Copy failed')
        }
    },

    // ---- file actions ----
    async clearFile(file) {
        if (!confirm(`Clear all contents of "${file.name}"? This cannot be undone.`)) return
        await this.request('clear', file.identifier, 'DELETE')
        this.notify('File cleared')
        if (this.selectedFile?.identifier === file.identifier) this.loadLogs()
        this.loadFiles()
    },
    async deleteFile(file) {
        if (!confirm(`Permanently delete "${file.name}"?`)) return
        await this.request('destroy', file.identifier, 'DELETE')
        this.notify('File deleted')
        if (this.selectedFile?.identifier === file.identifier) {
            this.selectedFile = null
            this.entries = []
        }
        this.loadFiles()
    },
    async request(name, id, method) {
        try {
            await fetch(this.url(name, id), {
                method,
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': window.LogViewer.csrfToken,
                },
            })
        } catch (e) {
            this.notify('Request failed')
        }
    },

    // ---- styling helpers ----
    chipClass(level) {
        const c = colorFor(level)
        return this.selectedLevels.includes(level) ? c.chip : c.chipOff
    },
    dotClass(level) {
        return colorFor(level).dot
    },
    badgeClass(level) {
        return colorFor(level).badge
    },
    capitalize(s) {
        return s.charAt(0).toUpperCase() + s.slice(1)
    },

    // ---- toast ----
    notify(message) {
        this.toast = message
        clearTimeout(this._toastTimer)
        this._toastTimer = setTimeout(() => (this.toast = ''), 2500)
    },
}))

window.Alpine = Alpine
Alpine.start()
