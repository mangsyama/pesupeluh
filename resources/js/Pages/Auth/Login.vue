<script setup>
import { ref, onBeforeUnmount, getCurrentInstance, nextTick } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ScanFace, RefreshCw, CheckCircle, X, Eye, EyeOff } from '@lucide/vue';
import * as faceapi from '@vladmandic/face-api';
import axios from 'axios';

const showPassword = ref(false);

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const { proxy } = getCurrentInstance();

const video = ref(null);
const canvas = ref(null);
const isStreaming = ref(false);
const isScanning = ref(false);
const scanStatus = ref('idle'); // idle, loading, scanning, success, failed
const errorMessage = ref('');
const modelsLoaded = ref(false);
const isFaceDetected = ref(false);
const isFaceModalOpen = ref(false);
const matchedUserName = ref('');

let stream = null;
let detectionLoop = null;

const startCamera = async () => {
    errorMessage.value = '';
    scanStatus.value = 'loading';
    isFaceModalOpen.value = true;
    matchedUserName.value = '';
    
    try {
        // Load face-api models from public/models folder
        if (!modelsLoaded.value) {
            try {
                await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
                await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
                await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
                modelsLoaded.value = true;
            } catch (modelErr) {
                console.error("Failed to load face-api models:", modelErr);
                throw new Error(proxy.__("Failed to load face-api models from server. Ensure model files are complete on the server."));
            }
        }

        // Access camera
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }
            });
        } catch (camErr) {
            console.error("Failed to access camera:", camErr);
            let msg = proxy.__("Failed to access camera. Please ensure you have granted camera access permission.");
            if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                msg += " " + proxy.__("IMPORTANT: Camera (getUserMedia) requires a secure connection (HTTPS or localhost).");
            }
            throw new Error(msg);
        }
        
        // Wait for Vue to render the DOM and resolve the video ref inside modal
        await nextTick();
        
        if (video.value) {
            video.value.srcObject = stream;
            
            // Wait for video metadata to load
            if (video.value.readyState < 1) {
                await new Promise((resolve) => {
                    video.value.onloadedmetadata = () => resolve();
                });
            }
            
            await video.value.play();
            isStreaming.value = true;
            scanStatus.value = 'scanning';
            
            // Start detection loop automatically
            triggerScanLoop();
        } else {
            console.error("Video element not found in DOM!");
            throw new Error(proxy.__("Failed to initialize camera scanner container."));
        }
    } catch (err) {
        errorMessage.value = err.message || proxy.__('Failed to start face module.');
        scanStatus.value = 'failed';
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
    isStreaming.value = false;
    isScanning.value = false;
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
    isFaceModalOpen.value = false;
    scanStatus.value = 'idle';
    errorMessage.value = '';
    isFaceDetected.value = false;
    stopCamera();
};

const triggerScanLoop = async () => {
    if (!isStreaming.value || scanStatus.value === 'success') return;
    
    try {
        const detection = await faceapi.detectSingleFace(
            video.value,
            new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.70 })
        ).withFaceLandmarks().withFaceDescriptor();
        
        // Draw real-time face bounding box and landmarks on overlay canvas
        if (canvas.value && video.value) {
            const dims = faceapi.matchDimensions(canvas.value, video.value, true);
            const ctx = canvas.value.getContext('2d');
            ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
            
            if (detection) {
                const resizedDetection = faceapi.resizeResults(detection, dims);
                faceapi.draw.drawDetections(canvas.value, resizedDetection);
                faceapi.draw.drawFaceLandmarks(canvas.value, resizedDetection);
            }
        }
        
        if (detection) {
                isFaceDetected.value = true;
                scanStatus.value = 'loading';
                
                // Use async/await to avoid callback nesting and reduce UI freeze
                (async () => {
                  try {
                    const response = await axios.post('/login-face', {
                      face_descriptor: Array.from(detection.descriptor)
                    });
                    if (response.data.success) {
                      scanStatus.value = 'success';
                      matchedUserName.value = response.data.name;
                      // Clear any pending detection loop timer
                      if (detectionLoop) { clearTimeout(detectionLoop); detectionLoop = null; }
                      stopCamera(); // Stop camera immediately
                      // Close modal and navigate quickly (short timeout)
                      setTimeout(() => {
                        closeFaceModal();
                        router.visit(route('dashboard'));
                      }, 500);
                    }
                  } catch (err) {
                    console.error('Failed to match face:', err);
                    isFaceDetected.value = false;
                    scanStatus.value = 'failed';
                    errorMessage.value = err.response?.data?.message || proxy.__('Face does not match any registered accounts.');
                    
                    // Automatically retry scan after 2.5 seconds if still streaming and status is failed
                    detectionLoop = setTimeout(() => {
                      if (isStreaming.value && scanStatus.value === 'failed') {
                        scanStatus.value = 'scanning';
                        errorMessage.value = '';
                        triggerScanLoop();
                      }
                    }, 2500);
                  }
                })();
                // Clear canvas on error
                if (canvas.value) {
                    canvas.value.getContext('2d').clearRect(0, 0, canvas.value.width, canvas.value.height);
                }

        } else {
            isFaceDetected.value = false;
            // No face found, run loop again after 200ms for more responsiveness
            detectionLoop = setTimeout(triggerScanLoop, 200);
        }
    } catch (err) {
        console.error("Error in detection loop:", err);
        isFaceDetected.value = false;
        scanStatus.value = 'failed';
        errorMessage.value = proxy.__('An error occurred while processing the face image.');
        
        if (canvas.value) {
            canvas.value.getContext('2d').clearRect(0, 0, canvas.value.width, canvas.value.height);
        }

        detectionLoop = setTimeout(() => {
            if (isStreaming.value && scanStatus.value !== 'success') {
                scanStatus.value = 'scanning';
                triggerScanLoop();
            }
        }, 2000);
    }
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

