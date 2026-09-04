/**
 * Sakuci PHP & MySQL Ground - Monaco Editor Controller (VS Code Dark+ Theme & Language Detection)
 */

window.CodeEditor = {
    editor: null,
    isMonacoLoaded: false,
    isSettingCode: false,

    detectLanguage: function (filename) {
        if (!filename) return 'php';
        const lower = filename.toLowerCase();
        if (lower.endsWith('.blade.php') || lower.endsWith('.sakuci.php') || lower.endsWith('.php')) return 'php';
        if (lower.endsWith('.html') || lower.endsWith('.htm')) return 'html';
        if (lower.endsWith('.css')) return 'css';
        if (lower.endsWith('.js') || lower.endsWith('.mjs')) return 'javascript';
        if (lower.endsWith('.json')) return 'json';
        if (lower.endsWith('.sql')) return 'sql';
        if (lower.endsWith('.md')) return 'markdown';
        return 'php';
    },

    init: function (containerId, initialCode, onRunShortcut, activeFile = 'index.php') {
        const container = document.getElementById(containerId);
        if (!container) return;

        const loadEditor = () => {
            if (this.editor) return;
            this.createMonacoInstance(container, initialCode, onRunShortcut, activeFile);
        };

        if (window.monaco) {
            loadEditor();
            return;
        }

        const tryRequire = () => {
            if (window.require && typeof window.require === 'function') {
                try {
                    window.require(['vs/editor/editor.main'], () => {
                        loadEditor();
                    }, (err) => {
                        console.warn('Gagal memuat Monaco via CDN, beralih ke editor fallback:', err);
                        this.createFallbackTextarea(container, initialCode, onRunShortcut);
                    });
                    return true;
                } catch (e) {
                    return false;
                }
            }
            return false;
        };

        if (tryRequire()) return;

        let attempts = 0;
        const checkInterval = setInterval(() => {
            attempts++;
            if (window.monaco) {
                clearInterval(checkInterval);
                loadEditor();
            } else if (tryRequire()) {
                clearInterval(checkInterval);
            } else if (attempts > 30) {
                clearInterval(checkInterval);
                if (!this.editor && !this.isMonacoLoaded) {
                    this.createFallbackTextarea(container, initialCode, onRunShortcut);
                }
            }
        }, 100);

        // Auto layout when window resizes
        window.addEventListener('resize', () => {
            if (this.editor) {
                this.editor.layout();
            }
        });
    },

    createMonacoInstance: function (container, initialCode, onRunShortcut, activeFile = 'index.php') {
        if (this.editor) return;

        const isMobile = window.innerWidth < 768;
        const language = this.detectLanguage(activeFile);

        // Tema resmi VS Code Dark+ dengan Pewarnaan Sintaks Tajam & Berwarna
        monaco.editor.defineTheme('vscode-dark-plus', {
            base: 'vs-dark',
            inherit: true,
            rules: [
                { token: '', foreground: 'd4d4d4' },
                { token: 'comment', foreground: '6a9955', fontStyle: 'italic' },
                { token: 'keyword', foreground: '569cd6', fontStyle: 'bold' },
                { token: 'keyword.control', foreground: 'c586c0', fontStyle: 'bold' },
                { token: 'keyword.php', foreground: '569cd6' },
                { token: 'string', foreground: 'ce9178' },
                { token: 'string.escape', foreground: 'd7ba7d' },
                { token: 'number', foreground: 'b5cea8' },
                { token: 'number.hex', foreground: 'b5cea8' },
                { token: 'regexp', foreground: 'd16969' },
                { token: 'type', foreground: '4ec9b0', fontStyle: 'bold' },
                { token: 'type.identifier', foreground: '4ec9b0' },
                { token: 'class', foreground: '4ec9b0' },
                { token: 'function', foreground: 'dcdcaa' },
                { token: 'function.call', foreground: 'dcdcaa' },
                { token: 'variable', foreground: '9cdcfe' },
                { token: 'variable.name', foreground: '9cdcfe' },
                { token: 'variable.parameter', foreground: '9cdcfe' },
                { token: 'variable.predefined', foreground: '4fc1ff' },
                { token: 'constant', foreground: '4fc1ff' },
                { token: 'delimiter', foreground: 'd4d4d4' },
                { token: 'delimiter.bracket', foreground: 'ffd700' },
                { token: 'tag', foreground: '569cd6' },
                { token: 'tag.attribute.name', foreground: '9cdcfe' },
                { token: 'tag.attribute.value', foreground: 'ce9178' },
                { token: 'metatag', foreground: '569cd6' },
                { token: 'metatag.content', foreground: 'ce9178' },
                { token: 'attribute.name', foreground: '9cdcfe' },
                { token: 'attribute.value', foreground: 'ce9178' }
            ],
            colors: {
                'editor.background': '#1e1e1e',
                'editor.foreground': '#d4d4d4',
                'editorLineNumber.foreground': '#858585',
                'editorLineNumber.activeForeground': '#c6c6c6',
                'editor.selectionBackground': '#264f78',
                'editor.inactiveSelectionBackground': '#3a3d41',
                'editorCursor.foreground': '#aeafad',
                'editorWhitespace.foreground': '#3e3e3d',
                'editorIndentGuide.background': '#404040',
                'editorIndentGuide.activeBackground': '#707070',
                'editorBracketMatch.background': '#0064001a',
                'editorBracketMatch.border': '#888888',
                'editorGutter.background': '#1e1e1e'
            }
        });

        this.editor = monaco.editor.create(container, {
            value: initialCode || '',
            language: language,
            theme: document.body.dataset.theme === 'light' ? 'vs' : 'vscode-dark-plus',
            fontSize: isMobile ? 13 : 14,
            fontFamily: '"JetBrains Mono", "Cascadia Code", "Fira Code", Consolas, "Courier New", monospace',
            fontLigatures: true,
            lineNumbers: 'on',
            lineNumbersMinChars: isMobile ? 3 : 4,
            minimap: { enabled: !isMobile },
            automaticLayout: true,
            scrollBeyondLastLine: false,
            tabSize: 4,
            insertSpaces: true,
            bracketPairColorization: { enabled: true },
            guides: {
                bracketPairs: true,
                indentation: true
            },
            wordWrap: 'on',
            suggestOnTriggerCharacters: true,
            renderWhitespace: 'selection',
            touchSupport: 'auto',
            folding: true,
            cursorBlinking: 'smooth',
            smoothScrolling: true
        });

        this.isMonacoLoaded = true;

        this.editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.Enter, function () {
            if (typeof onRunShortcut === 'function') {
                onRunShortcut();
            }
        });

        this.editor.onDidChangeCursorPosition((e) => {
            const posEl = document.getElementById('editor-cursor-pos');
            if (posEl) {
                posEl.textContent = `Ln ${e.position.lineNumber}, Col ${e.position.column}`;
            }
        });

        // Deteksi perubahan kode untuk auto-save ke cloud
        this.editor.onDidChangeModelContent(() => {
            if (this.isSettingCode) return;
            if (window.App && typeof window.App.onCodeChange === 'function') {
                window.App.onCodeChange();
            }
        });
    },

    createFallbackTextarea: function (container, initialCode, onRunShortcut) {
        container.innerHTML = '';
        const textarea = document.createElement('textarea');
        textarea.id = 'fallback-editor';
        textarea.className = 'w-full h-full p-3 bg-[#1e1e1e] text-[#d4d4d4] font-mono outline-none resize-none text-sm leading-relaxed';
        textarea.value = initialCode || '';
        textarea.spellcheck = false;

        textarea.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                if (typeof onRunShortcut === 'function') {
                    onRunShortcut();
                }
            }
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                textarea.value = textarea.value.substring(0, start) + "    " + textarea.value.substring(end);
                textarea.selectionStart = textarea.selectionEnd = start + 4;
            }
        });

        textarea.addEventListener('input', () => {
            if (window.App && typeof window.App.onCodeChange === 'function') {
                window.App.onCodeChange();
            }
        });

        container.appendChild(textarea);
    },

    getCode: function () {
        if (this.editor) {
            return this.editor.getValue();
        }
        const fallback = document.getElementById('fallback-editor');
        if (fallback) {
            return fallback.value;
        }
        return null;
    },

    setCode: function (code, filename) {
        const safeCode = (code !== null && code !== undefined) ? String(code) : '';
        this.isSettingCode = true;
        try {
            if (this.editor) {
                this.editor.setValue(safeCode);
                if (filename && window.monaco) {
                    const lang = this.detectLanguage(filename);
                    const model = this.editor.getModel();
                    if (model) {
                        monaco.editor.setModelLanguage(model, lang);
                    }
                }
            } else {
                const fallback = document.getElementById('fallback-editor');
                if (fallback) fallback.value = safeCode;
            }
        } finally {
            setTimeout(() => {
                this.isSettingCode = false;
            }, 30);
        }
    },

    setTheme: function (theme) {
        if (!this.editor || !window.monaco) return;
        monaco.editor.setTheme(theme === 'light' ? 'vs' : 'vscode-dark-plus');
    },

    formatCode: function () {
        if (this.editor) {
            this.editor.getAction('editor.action.formatDocument')?.run();
        }
    },

    layout: function () {
        if (this.editor) {
            setTimeout(() => this.editor.layout(), 50);
        }
    }
};
