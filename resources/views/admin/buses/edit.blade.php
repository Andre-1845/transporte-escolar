@extends('layouts.admin')

@section('title', 'Editar Ônibus')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="info-box">
                <div class="info-box-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="info-box-content">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>Editar Ônibus</h2>
                        <a href="{{ route('admin.buses.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                    <p>Edite as informações do ônibus: <strong>{{ $bus->plate }}</strong></p>
                </div>
            </div>

            <div class="info-box">
                <form method="POST" action="{{ route('admin.buses.update', $bus->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="plate">Placa do Ônibus <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('plate') is-invalid @enderror"
                                    id="plate" name="plate" value="{{ old('plate', $bus->plate) }}"
                                    placeholder="ABC-1234" required>
                                @error('plate')
                                    <div class="alert alert-danger mt-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="model">Modelo</label>
                                <input type="text" class="form-control @error('model') is-invalid @enderror"
                                    id="model" name="model" value="{{ old('model', $bus->model) }}"
                                    placeholder="Mercedes-Benz, Volvo, etc">
                                @error('model')
                                    <div class="alert alert-danger mt-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="capacity">Capacidade (passageiros) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('capacity') is-invalid @enderror"
                                    id="capacity" name="capacity" value="{{ old('capacity', $bus->capacity) }}"
                                    min="1" max="100" required>
                                @error('capacity')
                                    <div class="alert alert-danger mt-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="active">Status</label>
                                <select class="form-control" id="active" name="active">
                                    <option value="1" {{ old('active', $bus->active) == 1 ? 'selected' : '' }}>Ativo
                                    </option>
                                    <option value="0" {{ old('active', $bus->active) === 0 ? 'selected' : '' }}>
                                        Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Atualizar Ônibus
                            </button>
                            <a href="{{ route('admin.buses.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Informações adicionais -->
            <div class="info-box">
                <h3><i class="fas fa-info-circle"></i> Informações do Sistema</h3>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Criado em:</strong> {{ \Carbon\Carbon::parse($bus->created_at)->format('d/m/Y H:i:s') }}
                    </div>
                    <div class="col-md-6">
                        <strong>Última atualização:</strong>
                        {{ \Carbon\Carbon::parse($bus->updated_at)->format('d/m/Y H:i:s') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
