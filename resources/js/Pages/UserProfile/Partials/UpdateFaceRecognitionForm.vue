<script setup>
import { ref, computed, getCurrentInstance, nextTick } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { ScanFace, CheckCircle2, AlertCircle, Trash2, X, RefreshCw, CheckCircle } from '@lucide/vue';

let faceapi = null;

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
});

const { proxy } = getCurrentInstance();
const pageAuthUser = usePage().props.auth.user;
const currentUser = computed(() => props.user || pageAuthUser);

const hasFaceScan = computed(() => {
    const desc = currentUser.value?.face_descriptor;
    return Array.isArray(desc) && desc.length > 0;
});

// Modal & Camera Scanner States
const isFaceModalOpen = ref(false);
const modelsLoaded = ref(false);
const isCameraActive = ref(false);
const scanStatus = ref('idle'); // idle, loading, scanning, success, failed
const errorMessage = ref('');
const isFaceDetected = ref(false);

const video = ref(null);
const canvas = ref(null);
let stream = null;
let detectionLoop = null;

const startFaceScan = async () => {
    errorMessage.value = '';
    scanStatus.value = 'loading';
    isFaceModalOpen.value = true;

    try {
        if (!faceapi) {
            faceapi = await import('@vladmandic/face-api');
        }
        if (!modelsLoaded.value) {
            try {
                await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
                await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
                await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
                modelsLoaded.value = true;
            } catch (modelErr) {
                console.error("Failed to load face-api models:", modelErr);
                throw new Error("Gagal memuat model pengenal wajah dari server.");
            }
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }
            });
        } catch (camErr) {
            console.error("Failed to access camera:", camErr);
            let msg = "Gagal mengakses kamera. Pastikan Anda telah memberikan izin akses kamera pada browser.";
            if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                msg += " PENTING: Akses kamera membutuhkan koneksi aman (HTTPS).";
            }
            throw new Error(msg);
        }

        isCameraActive.value = true;
        await nextTick();

        if (video.value) {
            video.value.srcObject = stream;
            if (video.value.readyState < 1) {
                await new Promise((resolve) => {
                    video.value.onloadedmetadata = () => resolve();
                });
            }
            await video.value.play();
            scanStatus.value = 'scanning';
            triggerScanLoop();
        }
    } catch (err) {
        errorMessage.value = err.message || 'Gagal memulai kamera rekam wajah.';
        scanStatus.value = 'failed';
    }
};

const triggerScanLoop = async () => {
    if (!isCameraActive.value || scanStatus.value === 'success') return;

    try {
        const detection = await faceapi.detectSingleFace(
            video.value,
            new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.70 })
        ).withFaceLandmarks().withFaceDescriptor();

        if (canvas.value && video.value) {
            const displaySize = {
                width: video.value.clientWidth || video.value.offsetWidth || 320,
                height: video.value.clientHeight || video.value.offsetHeight || 320
            };
            faceapi.matchDimensions(canvas.value, displaySize);
            const ctx = canvas.value.getContext('2d');
            ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);

            if (detection) {
                const resizedDetection = faceapi.resizeResults(detection, displaySize);
                faceapi.draw.drawDetections(canvas.value, resizedDetection);
                faceapi.draw.drawFaceLandmarks(canvas.value, resizedDetection);
            }
        }

        if (detection) {
            isFaceDetected.value = true;
            scanStatus.value = 'success';
            const faceDescriptor = Array.from(detection.descriptor);
            stopCamera();

            // Save descriptor to backend
            router.patch(route('profile.update-face'), {
                face_descriptor: faceDescriptor,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    proxy.$toast('Sampel wajah biometrik berhasil diperbarui.', 'success');
                    setTimeout(() => {
                        closeFaceModal();
                    }, 600);
                },
                onError: () => {
                    scanStatus.value = 'failed';
                    errorMessage.value = 'Gagal menyimpan sampel wajah ke server.';
                }
            });
        } else {
            isFaceDetected.value = false;
            detectionLoop = setTimeout(triggerScanLoop, 200);
        }
    } catch (err) {
        console.error("Failed to detect face:", err);
        isFaceDetected.value = false;
        scanStatus.value = 'failed';
        errorMessage.value = 'Terjadi kesalahan saat memproses pindaian wajah.';
        if (canvas.value) {
            canvas.value.getContext('2d').clearRect(0, 0, canvas.value.width, canvas.value.height);
        }
        detectionLoop = setTimeout(triggerScanLoop, 2000);
    }
};

const stopCamera = () => {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    if (video.value) {
        video.value.srcObject = null;
    }
    isCameraActive.value = false;
    if (detectionLoop) {
        clearTimeout(detectionLoop);
        detectionLoop = null;
    }
    if (canvas.value) {
        const ctx = canvas.value.getContext('2d');
        ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
    }
};

const closeFaceModal = () => {
    if (detectionLoop) {
        clearTimeout(detectionLoop);
        detectionLoop = null;
    }
    stopCamera();
    isFaceModalOpen.value = false;
};

const deleteFaceScan = () => {
    proxy.$swal({
        title: 'Hapus Rekam Wajah?',
        text: 'Anda tidak dapat lagi menggunakan Login Wajah sampai Anda melakukan rekam wajah kembali.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('profile.delete-face'), {
                preserveScroll: true,
                onSuccess: () => {
                    proxy.$toast('Sampel rekam wajah berhasil dihapus.', 'success');
                }
            });
        }
    });
};
</script>

