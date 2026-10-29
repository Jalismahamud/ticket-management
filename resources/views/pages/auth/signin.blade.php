@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative z-1 bg-white dark:bg-gray-900 min-h-screen">
        <div class="flex min-h-screen w-full flex-col lg:flex-row">
            <!-- Left Side: Login Form -->
            <div class="flex w-full flex-1 flex-col justify-between p-6 sm:p-10 lg:w-1/2 lg:p-12 xl:p-16">
                <!-- Top Header: Back Link -->
                <div>
                    <a href="/"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
                        <svg class="size-4 rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Dashboard
                    </a>
                </div>

                <!-- Center Form Container -->
                <div class="mx-auto w-full max-w-md my-auto py-8">
                    <div class="mb-6">
                        <div class="inline-flex items-center gap-2 rounded-lg bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 mb-3">
                            <span>Secure Access</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Welcome Back
                        </h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Sign in to access your dashboard, manage tickets, and track SLA performance.
                        </p>
                    </div>

                    @if (session('error'))
                        <div class="mb-4 rounded-xl border border-error-200 bg-error-50/70 p-4 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-xl border border-error-200 bg-error-50/70 p-4 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
                            <ul class="list-disc ps-5 space-y-0.5 text-xs">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div x-data="{
                        email: '{{ old('email', '') }}',
                        password: '',
                        selectedRole: '',
                        fillCredentials(role, emailVal, passwordVal) {
                            this.selectedRole = role;
                            this.email = emailVal;
                            this.password = passwordVal;
                            this.$nextTick(() => {
                                const emailField = document.getElementById('email');
                                if (emailField) {
                                    emailField.focus();
                                    emailField.select();
                                }
                            });
                        }
                    }">
                        <!-- Quick Demo Logins for Evaluation -->
                        <div class="mb-5 rounded-xl border border-gray-200 bg-gray-50/60 p-3.5 dark:border-gray-800 dark:bg-white/[0.02]">
                            <div class="flex items-center justify-between mb-2">
                                <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Quick Demo Logins (Click to Autofill)
                                </span>
                                <span class="text-[11px] text-brand-600 dark:text-brand-400 font-medium" x-show="selectedRole" x-cloak>
                                    <span x-text="selectedRole.toUpperCase()"></span> Selected
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <button type="button"
                                    @click="fillCredentials('admin', 'admin@example.com', 'password')"
                                    class="flex flex-col items-center justify-center rounded-lg border py-2 text-xs font-medium transition cursor-pointer text-center"
                                    :class="selectedRole === 'admin'
                                        ? 'border-error-500 bg-error-100 ring-2 ring-error-500/30 text-error-800 dark:bg-error-500/20 dark:text-error-300'
                                        : 'border-error-200 bg-error-50/50 text-error-700 hover:bg-error-100 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400'">
                                    <span class="font-bold">Admin</span>
                                    <span class="text-[10px] opacity-75">admin@example.com</span>
                                </button>
                                <button type="button"
                                    @click="fillCredentials('agent', 'agent1@example.com', 'password')"
                                    class="flex flex-col items-center justify-center rounded-lg border py-2 text-xs font-medium transition cursor-pointer text-center"
                                    :class="selectedRole === 'agent'
                                        ? 'border-brand-500 bg-brand-100 ring-2 ring-brand-500/30 text-brand-800 dark:bg-brand-500/20 dark:text-brand-300'
                                        : 'border-brand-200 bg-brand-50/50 text-brand-700 hover:bg-brand-100 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-400'">
                                    <span class="font-bold">Agent</span>
                                    <span class="text-[10px] opacity-75">agent1@example.com</span>
                                </button>
                                <button type="button"
                                    @click="fillCredentials('customer', 'customer1@example.com', 'password')"
                                    class="flex flex-col items-center justify-center rounded-lg border py-2 text-xs font-medium transition cursor-pointer text-center"
                                    :class="selectedRole === 'customer'
                                        ? 'border-gray-500 bg-gray-200 ring-2 ring-gray-500/30 text-gray-900 dark:bg-gray-700 dark:text-white'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'">
                                    <span class="font-bold">Customer</span>
                                    <span class="text-[10px] opacity-75">customer1@example.com</span>
                                </button>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-2 text-center">
                                Clicking a role fills inputs &amp; focuses. Password: <span class="font-mono font-medium text-gray-600 dark:text-gray-300">password</span>
                            </p>
                        </div>

                        <form method="POST" action="{{ route('login') }}" class="space-y-4">
                            @csrf

                            <!-- Email -->
                            <div>
                                <label for="email" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Email Address <span class="text-error-500">*</span>
                                </label>
                                <input type="email" id="email" name="email" x-model="email" value="{{ old('email') }}" required autofocus
                                    placeholder="name@company.com"
                                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-500 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/50 dark:text-white dark:placeholder:text-white/30 transition"
                                    :class="{ 'border-brand-500 ring-1 ring-brand-500/30': selectedRole !== '' }" />
                            </div>

                            <!-- Password -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="password" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                        Password <span class="text-error-500">*</span>
                                    </label>
                                </div>
                                <div x-data="{ showPassword: false }" class="relative">
                                    <input :type="showPassword ? 'text' : 'password'"
                                        id="password"
                                        name="password"
                                        x-model="password"
                                        required
                                        placeholder="Enter your password"
                                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 ps-4 pe-11 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-500 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/50 dark:text-white dark:placeholder:text-white/30 transition"
                                        :class="{ 'border-brand-500 ring-1 ring-brand-500/30': selectedRole !== '' }" />
                                    <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 end-0 flex items-center pe-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer"
                                        tabindex="-1">
                                        <svg x-show="!showPassword" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showPassword" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember Me Checkbox -->
                            <div class="flex items-center justify-between pt-1">
                                <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-600 dark:text-gray-400 select-none">
                                    <input type="checkbox" name="remember" class="size-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800" />
                                    <span>Keep me signed in</span>
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-2">
                                <button type="submit"
                                    class="inline-flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/20 cursor-pointer">
                                    Sign In to Account
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Sign Up Redirect -->
                    <div class="mt-6 text-center text-xs text-gray-600 dark:text-gray-400">
                        Don't have an account?
                        <a href="/signup" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                            Create Account
                        </a>
                    </div>
                </div>

                <!-- Footer Copyright -->
                <div class="text-xs text-gray-400">
                    &copy; {{ date('Y') }} {{ config('app.name', 'SupportDesk') }}. All rights reserved.
                </div>
            </div>

            <!-- Right Side: Hero Visual Panel -->
            <div class="relative hidden w-full lg:flex lg:w-1/2 flex-col justify-between p-10 xl:p-14 bg-gradient-to-br from-brand-950 via-gray-900 to-brand-950 text-white overflow-hidden border-s border-gray-200/10">
                <!-- Grid Background Shapes -->
                <x-common.common-grid-shape />

                <!-- Ambient Glow Effect -->
                <div class="absolute -top-24 -right-24 size-96 rounded-full bg-brand-500/15 blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 size-96 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none"></div>

                <!-- Hero Top Branding & Status Badge -->
                <div class="relative z-10 flex items-center justify-between">
                    <a href="/" class="block">
                        <img src="/images/logo/auth-logo.svg" alt="{{ config('app.name', 'SupportDesk') }}" class="h-10 w-auto" />
                    </a>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-gray-300 backdrop-blur-md">
                        <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>SLA Engine Active</span>
                    </div>
                </div>

                <!-- Hero Center: Value Proposition & Feature Cards -->
                <div class="relative z-10 my-auto py-8 max-w-xl">
                    <div class="mb-6">
                        <h2 class="text-2xl xl:text-3xl font-extrabold tracking-tight text-white mb-2 leading-snug">
                            Enterprise Support Ticket &amp; SLA Management System
                        </h2>
                        <p class="text-xs xl:text-sm text-gray-300/80 leading-relaxed">
                            A high-performance Laravel solution engineered for rapid incident resolution, business-hour SLA compliance, and auditable support operations.
                        </p>
                    </div>

                    <!-- 3 Feature Highlight Cards -->
                    <div class="space-y-3">
                        <div class="flex items-start gap-3.5 rounded-xl border border-white/10 bg-white/[0.04] p-3.5 backdrop-blur-md transition hover:bg-white/[0.07]">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-500/20 text-brand-400 border border-brand-500/30">
                                <svg class="size-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-semibold text-white">Dynamic SLA Resolution Clock</h3>
                                <p class="text-[11px] text-gray-400 mt-0.5">Calculates deadlines strictly within business hours (Sun–Thu, 9 AM–6 PM) with weekend roll-over.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5 rounded-xl border border-white/10 bg-white/[0.04] p-3.5 backdrop-blur-md transition hover:bg-white/[0.07]">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                                <svg class="size-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-semibold text-white">Intelligent Workload Balancing</h3>
                                <p class="text-[11px] text-gray-400 mt-0.5">Least-busy agent auto-assignment with database-level concurrency locks preventing race conditions.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5 rounded-xl border border-white/10 bg-white/[0.04] p-3.5 backdrop-blur-md transition hover:bg-white/[0.07]">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-purple-500/20 text-purple-400 border border-purple-500/30">
                                <svg class="size-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-semibold text-white">Audit Trail &amp; Escalation Engine</h3>
                                <p class="text-[11px] text-gray-400 mt-0.5">Granular chronological audit timeline, private agent notes isolation, and automated notifications.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Metrics Highlight Bar -->
                    <div class="grid grid-cols-3 gap-4 pt-6 mt-6 border-t border-white/10">
                        <div>
                            <div class="text-lg xl:text-xl font-bold text-white font-mono">99.8%</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">SLA Compliance</div>
                        </div>
                        <div>
                            <div class="text-lg xl:text-xl font-bold text-white font-mono">&lt; 2h</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Critical Resolution</div>
                        </div>
                        <div>
                            <div class="text-lg xl:text-xl font-bold text-white font-mono">100%</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Audit Traceability</div>
                        </div>
                    </div>
                </div>

                <!-- Hero Footer: Security & Compliance -->
                <div class="relative z-10 flex items-center justify-between text-xs text-gray-400 pt-4 border-t border-white/10">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="size-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        256-bit SSL Protection &bull; Role-Based Authorization
                    </span>
                    <span>SupportDesk v1.0</span>
                </div>
            </div>
        </div>

        <!-- Floating Theme Toggler -->
        <div class="fixed end-6 bottom-6 z-50">
            <button
                class="inline-flex size-11 items-center justify-center rounded-full bg-brand-500 text-white shadow-theme-md transition hover:bg-brand-600 focus:outline-hidden"
                @click.prevent="$store.theme.toggle()"
                aria-label="Toggle Theme">
                <svg class="hidden size-5 fill-current dark:block" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/>
                </svg>
                <svg class="size-5 fill-current dark:hidden" viewBox="0 0 20 20">
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                </svg>
            </button>
        </div>
    </div>
@endsection
