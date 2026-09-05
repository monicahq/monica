{{-- The reference as plain text, for a reader that is not a browser. --}}
{{-- @var list<\App\ViewModels\Docs\ApiDocsSection> $sections --}}
@php
  $blocks = [];

  foreach ($sections as $section) {
    $block = ['# '.$section->title, '`'.$section->method.' /api/v1'.$section->path.'`'];

    if (! $section->auth) {
      $block[] = 'No token required.';
    }

    $block[] = $section->description;

    foreach ($section->body as $paragraph) {
      $block[] = $paragraph;
    }

    if ($section->hasParams()) {
      $block[] = '## '.$section->paramsTitle;

      $rows = [];

      foreach ($section->params as $param) {
        $default = $param['default'] ? ', defaults to '.$param['default'] : '';
        $rows[] = '- `'.$param['name'].'` ('.$param['type'].', '.($param['required'] ? 'required' : 'optional').$default.'): '.$param['description'];
      }

      $block[] = implode(PHP_EOL, $rows);
    }

    if ($section->returns || $section->hasReturnFields()) {
      $block[] = '## Returns';

      if ($section->returns) {
        $block[] = $section->returns;
      }

      if ($section->hasReturnFields()) {
        $rows = [];

        foreach ($section->returnFields as $field) {
          $rows[] = '- `'.$field['name'].'` ('.$field['type'].'): '.$field['description'];
        }

        $block[] = implode(PHP_EOL, $rows);
      }
    }

    $block[] = '## Rate limit';
    $block[] = $section->rateLimit.' Every answer carries the budget left in `X-RateLimit-Remaining`.';

    $block[] = '## Request';

    foreach (['curl' => 'sh', 'javascript' => 'javascript', 'php' => 'php'] as $language => $fence) {
      $block[] = '```'.$fence.PHP_EOL.$section->samples()[$language]['code'].PHP_EOL.'```';
    }

    $block[] = '## Response';

    if ($section->responseLines() === []) {
      $block[] = '`'.$section->responseStatus.'`, with no body.';
    } else {
      $body = collect($section->responseLines())
        ->map(fn (array $line): string => collect($line['parts'])->pluck('text')->implode(''))
        ->implode(PHP_EOL);

      $block[] = '`'.$section->responseStatus.'`';
      $block[] = '```json'.PHP_EOL.$body.PHP_EOL.'```';
    }

    $blocks[] = implode(PHP_EOL.PHP_EOL, $block);
  }
@endphp
{!! implode(PHP_EOL.PHP_EOL.'---'.PHP_EOL.PHP_EOL, $blocks) !!}
