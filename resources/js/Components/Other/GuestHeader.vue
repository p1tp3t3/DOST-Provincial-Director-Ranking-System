<template>
    <header class="border-b border-gray-300 bg-white">
        <!-- Main Header -->
        <div class="max-w-7xl mx-auto px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between">
            <!-- Logo and Title -->
            <div class="flex items-center gap-4 mb-6 md:mb-0">
                <div class="bg-blue-100 rounded-lg p-2">
                    <img class="w-10 h-10 text-white"src="assets/logo.png" alt="PRISM Logo" *\/>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-indigo-900">DOST PRISM</h1>
                    <p class="text-xs text-gray-600">Department of Science and Technology PSTO Ranking and Information System for Management</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="md:ml-auto">
                <ul class="flex flex-wrap items-center gap-1 md:gap-2">
                    <li>
                        <Link
                            href="/"
                            class="px-3 md:px-4 py-2 rounded-md text-sm font-medium text-white bg-indigo-900 hover:bg-indigo-800 transition"
                        >
                            Home
                        </Link>
                    </li>
                    <li>
                        <Link
                            href="#"
                            class="px-3 md:px-4 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition"
                        >
                            Directors
                        </Link>
                    </li>
                    <li>
                        <Link
                            href="#"
                            class="px-3 md:px-4 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition"
                        >
                            Rankings
                        </Link>
                    </li>
                    <li>
                        <Link
                            href="#"
                            class="px-3 md:px-4 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition"
                        >
                            About
                        </Link>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
</template>