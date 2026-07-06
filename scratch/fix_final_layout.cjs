const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, '..', 'resources', 'js', 'Layouts', 'AuthenticatedLayout.vue');
let content = fs.readFileSync(filePath, 'utf8');

content = content.replace(/\r\n/g, '\n');

// 1. Replace the entire mobile notification dropdown with a custom transition using fixed left-4 right-4 top-[84px]
const oldMobileArea = `                             <!-- Mobile Notification Dropdown with exact layout matching Profile Dropdown -->
                             <div class="lg:hidden">
                                 <Dropdown align="right" width="mobile-nav" :open="showMobileNotifications" @update:open="showMobileNotifications = $event">
                                     <template #trigger>
                                         <div class="hidden"></div>
                                     </template>
                                     <template #content>
                                         <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                             <div class="flex items-center gap-3">
                                                 <Bell class="h-4 w-4 text-slate-500 dark:text-slate-400" />
                                                 <span class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('Notifications') }}</span>
                                                 <span 
                                                     v-if="unreadCount > 0" 
                                                     class="h-5 min-w-5 px-1.5 flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold"
                                                 >{{ unreadCount }}</span>
                                             </div>
                                             <button
                                                 @click.stop="showMobileNotifications = false"
                                                 type="button"
                                                 class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2 rounded-md"
                                                 aria-label="Tutup notifikasi"
                                             >
                                                 <X class="h-4 w-4" />
                                             </button>
                                         </div>
                                         <div class="max-h-80 overflow-y-auto">
                                             <div v-if="notifications.length === 0" class="py-12 text-center text-xs text-slate-400 dark:text-slate-500 font-medium">
                                                 {{ __('No notifications') }}
                                             </div>
                                             <div 
                                                 v-else
                                                 v-for="notif in notifications" 
                                                 :key="notif.id"
                                                 @click="markAsRead(notif)"
                                                 :class="[
                                                     'flex gap-3 px-4 py-3 border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition cursor-pointer',
                                                     !notif.read_at ? 'bg-emerald-50/30 dark:bg-emerald-950/10' : ''
                                                 ]"
                                             >
                                                 <!-- Icon -->
                                                 <div :class="[
                                                     'h-9 w-9 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5',
                                                     notif.priority === 'URGENT' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-500' :
                                                     notif.type === 'ticket' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500' :
                                                     notif.type === 'progress' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-500' :
                                                     notif.type === 'done' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500' :
                                                     'bg-violet-50 dark:bg-violet-950/40 text-violet-500'
                                                 ]">
                                                     <Bell v-if="notif.type === 'ticket'" class="h-4 w-4" />
                                                     <Clock v-else-if="notif.type === 'progress'" class="h-4 w-4" />
                                                     <CheckCircle2 v-else-if="notif.type === 'done'" class="h-4 w-4" />
                                                     <User v-else class="h-4 w-4" />
                                                 </div>
                                                 <!-- Content -->
                                                 <div class="flex-1 min-w-0">
                                                     <div class="flex items-start justify-between gap-2">
                                                         <div class="flex items-center gap-1.5 min-w-0">
                                                             <span v-if="notif.priority === 'URGENT'" class="px-1.5 py-0.5 rounded text-[8px] font-extrabold uppercase bg-rose-500 text-white flex-shrink-0 animate-pulse">URGENT</span>
                                                             <p :class="['text-xs font-semibold truncate', !notif.read_at ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300']">{{ notif.title }}</p>
                                                         </div>
                                                         <span v-if="!notif.read_at" :class="['h-2 w-2 rounded-full flex-shrink-0 mt-1', notif.priority === 'URGENT' ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500']"></span>
                                                     </div>
                                                     <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed mt-0.5 line-clamp-2">{{ notif.message }}</p>
                                                     <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-medium">{{ notif.time }}</p>
                                                 </div>
                                             </div>
                                         </div>
                                         <div v-if="unreadCount > 0" class="px-4 py-2.5 border-t border-slate-100 dark:border-slate-800 text-center">
                                             <button @click="markAllAsRead" class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('Mark All as Read') }}</button>
                                         </div>
                                     </template>
                                 </Dropdown>
                             </div>`;

