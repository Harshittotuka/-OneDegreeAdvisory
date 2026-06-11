<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base target="_blank">
  @include('partials.brief._styles')
  @stack('head')
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <style>body{margin:0;}</style>
</head>
<body>
  <main class="odp-file-page">
    <div class="odp-file-container">
      @if(empty($sections))
        <div style="padding:80px 20px;text-align:center;color:#8a86a8;font-family:Poppins,sans-serif;">
          <p style="font-size:15px;">Your page is empty. Add a block on the left to see it here.</p>
        </div>
      @else
        @include('partials.brief._render', ['sections' => $sections])
      @endif
    </div>
  </main>
  <script>window.addEventListener('load', function () { if (window.lucide) lucide.createIcons(); });</script>
</body>
</html>
