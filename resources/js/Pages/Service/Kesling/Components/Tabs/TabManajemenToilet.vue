<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { 
    QrCode, 
    Plus, 
    Search, 
    Layers, 
    Copy, 
    Check, 
    Download,
    ExternalLink,
    Edit, 
    Trash2, 
    ChevronDown, 
    ChevronLeft, 
    ChevronRight,
    MapPin,
    Sparkles,
    ShieldCheck,
    X,
    Loader2,
    Upload,
    FileSpreadsheet,
    AlertCircle,
    FileText
} from '@lucide/vue';

const props = defineProps({
    toilets: {
        type: Array,
        default: () => []
    },
    qrConfig: {
        type: Object,
        default: () => ({ locations: [] })
    }
});

// Search & Filter State
const searchQuery = ref('');
const selectedBuilding = ref('');
const selectedFloor = ref('');
const selectedStatus = ref('');
const copiedId = ref(null);

// Extract unique buildings and floors
const availableBuildings = computed(() => {
    const list = new Set(props.toilets.map(t => t.building).filter(Boolean));
    return Array.from(list).sort();
});

const availableFloors = computed(() => {
    const list = new Set(props.toilets.map(t => t.floor).filter(Boolean));
    return Array.from(list).sort();
});

// Quick Building & Floor Presets for Form
const buildingPresets = ['Gedung A', 'Gedung B', 'Gedung C', 'Gedung Poliklinik', 'Gedung IGD', 'Gedung Rawat Inap', 'Gedung Penunjang'];
const floorPresets = ['Lantai 1', 'Lantai 2', 'Lantai 3', 'Lantai 4', 'Basement'];

// Filtered Toilet List
const filteredToilets = computed(() => {
    return props.toilets.filter(item => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !query || 
            item.name.toLowerCase().includes(query) ||
            item.code.toLowerCase().includes(query) ||
            item.building.toLowerCase().includes(query) ||
            item.floor.toLowerCase().includes(query);

        const matchesBuilding = !selectedBuilding.value || item.building === selectedBuilding.value;
        const matchesFloor = !selectedFloor.value || item.floor === selectedFloor.value;
        const matchesStatus = !selectedStatus.value || (item.status || 'AKTIF') === selectedStatus.value;

        return matchesSearch && matchesBuilding && matchesFloor && matchesStatus;
    });
});

// Summary Stats
const stats = computed(() => {
    const total = props.toilets.length;
    const activeCount = props.toilets.filter(t => (t.status || 'AKTIF') === 'AKTIF').length;
    const inactiveCount = props.toilets.filter(t => t.status === 'NONAKTIF').length;
    const buildingCount = availableBuildings.value.length;
    return {
        total,
        activeCount,
        inactiveCount,
        buildingCount
    };
});

// Pagination (Kasi/Feed.vue style)
const perPage = ref(10);
const currentPage = ref(1);

const totalPages = computed(() => Math.max(1, Math.ceil(filteredToilets.value.length / perPage.value)));

const paginatedToilets = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredToilets.value.slice(start, start + perPage.value);
});

const startItemIndex = computed(() => {
    if (filteredToilets.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const endItemIndex = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredToilets.value.length);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

watch([searchQuery, selectedBuilding, selectedFloor, selectedStatus, perPage], () => {
    currentPage.value = 1;
});

// --- Modal CRUD State ---
const showFormModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    building: 'Gedung A',
    floor: 'Lantai 1',
    status: 'AKTIF'
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.building = availableBuildings.value[0] || 'Gedung A';
    form.floor = 'Lantai 1';
    form.status = 'AKTIF';
    showFormModal.value = true;
};

const openEditModal = (toilet) => {
    isEditing.value = true;
    editingId.value = toilet.id;
    form.name = toilet.name;
    form.building = toilet.building;
    form.floor = toilet.floor;
    form.status = toilet.status || 'AKTIF';
    showFormModal.value = true;
};

const submitForm = () => {
    if (isEditing.value && editingId.value) {
        form.put(route('pansinar.toilets.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('pansinar.toilets.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                form.reset();
            }
        });
    }
};

// --- Modal Delete State ---
const showDeleteModal = ref(false);
const toiletToDelete = ref(null);

const confirmDelete = (toilet) => {
    toiletToDelete.value = toilet;
    showDeleteModal.value = true;
};

