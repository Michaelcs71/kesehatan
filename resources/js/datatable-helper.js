/**
 * DataGrid - wrapper untuk DataTables.net
 * 
 * Mirror pattern jqxGrid yang dipakai di EduraMind, tapi pakai DataTables.net.
 * Tujuan: konsisten API antara project pakai jqxGrid (EduraMind) dan project pakai DataTables (Kesehatan).
 * 
 * USAGE:
 *   const grid = new DataGrid({
 *     selector: '#datatable',
 *     ajaxUrl: '/master-obat/data',
 *     storageKey: 'master-obat',
 *     columns: [
 *       { data: 'no', orderable: false, className: 'text-center', width: '5%' },
 *       { data: 'nama', name: 'nama' },
 *       { data: 'kategori', name: 'kategori' },
 *       { data: 'actions', orderable: false, width: '15%' }
 *     ],
 *     filters: {
 *       status: '#filterStatus',
 *       kategori: '#filterKategori'
 *     },
 *     onDelete: (id) => fetch(`/master-obat/${id}`, { method: 'DELETE' })
 *   });
 */

export class DataGrid {
    constructor(config) {
        this.config = {
            selector: '#datatable',
            ajaxUrl: '',
            storageKey: 'datagrid',
            pageLength: 10,
            order: [[1, 'desc']],
            filters: {},
            columns: [],
            ...config
        };
        
        this.table = null;
        this.searchTimeout = null;
        
        this.init();
    }

    init() {
        if (!$(this.config.selector).length) {
            console.error(`DataGrid: element ${this.config.selector} not found`);
            return;
        }

        const savedPageLength = parseInt(
            localStorage.getItem(`${this.config.storageKey}_pagesize`)
        ) || this.config.pageLength;

        this.table = $(this.config.selector).DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: savedPageLength,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: this.config.order,
            ajax: {
                url: this.config.ajaxUrl,
                type: 'GET',
                data: (d) => {
                    // Normalize ke pattern EduraMind: pagenum, pagesize, search, plus filter
                    const params = {
                        pagenum: Math.floor(d.start / d.length),
                        pagesize: d.length,
                        search: d.search.value || '',
                        sort_by: d.columns[d.order[0]?.column]?.name || '',
                        sort_dir: d.order[0]?.dir || 'desc',
                    };
                    
                    // Apply custom filters
                    Object.entries(this.config.filters).forEach(([key, sel]) => {
                        const val = $(sel).val();
                        if (val !== undefined && val !== '') {
                            params[key] = val;
                        }
                    });
                    
                    return params;
                },
                dataSrc: (json) => {
                    // Normalize response (EduraMind returns {Rows, TotalRows})
                    if (json.Rows !== undefined) {
                        json.recordsTotal    = json.TotalRows;
                        json.recordsFiltered = json.TotalRows;
                        return json.Rows;
                    }
                    // Standard DataTables format
                    return json.data || [];
                },
                error: (xhr) => {
                    console.error('DataGrid load error:', xhr);
                    Swal.fire({
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Gagal memuat data',
                        icon: 'error'
                    });
                }
            },
            columns: this.config.columns,
            language: this.getLanguage(),
            drawCallback: () => this.bindDeleteHandler(),
        });

        this.bindFilterEvents();
        this.bindPageLengthSave();
    }

    getLanguage() {
        return {
            processing: '<div class="d-flex justify-content-center align-items-center"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</div>',
            search: '',
            searchPlaceholder: 'Cari...',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data',
            infoFiltered: '(difilter dari _MAX_ total data)',
            zeroRecords: 'Data tidak ditemukan',
            emptyTable: 'Tidak ada data tersedia',
            paginate: {
                first: '«',
                last: '»',
                next: '›',
                previous: '‹',
            },
            loadingRecords: 'Memuat...'
        };
    }

    bindFilterEvents() {
        Object.values(this.config.filters).forEach(sel => {
            $(sel).on('change keyup', () => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => this.reload(), 300);
            });
        });
    }

    bindPageLengthSave() {
        $(this.config.selector).on('length.dt', (e, settings, len) => {
            localStorage.setItem(`${this.config.storageKey}_pagesize`, len);
        });
    }

    bindDeleteHandler() {
        if (!this.config.onDelete) return;
        
        $(this.config.selector).find('.btn-delete-row').off('click').on('click', async (e) => {
            const btn = $(e.currentTarget);
            const id = btn.data('id');
            const name = btn.data('name') || 'data ini';
            
            if (!id) return;

            const result = await Swal.fire({
                title: 'Apakah Anda yakin?',
                html: `Data <strong>${name}</strong> akan dihapus!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger me-2',
                    cancelButton: 'btn btn-secondary',
                },
                buttonsStyling: false,
                reverseButtons: true,
            });

            if (!result.isConfirmed) return;

            btn.prop('disabled', true);
            try {
                const res = await this.config.onDelete(id);
                this.reload();
                Swal.fire({
                    title: 'Berhasil!',
                    text: res?.message || 'Data berhasil dihapus.',
                    icon: 'success',
                    timer: 2000,
                });
            } catch (xhr) {
                Swal.fire({
                    title: 'Gagal!',
                    text: xhr.responseJSON?.message || 'Terjadi kesalahan',
                    icon: 'error',
                });
                btn.prop('disabled', false);
            }
        });
    }

    reload() {
        this.table?.ajax.reload(null, false); // false = stay on current page
    }

    refresh() {
        this.table?.ajax.reload();
    }

    destroy() {
        this.table?.destroy();
    }
}

/**
 * Helper for fetch with CSRF token
 */
export async function ajaxRequest(url, options = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    
    const defaultOptions = {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
        },
    };

    if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
        defaultOptions.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(options.body);
    }

    const response = await fetch(url, {
        ...defaultOptions,
        ...options,
        headers: { ...defaultOptions.headers, ...(options.headers || {}) },
    });

    const data = await response.json().catch(() => ({}));
    
    if (!response.ok) {
        throw { status: response.status, responseJSON: data };
    }

    return data;
}