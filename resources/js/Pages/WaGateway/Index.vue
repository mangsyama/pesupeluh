<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { 
    MessageSquareCode, 
    QrCode, 

    CheckCircle2, 
    AlertCircle, 
    AlertTriangle,
    RefreshCw, 
    LogOut, 
    Send, 
    Smartphone, 
    Radio,
    ShieldCheck,
    Cpu,
    X
} from '@lucide/vue';

const props = defineProps({
    driver: {
        type: String,
        default: 'local'
    },
    localUrl: {
        type: String,
        default: 'http://127.0.0.1:3000/send'
    }
});

const gatewayStatus = ref('offline'); // 'offline' | 'disconnected' | 'connecting' | 'connected'
const qrCode = ref(null);
const connectedUser = ref(null);
const statusMessage = ref('');
const isChecking = ref(false);
const isDisconnecting = ref(false);
const showDisconnectModal = ref(false);
const showTestModal = ref(false);
let pollTimeout = null;

const testForm = useForm({
    phone: '',
    message: 'Halo! Ini adalah pesan uji coba dari WhatsApp Gateway Pesu Peluh.'
});

const scheduleNextCheck = () => {
    if (pollTimeout) clearTimeout(pollTimeout);
    
    // Check quickly (3s) when scanning QR, or slow (20s) when already connected
    const delay = gatewayStatus.value === 'connected' ? 20000 : 3000;
    
    pollTimeout = setTimeout(async () => {
        await checkStatus();
        scheduleNextCheck();
    }, delay);
};

const checkStatus = async () => {
    isChecking.value = true;
    try {
        const response = await fetch(route('admin.wa-gateway.status'));
        const data = await response.json();
        gatewayStatus.value = data.status || 'offline';
        qrCode.value = data.qr || null;
        connectedUser.value = data.user || null;
        statusMessage.value = data.message || '';
    } catch (e) {
        gatewayStatus.value = 'offline';
        qrCode.value = null;
        connectedUser.value = null;
        statusMessage.value = 'Server WA Gateway lokal tidak merespons.';
    } finally {
        isChecking.value = false;
    }
};

const confirmLogout = () => {
    showDisconnectModal.value = true;
};

const cancelLogout = () => {
    showDisconnectModal.value = false;
};

const handleLogout = async () => {
    isDisconnecting.value = true;
    try {
        await router.post(route('admin.wa-gateway.logout'), {}, {
            preserveScroll: true,
            onFinish: () => {
                isDisconnecting.value = false;
                showDisconnectModal.value = false;
                checkStatus();
            }
        });
    } catch (e) {
        isDisconnecting.value = false;
    }
};

const sendTestMessage = () => {
    testForm.post(route('admin.wa-gateway.test'), {
        preserveScroll: true,
        onSuccess: () => {
            showTestModal.value = false;
            testForm.reset('message');
            testForm.message = 'Halo! Ini merupakan pesan uji coba dari WhatsApp Gateway Pesu Peluh.';
        }
    });
};

onMounted(async () => {
    await checkStatus();
    scheduleNextCheck();
});

onUnmounted(() => {
    if (pollTimeout) clearTimeout(pollTimeout);
});
</script>