const deleteToiletAction = () => {
    if (!toiletToDelete.value) return;
    router.delete(route('pansinar.toilets.destroy', toiletToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            toiletToDelete.value = null;
        }
    });
};

// --- Download QR State & Action ---
const downloadingId = ref(null);

const getToiletUrl = (toilet) => {
    if (typeof window !== 'undefined' && window.location.origin) {
        return `${window.location.origin}/services/kesling/aduan-sanitasi?toilet=${toilet.code}`;
    }
    return toilet?.target_url || '';
};

const copyUrl = (url, id = null) => {
    navigator.clipboard.writeText(url);
    copiedId.value = id || 'table';
    setTimeout(() => {
        copiedId.value = null;
    }, 2000);
};

const downloadQr = async (toilet) => {
    if (!toilet) return;
    
    // Gabungkan nama toilet, gedung, dan lantai (contoh: Toilet Disabilitas_Gedung C_Lantai 2)
    const nameParts = [
        toilet.name || toilet.code || 'Toilet',
        toilet.building || '',
        toilet.floor || ''
    ].filter(p => p && String(p).trim() !== '');

    const combinedName = nameParts.join('_');
    // Bersihkan karakter terlarang sistem berkas agar nama file selalu valid
    const safeName = combinedName.replace(/[/\\?%*:|"<>]/g, '-').trim();
    const fileName = `${safeName}.png`;

    try {
        downloadingId.value = toilet.id;

        // Generate Ultra Crisp High-Res QR Code dengan margin putih (quiet zone) rapi
        const qrUrl = getToiletUrl(toilet);
        const dataUrl = await QRCode.toDataURL(qrUrl, {
            width: 1000,
            margin: 4, // Margin putih di sekeliling QR Code sesuai standar internasional
            color: {
                dark: '#000000',
                light: '#FFFFFF'
            },
            errorCorrectionLevel: 'H'
        });

        const a = document.createElement('a');
        a.href = dataUrl;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    } catch (err) {
        console.error('Download QR failed:', err);
    } finally {
        downloadingId.value = null;
    }
};

// --- Modal Import CSV State & Actions ---
const showImportModal = ref(false);
const importFile = ref(null);
const isParsing = ref(false);
const isDragging = ref(false);
const parsedRows = ref([]);
const parseError = ref(null);
const fileInputRef = ref(null);
const isSubmittingImport = ref(false);

const openImportModal = () => {
    showImportModal.value = true;
    importFile.value = null;
    isDragging.value = false;
    parsedRows.value = [];
    parseError.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
};

const downloadTemplate = () => {
    const headers = ['nama_toilet', 'gedung', 'lantai', 'status'];
    const sampleRows = [
        ['Toilet Utama Poliklinik (Pria)', 'Gedung A', 'Lantai 1', 'AKTIF'],
        ['Toilet Pengunjung Radiologi (Wanita)', 'Gedung B', 'Lantai 1', 'AKTIF'],
        ['Toilet Rawat Inap Melati 03', 'Gedung C', 'Lantai 2', 'NONAKTIF'],
        ['Toilet IGD Triase Darurat', 'Gedung IGD', 'Lantai 1', 'AKTIF'],
        ['Toilet Difabel Koridor Timur', 'Gedung A', 'Lantai 1', 'AKTIF']
    ];

    const csvContent = '\uFEFF' + [
        headers.join(','),
        ...sampleRows.map(row => row.map(val => `"${val.replace(/"/g, '""')}"`).join(','))
    ].join('\r\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'format_import_toilet.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};

const processCsvFile = (file) => {
    if (!file) return;

    importFile.value = file;
    parseError.value = null;
    parsedRows.value = [];
    isParsing.value = true;

    const reader = new FileReader();
    reader.onload = (event) => {
        try {
            const text = event.target.result;
            const lines = text.split(/\r\n|\n/).filter(line => line.trim() !== '');
            if (lines.length < 2) {
                parseError.value = 'Berkas CSV harus memiliki minimal 1 baris header dan 1 baris data.';
                isParsing.value = false;
                return;
            }

            const headerLine = lines[0];
            const delimiter = headerLine.includes(';') ? ';' : ',';

            const parseRow = (line) => {
                const result = [];
                let cur = '';
                let inQuotes = false;
                for (let i = 0; i < line.length; i++) {
                    const c = line[i];
                    if (c === '"') {
                        if (inQuotes && line[i + 1] === '"') {
                            cur += '"';
                            i++;
                        } else {
                            inQuotes = !inQuotes;
                        }
                    } else if (c === delimiter && !inQuotes) {
                        result.push(cur.trim());
                        cur = '';
                    } else {
                        cur += c;
                    }
                }
                result.push(cur.trim());
                return result;
            };

            const headers = parseRow(headerLine).map(h => h.toLowerCase().replace(/[^a-z0-9_]/g, ''));
            
            const rows = [];
            for (let i = 1; i < lines.length; i++) {
                const values = parseRow(lines[i]);
                if (values.every(v => v === '')) continue;

                const rowObj = {};
                headers.forEach((h, idx) => {
                    rowObj[h] = values[idx] || '';
                });

                const name = rowObj.nama_toilet || rowObj.namatoilet || rowObj.nama || rowObj.name || values[0] || '';
                const building = rowObj.gedung || rowObj.building || values[1] || 'Gedung A';
                const floor = rowObj.lantai || rowObj.floor || values[2] || 'Lantai 1';
                const statusRaw = (rowObj.status || values[3] || 'AKTIF').toUpperCase();
                const status = statusRaw === 'NONAKTIF' ? 'NONAKTIF' : 'AKTIF';

                if (name) {
                    rows.push({
                        nama_toilet: name,
                        gedung: building,
                        lantai: floor,
                        status: status
                    });
                }
            }

            if (rows.length === 0) {
                parseError.value = 'Tidak ditemukan data toilet yang valid di dalam berkas.';
            } else {
                parsedRows.value = rows;
            }
        } catch (err) {
            console.error(err);
            parseError.value = 'Gagal membaca berkas CSV: ' + err.message;
        } finally {
            isParsing.value = false;
        }
    };
    reader.onerror = () => {
        parseError.value = 'Gagal membuka berkas.';
        isParsing.value = false;
    };
    reader.readAsText(file);
};

const handleFileChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    processCsvFile(file);
};

const handleDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file) {
        processCsvFile(file);
    }
};

