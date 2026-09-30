{{-- The flag drawn beside a currency code, for the two currencies that have one; nothing for the rest. --}}
{{-- @var string $currency --}}
@if ($currency === 'eur')
  <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" class="shrink-0">
    <circle cx="12" cy="12" r="12" fill="#003399" />
    <defs>
      <polygon id="flag-eur-star" fill="#FFCC00" points="0,-1.6 .38,-.52 1.52,-.49 .61,.2 .94,1.29 0,.65 -.94,1.29 -.61,.2 -1.52,-.49 -.38,-.52" />
    </defs>
    <use href="#flag-eur-star" x="12" y="5.5" />
    <use href="#flag-eur-star" x="15.25" y="6.37" />
    <use href="#flag-eur-star" x="17.63" y="8.75" />
    <use href="#flag-eur-star" x="18.5" y="12" />
    <use href="#flag-eur-star" x="17.63" y="15.25" />
    <use href="#flag-eur-star" x="15.25" y="17.63" />
    <use href="#flag-eur-star" x="12" y="18.5" />
    <use href="#flag-eur-star" x="8.75" y="17.63" />
    <use href="#flag-eur-star" x="6.37" y="15.25" />
    <use href="#flag-eur-star" x="5.5" y="12" />
    <use href="#flag-eur-star" x="6.37" y="8.75" />
    <use href="#flag-eur-star" x="8.75" y="6.37" />
  </svg>
@elseif ($currency === 'usd')
  <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" class="shrink-0">
    <defs>
      <clipPath id="flag-usd-clip"><circle cx="12" cy="12" r="12" /></clipPath>
    </defs>
    <g clip-path="url(#flag-usd-clip)">
      <rect width="24" height="24" fill="#fff" />
      <rect y="0" width="24" height="1.85" fill="#B22234" />
      <rect y="3.69" width="24" height="1.85" fill="#B22234" />
      <rect y="7.38" width="24" height="1.85" fill="#B22234" />
      <rect y="11.08" width="24" height="1.85" fill="#B22234" />
      <rect y="14.77" width="24" height="1.85" fill="#B22234" />
      <rect y="18.46" width="24" height="1.85" fill="#B22234" />
      <rect y="22.15" width="24" height="1.85" fill="#B22234" />
      <rect width="12" height="12.92" fill="#3C3B6E" />
      <g fill="#fff">
        <circle cx="3" cy="3" r=".6" /><circle cx="6" cy="3" r=".6" /><circle cx="9" cy="3" r=".6" />
        <circle cx="4.5" cy="6" r=".6" /><circle cx="7.5" cy="6" r=".6" />
        <circle cx="3" cy="9" r=".6" /><circle cx="6" cy="9" r=".6" /><circle cx="9" cy="9" r=".6" />
      </g>
    </g>
    <circle cx="12" cy="12" r="11.6" fill="none" stroke="rgb(0 0 0 / 0.15)" stroke-width=".8" />
  </svg>
@endif
