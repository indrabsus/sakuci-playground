/**
 * Sakuci PHP & MySQL Ground - Monaco Editor Controller (Responsive & Mobile Ready)
 */

window.CodeEditor = {
    editor: null,
    isMonacoLoaded: false,

    init: function (containerId, initialCode, onRunShortcut) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (window.require && window.monaco) {
            this.createMonacoInstance(container, initialCode, onRunShortcut);
            return;
        }

        const checkMonaco = setInterval(() => {
            if (window.monaco) {
                clearInterval(checkMonaco);
                this.createMonacoInstance(container, initialCode, onRunShortcut);
            }
        }, 100);

        setTimeout(() => {
            if (!this.isMonacoLoaded && !this.editor) {
                clearInterval(checkMonaco);
                this.createFallbackTextarea(container, initialCode, onRunShortcut);
            }
        }, 5000);

        // Auto layout when window resizes (orientation change or split resize)
        window.addEventListener('resize', () => {
            if (this.editor) {
                this.editor.layout();
            }
        });
    },

    createMonacoInstance: function (container, initialCode, onRunShortcut) {
        if (this.editor) return;

        const isMobile = window.innerWidth < 768;

        monaco.editor.defineTheme('sakuci-dark', {
            base: 'vs-dark',
            inherit: true,
            rules: [
                { token: 'comment', foreground: '6e7681', fontStyle: 'italic' },
                { token: 'keyword', foreground: 'ff7b72', fontStyle: 'bold' },
                { token: 'string', foreground: 'a5d6ff' },
                { token: 'number', foreground: '79c0ff' },
                { token: 'variable', foreground: 'ffa657' },
                { token: 'type', foreground: '7ee787' }
            ],
            colors: {
                'editor.background': '#0d1117',
                'editor.foreground': '#c9d1d9',
                'editorLineNumber.foreground': '#484f58',
                'editorLineNumber.activeForeground': '#58a6ff',
                'editor.selectionBackground': '#264f78',
                'editor.inactiveSelectionBackground': '#203c5d',
                'editorCursor.foreground': '#58a6ff'
            }
        });

        this.editor = monaco.editor.create(container, {
            value: initialCode || '',
            language: 'php',
            theme: document.body.dataset.theme === 'light' ? 'vs' : 'sakuci-dark',
            fontSize: isMobile ? 13 : 14,
            fontFamily: '"JetBrains Mono", "Cascadia Code", "Fira Code", Consolas, monospace',
            lineNumbers: isMobile ? 'on' : 'on',
            lineNumbersMinChars: isMobile ? 3 : 4,
            minimap: { enabled: !isMobile },
            automaticLayout: true,
            scrollBeyondLastLine: false,
            tabSize: 4,
            insertSpaces: true,
            bracketPairColorization: { enabled: true },
            wordWrap: 'on',
            suggestOnTriggerCharacters: true,
            renderWhitespace: 'selection',
            touchSupport: 'auto'
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
    },

    createFallbackTextarea: function (container, initialCode, onRunShortcut) {
        container.innerHTML = '';
        const textarea = document.createElement('textarea');
        textarea.id = 'fallback-editor';
        textarea.className = 'w-full h-full p-3 bg-[#0d1117] text-gray-200 terminal-font outline-none resize-none text-sm';
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
        return null; // Editor belum siap, jangan timpa isi file dengan string kosong
    },

    setCode: function (code) {
        const safeCode = (code !== null && code !== undefined) ? String(code) : '';
        if (this.editor) {
            this.editor.setValue(safeCode);
        } else {
            const fallback = document.getElementById('fallback-editor');
            if (fallback) fallback.value = safeCode;
        }
    },

    setTheme: function (theme) {
        if (!this.editor || !window.monaco) return;
        monaco.editor.setTheme(theme === 'light' ? 'vs' : 'sakuci-dark');
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
