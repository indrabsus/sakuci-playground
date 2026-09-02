/**
 * Sakuci PHP & MySQL Ground - Core Application Controller
 * Mendukung File & Folder Explorer Sidebar, Multi-File Bertingkat, dan Mobile Responsive.
 * Simulasi MySQL 8.0 Zero-Config (Host: localhost, User: root, Pass: '', Database: latihan).
 */

const DEFAULT_INDEX_CODE = "";

window.App = {
    // State Berkas & Folder
    files: {},
    folders: [],
    openTabs: ['index.php'],
    activeFile: 'index.php',
    expandedFolders: {},
    targetFolderForCreate: '', // Folder konteks saat membuat file/folder baru
    isSidebarOpen: true,

    // State Tampilan
    activeTab: 'console', // 'console' | 'html' | 'db'
    mobileView: 'editor', // 'files' | 'editor' | 'console' | 'html' | 'db'
    isRunning: false,
    theme: 'dark',

    init: function () {
        this.loadFilesFromStorage();
        this.setupSplitPane();
        this.setupTabs();
        this.setupMobileNav();
        this.setupTheme();
        this.setupDbModal();
        this.setupExplorerModals();
        this.setupSidebarToggle();

        // Inisialisasi Monaco Editor dengan kode kosong default
        CodeEditor.init('monaco-editor-container', this.files[this.activeFile] ?? DEFAULT_INDEX_CODE, () => {
            this.runCode();
        });

        // Inisialisasi Database Manager
        DbManager.init();

        // Render Sidebar Pohon Berkas & Bilah Tab
        this.renderExplorer();
        this.renderOpenTabs();

        // Run Code Button
        const runBtn = document.getElementById('btn-run-code');
        if (runBtn) {
            runBtn.addEventListener('click', () => this.runCode());
        }

        // Reset Code Button (Mengosongkan file)
        const resetBtn = document.getElementById('btn-reset-code');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (confirm(`Kosongkan isi file "${this.activeFile}"?`)) {
                    this.files[this.activeFile] = "";
                    CodeEditor.setCode("");
                    this.saveFilesToStorage();
                }
            });
        }

        // Format Code Button
        const formatBtn = document.getElementById('btn-format-code');
        if (formatBtn) {
            formatBtn.addEventListener('click', () => {
                CodeEditor.formatCode();
            });
        }

        // Clear Console Button
        const clearConsoleBtn = document.getElementById('btn-clear-console');
        if (clearConsoleBtn) {
            clearConsoleBtn.addEventListener('click', () => {
                const outEl = document.getElementById('console-output-content');
                if (outEl) outEl.innerHTML = '<div class="text-gray-500 italic p-4 text-xs font-mono">Konsol kosong. Jalankan kode untuk melihat output.</div>';
            });
        }

        // Default mobile view: langsung ke editor penuh
        if (window.innerWidth < 768) {
            this.isSidebarOpen = false;
            this.applySidebarVisibility();
            this.applyMobileView('editor');
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                document.body.classList.remove('mobile-view-files', 'mobile-view-editor', 'mobile-view-other');
                const leftPane = document.getElementById('left-pane');
                const rightPane = document.getElementById('right-pane');
                if (leftPane && rightPane) {
                    leftPane.style.display = '';
                    rightPane.style.display = '';
                }
            } else {
                this.applyMobileView(this.mobileView);
            }
            CodeEditor.layout();
        });
    },

    // ==========================================
    // PENYIMPANAN LOCALSTORAGE
    // ==========================================
    loadFilesFromStorage: function () {
        try {
            const savedFiles = localStorage.getItem('sakuci_php_files_v4');
            const savedFolders = localStorage.getItem('sakuci_php_folders_v4');
            const savedTabs = localStorage.getItem('sakuci_open_tabs_v4');
            const savedActive = localStorage.getItem('sakuci_active_file_v4');

            if (savedFiles) {
                const parsed = JSON.parse(savedFiles);
                if (parsed && typeof parsed === 'object') {
                    if (parsed['index.php'] === undefined) {
                        parsed['index.php'] = DEFAULT_INDEX_CODE;
                    }
                    this.files = parsed;

                    if (savedFolders) {
                        this.folders = JSON.parse(savedFolders) || [];
                    }

                    if (savedTabs) {
                        this.openTabs = JSON.parse(savedTabs) || ['index.php'];
                        if (!this.openTabs.includes('index.php')) {
                            this.openTabs.unshift('index.php');
                        }
                    } else {
                        this.openTabs = Object.keys(this.files);
                    }

                    this.activeFile = (savedActive && this.files[savedActive] !== undefined) ? savedActive : 'index.php';
                    return;
                }
            }
        } catch (e) {}

        // Inisialisasi awal: HANYA index.php dalam keadaan kosong!
        this.files = {
            'index.php': DEFAULT_INDEX_CODE
        };
        this.folders = [];
        this.openTabs = ['index.php'];
        this.activeFile = 'index.php';
    },

    saveFilesToStorage: function () {
        try {
            if (this.activeFile && this.files[this.activeFile] !== undefined) {
                const currentCode = CodeEditor.getCode();
                if (currentCode !== null) {
                    this.files[this.activeFile] = currentCode;
                }
            }
            localStorage.setItem('sakuci_php_files_v4', JSON.stringify(this.files));
            localStorage.setItem('sakuci_php_folders_v4', JSON.stringify(this.folders));
            localStorage.setItem('sakuci_open_tabs_v4', JSON.stringify(this.openTabs));
            localStorage.setItem('sakuci_active_file_v4', this.activeFile);
        } catch (e) {}
    },

    // ==========================================
    // SIDEBAR & EXPLORER TREE MANAGEMENT
    // ==========================================
    setupSidebarToggle: function () {
        const toggleBtn = document.getElementById('btn-toggle-sidebar');
        const closeSidebarBtn = document.getElementById('btn-close-sidebar');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                this.isSidebarOpen = !this.isSidebarOpen;
                this.applySidebarVisibility();
            });
        }

        if (closeSidebarBtn) {
            closeSidebarBtn.addEventListener('click', () => {
                this.isSidebarOpen = false;
                this.applySidebarVisibility();
            });
        }
    },

    applySidebarVisibility: function () {
        const sidebar = document.getElementById('file-explorer-sidebar');
        const toggleBtn = document.getElementById('btn-toggle-sidebar');
        if (!sidebar) return;

        if (this.isSidebarOpen) {
            sidebar.classList.remove('hidden');
            if (toggleBtn) toggleBtn.classList.add('bg-[#30363d]', 'text-sky-400');
        } else {
            sidebar.classList.add('hidden');
            if (toggleBtn) toggleBtn.classList.remove('bg-[#30363d]', 'text-sky-400');
        }
        CodeEditor.layout();
    },

    buildTree: function () {
        const root = { name: '', path: '', isFolder: true, children: {} };

        const allFolders = new Set([...this.folders]);
        Object.keys(this.files).forEach(f => {
            const parts = f.split('/');
            for (let i = 1; i < parts.length; i++) {
                allFolders.add(parts.slice(0, i).join('/'));
            }
        });

        allFolders.forEach(folderPath => {
            if (!folderPath) return;
            const parts = folderPath.split('/');
            let curr = root;
            let curPath = '';
            parts.forEach(part => {
                curPath = curPath ? `${curPath}/${part}` : part;
                if (!curr.children[part]) {
                    curr.children[part] = { name: part, path: curPath, isFolder: true, children: {} };
                }
                curr = curr.children[part];
            });
        });

        Object.keys(this.files).forEach(filePath => {
            const parts = filePath.split('/');
            const fileName = parts.pop();
            let curr = root;
            parts.forEach(part => {
                if (!curr.children[part]) {
                    curr.children[part] = { name: part, path: part, isFolder: true, children: {} };
                }
                curr = curr.children[part];
            });
            curr.children[fileName] = { name: fileName, path: filePath, isFolder: false };
        });

        return root;
    },

    renderExplorer: function () {
        const container = document.getElementById('explorer-tree-list');
        if (!container) return;

        const tree = this.buildTree();
        container.innerHTML = this.renderTreeChildren(tree.children, 0);
    },

    renderTreeChildren: function (children, level) {
        if (!children || Object.keys(children).length === 0) {
            return '';
        }

        const keys = Object.keys(children).sort((a, b) => {
            const itemA = children[a];
            const itemB = children[b];
            if (itemA.isFolder && !itemB.isFolder) return -1;
            if (!itemA.isFolder && itemB.isFolder) return 1;
            return a.localeCompare(b);
        });

        return keys.map(key => {
            const item = children[key];
            const indent = level * 14;

            if (item.isFolder) {
                const isExpanded = this.expandedFolders[item.path] !== false;
                return `
                    <div>
                        <div class="tree-item flex items-center justify-between py-1 px-1.5 rounded text-xs text-gray-300 group cursor-pointer"
                            style="padding-left: ${indent + 6}px;"
                            onclick="App.toggleFolder('${item.path}')">
                            <div class="flex items-center gap-1.5 truncate">
                                <span class="text-[10px] text-gray-500 font-mono w-3 text-center">
                                    ${isExpanded ? '▼' : '▶'}
                                </span>
                                <span class="text-xs">${isExpanded ? '📂' : '📁'}</span>
                                <span class="font-mono text-gray-200 truncate">${item.name}</span>
                            </div>
                            <div class="tree-actions flex items-center gap-1 shrink-0" onclick="event.stopPropagation()">
                                <button onclick="App.openCreateFileModal('${item.path}')" title="Buat file di folder ${item.name}" 
                                    class="p-0.5 text-gray-400 hover:text-sky-400 rounded transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                                <button onclick="App.openCreateFolderModal('${item.path}')" title="Buat subfolder di ${item.name}" 
                                    class="p-0.5 text-gray-400 hover:text-amber-400 rounded transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                                </button>
                                <button onclick="App.deleteFolder('${item.path}')" title="Hapus folder ${item.name}" 
                                    class="p-0.5 text-gray-400 hover:text-red-400 rounded transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                        ${isExpanded ? `<div>${this.renderTreeChildren(item.children, level + 1)}</div>` : ''}
                    </div>
                `;
            } else {
                const isActive = item.path === this.activeFile;
                const isIndex = item.path === 'index.php';
                return `
                    <div class="tree-item flex items-center justify-between py-1 px-1.5 rounded text-xs group cursor-pointer ${
                        isActive ? 'active font-semibold' : 'text-gray-400 hover:text-gray-200'
                    }"
                        style="padding-left: ${indent + 18}px;"
                        onclick="App.openFile('${item.path}')">
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="text-xs">${isIndex ? '⭐' : '📄'}</span>
                            <span class="font-mono truncate ${isActive ? 'text-sky-300' : ''}">${item.name}</span>
                        </div>
                        <div class="tree-actions flex items-center shrink-0" onclick="event.stopPropagation()">
                            ${!isIndex ? `
                                <button onclick="App.deleteFile('${item.path}')" title="Hapus file ${item.name}" 
                                    class="p-0.5 text-gray-500 hover:text-red-400 rounded transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            }
        }).join('');
    },

    toggleFolder: function (path) {
        this.expandedFolders[path] = this.expandedFolders[path] === false ? true : false;
        this.renderExplorer();
    },

    // ==========================================
    // TOP OPEN TABS BAR
    // ==========================================
    renderOpenTabs: function () {
        const container = document.getElementById('open-tabs-list');
        if (!container) return;

        container.innerHTML = this.openTabs.map(path => {
            const isActive = path === this.activeFile;
            const isIndex = path === 'index.php';
            const displayName = path.split('/').pop();

            return `
                <div class="flex items-center rounded-t border-t border-x text-xs font-mono font-medium transition shrink-0 ${
                    isActive 
                        ? 'bg-[#0d1117] text-sky-400 border-[#30363d]' 
                        : 'bg-[#161b22] text-gray-400 border-transparent hover:text-gray-200 hover:bg-[#21262d]'
                }">
                    <button type="button" onclick="App.openFile('${path}')" 
                        class="px-2.5 py-1.5 flex items-center gap-1.5 focus:outline-none select-none">
                        <span class="text-xs">${isIndex ? '⭐' : '📄'}</span>
                        <span title="${path}">${displayName}</span>
                    </button>
                    ${!isIndex ? `
                        <button type="button" onclick="App.closeTab(event, '${path}')" title="Tutup tab" 
                            class="p-1 text-gray-500 hover:text-red-400 hover:bg-[#30363d] rounded transition mr-1 flex items-center justify-center focus:outline-none">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    ` : '<div class="w-1.5"></div>'}
                </div>
            `;
        }).join('');
    },

    openFile: function (path) {
        if (this.files[path] === undefined) return;

        if (this.activeFile && this.files[this.activeFile] !== undefined) {
            const currentCode = CodeEditor.getCode();
            if (currentCode !== null) {
                this.files[this.activeFile] = currentCode;
            }
        }

        if (!this.openTabs.includes(path)) {
            this.openTabs.push(path);
        }

        this.activeFile = path;
        CodeEditor.setCode(this.files[path] ?? '');
        this.renderExplorer();
        this.renderOpenTabs();
        this.saveFilesToStorage();

        // Di layar ponsel, otomatis langsung beralih ke editor layar penuh
        if (window.innerWidth < 768) {
            this.setMobileView('editor');
        }
    },

    closeTab: function (e, path) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        if (path === 'index.php') return;

        this.openTabs = this.openTabs.filter(p => p !== path);

        if (this.activeFile === path) {
            const nextTab = this.openTabs[this.openTabs.length - 1] || 'index.php';
            this.openFile(nextTab);
        } else {
            this.renderOpenTabs();
            this.saveFilesToStorage();
        }
    },

    // ==========================================
    // CREATION & DELETION MODALS
    // ==========================================
    setupExplorerModals: function () {
        const btnHeaderAddFile = document.getElementById('btn-explorer-add-file');
        const modalFile = document.getElementById('modal-create-file');
        const inputFileName = document.getElementById('input-create-file-name');
        const btnConfirmFile = document.getElementById('btn-confirm-create-file');
        const btnCancelFile = document.getElementById('btn-cancel-create-file');

        if (btnHeaderAddFile) {
            btnHeaderAddFile.addEventListener('click', () => this.openCreateFileModal(''));
        }

        if (btnConfirmFile) {
            btnConfirmFile.addEventListener('click', () => this.submitCreateFile());
        }

        if (btnCancelFile) {
            btnCancelFile.addEventListener('click', () => modalFile.classList.add('hidden'));
        }

        if (inputFileName) {
            inputFileName.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.submitCreateFile();
                } else if (e.key === 'Escape') {
                    modalFile.classList.add('hidden');
                }
            });
        }

        const btnHeaderAddFolder = document.getElementById('btn-explorer-add-folder');
        const modalFolder = document.getElementById('modal-create-folder');
        const inputFolderName = document.getElementById('input-create-folder-name');
        const btnConfirmFolder = document.getElementById('btn-confirm-create-folder');
        const btnCancelFolder = document.getElementById('btn-cancel-create-folder');

        if (btnHeaderAddFolder) {
            btnHeaderAddFolder.addEventListener('click', () => this.openCreateFolderModal(''));
        }

        if (btnConfirmFolder) {
            btnConfirmFolder.addEventListener('click', () => this.submitCreateFolder());
        }

        if (btnCancelFolder) {
            btnCancelFolder.addEventListener('click', () => modalFolder.classList.add('hidden'));
        }

        if (inputFolderName) {
            inputFolderName.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.submitCreateFolder();
                } else if (e.key === 'Escape') {
                    modalFolder.classList.add('hidden');
                }
            });
        }
    },

    openCreateFileModal: function (parentFolder = '') {
        this.targetFolderForCreate = parentFolder;
        const modal = document.getElementById('modal-create-file');
        const input = document.getElementById('input-create-file-name');
        const targetLabel = document.getElementById('label-target-folder-file');

        if (targetLabel) {
            targetLabel.textContent = parentFolder ? `Lokasi: /${parentFolder}/` : 'Lokasi: / (root)';
        }

        if (modal && input) {
            modal.classList.remove('hidden');
            input.value = '';
            input.focus();
        }
    },

    submitCreateFile: function () {
        const input = document.getElementById('input-create-file-name');
        const modal = document.getElementById('modal-create-file');
        if (!input) return;

        let name = input.value.trim();
        if (!name) return;

        name = name.replace(/[^a-zA-Z0-9_\-\.\/]/g, '');
        if (!strEndsWith(name.toLowerCase(), '.php')) {
            name += '.php';
        }

        const fullPath = this.targetFolderForCreate 
            ? `${this.targetFolderForCreate}/${name}` 
            : name;

        if (this.files[fullPath] !== undefined) {
            alert(`File "${fullPath}" sudah ada!`);
            this.openFile(fullPath);
            modal.classList.add('hidden');
            return;
        }

        this.files[fullPath] = "";
        modal.classList.add('hidden');
        this.openFile(fullPath);
    },

    openCreateFolderModal: function (parentFolder = '') {
        this.targetFolderForCreate = parentFolder;
        const modal = document.getElementById('modal-create-folder');
        const input = document.getElementById('input-create-folder-name');
        const targetLabel = document.getElementById('label-target-folder-dir');

        if (targetLabel) {
            targetLabel.textContent = parentFolder ? `Lokasi: /${parentFolder}/` : 'Lokasi: / (root)';
        }

        if (modal && input) {
            modal.classList.remove('hidden');
            input.value = '';
            input.focus();
        }
    },

    submitCreateFolder: function () {
        const input = document.getElementById('input-create-folder-name');
        const modal = document.getElementById('modal-create-folder');
        if (!input) return;

        let name = input.value.trim().replace(/[^a-zA-Z0-9_\-]/g, '');
        if (!name) return;

        const fullPath = this.targetFolderForCreate 
            ? `${this.targetFolderForCreate}/${name}` 
            : name;

        if (!this.folders.includes(fullPath)) {
            this.folders.push(fullPath);
            this.expandedFolders[fullPath] = true;
        }

        modal.classList.add('hidden');
        this.renderExplorer();
        this.saveFilesToStorage();
    },

    deleteFile: function (path) {
        if (path === 'index.php') {
            alert('File index.php adalah file utama dan tidak dapat dihapus.');
            return;
        }

        if (!confirm(`Hapus berkas "${path}"?`)) {
            return;
        }

        delete this.files[path];
        this.openTabs = this.openTabs.filter(p => p !== path);

        if (this.activeFile === path) {
            const nextTab = this.openTabs[this.openTabs.length - 1] || 'index.php';
            this.openFile(nextTab);
        } else {
            this.renderExplorer();
            this.renderOpenTabs();
            this.saveFilesToStorage();
        }
    },

    deleteFolder: function (folderPath) {
        if (!confirm(`Apakah Anda yakin ingin menghapus folder "${folderPath}" beserta seluruh file di dalamnya?`)) {
            return;
        }

        this.folders = this.folders.filter(f => f !== folderPath && !f.startsWith(folderPath + '/'));

        let wasActiveDeleted = false;
        Object.keys(this.files).forEach(f => {
            if (f.startsWith(folderPath + '/')) {
                delete this.files[f];
                this.openTabs = this.openTabs.filter(p => p !== f);
                if (this.activeFile === f) wasActiveDeleted = true;
            }
        });

        if (wasActiveDeleted) {
            const nextTab = this.openTabs[this.openTabs.length - 1] || 'index.php';
            this.openFile(nextTab);
        } else {
            this.renderExplorer();
            this.renderOpenTabs();
            this.saveFilesToStorage();
        }
    },

    // ==========================================
    // RESPONSIVE & MOBILE NAVIGATION
    // ==========================================
    setupMobileNav: function () {
        const views = ['files', 'editor', 'console', 'html', 'db'];
        views.forEach(view => {
            const btn = document.getElementById(`mobile-nav-${view}`);
            if (btn) {
                btn.addEventListener('click', () => {
                    this.setMobileView(view);
                });
            }
        });
    },

    setMobileView: function (view) {
        this.mobileView = view;
        this.applyMobileView(view);

        if (view === 'editor') {
            CodeEditor.layout();
        } else if (view !== 'files') {
            this.switchTab(view);
        }
    },

    applyMobileView: function (view) {
        if (window.innerWidth >= 768) return;

        document.body.classList.remove('mobile-view-files', 'mobile-view-editor', 'mobile-view-other');

        if (view === 'files') {
            document.body.classList.add('mobile-view-files');
        } else if (view === 'editor') {
            document.body.classList.add('mobile-view-editor');
        } else {
            document.body.classList.add('mobile-view-other');
        }

        const views = ['files', 'editor', 'console', 'html', 'db'];
        views.forEach(v => {
            const btn = document.getElementById(`mobile-nav-${v}`);
            if (btn) {
                if (v === view) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            }
        });
    },

    // ==========================================
    // RIGHT PANEL DESKTOP TABS
    // ==========================================
    setupTabs: function () {
        const tabs = ['console', 'html', 'db'];
        tabs.forEach(tab => {
            const btn = document.getElementById(`tab-btn-${tab}`);
            if (btn) {
                btn.addEventListener('click', () => this.switchTab(tab));
            }
        });
    },

    switchTab: function (tabName) {
        this.activeTab = tabName;
        const tabs = ['console', 'html', 'db'];

        tabs.forEach(t => {
            const btn = document.getElementById(`tab-btn-${t}`);
            const pane = document.getElementById(`tab-pane-${t}`);
            if (btn && pane) {
                if (t === tabName) {
                    btn.classList.add('active');
                    pane.classList.remove('hidden');
                } else {
                    btn.classList.remove('active');
                    pane.classList.add('hidden');
                }
            }
        });

        if (tabName === 'db') {
            DbManager.loadTables();
        }
    },

    // ==========================================
    // EXECUTE MULTI-FILE CODE
    // ==========================================
    runCode: async function () {
        if (this.isRunning) return;

        if (this.activeFile && this.files[this.activeFile] !== undefined) {
            const currentCode = CodeEditor.getCode();
            if (currentCode !== null) {
                this.files[this.activeFile] = currentCode;
            }
        }
        this.saveFilesToStorage();

        const runBtn = document.getElementById('btn-run-code');
        const consoleEl = document.getElementById('console-output-content');
        const executionBadge = document.getElementById('metric-exec-time');
        const statusBadge = document.getElementById('metric-status');

        this.isRunning = true;
        if (runBtn) {
            runBtn.classList.add('opacity-75', 'cursor-not-allowed', 'running-pulse');
            runBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white inline-block" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Menjalankan...</span>
            `;
        }

        if (window.innerWidth < 768) {
            this.setMobileView('console');
        } else if (this.activeTab !== 'html') {
            this.switchTab('console');
        }

        if (consoleEl) {
            consoleEl.innerHTML = '<div class="text-xs text-sky-400 p-4 font-mono animate-pulse">⚡ Menjalankan skrip PHP & MySQL...</div>';
        }

        try {
            const res = await fetch('/api/run', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    files: this.files,
                    entrypoint: 'index.php'
                })
            });

            const result = await res.json();

            if (executionBadge) {
                executionBadge.textContent = `${result.execution_time_ms} ms`;
            }
            if (statusBadge) {
                if (result.success) {
                    statusBadge.textContent = 'Sukses (Exit 0)';
                    statusBadge.className = 'px-2 py-0.5 rounded text-[10px] md:text-[11px] font-mono bg-emerald-950/80 text-emerald-300 border border-emerald-800';
                } else {
                    statusBadge.textContent = result.timed_out ? 'Timeout (5s)' : `Error (Exit ${result.exit_code})`;
                    statusBadge.className = 'px-2 py-0.5 rounded text-[10px] md:text-[11px] font-mono bg-red-950/80 text-red-300 border border-red-800';
                }
            }

            this.renderConsoleOutput(result);
            this.updateHtmlPreview(result.stdout);

        } catch (err) {
            if (consoleEl) {
                consoleEl.innerHTML = `
                    <div class="p-4 bg-red-950/50 border border-red-800 text-red-300 text-xs font-mono rounded">
                        <strong>Network Error:</strong> ${err.message}
                    </div>`;
            }
        } finally {
            this.isRunning = false;
            if (runBtn) {
                runBtn.classList.remove('opacity-75', 'cursor-not-allowed', 'running-pulse');
                runBtn.innerHTML = `
                    <svg class="w-3.5 h-3.5 md:w-4 md:h-4 mr-1 inline-block" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Jalankan</span>
                    <span class="hidden md:inline-block text-[10px] px-1 py-0.2 rounded bg-sky-700/80 font-mono ml-1">Ctrl+Enter</span>
                `;
            }
        }
    },

    renderConsoleOutput: function (result) {
        const consoleEl = document.getElementById('console-output-content');
        if (!consoleEl) return;

        let outputHtml = '';

        if (result.stderr) {
            outputHtml += `
                <div class="mb-3 p-3 bg-red-950/40 border border-red-800/80 rounded text-red-300 text-xs whitespace-pre-wrap font-mono">
                    <div class="flex items-center gap-1.5 font-bold mb-1 text-red-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Pesan Kesalahan / Warning:
                    </div>
                    ${this.escapeHtml(result.stderr)}
                </div>
            `;
        }

        if (result.stdout) {
            outputHtml += `
                <div class="text-xs text-gray-200 font-mono whitespace-pre-wrap leading-relaxed select-text">
                    ${this.escapeHtml(result.stdout)}
                </div>
            `;
        }

        if (!result.stdout && !result.stderr) {
            outputHtml = '<div class="text-gray-500 italic p-4 text-xs font-mono">Program selesai tanpa menghasilkan output teks.</div>';
        }

        consoleEl.innerHTML = outputHtml;
    },

    updateHtmlPreview: function (rawHtml) {
        const iframe = document.getElementById('html-preview-frame');
        if (!iframe) return;

        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        padding: 16px;
                        color: #1e293b;
                        background: #ffffff;
                    }
                </style>
            </head>
            <body>
                ${rawHtml || '<p style="color: #94a3b8; font-style: italic;">Tidak ada konten HTML untuk ditampilkan.</p>'}
            </body>
            </html>
        `);
        doc.close();
    },

    setupSplitPane: function () {
        const resizer = document.getElementById('split-resizer');
        const leftCol = document.getElementById('left-pane');
        const rightCol = document.getElementById('right-pane');
        const container = document.getElementById('workspace-container');

        if (!resizer || !leftCol || !rightCol || !container) return;

        let isDragging = false;

        resizer.addEventListener('mousedown', () => {
            if (window.innerWidth < 768) return;
            isDragging = true;
            resizer.classList.add('is-dragging');
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging || window.innerWidth < 768) return;
            const containerRect = container.getBoundingClientRect();
            const offset = e.clientX - containerRect.left;
            const containerWidth = containerRect.width;

            const minWidth = 300;
            const maxWidth = containerWidth - 300;

            if (offset >= minWidth && offset <= maxWidth) {
                const percentage = (offset / containerWidth) * 100;
                leftCol.style.width = `${percentage}%`;
                rightCol.style.width = `${100 - percentage}%`;
            }
        });

        document.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                resizer.classList.remove('is-dragging');
                document.body.style.cursor = 'default';
                document.body.style.userSelect = 'auto';
                CodeEditor.layout();
            }
        });
    },

    setupTheme: function () {
        const themeBtn = document.getElementById('btn-toggle-theme');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                this.theme = this.theme === 'dark' ? 'light' : 'dark';
                document.body.dataset.theme = this.theme;
                CodeEditor.setTheme(this.theme);
            });
        }
    },

    setupDbModal: function () {
        const openModalBtn = document.getElementById('btn-open-db-settings');
        const closeModalBtn = document.getElementById('btn-close-db-modal');
        const modal = document.getElementById('db-settings-modal');
        const testBtn = document.getElementById('btn-test-db-modal');

        if (!modal) return;

        const openModal = () => {
            modal.classList.remove('hidden');
        };

        if (openModalBtn) openModalBtn.addEventListener('click', openModal);
        if (closeModalBtn) closeModalBtn.addEventListener('click', () => modal.classList.add('hidden'));

        if (testBtn) {
            testBtn.addEventListener('click', async () => {
                const feedbackEl = document.getElementById('db-modal-feedback');
                feedbackEl.innerHTML = '<span class="text-gray-400">Menguji koneksi simulasi...</span>';

                try {
                    const res = await fetch('/api/db/test', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({})
                    });
                    const data = await res.json();
                    if (data.success) {
                        feedbackEl.innerHTML = `<span class="text-emerald-400 font-semibold">✓ ${data.message} (Engine: ${data.version || ''})</span>`;
                    } else {
                        feedbackEl.innerHTML = `<span class="text-red-400 font-semibold">✗ ${data.message}</span>`;
                    }
                } catch (err) {
                    feedbackEl.innerHTML = `<span class="text-red-400 font-semibold">✗ Error: ${err.message}</span>`;
                }
            });
        }
    },

    escapeHtml: function (text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
};

function strEndsWith(str, suffix) {
    return str.indexOf(suffix, str.length - suffix.length) !== -1;
}

// Start application when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.App.init();
});