<template>
    <section class="space-y-6">
        <div>
            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                Data Rekam Wajah (Biometrik Login)
            </h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Kelola sampel wajah biometrik untuk login instan tanpa mengetik kata sandi.</p>
        </div>

        <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="h-12 w-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/50 dark:border-emerald-800/40 flex items-center justify-center shrink-0 text-emerald-600 dark:text-emerald-400">
                        <ScanFace class="h-6 w-6" />
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h4 class="text-xs font-bold text-slate-800 dark:text-white uppercase">Status Biometrik Wajah</h4>
                            <span v-if="hasFaceScan" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/50 dark:border-emerald-800/40">
                                <CheckCircle2 class="h-3 w-3" /> Terdaftar
                            </span>
                            <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200/50 dark:border-amber-800/40">
                                <AlertCircle class="h-3 w-3" /> Belum Terdaftar
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            {{ hasFaceScan 
                                ? 'Sampel wajah Anda sudah terdaftar dan siap digunakan untuk Login Wajah.' 
                                : 'Anda belum melakukan pendaftaran sampel wajah biometrik.' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button
                        type="button"
                        @click="startFaceScan"
                        class="flex-1 sm:flex-none px-4 h-10 bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 font-bold text-xs rounded-xl inline-flex items-center justify-center gap-2 transition duration-150 shadow-sm border-0"
                    >
                        <ScanFace class="h-4 w-4" />
                        <span>{{ hasFaceScan ? 'Scan Ulang Wajah' : 'Mulai Scan Wajah' }}</span>
                    </button>
                    <button
                        v-if="hasFaceScan"
                        type="button"
                        @click="deleteFaceScan"
                        class="h-10 w-10 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 rounded-xl border border-rose-200/60 dark:border-rose-800/40 inline-flex items-center justify-center shrink-0 transition"
                        title="Hapus Rekam Wajah"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- FULLSCREEN FUTURISTIC FACE REGISTRATION SCANNER MODAL (EXACTLY MATCHING REGISTER/LOGIN) -->
        <Teleport to="body">
            <div v-if="isFaceModalOpen" class="fixed inset-0 z-50 flex flex-col items-center justify-center p-6 bg-white dark:bg-white text-slate-900 dark:text-slate-900">
                <!-- Close button -->
                <button type="button" @click="closeFaceModal" class="absolute top-6 right-6 p-3 text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:bg-slate-100 rounded-full transition outline-none cursor-pointer">
                    <X class="h-6 w-6" />
                </button>

                <!-- Camera stream area (Circle) -->
                <div class="relative w-72 h-72 sm:w-[24rem] sm:h-[24rem] md:w-[26rem] md:h-[26rem] aspect-square rounded-full overflow-hidden border-4 border-slate-200 dark:border-slate-200 shadow-sm flex items-center justify-center bg-black mx-auto shrink-0">
                    <!-- Video tag -->
                    <video 
                        ref="video" 
                        v-show="scanStatus !== 'loading'"
                        class="absolute inset-0 w-full h-full object-cover scale-x-[-1] rounded-full" 
                        playsinline
                        muted
                    ></video>

                    <!-- Overlay canvas -->
                    <canvas 
                        ref="canvas" 
                        class="absolute inset-0 w-full h-full object-cover scale-x-[-1] z-10"
                    ></canvas>

                    <!-- Circular Laser Scan Ring -->
                    <div v-if="scanStatus === 'scanning'" class="absolute inset-0 border-2 border-cyan-400 rounded-full animate-ping opacity-25 z-20"></div>

                    <!-- Success Overlay -->
                    <div v-if="scanStatus === 'success'" class="absolute inset-0 bg-slate-950/85 backdrop-blur-sm flex flex-col items-center justify-center z-30 transition-all duration-300">
                        <div class="p-3 bg-emerald-500/10 rounded-full border border-emerald-500/20 mb-4">
                            <CheckCircle class="h-10 w-10 text-emerald-400" />
                        </div>
                        <span class="text-[10px] font-medium tracking-widest text-emerald-400/90 uppercase">Rekam Wajah Berhasil</span>
                        <span class="text-lg font-semibold text-white mt-1.5">{{ currentUser.name }}</span>
                    </div>

                    <!-- Loading / Init state -->
                    <div v-if="scanStatus === 'loading'" class="absolute inset-0 bg-slate-950/95 flex flex-col items-center justify-center text-slate-400 z-30">
                        <RefreshCw class="h-8 w-8 animate-spin mb-3 text-emerald-500" />
                        <span class="text-xs tracking-wide">Memuat AI & Kamera...</span>
                    </div>
                </div>

                <!-- Status & Error Alert -->
                <div class="w-full mt-6 text-center min-h-[28px] max-w-sm">
                    <p v-if="scanStatus === 'scanning'" class="text-xs font-bold text-emerald-600 dark:text-emerald-600 animate-pulse">
                        {{ isFaceDetected ? 'Wajah Terdeteksi! Menyimpan...' : 'Posisikan wajah Anda tepat di tengah lingkaran...' }}
                    </p>
                    <p v-else-if="scanStatus === 'failed'" class="text-xs font-bold text-rose-600 dark:text-rose-600">
                        {{ errorMessage || 'Wajah tidak terdeteksi.' }}
                    </p>
                </div>

                <!-- Actions inside modal -->
                <div class="mt-8 flex items-center space-x-3">
                    <button 
                        v-if="scanStatus === 'failed'"
                        @click="startFaceScan" 
                        type="button" 
                        class="px-6 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-sm transition"
                    >
                        Coba Lagi
                    </button>
                    <button 
                        @click="closeFaceModal" 
                        type="button" 
                        class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 font-bold rounded-xl text-sm transition"
                    >
                        Batal
                    </button>
                </div>
            </div>
        </Teleport>
    </section>
</template>
