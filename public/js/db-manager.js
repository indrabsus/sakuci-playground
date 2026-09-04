/**
 * Sakuci PHP & MySQL Ground - Database Manager Controller
 */

window.DbManager = {
    currentTable: null,
    currentPage: 1,
    pageSize: 20,

    init: function () {
        this.loadTables();
        this.bindEvents();
    },

    bindEvents: function () {
        // Query Run Button
        const runQueryBtn = document.getElementById('btn-run-sql');
        if (runQueryBtn) {
            runQueryBtn.addEventListener('click', () => this.executeSql());
        }

        // Seed Sample Data Button
        const seedBtn = document.getElementById('btn-seed-db');
        if (seedBtn) {
            seedBtn.addEventListener('click', () => this.seedData());
        }

        // SQL Input shortcuts (Ctrl+Enter to run SQL)
        const sqlInput = document.getElementById('sql-query-input');
        if (sqlInput) {
            sqlInput.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    this.executeSql();
                }
            });
        }
    },

    loadTables: async function () {
        const tableListEl = document.getElementById('db-tables-list');
        if (!tableListEl) return;

        tableListEl.innerHTML = '<div class="text-xs text-gray-400 p-2">Memuat daftar tabel...</div>';

        try {
            const res = await fetch('/api/db/tables');
            const data = await res.json();

            if (!data.success) {
                tableListEl.innerHTML = `<div class="text-xs text-red-400 p-2">Gagal: ${data.error}</div>`;
                return;
            }

            // Update badge driver
            const badgeDriver = document.getElementById('db-active-driver-badge');
            if (badgeDriver) {
                const driverName = data.driver === 'mysql' ? 'MySQL 8.0' : 'SQLite';
                badgeDriver.textContent = driverName;
                badgeDriver.className = data.driver === 'mysql' 
                    ? 'px-2 py-0.5 text-xs rounded font-medium bg-blue-900/60 text-blue-300 border border-blue-700'
                    : 'px-2 py-0.5 text-xs rounded font-medium bg-emerald-900/60 text-emerald-300 border border-emerald-700';
            }

            if (data.tables.length === 0) {
                tableListEl.innerHTML = `
                    <div class="text-xs text-gray-400 p-3 text-center">
                        <p class="mb-2">Belum ada tabel di database.</p>
                        <button onclick="DbManager.seedData()" class="px-2.5 py-1 bg-sky-600 hover:bg-sky-500 text-white rounded text-xs transition">
                            🌱 Seed Data Contoh
                        </button>
                    </div>`;
                return;
            }

            tableListEl.innerHTML = data.tables.map(t => `
                <button onclick="DbManager.viewTable('${t.name}')" 
                    class="w-full flex items-center justify-between px-3 py-2 rounded text-left text-xs hover:bg-[#21262d] transition group ${this.currentTable === t.name ? 'bg-sky-950/50 text-sky-300 border border-sky-800' : 'text-gray-300'}">
                    <span class="flex items-center gap-1.5 font-mono">
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7M4 7c0-2 1.5-3 3.5-3h9c2 0 3.5 1 3.5 3M4 7h16m-8 4v8m-4-4h8"></path></svg>
                        ${t.name}
                    </span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-800 text-gray-400 font-mono">${t.rows} baris</span>
                </button>
            `).join('');

            // Auto-select first table if none selected
            if (!this.currentTable && data.tables.length > 0) {
                this.viewTable(data.tables[0].name);
            }
        } catch (err) {
            tableListEl.innerHTML = `<div class="text-xs text-red-400 p-2">Error koneksi: ${err.message}</div>`;
        }
    },

    viewTable: async function (tableName, page = 1) {
        this.currentTable = tableName;
        this.currentPage = page;
        this.loadTables(); // Update active highlight

        const dataContainer = document.getElementById('db-table-content');
        if (!dataContainer) return;

        dataContainer.innerHTML = '<div class="text-xs text-gray-400 p-4">Memuat data tabel...</div>';

        try {
            const [dataRes, schemaRes] = await Promise.all([
                fetch(`/api/db/table-data?table=${encodeURIComponent(tableName)}&page=${page}&limit=${this.pageSize}`),
                fetch(`/api/db/schema?table=${encodeURIComponent(tableName)}`)
            ]);

            const data = await dataRes.json();
            const schema = await schemaRes.json();

            if (!data.success) {
                dataContainer.innerHTML = `<div class="text-xs text-red-400 p-4">Error: ${data.error}</div>`;
                return;
            }

            const columns = schema.success ? schema.columns.map(c => c.name) : (data.rows[0] ? Object.keys(data.rows[0]) : []);

            let html = `
                <div class="flex items-center justify-between p-3 border-b border-[#30363d] bg-[#161b22]">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-white font-mono flex items-center gap-1.5">
                            📊 Tabel: <span class="text-sky-400">${tableName}</span>
                        </span>
                        <span class="text-xs text-gray-400 font-mono">(${data.total ?? data.total_rows ?? 0} total data)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="DbManager.copySelectQuery('${tableName}')" class="text-xs text-sky-400 hover:text-sky-300 px-2 py-1 bg-sky-950/40 rounded border border-sky-800 transition">
                            Salin SQL SELECT
                        </button>
                    </div>
                </div>
            `;

            if (data.rows.length === 0) {
                html += '<div class="p-8 text-center text-sm text-gray-400">Tabel ini belum memiliki data baris.</div>';
            } else {
                html += `
                    <div class="overflow-auto max-h-[360px]">
                        <table class="db-table-grid text-xs text-left">
                            <thead>
                                <tr>
                                    ${columns.map(col => `
                                        <th class="px-3 py-2 text-gray-300 font-mono font-medium whitespace-nowrap bg-[#1c2128]">
                                            ${col}
                                        </th>
                                    `).join('')}
                                </tr>
                            </thead>
                            <tbody>
                                ${data.rows.map(row => `
                                    <tr class="border-b border-[#21262d] hover:bg-[#21262d]/40 transition">
                                        ${columns.map(col => `
                                            <td class="px-3 py-2 font-mono text-gray-200 whitespace-nowrap">
                                                ${row[col] !== null ? this.escapeHtml(String(row[col])) : '<span class="text-gray-500 italic">NULL</span>'}
                                            </td>
                                        `).join('')}
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;

                // Pagination
                const totalCount = (data.total !== undefined) ? data.total : ((data.total_rows !== undefined) ? data.total_rows : (data.rows ? data.rows.length : 0));
                const totalPages = Math.ceil(totalCount / this.pageSize);
                if (totalPages > 1) {
                    html += `
                        <div class="flex items-center justify-between p-2.5 border-t border-[#30363d] bg-[#161b22] text-xs">
                            <span class="text-gray-400">Halaman ${page} dari ${totalPages}</span>
                            <div class="flex items-center gap-1">
                                <button onclick="DbManager.viewTable('${tableName}', ${page - 1})" 
                                    ${page <= 1 ? 'disabled class="opacity-40 cursor-not-allowed"' : 'class="hover:bg-gray-800"'} 
                                    class="px-2 py-1 rounded bg-[#21262d] text-gray-300 transition">
                                    ← Sebelumnya
                                </button>
                                <button onclick="DbManager.viewTable('${tableName}', ${page + 1})" 
                                    ${page >= totalPages ? 'disabled class="opacity-40 cursor-not-allowed"' : 'class="hover:bg-gray-800"'} 
                                    class="px-2 py-1 rounded bg-[#21262d] text-gray-300 transition">
                                    Berikutnya →
                                </button>
                            </div>
                        </div>
                    `;
                }
            }

            dataContainer.innerHTML = html;
        } catch (err) {
            dataContainer.innerHTML = `<div class="text-xs text-red-400 p-4">Gagal memuat tabel: ${err.message}</div>`;
        }
    },

    executeSql: async function () {
        const inputEl = document.getElementById('sql-query-input');
        const resultEl = document.getElementById('sql-query-result');
        if (!inputEl || !resultEl) return;

        const sql = inputEl.value.trim();
        if (!sql) {
            resultEl.innerHTML = '<div class="text-xs text-amber-400 p-3">Silakan tulis query SQL terlebih dahulu.</div>';
            return;
        }

        resultEl.innerHTML = '<div class="text-xs text-gray-400 p-3">Menjalankan query SQL...</div>';

        try {
            const res = await fetch('/api/db/query', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sql })
            });

            const data = await res.json();

            if (!data.success) {
                resultEl.innerHTML = `
                    <div class="p-3 bg-red-950/40 border border-red-800 rounded text-xs text-red-300">
                        <div class="font-semibold mb-1">❌ SQL Error:</div>
                        <div class="font-mono">${this.escapeHtml(data.error)}</div>
                    </div>`;
                return;
            }

            let html = `
                <div class="p-2 mb-2 bg-emerald-950/40 border border-emerald-800/60 rounded flex items-center justify-between text-xs text-emerald-300">
                    <span>${data.is_select ? `Ditemukan ${data.row_count} baris` : data.message}</span>
                    <span class="font-mono text-[11px] opacity-80">${data.duration_ms} ms</span>
                </div>
            `;

            if (data.is_select && data.rows) {
                if (data.rows.length === 0) {
                    html += '<div class="text-xs text-gray-400 p-2 italic">Hasil query kosong (0 baris).</div>';
                } else {
                    html += `
                        <div class="overflow-auto max-h-[220px] rounded border border-[#30363d]">
                            <table class="db-table-grid text-xs text-left">
                                <thead>
                                    <tr>
                                        ${data.columns.map(c => `<th class="px-2.5 py-1.5 font-mono text-gray-300 bg-[#1c2128]">${c}</th>`).join('')}
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.rows.map(row => `
                                        <tr class="border-b border-[#21262d]">
                                            ${data.columns.map(c => `
                                                <td class="px-2.5 py-1.5 font-mono text-gray-200 whitespace-nowrap">
                                                    ${row[c] !== null ? this.escapeHtml(String(row[c])) : '<span class="text-gray-500 italic">NULL</span>'}
                                                </td>
                                            `).join('')}
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                }
            }

            resultEl.innerHTML = html;

            // Refresh table list in case CREATE TABLE / DROP TABLE / INSERT occurred
            this.loadTables();
        } catch (err) {
            resultEl.innerHTML = `<div class="p-3 text-xs text-red-400">Request error: ${err.message}</div>`;
        }
    },

    seedData: async function () {
        if (!confirm('Apakah Anda ingin mengisi database dengan sampel data pembelajaran (mahasiswa, kategori, produk, transaksi)?')) {
            return;
        }

        try {
            const res = await fetch('/api/db/seed', { method: 'POST' });
            const data = await res.json();

            if (data.success) {
                alert(data.message);
                this.loadTables();
            } else {
                alert('Gagal membuat data sampel: ' + data.error);
            }
        } catch (err) {
            alert('Error request: ' + err.message);
        }
    },

    copySelectQuery: function (tableName) {
        const inputEl = document.getElementById('sql-query-input');
        if (inputEl) {
            inputEl.value = `SELECT * FROM ${tableName} LIMIT 20;`;
            inputEl.focus();
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
        return text.replace(/[&<>"']/g, m => map[m]);
    }
};
