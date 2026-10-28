@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-common.page-breadcrumb pageTitle="User Profile" />

        <!-- User Profile Header Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4 sm:gap-6">
                    <div class="relative size-20 shrink-0 overflow-hidden rounded-full border-2 border-brand-500/20 bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center">
                        @if ($user->avatar && Storage::disk('public')->exists($user->avatar))
                            <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="size-full object-cover" />
                        @else
                            <span class="text-2xl font-bold uppercase text-brand-600 dark:text-brand-400">
                                {{ substr($user->name, 0, 2) }}
                            </span>
                        @endif
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $user->name }}
                            </h2>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase
                                {{ $user->isAdmin() ? 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400 border border-error-200 dark:border-error-500/30' : ($user->isAgent() ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 border border-brand-200 dark:border-brand-500/30' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700') }}">
                                {{ $user->role }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->email }}
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            Department: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $user->department ?? 'General Support' }}</span>
                            &bull; Member since {{ $user->created_at->format('M Y') }}
                        </p>
                    </div>
                </div>

                <!-- Stats summary pill -->
                <div class="grid grid-cols-2 gap-3 sm:flex sm:items-center sm:gap-4 border-t pt-4 sm:border-t-0 sm:pt-0 border-gray-100 dark:border-gray-800">
                    <div class="rounded-xl bg-gray-50 px-4 py-2.5 text-center dark:bg-white/[0.03]">
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Tickets Created</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $stats['tickets_created'] }}</span>
                    </div>
                    @if ($user->canManageTickets())
                        <div class="rounded-xl bg-gray-50 px-4 py-2.5 text-center dark:bg-white/[0.03]">
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Assigned Tickets</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $stats['tickets_assigned'] }}</span>
                        </div>
                    @else
                        <div class="rounded-xl bg-gray-50 px-4 py-2.5 text-center dark:bg-white/[0.03]">
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Replies Sent</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $stats['replies_count'] }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Edit Profile Information Form -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs">
                <div class="pb-4 mb-5 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Personal Information
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Update your account details, department, and profile photo.
                    </p>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Full Name <span class="text-error-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white @error('name') border-error-500 @enderror" />
                        @error('name')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Email Address <span class="text-error-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white @error('email') border-error-500 @enderror" />
                        @error('email')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="department" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Department / Team
                        </label>
                        <input type="text" id="department" name="department" value="{{ old('department', $user->department) }}"
                            placeholder="e.g. Technical Support, Billing, Customer Success"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white @error('department') border-error-500 @enderror" />
                        @error('department')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="avatar" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Profile Photo (Max 2MB)
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/*"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:file:bg-gray-800 dark:file:text-gray-300" />
                        @error('avatar')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security / Password Update Form -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs">
                <div class="pb-4 mb-5 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Security & Password
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Ensure your account is using a long, random password to stay secure.
                    </p>
                </div>

                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="current_password" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Current Password <span class="text-error-500">*</span>
                        </label>
                        <input type="password" id="current_password" name="current_password" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white @error('current_password') border-error-500 @enderror" />
                        @error('current_password')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            New Password <span class="text-error-500">*</span>
                        </label>
                        <input type="password" id="password" name="password" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white @error('password') border-error-500 @enderror" />
                        @error('password')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Confirm New Password <span class="text-error-500">*</span>
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs transition hover:bg-gray-800 dark:bg-brand-500 dark:hover:bg-brand-600">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
