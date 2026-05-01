@extends('layouts.admin')

@section('title', 'Noticias Médicas')
@section('subtitle', 'Novedades y actualidad en Oftalmología')

@section('content')

<style>
/* Enterprise Card Styling */
.news-card {
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    border: 1px solid rgba(0,0,0,0.05);
    background: #ffffff;
}
body.theme-dark .news-card {
    background: #1e293b;
    border-color: rgba(255,255,255,0.05);
}
.news-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
}
.source-badge {
    background: linear-gradient(135deg, var(--primary-color) 0%, #4a54a4 100%);
    color: white;
}
.btn-favorite {
    background: none;
    border: none;
    color: #cbd5e1;
    font-size: 1.5rem;
    transition: all 0.2s;
}
.btn-favorite:hover {
    color: #fbbf24;
}
.btn-favorite.active {
    color: #fbbf24;
}
.trash-btn {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 10;
    opacity: 0;
    transition: opacity 0.2s;
}
.news-card:hover .trash-btn {
    opacity: 1;
}
</style>

<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-pills custom-tabs px-3 py-2 bg-white rounded-pill shadow-sm d-inline-flex align-items-center">
            <li class="nav-item">
                <a class="nav-link active rounded-pill px-4" href="{{ route('news.index') }}">
                    <i class="bi bi-globe me-2"></i> Últimas Noticias
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-muted rounded-pill px-4" href="{{ route('news.favorites') }}">
                    <i class="bi bi-star-fill me-2"></i> Mis Favoritos
                </a>
            </li>
            <li class="nav-item ms-3">
                <a class="btn btn-outline-primary rounded-pill px-3 py-1 btn-sm fw-bold" href="{{ route('news.refresh') }}" title="Refrescar noticias">
                    <i class="bi bi-arrow-clockwise me-1"></i> Buscar Nuevas
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="row g-4" id="newsGrid">
    @if(empty($news))
        <div class="col-12 text-center py-5">
            <i class="bi bi-exclamation-circle text-muted" style="font-size: 3rem;"></i>
            <h5 class="text-muted mt-3">No hay noticias disponibles en este momento.</h5>
            <p class="small text-muted">Las fuentes de noticias (RSS) podrían estar temporalmente fuera de servicio o no hay artículos recientes. Por favor, intenta recargar más tarde.</p>
        </div>
    @else
        @foreach($news as $item)
            @php
                $isFav = in_array($item['link'], $favoritesLinks);
                $hasImage = !empty($item['image_url']);
            @endphp
            <div class="col-md-6 col-lg-4 news-card-wrapper" data-link="{{ $item['link'] }}">
                <div class="card rounded-4 shadow-sm h-100 news-card overflow-hidden position-relative" style="cursor: pointer;" onclick='openNewsOffcanvas(@json($item))'>
                    <button class="btn btn-sm btn-danger rounded-circle trash-btn shadow" style="width:35px;height:35px;" onclick="event.stopPropagation(); hideNews(this, '{{ $item['link'] }}')" title="Ocultar Noticia">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                    @if($hasImage)
                        <div style="height: 160px; overflow: hidden;">
                            <img src="{{ $item['image_url'] }}" class="w-100 h-100 object-fit-cover" alt="Noticia">
                        </div>
                    @endif
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge source-badge rounded-pill px-3 py-2 ms-auto"><i class="bi bi-journal-medical me-1"></i> {{ $item['source'] }}</span>
                            <button class="btn-favorite ms-2 {{ $isFav ? 'active' : '' }}" onclick="event.stopPropagation(); toggleFavorite(this, {{ json_encode($item) }})">
                                <i class="bi bi-star{{ $isFav ? '-fill' : '' }}"></i>
                            </button>
                        </div>
                        <h5 class="card-title fw-bold text-dark mb-3">{{ $item['title'] }}</h5>
                        <p class="card-text text-secondary mb-4" style="font-size: 0.95rem; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            {{ $item['description'] }}
                        </p>
                        <div class="mt-auto pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted"><i class="bi bi-clock me-1"></i> {{ $item['pub_date'] }}</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary rounded-pill fw-bold flex-grow-1" onclick="event.stopPropagation(); openNewsOffcanvas(@json($item))">
                                    Leer resumen <i class="bi bi-text-paragraph ms-1"></i>
                                </button>
                                <a href="{{ $item['link'] }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3" title="Ver Nota Completa" onclick="event.stopPropagation();">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>

<!-- Offcanvas Detalle Noticia -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="newsOffcanvas" aria-labelledby="newsOffcanvasLabel" style="width: 450px;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold" id="newsOffcanvasLabel">Detalle de la Noticia</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div id="offcanvasImageContainer" style="height: 250px; overflow: hidden; display: none;">
        <img id="offcanvasImage" src="" class="w-100 h-100 object-fit-cover" alt="Imagen noticia">
    </div>
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <span class="badge source-badge rounded-pill px-3 py-2" id="offcanvasSource"></span>
        </div>
        <h4 class="fw-bold mb-3 text-dark" id="offcanvasTitle"></h4>
        <p class="text-secondary mb-4" id="offcanvasDesc" style="line-height: 1.8; font-size: 1.05rem;"></p>
        <p class="text-muted small mb-4" id="offcanvasDate"><i class="bi bi-clock me-1"></i> <span></span></p>
        <a href="#" id="offcanvasLink" target="_blank" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">Ver Fuente Original <i class="bi bi-box-arrow-up-right ms-2"></i></a>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const hiddenNews = JSON.parse(localStorage.getItem('hiddenNews') || '[]');
        hiddenNews.forEach(link => {
            const card = document.querySelector(`.news-card-wrapper[data-link="${link}"]`);
            if (card) {
                card.style.display = 'none';
            }
        });
    });

    function hideNews(btn, link) {
        const card = btn.closest('.news-card-wrapper');
        card.style.transition = 'opacity 0.3s, transform 0.3s';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.9)';
        setTimeout(() => {
            card.style.display = 'none';
            const hiddenNews = JSON.parse(localStorage.getItem('hiddenNews') || '[]');
            if (!hiddenNews.includes(link)) {
                hiddenNews.push(link);
                localStorage.setItem('hiddenNews', JSON.stringify(hiddenNews));
            }
        }, 300);
    }

    function openNewsOffcanvas(item) {
        document.getElementById('offcanvasTitle').innerText = item.title;
        let extText = item.extended_text ? item.extended_text : item.description;
        // Simple markdown to HTML para los párrafos
        extText = extText.replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');
        document.getElementById('offcanvasDesc').innerHTML = extText;
        document.getElementById('offcanvasSource').innerHTML = '<i class="bi bi-journal-medical me-1"></i> ' + item.source;
        document.querySelector('#offcanvasDate span').innerText = item.pub_date;
        document.getElementById('offcanvasLink').href = item.link;
        
        const imgContainer = document.getElementById('offcanvasImageContainer');
        const img = document.getElementById('offcanvasImage');
        if (item.image_url) {
            img.src = item.image_url;
            imgContainer.style.display = 'block';
        } else {
            imgContainer.style.display = 'none';
        }

        var bsOffcanvas = new bootstrap.Offcanvas(document.getElementById('newsOffcanvas'));
        bsOffcanvas.show();
    }

function toggleFavorite(btn, item) {
    const icon = btn.querySelector('i');
    const isCurrentlyFav = btn.classList.contains('active');
    
    // Optimistic UI update
    btn.classList.toggle('active');
    if (isCurrentlyFav) {
        icon.classList.remove('bi-star-fill');
        icon.classList.add('bi-star');
    } else {
        icon.classList.remove('bi-star');
        icon.classList.add('bi-star-fill');
    }

    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const url = '{{ route("news.saveFavorite") }}'; // Use same URL for POST/DELETE
    
    fetch(url, {
        method: isCurrentlyFav ? 'DELETE' : 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify(item)
    }).then(res => res.json())
      .then(data => {
          if(!data.success) {
              console.error("Error al guardar favorito");
              // Revert UI if error
              btn.classList.toggle('active');
          }
      }).catch(err => console.error(err));
}
</script>

@endsection
