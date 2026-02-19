@extends('layouts.app')

@section('title', 'Панель диспетчера')

@section('content')
<h2 class="mb-4">Панель диспетчера</h2>

{{-- Фильтр по статусам --}}
<div class="mb-4">
    <div class="btn-group flex-wrap" role="group">
        <a href="/dispatcher"
           class="btn {{ $currentFilter === '' ? 'btn-primary' : 'btn-outline-primary' }}">
            Все
        </a>
        @foreach($statuses as $status)
            <a href="/dispatcher?status={{ $status->value }}"
               class="btn {{ $currentFilter === $status->value ? 'btn-primary' : 'btn-outline-primary' }}">
                {{ $status->label() }}
            </a>
        @endforeach
    </div>
</div>

{{-- Таблица заявок --}}
@if(count($requests) === 0)
    <div class="alert alert-info">Заявки не найдены.</div>
@else
    <div class="table-responsive">
        <table class="table table-hover table-bordered">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Адрес</th>
                    <th>Проблема</th>
                    <th>Статус</th>
                    <th>Мастер</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $request)
                    @php
                        $status = \App\Domain\Enum\RequestStatus::from($request['status']);
                    @endphp
                    <tr>
                        <td>{{ $request['id'] }}</td>
                        <td>{{ $request['client_name'] }}</td>
                        <td>{{ $request['phone'] }}</td>
                        <td>{{ $request['address'] }}</td>
                        <td title="{{ htmlspecialchars($request['problem_text'], ENT_QUOTES, 'UTF-8') }}">
                            {{ mb_substr($request['problem_text'], 0, 50) }}{{ mb_strlen($request['problem_text']) > 50 ? '...' : '' }}
                        </td>
                        <td>
                            <span class="badge {{ $status->badgeClass() }}">
                                {{ $status->label() }}
                            </span>
                        </td>
                        <td>{{ $request['master_name'] ?? '—' }}</td>
                        <td>{{ $request['created_at'] }}</td>
                        <td>
                            @if($status === \App\Domain\Enum\RequestStatus::New)
                                {{-- Назначение мастера --}}
                                <form method="POST" action="/dispatcher/assign" class="d-inline">
                                    <input type="hidden" name="_csrf_token" value="{{ $csrfToken }}">
                                    <input type="hidden" name="request_id" value="{{ $request['id'] }}">
                                    <div class="input-group input-group-sm">
                                        <select name="master_id" class="form-select form-select-sm" required>
                                            <option value="">Выбрать мастера</option>
                                            @foreach($masters as $master)
                                                <option value="{{ $master['id'] }}">{{ $master['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-success btn-sm">Назначить</button>
                                    </div>
                                </form>
                                <form method="POST" action="/dispatcher/cancel" class="d-inline mt-1">
                                    <input type="hidden" name="_csrf_token" value="{{ $csrfToken }}">
                                    <input type="hidden" name="request_id" value="{{ $request['id'] }}">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Отменить заявку?')">Отменить</button>
                                </form>
                            @elseif($status === \App\Domain\Enum\RequestStatus::Assigned)
                                <form method="POST" action="/dispatcher/cancel" class="d-inline">
                                    <input type="hidden" name="_csrf_token" value="{{ $csrfToken }}">
                                    <input type="hidden" name="request_id" value="{{ $request['id'] }}">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Отменить заявку?')">Отменить</button>
                                </form>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
