<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Church QR Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto text-center">
        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="text-6xl text-red-500 mb-4">🚫</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Access Denied</h1>
            <p class="text-gray-600 mb-6">
                You don't have permission to access this page. 
                Please contact your administrator if you believe this is an error.
            </p>
            <div class="space-y-3">
                <a href="{{ route('dashboard') }}" 
                   class="block w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition duration-200">
                    Go to Dashboard
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" 
                            class="w-full text-gray-500 hover:text-gray-700 py-2 px-4 border border-gray-300 rounded transition duration-200">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>