<template>
    <Head title="WhatsApp Gateway" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                <!-- Header Panel (ALWAYS VISIBLE) -->
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <MessageSquareCode class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-955 dark:text-white leading-tight">
                                WhatsApp Gateway
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Status koneksi & manajemen perangkat perpesanan WhatsApp otomatis
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3">
                        <button 
                            @click="checkStatus" 
                            :disabled="isChecking"
                            class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center sm:justify-start gap-2 transition disabled:opacity-50 cursor-pointer"
                        >
                            <RefreshCw :class="['h-4 w-4', isChecking ? 'animate-spin' : '']" />
                            <span>Refresh Status</span>
                        </button>

                        <button 
                            @click="showTestModal = true" 
                            class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center justify-center sm:justify-start gap-2 transition shadow-sm border-0 cursor-pointer"
                        >
                            <Send class="h-4 w-4" />
                            <span>Uji Coba Pesan</span>
                        </button>
                    </div>
                </div>

                <!-- Main Content Card: Connection Status & QR Code -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                            Status Perangkat
                        </h3>

                        <!-- Status Badge -->
                        <span 
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold uppercase border',
                                gatewayStatus === 'connected' 
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white dark:border-white/20' 
                                    : (gatewayStatus === 'connecting' || gatewayStatus === 'disconnected')
                                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border-amber-200 dark:border-amber-800'
                                        : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border-rose-200 dark:border-rose-800'
                            ]"
                        >
                            <span class="h-2 w-2 rounded-full" :class="gatewayStatus === 'connected' ? 'bg-emerald-500' : (gatewayStatus === 'connecting' || gatewayStatus === 'disconnected') ? 'bg-amber-500 animate-ping' : 'bg-rose-500'"></span>
                            {{ 
                                gatewayStatus === 'connected' 
                                    ? 'Terhubung' 
                                    : gatewayStatus === 'connecting'
                                        ? (qrCode ? 'Menunggu Scan' : 'Menghubungkan')
                                        : gatewayStatus === 'disconnected'
                                            ? 'Menyiapkan'
                                            : 'Offline' 
                            }}
                        </span>
                    </div>

                    <!-- CASE 1: CONNECTED STATE -->
                    <div v-if="gatewayStatus === 'connected'" class="p-5 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-900/50 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 shrink-0 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                    <Smartphone class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ connectedUser?.name || 'Nomor WhatsApp Perangkat' }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5">+{{ connectedUser?.id }}</div>
                                </div>
                            </div>

                            <button 
                                @click="confirmLogout" 
                                :disabled="isDisconnecting"
                                class="w-full sm:w-auto px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 text-xs font-semibold flex items-center justify-center sm:justify-start gap-1.5 transition border border-rose-200 dark:border-rose-900 cursor-pointer order-last sm:order-none"
                            >
                                <LogOut class="h-3.5 w-3.5" />
                                <span>Putuskan WA</span>
                            </button>
                        </div>

                        <div class="text-xs text-emerald-800 dark:text-emerald-300 leading-relaxed bg-white/80 dark:bg-slate-900/80 p-3 rounded-xl border border-emerald-100 dark:border-emerald-900/30 flex items-start gap-2">
                            <ShieldCheck class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                            <span>Perangkat WhatsApp terhubung aktif! Semua notifikasi otomatis sistem akan dikirim menggunakan nomor ini.</span>
                        </div>
                    </div>

                    <!-- CASE 2: CONNECTING STATE (WITHOUT ACTIVE QR) -->
                    <div v-else-if="gatewayStatus === 'connecting' && !qrCode" class="flex flex-col items-center justify-center p-8 rounded-2xl bg-amber-50/20 dark:bg-amber-950/10 border border-amber-200/30 dark:border-amber-900/20 text-center space-y-4">
                        <div class="h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto">
                            <RefreshCw class="h-6 w-6 animate-spin" />
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Menghubungkan ke WhatsApp...</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                                Sedang memproses sambungan dan menyiapkan socket WhatsApp. Proses ini memerlukan waktu beberapa saat.
                            </p>
                        </div>
                    </div>

                    <!-- CASE 3: SCAN QR STATE -->
                    <div v-else-if="qrCode" class="flex flex-col items-center justify-center p-6 rounded-2xl bg-amber-50/40 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-900/30 text-center space-y-4">
                        <div class="space-y-1">
                            <h3 class="text-sm font-bold text-amber-900 dark:text-amber-300 flex items-center justify-center gap-1.5">
                                <QrCode class="h-4 w-4" />
                                Scan QR Code WhatsApp
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Buka aplikasi WhatsApp di HP &rarr; Perangkat Tertaut &rarr; Tautkan Perangkat</p>
                        </div>

                        <div class="p-3 bg-white rounded-2xl shadow-md border border-slate-200">
                            <img :src="qrCode" alt="Scan QR Code WA" class="w-56 h-56 object-contain" />
                        </div>

                        <div class="text-[11px] text-amber-700 dark:text-amber-400 animate-pulse font-medium">
                            Kode QR diperbarui secara realtime. QR akan otomatis hilang setelah berhasil di-scan.
                        </div>
                    </div>

                    <!-- CASE 4: SERVER OFFLINE STATE -->
                    <div v-else-if="gatewayStatus === 'offline'" class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-center space-y-3">
                        <div class="h-10 w-10 rounded-full bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
                            <AlertCircle class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Server WA Gateway Offline</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                                <template v-if="driver === 'local'">
                                    Server WA Gateway lokal di port 3000 belum berjalan. Jalankan command <code>npm start</code> di folder <code>wa-gateway</code>.
                                </template>
                                <template v-else>
                                    Layanan WhatsApp Gateway saat ini tidak dapat dihubungi. Silakan hubungi tim administrator sistem untuk bantuan lebih lanjut.
                                </template>
                            </p>
                        </div>

                        <div v-if="driver === 'local'" class="pt-2">
                            <code class="text-xs bg-slate-900 text-emerald-400 px-3 py-1.5 rounded-lg font-mono inline-block">
                                cd wa-gateway && npm start
                            </code>
                        </div>
                    </div>

                    <!-- CASE 5: DISCONNECTED (INITIALIZING / WAITING FOR QR) -->
                    <div v-else class="flex flex-col items-center justify-center p-8 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-center space-y-4">
                        <div class="h-12 w-12 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400 flex items-center justify-center mx-auto">
                            <RefreshCw class="h-6 w-6 animate-spin text-slate-400 dark:text-slate-500" />
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Memulai WhatsApp Gateway...</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                                Menyiapkan instansi WhatsApp Gateway dan menunggu kode QR dari server...
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Uji Coba Pesan WA -->
            <Modal :show="showTestModal" @close="showTestModal = false" max-width="md">
                <div class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900">
                    <!-- Solid Emerald Sticky Header (No X button) -->
                    <div class="bg-emerald-600 dark:bg-emerald-950/90 text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-10 shrink-0 border-b border-emerald-500/30 dark:border-emerald-800/50 shadow-sm">
                        <div class="flex items-center gap-3 pr-2">
                            <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                                <Send class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-white leading-tight">
                                    Uji Coba Pesan WhatsApp
                                </h3>
                                <p class="text-xs text-emerald-100/90 dark:text-emerald-200/90 mt-0.5 font-medium">
                                    Kirim pesan uji coba ke nomor tujuan
                                </p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="sendTestMessage" class="flex flex-col flex-1 justify-between min-h-0">
                        <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                            <!-- Banner Informasi Uji Coba -->
                            <div class="p-3.5 sm:p-4 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/60 dark:border-emerald-900/50 text-emerald-900 dark:text-emerald-200 text-xs leading-relaxed flex items-start gap-2.5">
                                <Radio class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5 animate-pulse" />
                                <div>
                                    <span class="font-bold">Pengujian Konektivitas Gateway:</span>
                                    <span class="font-normal block mt-0.5 text-emerald-800 dark:text-emerald-300">Fitur ini digunakan untuk memastikan server WhatsApp Gateway aktif dan dapat mengirimkan notifikasi pesan otomatis secara realtime ke nomor tujuan.</span>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Nomor WA Tujuan <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    v-model="testForm.phone"
                                    type="text" 
                                    placeholder="Contoh: 081234567890"
                                    required
                                    class="w-full px-3.5 py-2.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none transition duration-150"
                                />
                                <div v-if="testForm.errors.phone" class="text-[10px] text-red-500 font-semibold mt-1">{{ testForm.errors.phone }}</div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Isi Pesan Uji Coba <span class="text-red-500">*</span>
                                </label>
                                <textarea 
                                    v-model="testForm.message"
                                    rows="5"
                                    required
                                    class="w-full p-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none transition duration-150 leading-relaxed min-h-[120px]"
                                ></textarea>
                                <div v-if="testForm.errors.message" class="text-[10px] text-red-500 font-semibold mt-1">{{ testForm.errors.message }}</div>
                            </div>
                        </div>

                        <!-- Sticky Action Footer -->
                        <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-end gap-3 sticky bottom-0 z-10 shrink-0">
                            <SecondaryButton type="button" @click="showTestModal = false" class="h-11 px-5">Batal</SecondaryButton>
                            <PrimaryButton type="submit" :disabled="testForm.processing" class="h-11 px-6 !bg-emerald-600 hover:!bg-emerald-500 font-bold">
                                {{ testForm.processing ? 'Mengirim...' : 'Kirim Pesan Uji Coba' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- Modal Konfirmasi Putuskan WhatsApp -->
            <Modal :show="showDisconnectModal" @close="cancelLogout" max-width="md">
                <div class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900">
                    <!-- Solid Rose Sticky Header (No X button) -->
                    <div class="bg-rose-600 dark:bg-rose-950/90 text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-10 shrink-0 border-b border-rose-500/30 dark:border-rose-800/50 shadow-sm">
                        <div class="flex items-center gap-3 pr-2">
                            <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                                <AlertTriangle class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-white leading-tight">
                                    Putuskan Perangkat WhatsApp?
                                </h3>
                                <p class="text-xs text-rose-100/90 dark:text-rose-200/90 mt-0.5 font-medium">
                                    Konfirmasi pemutusan koneksi sesi WhatsApp
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                            Apakah Anda yakin ingin memutuskan koneksi perangkat WhatsApp ini? Notifikasi otomatis sistem tidak akan terkirim sampai Anda melakukan scan QR Code kembali.
                        </p>
                    </div>

                    <!-- Sticky Action Footer -->
                    <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-end gap-3 sticky bottom-0 z-10 shrink-0">
                        <SecondaryButton type="button" @click="cancelLogout" class="h-11 px-5">Batal</SecondaryButton>
                        <button
                            type="button"
                            @click="handleLogout"
                            :disabled="isDisconnecting"
                            class="h-11 px-6 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow-sm inline-flex items-center justify-center gap-2 transition disabled:opacity-50 cursor-pointer border-0"
                        >
                            <LogOut class="h-4 w-4" />
                            <span>{{ isDisconnecting ? 'Memutuskan...' : 'Ya, Putuskan Perangkat' }}</span>
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
