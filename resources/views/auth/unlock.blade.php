<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Desbloquear — LICORERA POS</title>
    <script>
        if (localStorage.getItem('pos_theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex items-center justify-center min-h-full p-4 bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 select-none transition-colors duration-200">
    <div class="w-full max-w-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative"
         x-data="{
             pin: '',
             addDigit(num) {
                 if (this.pin.length < 4) {
                     this.pin += num;
                     if (window.PosSound) window.PosSound.beep('add');
                     if (this.pin.length === 4) {
                         this.$nextTick(() => {
                             this.$refs.unlockForm.submit();
                         });
                     }
                 }
             },
             clearPin() {
                 this.pin = '';
             },
             backspace() {
                 this.pin = this.pin.slice(0, -1);
             }
         }">

        <!-- User Avatar & Lock Header -->
        <div class="text-center mb-6">
            <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-500/40 mx-auto flex items-center justify-center mb-3">
                <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">{{ $user->name }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ingresa tu PIN de 4 dígitos para desbloquear</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 dark:bg-rose-950/80 dark:border-rose-500/30 rounded-xl text-rose-700 dark:text-rose-300 text-xs font-semibold text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('unlock.pin') }}" method="POST" x-ref="unlockForm">
            @csrf
            <input type="hidden" name="pin" :value="pin">

            <!-- PIN Display Dots -->
            <div class="flex items-center justify-center space-x-3 my-6">
                <template x-for="i in 4" :key="i">
                    <div class="w-4 h-4 rounded-full border-2 transition-all duration-150 flex items-center justify-center"
                         :class="pin.length >= i ? 'bg-emerald-500 border-emerald-500 shadow-md shadow-emerald-500/40 scale-110' : 'border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800'">
                    </div>
                </template>
            </div>

            <!-- Keypad -->
            <div class="grid grid-cols-3 gap-2.5 mb-6">
                <template x-for="num in ['1','2','3','4','5','6','7','8','9']" :key="num">
                    <button type="button" @click="addDigit(num)"
                            class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-750 active:scale-95 text-xl font-black text-slate-800 dark:text-white border border-slate-200 dark:border-slate-700 transition-all flex items-center justify-center shadow-sm">
                        <span x-text="num"></span>
                    </button>
                </template>
                <button type="button" @click="clearPin()"
                        class="h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 active:scale-95 text-xs font-bold text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 transition-all flex items-center justify-center uppercase">
                    C
                </button>
                <button type="button" @click="addDigit('0')"
                        class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-750 active:scale-95 text-xl font-black text-slate-800 dark:text-white border border-slate-200 dark:border-slate-700 transition-all flex items-center justify-center shadow-sm">
                    0
                </button>
                <button type="button" @click="backspace()"
                        class="h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 active:scale-95 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 transition-all flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l7-7h11a2 2 0 012 2v10a2 2 0 01-2 2H10l-7-7z"></path>
                    </svg>
                </button>
            </div>
        </form>

        <div class="text-center pt-2">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-white underline">
                    Cambiar de usuario
                </button>
            </form>
        </div>
    </div>
</body>
</html>
