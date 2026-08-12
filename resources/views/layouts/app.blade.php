<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sikoor') - Sistem Koordinasi Polda Sumbar</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 950: '#0B1B33', 900: '#0F2547', 800: '#16355F', 700: '#1E4478' },
                        gold: { 500: '#C89B3C', 400: '#D7B15E' },
                    },
                    fontFamily: {
                        display: ['"Plus Jakarta Sans"', 'sans-serif'],
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    @stack('styles')
</head>
<body class="font-sans bg-slate-100 text-slate-800 antialiased">

    @hasSection('sidebar')
        <div class="flex min-h-screen">
            <aside class="w-64 shrink-0 bg-navy-950 text-slate-200 flex flex-col">
                @yield('sidebar')
            </aside>
            <div class="flex-1 flex flex-col min-w-0">
                @include('components.topbar')
                <main class="flex-1 p-6">
                    @yield('content')
                </main>
            </div>
        </div>

        @auth
            <a href="{{ route('messages.index') }}"
               class="fixed bottom-6 right-6 w-14 h-14 rounded-full bg-navy-900 hover:bg-navy-800 text-white shadow-lg flex items-center justify-center transition z-50"
               aria-label="Buka live chat">
                <i class="ti ti-message-circle text-2xl"></i>
            </a>
        @endauth
    @else
        <main>
            @yield('content')
        </main>
    @endif

    @stack('scripts')
</body>
</html>