@props(['book'])

{{--
    Reusable book card (cover + title + author + rating + price + stock-aware CTA).
    Uses the Book::cover_url accessor. Designed to sit in a responsive grid:
    full-width on mobile, multi-column on the desktop canvas.
--}}

@php
    $isOutOfStock = ($book->stok_beli <= 0 && $book->stok_sewa <= 0);
    $reviewsCount = $book->reviews_count ?? 0;
@endphp

<div {{ $attributes->class([
        'w-full border border-gray-200 rounded-2xl p-3 flex flex-col gap-3 shadow-sm transition-all duration-300',
        'bg-gray-100 opacity-75 grayscale' => $isOutOfStock,
        'bg-white group hover:border-blue-300 hover:shadow-md hover:-translate-y-1' => ! $isOutOfStock,
    ]) }}>
    <div class="flex gap-3">
        <a href="{{ route('product.show', $book->id) }}" class="flex-shrink-0 w-20 h-28 overflow-hidden rounded-lg relative bg-gray-200">
            <img src="{{ $book->cover_url }}" alt="{{ $book->judul_buku }}"
                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                 onerror="this.src='{{ asset('images/illustration-no-books.png') }}'">
        </a>

        <div class="flex-grow flex flex-col justify-center min-w-0">
            <a href="{{ route('product.show', $book->id) }}">
                <h4 class="font-bold text-gray-900 text-base truncate leading-tight group-hover:text-blue-600 transition-colors" title="{{ $book->judul_buku }}">
                    {{ $book->judul_buku }}
                </h4>
            </a>
            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $book->nama_penulis }}</p>

            <div class="flex items-center gap-1 text-xs mt-1.5">
                <span class="font-bold text-yellow-500 text-sm">{{ number_format($book->reviews_avg_rating ?? 0, 1) }}</span>
                <span class="text-gray-300">|</span>
                <span class="text-gray-400 truncate">
                    Based on {{ $reviewsCount > 1000 ? number_format($reviewsCount / 1000, 1) . 'k' : $reviewsCount }} Reviews
                </span>
            </div>

            <p class="text-base font-bold text-gray-900 mt-1">Rp {{ number_format($book->harga_beli, 0, ',', '.') }}</p>
        </div>
    </div>

    @if($isOutOfStock)
        <button disabled class="block w-full bg-gray-400 text-white text-xs font-bold uppercase tracking-wide py-2.5 rounded-full text-center cursor-not-allowed">
            Out of Stock
        </button>
    @else
        <a href="{{ route('product.show', $book->id) }}" class="block w-full bg-yellow-400 text-yellow-900 text-xs font-bold uppercase tracking-wide py-2.5 rounded-full text-center hover:bg-yellow-500 hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
            Grab Now
        </a>
    @endif
</div>