onBeforeUnmount(() => {
    stopCamera();
});
</script>

<template>
    <GuestLayout>
        <Head :title="__('global.profile')" />

        <!-- HEADER FORM -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-slate-950 dark:text-white tracking-tight">
                {{ __('auth.login.title') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                {{ __("auth.login.subtitle") }}
                <Link :href="route('register')" class="font-bold text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 underline ms-1">
                    {{ __('auth.login.create_account_link') }}
                </Link>
            </p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-emerald-600">
            {{ status }}
        </div>

        <!-- FORM CREDENTIALS -->
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Username Input Field -->
            <div>
                <label for="username" class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">
                    {{ __('global.username') }}
                </label>
                <input
                    id="username"
                    type="text"
                    v-model="form.username"
                    required
                    autofocus
                    autocomplete="username"
                    :placeholder="__('auth.register.username_placeholder')"
                    class="border-b-2 border-slate-200 dark:border-slate-800 bg-transparent py-2.5 px-0 focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-0 outline-none w-full border-t-0 border-l-0 border-r-0 rounded-none transition duration-150 text-slate-900 dark:text-white text-base"
                />
                <InputError class="mt-1" :message="form.errors.username" />
            </div>

            <!-- Password Input Field -->
            <div>
                <label for="password" class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">
                    {{ __('global.password') }}
                </label>
                <div class="relative">
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="border-b-2 border-slate-200 dark:border-slate-800 bg-transparent py-2.5 pe-10 ps-0 focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-0 outline-none w-full border-t-0 border-l-0 border-r-0 rounded-none transition duration-150 text-slate-900 dark:text-white text-base"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none"
                    >
                        <Eye v-if="!showPassword" class="h-5 w-5" />
                        <EyeOff v-else class="h-5 w-5" />
                    </button>
                </div>
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <!-- Remember Me Checkbox -->
            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer select-none">
                    <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 bg-transparent" />
                    <span class="ms-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400">
                        {{ __('global.remember_me') }}
                    </span>
                </label>
            </div>

            <!-- Action buttons -->
            <div class="space-y-3 pt-2">
                <button
                    type="submit"
                    class="w-full text-center py-4 bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-slate-200 text-white dark:text-slate-950 font-bold rounded-xl text-base tracking-wide transition duration-150 select-none outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 focus:ring-offset-white dark:focus:ring-offset-slate-900 flex items-center justify-center gap-3"
                    :class="{ 'opacity-25 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                    :aria-busy="form.processing"
                >
                    <RefreshCw v-if="form.processing" class="h-4 w-4 animate-spin text-white dark:text-slate-950" />
                    <span>
                        {{ form.processing ? __('auth.login.loading') : __('auth.login.log_in_btn') }}
                    </span>
                </button>

                <!-- Face Scanner Switcher -->
                <button
                    @click="startCamera"
                    type="button"
                    class="w-full inline-flex items-center justify-center px-6 py-4 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition duration-150 text-base"
                >
                    <ScanFace class="h-6 w-6 me-2.5 text-indigo-600 dark:text-indigo-400" />
                    {{ __('auth.login.face_camera_btn') }}
                </button>
            </div>

            <!-- Forgot password -->
            <div class="text-center pt-2">
                <span class="text-sm text-slate-500 dark:text-slate-400">
                    {{ __('auth.login.forgot_password_link') }}
                </span>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-bold text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 underline ms-1"
                >
                    {{ __('auth.login.click_here') }}
                </Link>
            </div>
        </form>

        <!-- FUTURISTIC FACE SCANNER MODAL -->
        <div v-if="isFaceModalOpen" class="fixed inset-0 z-50 flex flex-col items-center justify-center p-6 bg-white dark:bg-white text-slate-900 dark:text-slate-900">
            <!-- Close button -->
            <button type="button" @click="closeFaceModal" class="absolute top-6 right-6 p-3 text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:bg-slate-100 rounded-full transition outline-none cursor-pointer">
                <X class="h-6 w-6" />
            </button>

            <!-- Camera stream area (Circle) -->
            <div class="relative w-80 h-80 sm:w-[26rem] sm:h-[26rem] md:w-[30rem] md:h-[30rem] rounded-full overflow-hidden border-4 border-slate-200 dark:border-slate-200 shadow-sm flex items-center justify-center bg-black mx-auto">
                <!-- Video tag -->
                <video 
                    ref="video" 
                    v-show="scanStatus !== 'loading'"
                    class="absolute inset-0 w-full h-full object-cover scale-x-[-1]" 
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
                    <span class="text-[10px] font-medium tracking-widest text-emerald-400/90 uppercase">{{ __('auth.login.title') }}</span>
                    <span class="text-lg font-semibold text-white mt-1.5">{{ matchedUserName }}</span>
                </div>

                <!-- Loading / Init state -->
                <div v-if="scanStatus === 'loading'" class="absolute inset-0 bg-slate-950/95 flex flex-col items-center justify-center text-slate-400 z-30">
                    <RefreshCw class="h-8 w-8 animate-spin mb-3 text-indigo-500" />
                    <span class="text-xs tracking-wide">{{ __('auth.login.matching') }}</span>
                </div>
            </div>

            <!-- Status & Error Alert -->
            <div class="w-full mt-6 text-center min-h-[28px] max-w-sm">
                <p v-if="scanStatus === 'scanning'" class="text-xs font-bold text-indigo-600 dark:text-indigo-600 animate-pulse">
                    {{ isFaceDetected ? __('auth.login.face_detected') : __('auth.login.looking_for_face') }}
                </p>
                <p v-else-if="scanStatus === 'failed'" class="text-xs font-bold text-rose-600 dark:text-rose-600">
                    {{ errorMessage || __('auth.login.error_not_match') }}
                </p>
            </div>

            <!-- Cancel Button -->
            <div class="mt-8">
                <button 
                    @click="closeFaceModal" 
                    type="button" 
                    class="px-6 py-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-100 dark:hover:bg-slate-200 text-slate-700 dark:text-slate-700 border border-slate-200 dark:border-slate-200 font-bold rounded-xl text-sm transition"
                >
                    {{ __('global.cancel') }}
                </button>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.scanner-laser {
    position: absolute;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, rgba(34, 211, 238, 0) 0%, rgba(34, 211, 238, 0.8) 50%, rgba(34, 211, 238, 0) 100%);
    box-shadow: 0 0 10px rgba(34, 211, 238, 0.8), 0 0 20px rgba(34, 211, 238, 0.4);
    z-index: 20;
    pointer-events: none;
    animation: scan-line 3s ease-in-out infinite;
}

.scanner-grid {
    background-size: 20px 20px;
    background-image: 
        linear-gradient(to right, rgba(34, 211, 238, 0.15) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(34, 211, 238, 0.15) 1px, transparent 1px);
}

@keyframes scan-line {
    0% { top: 0%; }
    50% { top: 98%; }
    100% { top: 0%; }
}

@keyframes spin-slow {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.animate-spin-slow {
    animation: spin-slow 12s linear infinite;
}

@keyframes pulse-slow {
    0%, 100% { transform: scale(1); opacity: 0.3; }
    50% { transform: scale(1.05); opacity: 0.6; }
}

.animate-pulse-slow {
    animation: pulse-slow 3s ease-in-out infinite;
}

@keyframes pulse-normal {
    0%, 100% { opacity: 0.4; }
    50% { opacity: 0.8; }
}

.animate-pulse-normal {
    animation: pulse-normal 2s ease-in-out infinite;
}

@keyframes bounce-slow {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

.animate-bounce-slow {
    animation: bounce-slow 2s ease-in-out infinite;
}
</style>