const submitImport = () => {
    if (parsedRows.value.length === 0) return;

    isSubmittingImport.value = true;
    router.post(route('pansinar.toilets.import'), {
        data: parsedRows.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showImportModal.value = false;
            importFile.value = null;
            parsedRows.value = [];
            isSubmittingImport.value = false;
        },
        onError: (errors) => {
            console.error(errors);
            isSubmittingImport.value = false;
        }
    });
};

// Expose openCreateModal & openImportModal for parent header buttons
defineExpose({
    openCreateModal,
    openImportModal
});
</script>

<template>
    <div class="space-y-4">
        <!-- 3 Summary Stat Cards (Kasi/Feed Kesling Style) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Card 1: Total Toilet -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Titik Toilet</span>
                    <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.total }}</div>
                    <span class="text-[11px] text-slate-400 block">Semua fasilitas terdaftar</span>
                </div>
                <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">
                    <MapPin class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>

            <!-- Card 2: Sebaran Gedung -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Sebaran Gedung</span>
                    <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.buildingCount }}</div>
                    <span class="text-[11px] text-slate-400 block">Gedung terpasang sanitasi</span>
                </div>
                <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-blue-50 dark:bg-blue-950/40">
                    <Layers class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>

            <!-- Card 3: QR Siap Scan -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 block">QR Siap Scan</span>
                    <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-tight">{{ stats.activeCount }}</div>
                    <span class="text-[11px] text-slate-400 block">{{ stats.inactiveCount }} toilet status nonaktif</span>
                </div>
                <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                    <QrCode class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>
        </div>

        <!-- Unified Table Card Wrapper -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/60 rounded-2xl shadow-sm overflow-hidden mb-4">
            <!-- Search & Custom Filter Controls (Combined Header Sesuai Aduan Sanitasi) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 sm:p-5">
                <!-- Left: Search Box -->
                <div class="relative w-full sm:w-80 xl:w-96">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama toilet, gedung, lantai, atau kode..."
                        class="w-full h-10 pl-10 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150 shadow-none"
                    />
                </div>

                <!-- Right Controls: Gedung Filter, Lantai Filter, Status Filter & Segmented View Switcher -->
                <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end flex-wrap">
                    <!-- Gedung Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-initial">
                        <select
                            v-model="selectedBuilding"
                            class="w-full sm:w-auto h-10 pl-3.5 pr-8 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-emerald-500 cursor-pointer appearance-none"
                        >
                            <option value="">Semua Gedung</option>
                            <option v-for="b in availableBuildings" :key="b" :value="b">{{ b }}</option>
                        </select>
                        <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
                    </div>

                    <!-- Lantai Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-initial">
                        <select
                            v-model="selectedFloor"
                            class="w-full sm:w-auto h-10 pl-3.5 pr-9 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-none appearance-none transition-all duration-150"
                        >
                            <option value="">Semua Lantai</option>
                            <option v-for="f in availableFloors" :key="f" :value="f">{{ f }}</option>
                        </select>
                        <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
                    </div>

                    <!-- Status Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-initial">
                        <select
                            v-model="selectedStatus"
                            class="w-full sm:w-auto h-10 pl-3.5 pr-8 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-emerald-500 cursor-pointer appearance-none"
                        >
                            <option value="">Semua Status</option>
                            <option value="AKTIF">Aktif</option>
                            <option value="NONAKTIF">Nonaktif</option>
                        </select>
                        <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
                    </div>
                </div>
            </div>

            <!-- TABEL TITIK TOILET (STANDAR RESMI RS DALAM CONTAINER ROUNDED) -->
            <div class="px-4 sm:px-5 pb-4 sm:pb-5 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/50 text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider whitespace-nowrap">
                                <th class="px-4 py-3.5 text-center w-12">NO</th>
                                <th class="px-5 py-3.5 text-left">NAMA TITIK TOILET</th>
                                <th class="px-5 py-3.5 text-center">GEDUNG</th>
                                <th class="px-5 py-3.5 text-center">LANTAI</th>
                                <th class="px-5 py-3.5 text-center">STATUS</th>
                                <th class="px-5 py-3.5 text-center min-w-[240px]">TAUTAN ADUAN PUBLIK</th>
                                <th class="px-5 py-3.5 text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300 bg-white dark:bg-slate-900">
                            <tr 
                                v-for="(toilet, idx) in paginatedToilets" 
                                :key="toilet.id"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors duration-150"
                            >
                                <!-- No -->
                                <td class="px-4 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-400 dark:text-slate-500">
                                    {{ (currentPage - 1) * perPage + idx + 1 }}
                                </td>

                                <!-- Nama Titik Toilet -->
                                <td class="px-5 py-4">
                                    <div class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        {{ toilet.name }}
                                    </div>
                                </td>

                                <!-- Gedung (Center, Tanpa Icon) -->
                                <td class="px-5 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    {{ toilet.building }}
                                </td>

                                <!-- Lantai (Center, Tanpa Icon) -->
                                <td class="px-5 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    {{ toilet.floor }}
                                </td>

                                <!-- Status (Center) -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span 
                                        v-if="(toilet.status || 'AKTIF') === 'AKTIF'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                    <span 
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Nonaktif
                                    </span>
                                </td>

                                <!-- Tautan Aduan Publik (Center) -->
                                <td class="px-5 py-4 text-xs text-center">
                                    <div class="inline-flex items-center justify-center gap-2 max-w-sm px-2.5 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-800 text-xs">
                                        <span class="font-mono text-[11px] text-slate-600 dark:text-slate-300 truncate max-w-[200px] select-all">
                                            {{ getToiletUrl(toilet) }}
                                        </span>
                                        <button 
                                            type="button"
                                            @click="copyUrl(getToiletUrl(toilet), toilet.id)"
                                            :title="copiedId === toilet.id ? 'URL Berhasil Disalin' : 'Salin Tautan'"
                                            class="p-1 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer shrink-0"
                                        >
                                            <Check v-if="copiedId === toilet.id" class="w-3.5 h-3.5 text-emerald-600 font-bold" />
                                            <Copy v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>

                                <!-- Aksi (Center) -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Unduh Barcode QR (Hanya Icon seperti Edit & Delete) -->
                                        <button
                                            type="button"
                                            @click="downloadQr(toilet)"
                                            :disabled="downloadingId === toilet.id"
                                            class="h-8 w-8 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 dark:hover:bg-emerald-950/40 dark:hover:border-emerald-800 text-slate-600 dark:text-slate-300 flex items-center justify-center transition cursor-pointer disabled:opacity-50"
                                            :title="`Unduh Barcode QR ${toilet.name}`"
                                        >
                                            <Loader2 v-if="downloadingId === toilet.id" class="h-3.5 w-3.5 animate-spin text-emerald-600" />
                                            <Download v-else class="h-3.5 w-3.5" />
                                        </button>

                                        <!-- Edit Data -->
                                        <button
                                            type="button"
                                            @click="openEditModal(toilet)"
                                            class="h-8 w-8 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center transition cursor-pointer"
                                            title="Edit Data Toilet"
                                        >
                                            <Edit class="h-3.5 w-3.5" />
                                        </button>

                                        <!-- Hapus Data -->
                                        <button
                                            type="button"
                                            @click="confirmDelete(toilet)"
                                            class="h-8 w-8 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 dark:hover:bg-rose-950/40 dark:hover:border-rose-900/50 text-slate-400 flex items-center justify-center transition cursor-pointer"
                                            title="Hapus Data Toilet"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredToilets.length === 0">
                                <td colspan="7" class="py-16 text-center text-slate-400 bg-white dark:bg-slate-900">
                                    <MapPin class="h-12 w-12 text-slate-200 dark:text-slate-700 mx-auto mb-2" />
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Tidak ada data titik toilet yang sesuai</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Coba ubah kata kunci pencarian atau filter gedung/lantai.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Table Footer / Interactive Pagination (Sesuai Aduan Sanitasi & Feed.vue) -->
            <div class="px-4 sm:px-5 py-4 border-t border-slate-100 dark:border-slate-800/60 bg-white dark:bg-slate-900 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <!-- Left: Per-Page Selector -->
                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <span class="text-[11px] font-medium">Tampilkan</span>
                    <select
                        v-model="perPage"
                        @change="currentPage = 1"
                        class="h-7 py-0 pl-2 pr-6 text-[11px] font-normal rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 cursor-pointer transition"
                    >
                        <option :value="5">5</option>
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                    <span class="text-[11px] font-medium">data</span>
                </div>

                <!-- Right: Compact Range & Navigation Buttons -->
                <div class="flex items-center gap-3">
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        {{ startItemIndex }}–{{ endItemIndex }} dari {{ filteredToilets.length }}
                    </span>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="h-7 w-7 rounded-lg flex items-center justify-center border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150 cursor-pointer"
                            aria-label="Halaman sebelumnya"
                        >
                            <ChevronLeft class="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage >= totalPages"
                            class="h-7 w-7 rounded-lg flex items-center justify-center border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150 cursor-pointer"
                            aria-label="Halaman selanjutnya"
                        >
                            <ChevronRight class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL 1: FORM TAMBAH / EDIT TOILET                                        -->
        <!-- ========================================================================= -->
        <Modal :show="showFormModal" @close="showFormModal = false" max-width="lg">
            <div class="flex flex-col h-[100dvh] sm:h-auto max-h-[100dvh] sm:max-h-[90vh] bg-white dark:bg-slate-900 justify-between overflow-hidden">
                <!-- Solid Emerald Header -->
                <div class="bg-emerald-600 dark:bg-emerald-950/90 text-white p-4 sm:p-5 flex items-center justify-between shrink-0 border-b border-emerald-500/30 dark:border-emerald-800/50 shadow-sm">
                    <div class="flex items-center gap-3 pr-2">
                        <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                            <MapPin class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-black tracking-tight text-white leading-tight">
                                {{ isEditing ? 'Edit Data Titik Toilet' : 'Tambah Titik Toilet Baru' }}
                            </h3>
                            <p class="text-[11px] text-emerald-100/90 dark:text-emerald-200/80 font-medium leading-tight mt-0.5">
                                Pendataan nama toilet, gedung, dan lantai untuk barcode QR aduan
                            </p>
                        </div>
                    </div>
                    <button 
                        type="button"
                        @click="showFormModal = false"
                        class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition outline-none focus:outline-none ring-0 focus:ring-0"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Form Body -->
                <form @submit.prevent="submitForm" class="flex flex-col flex-1 min-h-0 justify-between overflow-hidden">
                    <div class="p-5 sm:p-6 overflow-y-auto space-y-4 max-h-[calc(100dvh-220px)] sm:max-h-[65vh] overscroll-contain touch-pan-y">
                        <!-- Info Box -->
                        <div class="p-3.5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/60 dark:border-emerald-800/50 text-xs text-emerald-800 dark:text-emerald-300 flex items-start gap-2.5">
                            <Sparkles class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                            <p class="leading-relaxed text-[11px]">
                                Setelah disimpan, sistem otomatis membuat stiker barcode QR unik yang langsung terhubung ke formulir aduan masyarakat.
                            </p>
                        </div>

                        <!-- Nama Titik Toilet -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                Nama Titik Toilet <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Contoh: Toilet Pengunjung Timur (Pria)"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                            <p class="text-[10px] text-slate-400 mt-1">Sertakan gender/jenis toilet jika spesifik (misal: Pria, Wanita, atau Difabel).</p>
                        </div>

                        <!-- Gedung -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                Lokasi Gedung <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                v-model="form.building"
                                type="text"
                                required
                                list="building-options"
                                placeholder="Contoh: Gedung A atau Gedung Rawat Inap"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                            <datalist id="building-options">
                                <option v-for="b in buildingPresets" :key="b" :value="b" />
                            </datalist>
                        </div>

                        <!-- Lantai -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                Lokasi Lantai <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                v-model="form.floor"
                                type="text"
                                required
                                list="floor-options"
                                placeholder="Contoh: Lantai 1, Lantai 2, atau Basement"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                            <datalist id="floor-options">
                                <option v-for="fl in floorPresets" :key="fl" :value="fl" />
                            </datalist>
                        </div>

                        <!-- Status Toilet (Aktif / Nonaktif) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Status Toilet <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Opsi 1: Aktif -->
                                <label 
                                    :class="[
                                        'p-3.5 rounded-xl border-2 cursor-pointer transition flex items-start gap-3 select-none',
                                        form.status === 'AKTIF' 
                                            ? 'border-emerald-600 bg-emerald-50/50 dark:bg-emerald-950/30 dark:border-emerald-500 ring-2 ring-emerald-500/20' 
                                            : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'
                                    ]"
                                >
                                    <input 
                                        type="radio" 
                                        v-model="form.status" 
                                        value="AKTIF" 
                                        class="mt-0.5 text-emerald-600 focus:ring-emerald-500" 
                                    />
                                    <div>
                                        <span class="block text-xs font-black text-slate-900 dark:text-white">
                                            Aktif
                                        </span>
                                        <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                                            Fasilitas toilet aktif beroperasi dan dapat menerima aduan masyarakat.
                                        </span>
                                    </div>
                                </label>

                                <!-- Opsi 2: Nonaktif -->
                                <label 
                                    :class="[
                                        'p-3.5 rounded-xl border-2 cursor-pointer transition flex items-start gap-3 select-none',
                                        form.status === 'NONAKTIF' 
                                            ? 'border-amber-500 bg-amber-50/50 dark:bg-amber-950/30 dark:border-amber-500 ring-2 ring-amber-500/20' 
                                            : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'
                                    ]"
                                >
                                    <input 
                                        type="radio" 
                                        v-model="form.status" 
                                        value="NONAKTIF" 
                                        class="mt-0.5 text-amber-500 focus:ring-amber-500" 
                                    />
                                    <div>
                                        <span class="block text-xs font-black text-slate-900 dark:text-white">
                                            Nonaktif
                                        </span>
                                        <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                                            Fasilitas toilet sedang ditutup, perbaikan, atau dinonaktifkan.
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Action Footer (Responsive Stacked on Mobile) -->
                    <div class="sticky bottom-0 z-30 shrink-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 p-4 pt-3.5 pb-8 sm:pb-5 sm:p-5 shadow-lg sm:shadow-none">
                        <!-- Mobile View (< sm) -->
                        <div class="flex flex-col gap-2.5 w-full sm:hidden">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:opacity-70 shadow-sm"
                            >
                                <span v-if="form.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                <span v-else>{{ isEditing ? 'Simpan Perubahan' : 'Simpan Titik Toilet' }}</span>
                            </button>
                            <button
                                type="button"
                                @click="showFormModal = false"
                                :disabled="form.processing"
                                class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none"
                            >
                                <span>Batal</span>
                            </button>
                        </div>

                        <!-- Desktop View (>= sm) -->
                        <div class="hidden sm:flex items-center justify-end gap-3">
                            <SecondaryButton type="button" @click="showFormModal = false" class="h-11 px-5 cursor-pointer">
                                Batal
                            </SecondaryButton>
                            <PrimaryButton 
                                type="submit" 
                                :disabled="form.processing" 
                                class="h-11 px-6 !bg-emerald-600 hover:!bg-emerald-500 font-bold cursor-pointer active:scale-95"
                            >
                                {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Titik Toilet') }}
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ========================================================================= -->
        <!-- MODAL 2: KONFIRMASI HAPUS TOILET                                          -->
        <!-- ========================================================================= -->
        <Modal :show="showDeleteModal && !!toiletToDelete" @close="showDeleteModal = false" max-width="md">
            <div v-if="toiletToDelete" class="p-6 bg-white dark:bg-slate-900 rounded-2xl">
                <div class="flex items-center gap-3.5 mb-4">
                    <div class="h-12 w-12 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <Trash2 class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">
                            Hapus Titik Toilet?
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Data yang dihapus tidak akan dapat di-scan oleh pengunjung.
                        </p>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-xs mb-5 space-y-1">
                    <div class="font-extrabold text-slate-900 dark:text-white">{{ toiletToDelete.name }}</div>
                    <div class="text-slate-500">{{ toiletToDelete.building }} - {{ toiletToDelete.floor }} ({{ toiletToDelete.code }})</div>
                </div>

                <div class="flex items-center justify-end gap-2.5">
                    <SecondaryButton @click="showDeleteModal = false">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="deleteToiletAction" class="font-bold">
                        Ya, Hapus Toilet
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <!-- ========================================================================= -->
        <!-- MODAL 3: IMPORT DATA TOILET DARI CSV                                      -->
        <!-- ========================================================================= -->
        <Modal :show="showImportModal" @close="showImportModal = false" max-width="2xl">
            <div class="flex flex-col h-[100dvh] sm:h-auto max-h-[100dvh] sm:max-h-[90vh] bg-white dark:bg-slate-900 justify-between overflow-hidden">
                <!-- Solid Emerald Header -->
                <div class="bg-emerald-600 dark:bg-emerald-950/90 text-white p-4 sm:p-5 flex items-center justify-between shrink-0 border-b border-emerald-500/30 dark:border-emerald-800/50 shadow-sm">
                    <div class="flex items-center gap-3 pr-2">
                        <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                            <Upload class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-black tracking-tight text-white leading-tight">
                                Import Data Titik Toilet
                            </h3>
                            <p class="text-[11px] text-emerald-100/90 dark:text-emerald-200/80 font-medium leading-tight mt-0.5">
                                Unggah data toilet massal menggunakan format berkas CSV / Excel
                            </p>
                        </div>
                    </div>
                    <button 
                        type="button"
                        @click="showImportModal = false"
                        class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition outline-none focus:outline-none ring-0 focus:ring-0"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 sm:p-6 overflow-y-auto space-y-4 max-h-[calc(100dvh-220px)] sm:max-h-[65vh] overscroll-contain touch-pan-y">
                    <!-- Langkah 1: Unduh Format CSV -->
                    <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="h-10 w-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 flex items-center justify-center shrink-0 mt-0.5">
                                <FileSpreadsheet class="w-5 h-5" />
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-900 dark:text-white">
                                    Unduh Format Template
                                </h4>
                                <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5 leading-relaxed">
                                    Format template CSV memuat 4 kolom wajib:<br />
                                    <span class="font-semibold text-emerald-700 dark:text-emerald-300">nama_toilet, gedung, lantai, status.</span>
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="downloadTemplate"
                            class="shrink-0 h-9 px-3.5 inline-flex items-center justify-center gap-1.5 rounded-xl bg-white dark:bg-slate-800 border border-emerald-300 dark:border-emerald-700 hover:bg-emerald-50 dark:hover:bg-slate-700 text-emerald-700 dark:text-emerald-300 text-xs font-bold transition shadow-xs cursor-pointer active:scale-95"
                        >
                            <Download class="w-3.5 h-3.5" />
                            <span>Unduh Format CSV</span>
                        </button>
                    </div>

                    <!-- Upload Area -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            Pilih Berkas CSV yang Sudah Diisi
                        </label>
                        
                        <div 
                            @click="fileInputRef?.click()"
                            @dragover.prevent="isDragging = true"
                            @dragenter.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                            :class="[
                                'border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition select-none',
                                isDragging 
                                    ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/30 ring-2 ring-emerald-500/20' 
                                    : 'border-slate-300 dark:border-slate-700 hover:border-emerald-500 dark:hover:border-emerald-500 bg-slate-50/50 dark:bg-slate-800/40'
                            ]"
                        >
                            <input 
                                ref="fileInputRef"
                                type="file" 
                                accept=".csv" 
                                class="hidden" 
                                @change="handleFileChange"
                            />
                            <div class="flex flex-col items-center justify-center gap-2 pointer-events-none">
                                <div class="h-12 w-12 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <Upload class="w-6 h-6" />
                                </div>
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    <span v-if="importFile" class="text-emerald-600 dark:text-emerald-400">{{ importFile.name }}</span>
                                    <span v-else>Klik atau seret berkas .csv ke sini</span>
                                </div>
                                <p class="text-[11px] text-slate-400">
                                    Mendukung berkas CSV UTF-8 pemisah koma (,) atau titik-koma (;)
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Error Alert -->
                    <div v-if="parseError" class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2.5">
                        <AlertCircle class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                        <span class="leading-relaxed">{{ parseError }}</span>
                    </div>

                    <!-- Parsing Loading -->
                    <div v-if="isParsing" class="py-6 text-center text-xs text-slate-500 flex items-center justify-center gap-2">
                        <Loader2 class="w-4 h-4 animate-spin text-emerald-600" />
                        <span>Membaca dan memvalidasi isi berkas CSV...</span>
                    </div>

                    <!-- Langkah 3: Pratinjau Data (Jika Berhasil Diparsing) -->
                    <div v-if="parsedRows.length > 0 && !isParsing" class="space-y-2.5 pt-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                Pratinjau Data Siap Diimpor
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                {{ parsedRows.length }} Data Terdeteksi
                            </span>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                            <div class="max-h-60 overflow-y-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-slate-50 dark:bg-slate-800/60 sticky top-0 border-b border-slate-200 dark:border-slate-800 font-bold text-slate-600 dark:text-slate-300">
                                        <tr>
                                            <th class="px-3 py-2 text-center w-10">NO</th>
                                            <th class="px-3 py-2">NAMA TOILET</th>
                                            <th class="px-3 py-2 text-center">GEDUNG</th>
                                            <th class="px-3 py-2 text-center">LANTAI</th>
                                            <th class="px-3 py-2 text-center">STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                                        <tr v-for="(row, i) in parsedRows" :key="i" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                            <td class="px-3 py-2 text-center text-slate-400 font-mono">{{ i + 1 }}</td>
                                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-white">{{ row.nama_toilet }}</td>
                                            <td class="px-3 py-2 text-center">{{ row.gedung }}</td>
                                            <td class="px-3 py-2 text-center">{{ row.lantai }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <span 
                                                    :class="[
                                                        'px-2 py-0.5 rounded-full text-[10px] font-bold border',
                                                        row.status === 'AKTIF'
                                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800'
                                                            : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
                                                    ]"
                                                >
                                                    {{ row.status === 'AKTIF' ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Action Footer -->
                <div class="sticky bottom-0 z-30 shrink-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 p-4 pt-3.5 pb-8 sm:pb-5 sm:p-5 shadow-lg sm:shadow-none">
                    <!-- Mobile View (< sm) -->
                    <div class="flex flex-col gap-2.5 w-full sm:hidden">
                        <button
                            type="button"
                            @click="submitImport"
                            :disabled="parsedRows.length === 0 || isSubmittingImport"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] disabled:opacity-50 shadow-sm"
                        >
                            <span v-if="isSubmittingImport" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            <span v-else>Import {{ parsedRows.length > 0 ? `(${parsedRows.length} Data)` : '' }}</span>
                        </button>
                        <button
                            type="button"
                            @click="showImportModal = false"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99]"
                        >
                            <span>Batal</span>
                        </button>
                    </div>

                    <!-- Desktop View (>= sm) -->
                    <div class="hidden sm:flex items-center justify-between w-full">
                        <span class="text-xs text-slate-400">
                            {{ parsedRows.length > 0 ? `${parsedRows.length} titik toilet siap dibuatkan QR barcode.` : '' }}
                        </span>
                        <div class="flex items-center gap-3">
                            <SecondaryButton type="button" @click="showImportModal = false" class="h-11 px-5 cursor-pointer">
                                Batal
                            </SecondaryButton>
                            <PrimaryButton 
                                type="button" 
                                @click="submitImport"
                                :disabled="parsedRows.length === 0 || isSubmittingImport" 
                                class="h-11 px-6 !bg-emerald-600 hover:!bg-emerald-500 font-bold cursor-pointer active:scale-95 disabled:opacity-50 flex items-center gap-2"
                            >
                                <span v-if="isSubmittingImport" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                <Upload v-else class="w-4 h-4" />
                                <span>{{ isSubmittingImport ? 'Mengimpor...' : `Konfirmasi Import (${parsedRows.length})` }}</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>
