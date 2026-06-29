<script setup>
import { ref, getCurrentInstance, nextTick, onBeforeUnmount } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ScanFace, RefreshCw, CheckCircle, X, Camera, UploadCloud, User, Image, Eye, EyeOff } from '@lucide/vue';
import * as faceapi from '@vladmandic/face-api';
import { compressImage } from '@/Utils/imageCompressor';

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    name: '',
    nip: '',
    username: '',
    email: '',
    phone_number: '',
    password: '',
    password_confirmation: '',
    face_descriptor: null,
    profile_photo: null,
});

// Photo registration logic
const photoMode = ref('upload'); // upload or camera
const isPhotoCameraActive = ref(false);
const isPhotoCameraLoading = ref(false);
const photoStream = ref(null);
const photoVideo = ref(null);
const photoCanvas = ref(null);
const photoPreview = ref(null);

const startPhotoCamera = async () => {
    photoPreview.value = null;
    form.profile_photo = null;
    isPhotoCameraActive.value = true;
    isPhotoCameraLoading.value = true;
    try {
        await nextTick();
        photoStream.value = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }
        });
        await nextTick();
        if (photoVideo.value) {
            photoVideo.value.srcObject = photoStream.value;
            // Wait for video metadata to be fully loaded
            await new Promise((resolve) => {
                photoVideo.value.onloadedmetadata = () => {
                    resolve();
                };
            });
            await photoVideo.value.play();
            isPhotoCameraLoading.value = false;
        }
    } catch (err) {
        isPhotoCameraActive.value = false;
        isPhotoCameraLoading.value = false;
        console.error("Failed to open camera:", err);
        alert(proxy.__("Failed to access camera. Please ensure you have granted camera access permission."));
    }
};

const stopPhotoCamera = () => {
    if (photoStream.value) {
        photoStream.value.getTracks().forEach(track => track.stop());
        photoStream.value = null;
    }
    isPhotoCameraActive.value = false;
    isPhotoCameraLoading.value = false;
};

