@extends('layouts.admin')

@section('title', 'Editar Ônibus')

@section('content')

    <!-- HEADER -->
    <div class="section">
        <div class="info-box">

            <div class="info-box-icon">
                <i class="fas fa-edit"></i>
            </div>

            <div class="d-flex-between" style="width:100%;">
                <div>
                    <h2>Editar Ônibus</h2>
                    <p>Placa: <strong>{{ $bus->plate }}</strong></p>
                </div>

                <a href="{{ route('admin.buses.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>

        </div>
    </div>

    <!-- FORM -->
    <div class="section">
        <div class="info-box" style="flex-direction: column; align-items: stretch;">

            <div class="section-header">
                <h3><i class="fas fa-cog"></i> Dados do Ônibus</h3>
            </div>

            <form method="POST" action="{{ route('admin.buses.update', $bus->id) }}" class="mt-2">
                @csrf
                @method('PUT')

                <div class="row">

                    <!-- PLACA -->
                    <div class="col-md-6">
                        <label>Placa *</label>
                        <input type="text" name="plate" class="form-control" value="{{ old('plate', $bus->plate) }}"
                            placeholder="ABC-1234" required>

                        @error('plate')
                            <div class="alert alert-danger mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- MODELO -->
                    <div class="col-md-6">
                        <label>Modelo</label>
                        <input type="text" name="model" class="form-control" value="{{ old('model', $bus->model) }}"
                            placeholder="Mercedes, Volvo...">

                        @error('model')
                            <div class="alert alert-danger mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                </div>

                <div class="row mt-2">

                    <!-- CAPACIDADE -->
                    <div class="col-md-6">
                        <label>Capacidade *</label>
                        <input type="number" name="capacity" class="form-control"
                            value="{{ old('capacity', $bus->capacity) }}" min="1" max="100" required>

                        @error('capacity')
                            <div class="alert alert-danger mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- STATUS -->
                    <div class="col-md-6">
                        <label>Status</label>
                        <select name="active" class="form-control">
                            <option value="1" {{ old('active', $bus->active) == 1 ? 'selected' : '' }}>Ativo</option>
                            <option value="0" {{ old('active', $bus->active) == 0 ? 'selected' : '' }}>Inativo
                            </option>
                        </select>
                    </div>

                </div>

                <!-- BOTÕES -->
                <div class="mt-3 d-flex-between">

                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar
                        </button>

                        <a href="{{ route('admin.buses.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>

                </div>

            </form>

        </div>
    </div>

    <!-- INFO SISTEMA -->
    <div class="section">
        <div class="info-box" style="flex-direction: column; align-items: stretch;">

            <div class="section-header">
                <h3><i class="fas fa-info-circle"></i> Informações do Sistema</h3>
            </div>

            <div class="row mt-2">
                <div class="col-md-6">
                    <strong>Criado em:</strong><br>
                    {{ \Carbon\Carbon::parse($bus->created_at)->format('d/m/Y H:i') }}
                </div>

                <div class="col-md-6">
                    <strong>Última atualização:</strong><br>
                    {{ \Carbon\Carbon::parse($bus->updated_at)->format('d/m/Y H:i') }}
                </div>
            </div>

        </div>
    </div>

@endsection
