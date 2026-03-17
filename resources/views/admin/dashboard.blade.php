@extends('layouts.admin')

@section('content')
    <h1>Painel Administrativo</h1>

    <hr>

    <h2>Resumo</h2>

    <ul>

        <li>
            Trips cadastradas: <strong>{{ $trips }}</strong>
        </li>

        <li>
            Usuários cadastrados: <strong>{{ $users }}</strong>
        </li>

        <li>
            Ônibus cadastrados: <strong>{{ $buses }}</strong>
        </li>

    </ul>

    <hr>

    <h2>Gerenciamento</h2>

    <ul>

        <li>
            <a href="{{ route('admin.trips.index') }}">
                Gerenciar Trips
            </a>
        </li>

        <li>
            <a href="{{ route('admin.users.index') }}">
                Gerenciar Usuários
            </a>
        </li>

        <li>
            <a href="{{ route('admin.buses.index') }}">
                Gerenciar Ônibus
            </a>
        </li>

        <li>
            <a href="{{ route('admin.routes.index') }}">
                Gerenciar Rotas
            </a>
        </li>

    </ul>
@endsection