const capturePhoto = () => {
    if (photoVideo.value && photoCanvas.value) {
        const videoEl = photoVideo.value;
        const canvasEl = photoCanvas.value;
        canvasEl.width = videoEl.videoWidth;
        canvasEl.height = videoEl.videoHeight;
        const ctx = canvasEl.getContext('2d');
        
        ctx.translate(canvasEl.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(videoEl, 0, 0, canvasEl.width, canvasEl.height);
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        
        // Compress captured image with 0.8 quality
        const dataUrl = canvasEl.toDataURL('image/jpeg', 0.8);
        photoPreview.value = dataUrl;
        form.profile_photo = dataUrl;
        stopPhotoCamera();
    }
};

const retakePhoto = () => {
    photoPreview.value = null;
    form.profile_photo = null;
    startPhotoCamera();
};

const handlePhotoFileChange = async (e) => {
    const file = e.target.files[0];
    if (file) {
        if (!file.type.startsWith('image/')) {
            proxy.$swal({
                title: 'File tidak didukung',
                text: 'Harap hanya mengunggah file gambar (JPEG, PNG, JPG, WEBP).',
                icon: 'warning',
                confirmButtonColor: '#4f46e5',
            });
            e.target.value = ''; // clear input
            form.profile_photo = null;
            photoPreview.value = null;
            return;
        }
        try {
            // Compress uploaded image with 1200px max bound and 0.8 quality
            const compressedDataUrl = await compressImage(file, 1200, 1200, 0.8);
            form.profile_photo = compressedDataUrl;
            photoPreview.value = compressedDataUrl;
        } catch (err) {
            console.error("Image compression failed:", err);
            // Fallback to original file
            form.profile_photo = file;
            const reader = new FileReader();
            reader.onload = (event) => {
                photoPreview.value = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    }
};

const setPhotoMode = (mode) => {
    photoMode.value = mode;
    stopPhotoCamera();
    photoPreview.value = null;
    form.profile_photo = null;
    if (mode === 'camera') {
        startPhotoCamera();
    }
};

const { proxy } = getCurrentInstance();

const modelsLoaded = ref(false);
const isCameraActive = ref(false);
const scanStatus = ref('idle'); // idle, loading, scanning, success, failed
const errorMessage = ref('');
const hasFaceScan = ref(false);
const isFaceModalOpen = ref(false);
const isFaceDetected = ref(false);

const video = ref(null);
const canvas = ref(null);
let stream = null;
let detectionLoop = null;

const loadModelsAndStart = async () => {
    errorMessage.value = '';
    scanStatus.value = 'loading';
    isFaceModalOpen.value = true;
    
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

        isCameraActive.value = true;
        await nextTick();

        if (video.value) {
            video.value.srcObject = stream;
            
            // Wait for metadata
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
        errorMessage.value = err.message || proxy.__('Failed to start face module.');
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
            form.face_descriptor = Array.from(detection.descriptor);
            scanStatus.value = 'success';
            hasFaceScan.value = true;
            // Clear any pending detection loop timer
            if (detectionLoop) { clearTimeout(detectionLoop); detectionLoop = null; }
            stopCamera(); // Stop camera immediately
            // Close modal quickly (short timeout)
            setTimeout(() => {
                closeFaceModal();
            }, 500);
        } else {
            isFaceDetected.value = false;
            // No face found, run loop again after 200ms
            detectionLoop = setTimeout(triggerScanLoop, 200);
        }
    } catch (err) {
        console.error("Failed to detect face:", err);
        isFaceDetected.value = false;
        scanStatus.value = 'failed';
        errorMessage.value = proxy.__('An error occurred while processing the face image.');
        
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
    isFaceModalOpen.value = false;
    scanStatus.value = 'idle';
    errorMessage.value = '';
    isFaceDetected.value = false;
    stopCamera();
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

onBeforeUnmount(() => {
    stopCamera();
    stopPhotoCamera();
});
</script>

<template>
    <GuestLayout>
        <Head :title="__('global.register')" />

        <!-- Form Title -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-slate-950 dark:text-white tracking-tight">
                {{ __('auth.register.title') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                {{ __('auth.register.subtitle') }}
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Name Input Field -->
            <div>
                <label for="name" class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">
                    {{ __('global.name') }}
                </label>
                <input
                    id="name"
                    type="text"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    :placeholder="__('auth.register.name_placeholder')"
                    class="border-b-2 border-slate-200 dark:border-slate-800 bg-transparent py-2.5 px-0 focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-0 outline-none w-full border-t-0 border-l-0 border-r-0 rounded-none transition duration-150 text-slate-900 dark:text-white text-base"
                />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <!-- NIP Input Field -->
            <div>
                <label for="nip" class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">
                    {{ __('auth.register.nip_label') }}
                </label>
                <input
                    id="nip"
                    type="text"
                    v-model="form.nip"
                    @input="form.nip = form.nip.replace(/\D/g, '')"
                    maxlength="18"
                    required
                    :placeholder="__('auth.register.nip_placeholder')"
                    class="border-b-2 border-slate-200 dark:border-slate-800 bg-transparent py-2.5 px-0 focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-0 outline-none w-full border-t-0 border-l-0 border-r-0 rounded-none transition duration-150 text-slate-900 dark:text-white text-base"
                />
                <InputError class="mt-1" :message="form.errors.nip" />
            </div>

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
                    :placeholder="__('auth.register.username_placeholder')"
                    class="border-b-2 border-slate-200 dark:border-slate-800 bg-transparent py-2.5 px-0 focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-0 outline-none w-full border-t-0 border-l-0 border-r-0 rounded-none transition duration-150 text-slate-900 dark:text-white text-base"
                />
                <InputError class="mt-1" :message="form.errors.username" />
            </div>

            <!-- Email Input Field -->
            <div>
                <label for="email" class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">
                    {{ __('global.email') }}
                </label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    :placeholder="__('auth.register.email_placeholder')"
                    class="border-b-2 border-slate-200 dark:border-slate-800 bg-transparent py-2.5 px-0 focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-0 outline-none w-full border-t-0 border-l-0 border-r-0 rounded-none transition duration-150 text-slate-900 dark:text-white text-base"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <!-- Phone Number Input Field -->
            <div>
                <label for="phone_number" class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">
                    {{ __('auth.register.phone_label') }}
                </label>
                <input
                    id="phone_number"
                    type="text"
                    v-model="form.phone_number"
                    @input="form.phone_number = form.phone_number.replace(/\D/g, '')"
                    maxlength="15"
                    required
                    :placeholder="__('auth.register.phone_placeholder')"
                    class="border-b-2 border-slate-200 dark:border-slate-800 bg-transparent py-2.5 px-0 focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-0 outline-none w-full border-t-0 border-l-0 border-r-0 rounded-none transition duration-150 text-slate-900 dark:text-white text-base"
                />
                <InputError class="mt-1" :message="form.errors.phone_number" />
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
                        autocomplete="new-password"
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

            <!-- Confirm Password Input Field -->
            <div>
                <label for="password_confirmation" class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">
                    {{ __('global.confirm_password') }}
                </label>
                <div class="relative">
                    <input
                        id="password_confirmation"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="border-b-2 border-slate-200 dark:border-slate-800 bg-transparent py-2.5 pe-10 ps-0 focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-0 outline-none w-full border-t-0 border-l-0 border-r-0 rounded-none transition duration-150 text-slate-900 dark:text-white text-base"
                    />
                    <button
                        type="button"
                        @click="showConfirmPassword = !showConfirmPassword"
                        class="absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none"
                    >
                        <Eye v-if="!showConfirmPassword" class="h-5 w-5" />
                        <EyeOff v-else class="h-5 w-5" />
                    </button>
                </div>
                <InputError class="mt-1" :message="form.errors.password_confirmation" />
            </div>

            <!-- Profile Photo (Required) Card -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl p-5 bg-slate-50 dark:bg-slate-900/50 space-y-4">
                <div>
                    <label class="text-sm font-bold text-slate-800 dark:text-slate-200 block mb-1">
                        {{ __('auth.register.profile_photo_title') }}
                    </label>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4">
                        {{ __('auth.register.profile_photo_desc') }}
                    </p>
                </div>

                <!-- Tab Segmented Controls -->
                <div class="flex bg-slate-200/50 dark:bg-slate-950/40 p-1 rounded-xl w-full">
                    <button 
                        @click="setPhotoMode('upload')"
                        type="button" 
                        :class="['flex-1 flex items-center justify-center px-3 py-2 text-xs font-bold rounded-lg transition duration-150', photoMode === 'upload' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-200']"
                    >
                        <UploadCloud class="h-3.5 w-3.5 inline-block me-1" />
                        {{ __('auth.register.upload_file_tab') }}
                    </button>
                    <button 
                        @click="setPhotoMode('camera')"
                        type="button" 
                        :class="['flex-1 flex items-center justify-center px-3 py-2 text-xs font-bold rounded-lg transition duration-150', photoMode === 'camera' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-200']"
                    >
                        <Camera class="h-3.5 w-3.5 inline-block me-1" />
                        {{ __('auth.register.take_photo_tab') }}
                    </button>
                </div>

                <!-- Photo Upload Mode -->
                <div v-if="photoMode === 'upload'" class="space-y-3">
                    <div class="border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-2 text-center hover:border-indigo-500/50 dark:hover:border-indigo-400/50 transition cursor-pointer relative overflow-hidden h-72 w-full max-w-md flex items-center justify-center bg-slate-100/50 dark:bg-slate-900/30 mx-auto">
                        <input 
                            type="file" 
                            accept="image/*"
                            @change="handlePhotoFileChange" 
                            class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-20"
                        />
                        
                        <div v-if="photoPreview" class="absolute inset-0 w-full h-full flex items-center justify-center p-2 bg-slate-900/5 dark:bg-slate-900/40">
                            <img :src="photoPreview" class="max-w-full max-h-full object-contain rounded-xl" />
                            <!-- Hover state for change file -->
                            <div class="absolute inset-0 bg-black/50 opacity-0 hover:opacity-100 transition flex flex-col items-center justify-center space-y-2 z-10">
                                <UploadCloud class="h-8 w-8 text-white animate-bounce-slow" />
                                <span class="text-xs font-bold text-white uppercase tracking-wider">{{ __('auth.register.change_photo') }}</span>
                            </div>
                        </div>
                        
                        <div v-else class="flex flex-col items-center justify-center space-y-2 py-6">
                            <UploadCloud class="h-8 w-8 text-slate-400" />
                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('auth.register.select_image_placeholder') }}</span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500">PNG, JPG, JPEG (Max. 5MB)</span>
                        </div>
                    </div>
                </div>

                <!-- Photo Camera Mode -->
                <div v-else-if="photoMode === 'camera'" class="space-y-3">
                    <!-- Active Camera View -->
                    <div v-if="isPhotoCameraActive" class="relative h-72 w-full max-w-md bg-black rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 flex items-center justify-center mx-auto">
                        <!-- Skeleton loading overlay -->
                        <div v-if="isPhotoCameraLoading" class="absolute inset-0 bg-slate-900/95 flex flex-col items-center justify-center text-slate-400 z-30 animate-pulse">
                            <Camera class="h-8 w-8 text-indigo-500 animate-bounce-slow mb-3" />
                            <span class="text-xs font-semibold tracking-wider text-slate-300">{{ __('auth.register.opening_camera') }}</span>
                        </div>
                        
                        <!-- Video and Canvas elements -->
                        <video 
                            ref="photoVideo" 
                            v-show="!isPhotoCameraLoading"
                            class="absolute inset-0 w-full h-full object-cover scale-x-[-1]" 
                            playsinline
                            muted
                        ></video>
                        <canvas ref="photoCanvas" class="hidden"></canvas>
                        
                        <!-- Overlay Capture Button -->
                        <button 
                            v-show="!isPhotoCameraLoading"
                            @click="capturePhoto" 
                            type="button"
                            class="absolute bottom-4 h-12 w-12 bg-white rounded-full border-4 border-indigo-500 flex items-center justify-center hover:scale-105 active:scale-95 transition z-20 shadow-lg"
                        >
                            <span class="h-4.5 w-4.5 bg-indigo-500 rounded-full"></span>
                        </button>
                    </div>

                    <!-- Captured Photo Preview View -->
                    <div v-else-if="photoPreview" class="relative h-72 w-full max-w-md bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden flex items-center justify-center mx-auto group">
                        <img :src="photoPreview" class="w-full h-full object-cover" />
                        <!-- Action Overlay -->
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center rounded-2xl">
                            <button 
                                @click="retakePhoto" 
                                type="button" 
                                class="px-4 py-2 bg-white rounded-xl text-slate-800 hover:bg-slate-100 font-bold transition shadow-sm text-sm"
                            >
                                {{ __('auth.register.retake_photo') }}
                            </button>
                        </div>
                    </div>
                </div>
                
                <InputError class="mt-1" :message="form.errors.profile_photo" />
            </div>

            <!-- Optional Face Registration Card -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl p-5 bg-slate-50 dark:bg-slate-900/50">
                <label class="text-sm font-bold text-slate-800 dark:text-slate-200 block mb-1">
                    {{ __('auth.register.face_login_title') }}
                </label>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 leading-relaxed">
                    {{ __('auth.register.face_login_desc') }}
                </p>

                <!-- Status indicator -->
                <div v-if="hasFaceScan" class="mb-4 flex items-center text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 p-3 rounded-xl border border-emerald-100 dark:border-emerald-900/30">
                    <CheckCircle class="h-5 w-5 me-2 shrink-0" />
                    <span>{{ __('auth.register.face_success_msg') }}</span>
                </div>

                <!-- Open scanner button -->
                <button
                    @click="loadModelsAndStart"
                    type="button"
                    class="w-full inline-flex items-center justify-center px-4 py-3 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition duration-150 text-sm"
                >
                    <ScanFace class="h-5 w-5 me-2 text-indigo-600 dark:text-indigo-400" />
                    {{ hasFaceScan ? __('auth.register.retry_scan_btn') : __('auth.register.face_scan_btn') }}
                </button>
            </div>

            <!-- Submit actions -->
            <div class="pt-4 space-y-4">
                <button
                    type="submit"
                    class="w-full text-center py-4 bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-slate-200 text-white dark:text-slate-950 font-bold rounded-xl text-base tracking-wide transition duration-150 select-none outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 focus:ring-offset-white dark:focus:ring-offset-slate-900"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ __('global.register') }}
                </button>

                <div class="text-center mt-2 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('auth.register.already_registered') }}
                    <Link
                        :href="route('login')"
                        class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline outline-none ms-1"
                    >
                        {{ __('auth.register.log_in_link') }}
                    </Link>
                </div>
            </div>
        </form>

        <!-- FUTURISTIC FACE REGISTRATION SCANNER MODAL -->
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
                    <span class="text-[10px] font-medium tracking-widest text-emerald-400/90 uppercase">{{ __('auth.login.success_msg') }}</span>
                    <span class="text-lg font-semibold text-white mt-1.5">{{ form.name || __('global.name') }}</span>
                </div>

                <!-- Loading / Init state -->
                <div v-if="scanStatus === 'loading'" class="absolute inset-0 bg-slate-950/95 flex flex-col items-center justify-center text-slate-400 z-30">
                    <RefreshCw class="h-8 w-8 animate-spin mb-3 text-indigo-500" />
                    <span class="text-xs tracking-wide">{{ __('global.initializing') }}</span>
                </div>
            </div>

            <!-- Status & Error Alert -->
            <div class="w-full mt-6 text-center min-h-[28px] max-w-sm">
                <p v-if="scanStatus === 'scanning'" class="text-xs font-bold text-indigo-600 dark:text-indigo-600 animate-pulse">
                    {{ isFaceDetected ? __('auth.login.face_detected') : __('auth.login.looking_for_face') }}
                </p>
                <p v-else-if="scanStatus === 'failed'" class="text-xs font-bold text-rose-600 dark:text-rose-600">
                    {{ errorMessage || __('global.errors.face_not_detected') }}
                </p>
            </div>

            <!-- Actions inside modal -->
            <div class="mt-8 flex items-center space-x-3">
                <button 
                    v-if="scanStatus === 'failed'"
                    @click="loadModelsAndStart" 
                    type="button" 
                    class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-sm transition"
                >
                    {{ __('auth.register.retry_scan_btn') }}
                </button>
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
