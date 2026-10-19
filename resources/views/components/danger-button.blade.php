<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-danger bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
