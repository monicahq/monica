<x-db-layout>
  <div class="relative mx-auto flex w-full max-w-8xl flex-auto sm:px-2 lg:px-8 xl:px-12 bg-gray-100 dark:bg-gray-600 pt-5">
    <div class="flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 mr-3">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
      </svg>

      <div class="flex items-center">
        <h1 class="mr-3">{{ $database['database_name'] }}</h1>

        <span class="text-gray-400 dark:text-gray-300 text-xs font-mono">({{ $database['type'] }})</span>
      </div>
    </div>

  </div>

  <div class="relative mx-auto flex w-full max-w-8xl flex-auto justify-center sm:px-2 lg:px-8 xl:px-12 bg-gray-100 dark:bg-gray-600 p-5">
    <!-- left -->
    <div>
      <div class="hidden lg:relative lg:block lg:flex-none bg-white dark:bg-gray-800 mr-3 rounded-xl">
        <ul class="sticky h-[calc(100vh-4.75rem)] w-64 overflow-y-auto overflow-x-hidden text-xs font-mono">
          @foreach ($tables as $table)
          <li @class(['flex items-center pb-1 border-b border-gray-200 dark:border-gray-500 dark:text-gray-200 px-2 py-1 hover:bg-blue-50 hover:dark:bg-blue-950', 'font-bold bg-blue-50 dark:bg-blue-950' => $table['name'] === $requestedTable])>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3 mr-1 text-gray-500 dark:text-gray-200">
              <path fill-rule="evenodd" d="M.99 5.24A2.25 2.25 0 0 1 3.25 3h13.5A2.25 2.25 0 0 1 19 5.25l.01 9.5A2.25 2.25 0 0 1 16.76 17H3.26A2.267 2.267 0 0 1 1 14.74l-.01-9.5Zm8.26 9.52v-.625a.75.75 0 0 0-.75-.75H3.25a.75.75 0 0 0-.75.75v.615c0 .414.336.75.75.75h5.373a.75.75 0 0 0 .627-.74Zm1.5 0a.75.75 0 0 0 .627.74h5.373a.75.75 0 0 0 .75-.75v-.615a.75.75 0 0 0-.75-.75H11.5a.75.75 0 0 0-.75.75v.625Zm6.75-3.63v-.625a.75.75 0 0 0-.75-.75H11.5a.75.75 0 0 0-.75.75v.625c0 .414.336.75.75.75h5.25a.75.75 0 0 0 .75-.75Zm-8.25 0v-.625a.75.75 0 0 0-.75-.75H3.25a.75.75 0 0 0-.75.75v.625c0 .414.336.75.75.75H8.5a.75.75 0 0 0 .75-.75ZM17.5 7.5v-.625a.75.75 0 0 0-.75-.75H11.5a.75.75 0 0 0-.75.75V7.5c0 .414.336.75.75.75h5.25a.75.75 0 0 0 .75-.75Zm-8.25 0v-.625a.75.75 0 0 0-.75-.75H3.25a.75.75 0 0 0-.75.75V7.5c0 .414.336.75.75.75H8.5a.75.75 0 0 0 .75-.75Z" clip-rule="evenodd" />
            </svg>

            <a href="{{ $table['route'] }}">{{ $table['name'] }}</a>
          </li>
          @endforeach
        </ul>
      </div>
    </div>

    <!-- right -->
    <div class="min-w-0 max-w-2xl flex-auto lg:max-w-none">
      <div class="overflow-x-auto overflow-y-auto text-sm bg-white dark:bg-gray-800 rounded-xl">
        <table class="w-full font-mono">
        @foreach ($rows as $row)
          @if ($loop->first)
          <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">
              @foreach ($row as $item)
              <th class="px-2 border-r">{{ $item['value'] }}</th>
              @endforeach
            </tr>
          </thead>
          @endif

          @if (! $loop->first)
          <tbody>
          <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">
            @foreach ($row as $item)
              @if (is_null($item['value']))
              <td class="border-r p-1 whitespace-nowrap text-gray-400 dark:text-gray-300">NULL</td>
              @else
              <td class="border-r p-1 whitespace-nowrap text-gray-400 dark:text-gray-300">{{ $item['value'] }}</td>
              @endif
            @endforeach
          </tr>
          </tbody>
          @endif

        @endforeach
        </table>
      </div>
    </div>
  </div>
</x-db-layout>