const newMobileArea = `                             <!-- Backdrop for closing mobile notifications dropdown on click outside -->
                             <Transition
                                 enter-active-class="transition ease-out duration-200"
                                 enter-from-class="opacity-0"
                                 enter-to-class="opacity-100"
                                 leave-active-class="transition ease-in duration-150"
                                 leave-from-class="opacity-100"
                                 leave-to-class="opacity-0"
                             >
                                 <div
                                     v-if="showMobileNotifications"
                                     class="fixed inset-0 z-50 lg:hidden"
                                     @click="showMobileNotifications = false"
                                     aria-hidden="true"
                                 />
                             </Transition>

                             <!-- Mobile Notifications Panel with fixed positioning to avoid screen edge collision -->
                             <Transition
                                 enter-active-class="transition ease-out duration-200"
                                 enter-from-class="opacity-0 scale-95 translate-y-1"
                                 enter-to-class="opacity-100 scale-100 translate-y-0"
                                 leave-active-class="transition ease-in duration-150"
                                 leave-from-class="opacity-100 scale-100 translate-y-0"
                                 leave-to-class="opacity-0 scale-95 translate-y-1"
                             >
                                 <div ref="mobileNotificationsPanel" v-if="showMobileNotifications" @click.stop class="lg:hidden fixed left-4 right-4 top-[84px] z-50 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden w-auto">
                                     <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                         <div class="flex items-center gap-3">
                                             <Bell class="h-4 w-4 text-slate-500 dark:text-slate-400" />
                                             <span class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('Notifications') }}</span>
                                             <span 
                                                 v-if="unreadCount > 0" 
                                                 class="h-5 min-w-5 px-1.5 flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold"
                                             >{{ unreadCount }}</span>
                                         </div>
                                         <button
                                             @click.stop="showMobileNotifications = false"
                                             type="button"
                                             class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2 rounded-md"
                                             aria-label="Tutup notifikasi"
                                         >
                                             <X class="h-4 w-4" />
                                         </button>
                                     </div>
                                     <div class="max-h-80 overflow-y-auto">
                                         <div v-if="notifications.length === 0" class="py-12 text-center text-xs text-slate-400 dark:text-slate-500 font-medium">
                                             {{ __('No notifications') }}
                                         </div>
                                         <div 
                                             v-else
                                             v-for="notif in notifications" 
                                             :key="notif.id"
                                             @click="markAsRead(notif)"
                                             :class="[
                                                 'flex gap-3 px-4 py-3 border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition cursor-pointer',
                                                 !notif.read_at ? 'bg-emerald-50/30 dark:bg-emerald-950/10' : ''
                                             ]"
                                         >
                                             <!-- Icon -->
                                             <div :class="[
                                                 'h-9 w-9 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5',
                                                 notif.priority === 'URGENT' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-500' :
                                                 notif.type === 'ticket' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500' :
                                                 notif.type === 'progress' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-500' :
                                                 notif.type === 'done' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500' :
                                                 'bg-violet-50 dark:bg-violet-950/40 text-violet-500'
                                             ]">
                                                 <Bell v-if="notif.type === 'ticket'" class="h-4 w-4" />
                                                 <Clock v-else-if="notif.type === 'progress'" class="h-4 w-4" />
                                                 <CheckCircle2 v-else-if="notif.type === 'done'" class="h-4 w-4" />
                                                 <User v-else class="h-4 w-4" />
                                             </div>
                                             <!-- Content -->
                                             <div class="flex-1 min-w-0">
                                                 <div class="flex items-start justify-between gap-2">
                                                     <div class="flex items-center gap-1.5 min-w-0">
                                                         <p :class="['text-xs font-semibold truncate', !notif.read_at ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300']">{{ notif.title }}</p>
                                                         <span v-if="notif.priority === 'URGENT'" class="px-1.5 py-0.5 rounded text-[8px] font-extrabold uppercase bg-rose-500 text-white flex-shrink-0 animate-pulse">URGENT</span>
                                                     </div>
                                                     <span v-if="!notif.read_at" :class="['h-2 w-2 rounded-full flex-shrink-0 mt-1', notif.priority === 'URGENT' ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500']"></span>
                                                 </div>
                                                 <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed mt-0.5 line-clamp-2">{{ notif.message }}</p>
                                                 <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-medium">{{ notif.time }}</p>
                                             </div>
                                         </div>
                                     </div>
                                     <div v-if="unreadCount > 0" class="px-4 py-2.5 border-t border-slate-100 dark:border-slate-800 text-center">
                                         <button @click="markAllAsRead" class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('Mark All as Read') }}</button>
                                     </div>
                                 </div>
                             </Transition>`;

content = content.replace(oldMobileArea, newMobileArea);

// 2. Align desktop list item URGENT badge on the right of the title (inside the flex row)
const oldDesktopTitle = `<div class="flex items-center gap-1.5 min-w-0">
                                                          <p :class="['text-xs font-semibold truncate', !notif.read_at ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300']">
                                                               {{ notif.title }}
                                                               <span v-if="notif.priority === 'URGENT'" class="ml-1 text-rose-500 font-extrabold text-[9px] uppercase animate-pulse">[URGENT]</span>
                                                           </p>
                                                       </div>`;

const newDesktopTitle = `<div class="flex items-center gap-1.5 min-w-0">
                                                          <p :class="['text-xs font-semibold truncate', !notif.read_at ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300']">{{ notif.title }}</p>
                                                          <span v-if="notif.priority === 'URGENT'" class="px-1.5 py-0.5 rounded text-[8px] font-extrabold uppercase bg-rose-500 text-white flex-shrink-0 animate-pulse">URGENT</span>
                                                      </div>`;

content = content.split(oldDesktopTitle).join(newDesktopTitle);

// 3. Align mobile toast alert URGENT badge on the right of the title
const oldToastTitle = `<div class="flex items-center gap-1.5">
                            <p :class="['text-xs font-extrabold leading-normal', toast.priority === 'URGENT' ? 'text-rose-950 dark:text-rose-100' : 'text-slate-900 dark:text-white']">
                                {{ toast.title }}
                                <span v-if="toast.priority === 'URGENT'" class="ml-1 text-rose-500 font-extrabold text-[9px] uppercase animate-pulse">[URGENT]</span>
                            </p>
                        </div>`;

const newToastTitle = `<div class="flex items-center gap-1.5">
                            <p :class="['text-xs font-extrabold leading-normal', toast.priority === 'URGENT' ? 'text-rose-950 dark:text-rose-100' : 'text-slate-900 dark:text-white']">{{ toast.title }}</p>
                            <span v-if="toast.priority === 'URGENT'" class="px-1.5 py-0.5 rounded text-[8px] font-extrabold uppercase bg-rose-550 text-white flex-shrink-0">URGENT</span>
                        </div>`;

content = content.split(oldToastTitle).join(newToastTitle);

fs.writeFileSync(filePath, content, 'utf8');
console.log("Successfully aligned mobile panel fixed and urgent badges to the right of title!");
