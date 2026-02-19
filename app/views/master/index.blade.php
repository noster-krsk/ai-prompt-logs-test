@extends('layouts.app')

@section('title', 'Панель мастера')

@section('content')
<h2 class="mb-4">Панель мастера</h2>

@if(count($requests) === 0)
    <div class="alert alert-info">У вас пока нет назначенных заявок.</div>
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
                            {{ mb_substr($request['problem_text'], 0, 80) }}{{ mb_strlen($request['problem_text']) > 80 ? '...' : '' }}
                        </td>
                        <td>
                            <span class="badge {{ $status->badgeClass() }}">
                                {{ $status->label() }}
                            </span>
                        </td>
                        <td>{{ $request['created_at'] }}</td>
                        <td>
                            @if($status === \App\Domain\Enum\RequestStatus::Assigned)
                                <form method="POST" action="/master/take">
                                    <input type="hidden" name="_csrf_token" value="{{ $csrfToken }}">
                                    <input type="hidden" name="request_id" value="{{ $request['id'] }}">
                                    <button type="submit" class="btn btn-primary btn-sm">Взять в работу</button>
                                </form>
                            @elseif($status === \App\Domain\Enum\RequestStatus::InProgress)
                                <form method="POST" action="/master/finish">
                                    <input type="hidden" name="_csrf_token" value="{{ $csrfToken }}">
                                    <input type="hidden" name="request_id" value="{{ $request['id'] }}">
                                    <button type="submit" class="btn btn-success btn-sm"
                                            onclick="return confirm('Завершить заявку?')">Завершить</button>
                                </form>
                            @elseif($status === \App\Domain\Enum\RequestStatus::Done)
                                <span class="text-success">Выполнена</span>
                            @elseif($status === \App\Domain\Enum\RequestStatus::Canceled)
                                <span class="text-danger">Отменена</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
