<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ремонтная служба')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">Ремонтная служба</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/requests/create">Создать заявку</a>
                    </li>
                    @if(isset($currentUser))
                        @if($currentUser['role'] === 'dispatcher')
                            <li class="nav-item">
                                <a class="nav-link" href="/dispatcher">Панель диспетчера</a>
                            </li>
                        @endif
                        @if($currentUser['role'] === 'master')
                            <li class="nav-item">
                                <a class="nav-link" href="/master">Панель мастера</a>
                            </li>
                        @endif
                    @endif
                </ul>
                <ul class="navbar-nav">
                    @if(isset($currentUser))
                        <li class="nav-item">
                            <span class="nav-link text-light">
                                {{ $currentUser['name'] }} ({{ $currentUser['role'] === 'dispatcher' ? 'Диспетчер' : 'Мастер' }})
                            </span>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="/logout" class="d-inline">
                                <input type="hidden" name="_csrf_token" value="{{ $csrfToken }}">
                                <button type="submit" class="btn btn-outline-light btn-sm mt-1">Выход</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="/login">Войти</a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        @if(isset($flash['success']))
            <div class="alert alert-success alert-dismissible fade show">
                {{ $flash['success'] }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(isset($flash['error']))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ $flash['error'] }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <footer class="mt-5 py-3 text-center text-muted">
        <small>Ремонтная служба &copy; {{ date('Y') }}</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
